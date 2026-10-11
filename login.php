<?php
require 'db.php';
require_guest();

$identifier = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    $st = $db->prepare("SELECT * FROM users WHERE username = :a OR email = :b LIMIT 1");
    $st->execute(['a' => $identifier, 'b' => $identifier]);
    $u = $st->fetch();

    if ($u && password_verify($password, $u['password_hash'])) {
        login_user($u['id']);
        flash('success', 'Tekrar hoş geldin, ' . $u['username'] . '! 👋');
        redirect('index.php');
    }
    flash('error', 'Kullanıcı adı/e-posta veya şifre hatalı.');
}

$pageTitle = 'Giriş Yap';
$bodyClass = 'auth-body';
include 'header.php';
?>
<div class="auth-card">
    <div class="auth-brand"><span class="brand-logo lg">✓</span></div>
    <h1>Giriş Yap</h1>
    <p class="muted center">Görevlerine ulaşmak için hesabına giriş yap.</p>

    <form method="POST" class="form">
        <?= csrf_field() ?>
        <label>Kullanıcı adı veya e-posta
            <input type="text" name="identifier" value="<?= e($identifier) ?>" required autofocus autocomplete="username">
        </label>
        <label>Şifre
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary btn-block">Giriş Yap</button>
    </form>

    <p class="auth-switch">Hesabın yok mu? <a href="register.php">Kayıt ol</a></p>
</div>
<?php include 'footer.php'; ?>
