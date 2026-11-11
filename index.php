<?php
// Espace admin : messages reçus via le formulaire de contact.
require dirname(__DIR__) . '/api/_lib.php';
header('X-Robots-Tag: noindex, nofollow');
header('X-Frame-Options: DENY');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_name('sb20admin');
session_start();

$cfg = store_read('config');
$isSetup = empty($cfg['pass_hash']);
$logged = !empty($_SESSION['admin']) && !$isSetup;
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
$csrfOk = fn() => hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '');
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if (!$csrfOk()) { $flash = 'Session expirée, recommence.'; }
    elseif ($a === 'setup' && $isSetup) {
        $p = $_POST['pass'] ?? '';
        if (strlen($p) < 10) $flash = 'Mot de passe trop court (10 caractères minimum).';
        elseif ($p !== ($_POST['pass2'] ?? '')) $flash = 'Les deux mots de passe ne correspondent pas.';
        else { $cfg['pass_hash'] = password_hash($p, PASSWORD_DEFAULT); store_write('config', $cfg); session_regenerate_id(true); $_SESSION['admin'] = 1; header('Location: ./'); exit; }
    }
    elseif ($a === 'login' && !$isSetup) {
        if (password_verify($_POST['pass'] ?? '', $cfg['pass_hash'])) { session_regenerate_id(true); $_SESSION['admin'] = 1; header('Location: ./'); exit; }
        sleep(2); $flash = 'Mot de passe incorrect.';
    }
    elseif ($logged) {
        $id = $_POST['id'] ?? '';
        if ($a === 'logout') { session_destroy(); header('Location: ./'); exit; }
        if ($a === 'read' || $a === 'unread') store_update('messages', function ($m) use ($id, $a) { foreach ($m as &$x) if ($x['id'] === $id) $x['read'] = ($a === 'read'); return $m; });
        if ($a === 'delete') store_update('messages', fn($m) => array_values(array_filter($m, fn($x) => $x['id'] !== $id)));
        if ($a === 'readall') store_update('messages', function ($m) { foreach ($m as &$x) $x['read'] = true; return $m; });
        if ($a === 'settings') {
            $e = trim($_POST['notify_email'] ?? '');
            if ($e === '' || filter_var($e, FILTER_VALIDATE_EMAIL)) { $cfg['notify_email'] = $e; $flash = 'Réglages enregistrés.'; } else $flash = 'Adresse e-mail invalide.';
            $np = $_POST['newpass'] ?? '';
            if ($np !== '') { if (strlen($np) < 10) $flash = 'Nouveau mot de passe trop court.'; else { $cfg['pass_hash'] = password_hash($np, PASSWORD_DEFAULT); $flash = 'Réglages et mot de passe enregistrés.'; } }
            store_write('config', $cfg);
        }
        if ($a !== 'settings') { header('Location: ./' . (isset($_GET['f']) ? '?f=' . urlencode($_GET['f']) : '')); exit; }
    }
}

$msgs = $logged ? store_read('messages') : [];
$unread = count(array_filter($msgs, fn($x) => empty($x['read'])));
$f = $_GET['f'] ?? 'all';
$shown = array_filter($msgs, fn($x) => $f === 'all' || ($f === 'unread' && empty($x['read'])) || $x['topic'] === $f);
$topicLabel = ['affiliate' => 'Affiliation', 'contact' => 'Contact'];
?><!doctype html>
<html lang="fr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $unread ? "($unread) " : '' ?>Admin · Stake Bonus 20</title>
<link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="/assets/style.css">
<style>
.adm{max-width:960px;margin:0 auto;padding:32px 16px 80px}
.adm-top{display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:24px}
.adm-top h1{font-size:26px;margin-right:auto}
.badge{background:var(--red);color:#fff;border-radius:999px;padding:2px 10px;font-weight:800;font-size:13px;margin-left:6px}
.tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px}
.tabs a{background:var(--card);color:var(--muted);padding:7px 14px;border-radius:999px;font-weight:700;font-size:14px}
.tabs a.on{background:var(--blue);color:#fff}
.msg{background:var(--card);border-radius:12px;margin-bottom:10px;border-left:4px solid transparent}
.msg.new{border-left-color:var(--blue2)}
.msg summary{list-style:none;cursor:pointer;padding:14px 18px;display:grid;grid-template-columns:1fr auto;gap:4px 12px}
.msg summary::-webkit-details-marker{display:none}
.msg .who{font-weight:800}.msg.new .who::before{content:"● ";color:var(--blue2)}
.msg .meta{color:var(--dim);font-size:13px;text-align:right}
.msg .prev{color:var(--muted);font-size:14px;grid-column:1/-1;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.msg .body{padding:0 18px 16px}
.msg .body p{white-space:pre-wrap;color:#dfe6f3;background:var(--bg);border-radius:8px;padding:14px;margin:0 0 12px}
.act{display:flex;gap:8px;flex-wrap:wrap}.act form{display:inline}
.mini{background:var(--card2);color:#fff;border:0;border-radius:8px;padding:7px 12px;font:inherit;font-size:13px;cursor:pointer;text-decoration:none;display:inline-block}
.mini.red{background:rgba(237,65,99,.2);color:#ff8fa3}
.tag{font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.6px;border-radius:999px;padding:2px 8px;background:rgba(20,117,225,.18);color:#7cc0ff;margin-left:8px}
.tag.affiliate{background:rgba(0,231,1,.12);color:var(--green)}
.box{background:var(--card);border-radius:14px;padding:24px;max-width:420px;margin:60px auto}
.box input,.set input{width:100%;background:var(--bg);border:1px solid var(--card2);color:#fff;border-radius:8px;padding:12px;font:inherit;margin:6px 0 14px}
.flash{background:rgba(20,117,225,.15);border-radius:8px;padding:10px 14px;margin-bottom:16px}
.empty{text-align:center;color:var(--dim);padding:40px;background:var(--card);border-radius:12px}
.set{background:var(--card);border-radius:12px;padding:20px;margin-top:32px}
.set label{font-weight:700;font-size:14px}
</style></head><body>
<?php if (!$logged): ?>
<div class="box">
  <a class="logo" href="/">Stake Bonus <b>20</b></a>
  <h1 style="font-size:24px;margin:18px 0 6px"><?= $isSetup ? 'Créer ton accès admin' : 'Connexion admin' ?></h1>
  <?php if ($isSetup): ?><p>Première connexion : choisis ton mot de passe (10 caractères minimum). Fais-le juste après la mise en ligne.</p><?php endif; ?>
  <?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">
    <input type="hidden" name="action" value="<?= $isSetup ? 'setup' : 'login' ?>">
    <label>Mot de passe</label><input type="password" name="pass" required autofocus autocomplete="<?= $isSetup ? 'new-password' : 'current-password' ?>">
    <?php if ($isSetup): ?><label>Confirmer</label><input type="password" name="pass2" required autocomplete="new-password"><?php endif; ?>
    <button class="btn" style="width:100%"><?= $isSetup ? 'Créer et entrer' : 'Se connecter' ?></button>
  </form>
</div>
<?php else: ?>
<div class="adm">
  <div class="adm-top">
    <h1>Messages<?php if ($unread): ?><span class="badge"><?= $unread ?>&nbsp;nouveau<?= $unread > 1 ? 'x' : '' ?></span><?php endif; ?></h1>
    <?php if ($unread): ?><form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><button class="mini" name="action" value="readall">Tout marquer comme lu</button></form><?php endif; ?>
    <a class="mini" href="/">Voir le site</a>
    <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><button class="mini" name="action" value="logout">Déconnexion</button></form>
  </div>
  <?php if ($flash): ?><div class="flash"><?= h($flash) ?></div><?php endif; ?>
  <nav class="tabs">
    <?php foreach (['all' => 'Tous (' . count($msgs) . ')', 'unread' => 'Non lus (' . $unread . ')', 'affiliate' => 'Affiliation', 'contact' => 'Contact'] as $k => $v): ?>
      <a href="?f=<?= $k ?>" class="<?= $f === $k ? 'on' : '' ?>"><?= h($v) ?></a>
    <?php endforeach; ?>
  </nav>
  <?php if (!$shown): ?><div class="empty">Aucun message ici pour l'instant.</div><?php endif; ?>
  <?php foreach ($shown as $m): $new = empty($m['read']); ?>
  <details class="msg<?= $new ? ' new' : '' ?>">
    <summary>
      <span class="who"><?= h($m['name']) ?><span class="tag <?= h($m['topic']) ?>"><?= h($topicLabel[$m['topic']] ?? $m['topic']) ?></span></span>
      <span class="meta"><?= h(date('d/m/Y H:i', strtotime($m['date']))) ?></span>
      <span class="prev"><?= h(mb_substr($m['message'], 0, 140)) ?></span>
    </summary>
    <div class="body">
      <p><?= h($m['message']) ?></p>
      <div class="act">
        <a class="mini" href="mailto:<?= h($m['email']) ?>?subject=<?= rawurlencode('Re: Stake Bonus 20') ?>">Répondre à <?= h($m['email']) ?></a>
        <form method="post"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="mini" name="action" value="<?= $new ? 'read' : 'unread' ?>"><?= $new ? 'Marquer comme lu' : 'Marquer non lu' ?></button></form>
        <form method="post" onsubmit="return confirm('Supprimer ce message ?')"><input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="id" value="<?= h($m['id']) ?>"><button class="mini red" name="action" value="delete">Supprimer</button></form>
      </div>
    </div>
  </details>
  <?php endforeach; ?>

  <form class="set" method="post">
    <h3>Réglages</h3>
    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="settings">
    <label>Recevoir aussi une alerte par e-mail (optionnel)</label>
    <input type="email" name="notify_email" value="<?= h($cfg['notify_email'] ?? '') ?>" placeholder="ton@email.com">
    <label>Changer le mot de passe (laisser vide pour garder l'actuel)</label>
    <input type="password" name="newpass" autocomplete="new-password">
    <button class="btn btn-sm">Enregistrer</button>
  </form>
</div>
<script>
// Rafraîchit la page toutes les 60 s pour voir les nouveaux messages (sauf si un message est ouvert)
setInterval(function(){ if(!document.querySelector('details[open]')) location.reload(); }, 60000);
// Marquer comme lu à l'ouverture
document.querySelectorAll('details.msg.new').forEach(function(d){ d.addEventListener('toggle', function(){
  if(!d.open) return; var f=d.querySelector('button[value="read"]'); if(!f) return;
  var fd=new FormData(f.form); fd.append('action','read'); fetch('./',{method:'POST',body:fd}); d.classList.remove('new');
}, {once:true}); });
</script>
<?php endif; ?>
</body></html>
