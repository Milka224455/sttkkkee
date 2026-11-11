<?php
// Gemeinsame Funktionen für Kontaktformular und Admin-Bereich.
// Daten liegen in /data/ – geschützt per .htaccess UND durch einen PHP-Exit-Prefix.
if (basename($_SERVER['SCRIPT_FILENAME']) === '_lib.php') { http_response_code(404); exit; }

date_default_timezone_set('Europe/Berlin');
define('DATA_DIR', dirname(__DIR__) . '/data');
define('GUARD', "<?php exit; ?>\n");

function store_path($name) { return DATA_DIR . '/' . $name . '.php'; }

function store_read($name, $default = []) {
    $f = store_path($name);
    if (!is_file($f)) return $default;
    $raw = file_get_contents($f);
    if (strpos($raw, GUARD) === 0) $raw = substr($raw, strlen(GUARD));
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function store_write($name, $data) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    $f = store_path($name);
    $tmp = $f . '.tmp';
    file_put_contents($tmp, GUARD . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), LOCK_EX);
    rename($tmp, $f);
}

// Atomare Änderung mit Dateisperre (verhindert verlorene Nachrichten bei gleichzeitigen Anfragen)
function store_update($name, callable $fn, $default = []) {
    if (!is_dir(DATA_DIR)) mkdir(DATA_DIR, 0750, true);
    $lock = fopen(DATA_DIR . '/.' . $name . '.lock', 'c');
    flock($lock, LOCK_EX);
    $data = store_read($name, $default);
    $data = $fn($data);
    store_write($name, $data);
    flock($lock, LOCK_UN);
    fclose($lock);
    return $data;
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function client_ip_hash() { return hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . date('Y-m-d')); }
