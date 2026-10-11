<?php
require 'db.php';
require_guest();

$username = $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';
    $errors   = [];

    if (!preg_match('/^[\p{L}0-9_.-]{3,30}$/u', $username)) $errors[] = 'Kullanıcı adı 3-30 karakter olmalı (harf, rakam, _ . -).';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) $errors[] = 'Geçerli bir e-posta adresi girin.';
    if (strlen($password) < 8) $errors[] = 'Şifre en az 8 karakter olmalı.';
    if ($password !== $confirm) $errors[] = 'Şifreler eşleşmiyor.';

    if (!$errors) {
        $st = $db->prepare("SELECT username, email FROM users WHERE username = :u OR email = :e");
        $st->execute(['u' => $username, 'e' => $email]);
        foreach ($st->fetchAll() as $row) {
            if (mb_strtolower($row['username']) === mb_strtolower($username)) $errors[] = 'Bu kullanıcı adı zaten alınmış.';
            if (mb_strtolower($row['email']) === mb_strtolower($email)) $errors[] = 'Bu e-posta ile zaten bir hesap var.';
        }
        $errors = array_unique($errors);
    }

    if (!$errors) {
        $ins = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (:u, :e, :p)");
        $ins->execute(['u' => $username, 'e' => $email, 'p' => password_hash($password, PASSWORD_DEFAULT)]);
        login_user($db->lastInsertId());
        flash('success', 'Hesabın oluşturuldu, hoş geldin! 🎉');
        redirect('index.php');
    }
    foreach ($errors as $err) flash('error', $err);
}

$pageTitle = 'Kayıt Ol';
$bodyClass = 'auth-body';
include 'header.php';
?>
<div class="auth-card">
    <div class="auth-brand"><span class="brand-logo lg">✓</span></div>
    <h1>Hesap Oluştur</h1>
    <p class="muted center">Ücretsiz kaydol, görevlerini düzenlemeye başla.</p>

    <form method="POST" class="form">
        <?= csrf_field() ?>
        <label>Kullanıcı adı
            <input type="text" name="username" value="<?= e($username) ?>" required autocomplete="username">
        </label>
        <label>E-posta
            <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
        </label>
        <label>Şifre <small class="muted">(en az 8 karakter)</small>
            <input type="password" name="password" required minlength="8" autocomplete="new-password">
        </label>
        <label>Şifre (tekrar)
            <input type="password" name="password_confirm" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Kayıt Ol</button>
    </form>

    <p class="auth-switch">Zaten hesabın var mı? <a href="login.php">Giriş yap</a></p>
</div>
<?php include 'footer.php'; ?>
