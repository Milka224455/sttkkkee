<?php
// Empfängt das Kontaktformular und speichert die Nachricht für den Admin-Bereich.
require __DIR__ . '/_lib.php';
header('X-Robots-Tag: noindex');

$wantsJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
function done($ok, $msg, $wantsJson, $lang) {
    if ($wantsJson) {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($ok ? 200 : 400);
        echo json_encode(['ok' => $ok, 'error' => $ok ? null : $msg]);
    } else {
        $base = $lang === 'fr' ? '/fr/kontakt/' : '/kontakt/';
        header('Location: ' . $base . ($ok ? '?ok=1' : '?err=' . urlencode($msg)), true, 303);
    }
    exit;
}

$lang = ($_POST['lang'] ?? 'de') === 'fr' ? 'fr' : 'de';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') done(false, 'method', $wantsJson, $lang);

// Spam-Schutz: Honeypot-Feld muss leer sein, Formular muss mind. 3 Sekunden offen gewesen sein
if (!empty($_POST['website'])) done(true, '', $wantsJson, $lang); // Bots still "erfolgreich" abweisen
$ts = (int)($_POST['ts'] ?? 0);
if ($ts <= 0 || (microtime(true) * 1000 - $ts) < 2500) done(false, 'too_fast', $wantsJson, $lang);

$name    = trim(mb_substr($_POST['name'] ?? '', 0, 100));
$email   = trim(mb_substr($_POST['email'] ?? '', 0, 160));
$topic   = in_array($_POST['topic'] ?? '', ['affiliate', 'contact'], true) ? $_POST['topic'] : 'contact';
$message = trim(mb_substr($_POST['message'] ?? '', 0, 5000));
$consent = !empty($_POST['consent']);

if ($name === '' || $message === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) done(false, 'fields', $wantsJson, $lang);
if (!$consent) done(false, 'consent', $wantsJson, $lang);

// Rate-Limit: max. 5 Nachrichten pro IP und Tag (IP wird nur gehasht gespeichert)
$ipHash = client_ip_hash();
$limited = false;
store_update('ratelimit', function ($r) use ($ipHash, &$limited) {
    $today = date('Y-m-d');
    if (($r['day'] ?? '') !== $today) $r = ['day' => $today, 'hits' => []];
    $r['hits'][$ipHash] = ($r['hits'][$ipHash] ?? 0) + 1;
    if ($r['hits'][$ipHash] > 5) $limited = true;
    return $r;
});
if ($limited) done(false, 'limit', $wantsJson, $lang);

$msg = [
    'id'      => bin2hex(random_bytes(8)),
    'date'    => date('c'),
    'name'    => $name,
    'email'   => $email,
    'topic'   => $topic,
    'lang'    => $lang,
    'message' => $message,
    'read'    => false,
];
store_update('messages', function ($all) use ($msg) { array_unshift($all, $msg); return array_slice($all, 0, 2000); });

// Optionale E-Mail-Benachrichtigung (Adresse im Admin-Bereich einstellbar)
$cfg = store_read('config');
if (!empty($cfg['notify_email']) && function_exists('mail')) {
    $subject = '=?UTF-8?B?' . base64_encode('Neue Nachricht (' . ($topic === 'affiliate' ? 'Affiliate' : 'Kontakt') . ') – Stake Bonus 20') . '?=';
    $body = "Von: $name <$email>\nThema: $topic\n\n$message\n\n→ Admin: https://" . ($_SERVER['HTTP_HOST'] ?? '') . "/admin/";
    @mail($cfg['notify_email'], $subject, $body, "Content-Type: text/plain; charset=UTF-8\r\nReply-To: $email");
}
done(true, '', $wantsJson, $lang);
