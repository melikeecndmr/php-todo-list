<?php
// Oturum + veritabanı bağlantısı + yardımcı fonksiyonlar
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

$host   = 'localhost';
$user   = 'root';
$pass   = '';
$dbname = 'todo_app';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die("Bağlantı hatası: " . $e->getMessage());
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function redirect($url) { header("Location: $url"); exit; }

function csrf_token() {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . csrf_token() . '">'; }
function csrf_verify() {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(419);
        die('Geçersiz istek (CSRF). Sayfayı yenileyip tekrar deneyin.');
    }
}

function flash($type, $msg) { $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg]; }
function get_flashes() { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function require_login() {
    if (empty($_SESSION['user_id'])) redirect('login.php');
}
function require_guest() {
    if (!empty($_SESSION['user_id'])) redirect('index.php');
}

function current_user() {
    global $db;
    static $user = null;
    if ($user === null && !empty($_SESSION['user_id'])) {
        $st = $db->prepare("SELECT id, username, email, created_at FROM users WHERE id = :id");
        $st->execute(['id' => $_SESSION['user_id']]);
        $user = $st->fetch();
        if (!$user) { session_destroy(); redirect('login.php'); }
    }
    return $user;
}

function login_user($id) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$id;
}

function valid_date($d) {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}
