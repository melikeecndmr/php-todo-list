<?php
require 'db.php';
require_login();
$uid = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'profile') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $errors   = [];
        if (!preg_match('/^[\p{L}0-9_.-]{3,30}$/u', $username)) $errors[] = 'Kullanıcı adı 3-30 karakter olmalı (harf, rakam, _ . -).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 120) $errors[] = 'Geçerli bir e-posta adresi girin.';
        if (!$errors) {
            $c = $db->prepare("SELECT username, email FROM users WHERE (username = :u OR email = :e) AND id <> :id");
            $c->execute(['u' => $username, 'e' => $email, 'id' => $uid]);
            foreach ($c->fetchAll() as $row) {
                if (mb_strtolower($row['username']) === mb_strtolower($username)) $errors[] = 'Bu kullanıcı adı zaten alınmış.';
                if (mb_strtolower($row['email']) === mb_strtolower($email)) $errors[] = 'Bu e-posta başka bir hesapta kayıtlı.';
            }
        }
        if ($errors) {
            foreach (array_unique($errors) as $er) flash('error', $er);
        } else {
            $db->prepare("UPDATE users SET username = :u, email = :e WHERE id = :id")
               ->execute(['u' => $username, 'e' => $email, 'id' => $uid]);
            flash('success', 'Profil bilgilerin güncellendi.');
        }
    } elseif ($action === 'password') {
        $st = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $st->execute(['id' => $uid]);
        $hash = $st->fetchColumn();
        $new = $_POST['new_password'] ?? '';
        if (!password_verify($_POST['current_password'] ?? '', $hash)) {
            flash('error', 'Mevcut şifre hatalı.');
        } elseif (strlen($new) < 8) {
            flash('error', 'Yeni şifre en az 8 karakter olmalı.');
        } elseif ($new !== ($_POST['new_password_confirm'] ?? '')) {
            flash('error', 'Yeni şifreler eşleşmiyor.');
        } else {
            $db->prepare("UPDATE users SET password_hash = :p WHERE id = :id")
               ->execute(['p' => password_hash($new, PASSWORD_DEFAULT), 'id' => $uid]);
            session_regenerate_id(true);
            flash('success', 'Şifren değiştirildi. 🔒');
        }
    } elseif ($action === 'delete_account') {
        $st = $db->prepare("SELECT password_hash FROM users WHERE id = :id");
        $st->execute(['id' => $uid]);
        if (password_verify($_POST['password'] ?? '', $st->fetchColumn())) {
            $db->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $uid]);
            $_SESSION = [];
            session_destroy();
            session_start();
            flash('success', 'Hesabın ve tüm görevlerin silindi.');
            redirect('login.php');
        }
        flash('error', 'Şifre hatalı, hesap silinmedi.');
    }
    redirect('profile.php');
}

$me = current_user();
$cnt = $db->prepare("SELECT COUNT(*) AS total, COALESCE(SUM(status = 1), 0) AS done FROM tasks WHERE user_id = :u");
$cnt->execute(['u' => $uid]);
$c = $cnt->fetch();

$pageTitle = 'Profil';
$active = 'profile';
include 'header.php';
?>
<section class="page-head">
    <div>
        <h1>Profilim</h1>
        <p class="muted">Hesap bilgilerini ve güvenlik ayarlarını buradan yönet.</p>
    </div>
</section>

<section class="panel profile-summary">
    <div class="avatar xl"><?= e(mb_strtoupper(mb_substr($me['username'], 0, 1))) ?></div>
    <div>
        <h2><?= e($me['username']) ?></h2>
        <p class="muted"><?= e($me['email']) ?></p>
        <p class="muted small">Üyelik: <?= date('d.m.Y', strtotime($me['created_at'])) ?> · <?= (int)$c['total'] ?> görev · <?= (int)$c['done'] ?> tamamlandı</p>
    </div>
</section>

<div class="grid-2">
    <section class="panel">
        <h2>Bilgilerimi düzenle</h2>
        <form method="POST" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="profile">
            <label>Kullanıcı adı
                <input type="text" name="username" value="<?= e($me['username']) ?>" required>
            </label>
            <label>E-posta
                <input type="email" name="email" value="<?= e($me['email']) ?>" required>
            </label>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </form>
    </section>

    <section class="panel">
        <h2>Şifre değiştir</h2>
        <form method="POST" class="form">
            <?= csrf_field() ?><input type="hidden" name="action" value="password">
            <label>Mevcut şifre
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <label>Yeni şifre
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
            </label>
            <label>Yeni şifre (tekrar)
                <input type="password" name="new_password_confirm" required minlength="8" autocomplete="new-password">
            </label>
            <button type="submit" class="btn btn-primary">Şifreyi güncelle</button>
        </form>
    </section>
</div>

<section class="panel danger-zone">
    <h2>Tehlikeli bölge</h2>
    <p class="muted">Hesabını sildiğinde tüm görevlerin kalıcı olarak silinir. Bu işlem geri alınamaz.</p>
    <form method="POST" class="inline-form" onsubmit="return confirm('Hesabın kalıcı olarak silinecek. Emin misin?')">
        <?= csrf_field() ?><input type="hidden" name="action" value="delete_account">
        <input type="password" name="password" placeholder="Onay için şifren" required>
        <button type="submit" class="btn btn-danger">Hesabı sil</button>
    </form>
</section>
<?php include 'footer.php'; ?>
