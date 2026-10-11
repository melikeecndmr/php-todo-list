<?php
$pageTitle = $pageTitle ?? 'Görev Paneli';
$active    = $active ?? '';
$bodyClass = $bodyClass ?? '';
$me        = !empty($_SESSION['user_id']) ? current_user() : null;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> · TaskFlow</title>
    <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>✅</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        // Tema, sayfa çizilmeden önce uygulanır (yanıp sönmeyi önler)
        (function () {
            var t = null;
            try { t = localStorage.getItem('theme'); } catch (e) {}
            if (!t) t = (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', t);
        })();
    </script>
    <link rel="stylesheet" href="style.css">
</head>
<body class="<?= e($bodyClass) ?>">

<?php if ($me): ?>
<header class="topbar">
    <div class="topbar-inner">
        <a href="index.php" class="brand"><span class="brand-logo">✓</span> TaskFlow</a>
        <nav class="nav">
            <a href="index.php" class="<?= $active === 'tasks' ? 'active' : '' ?>">Görevlerim</a>
            <a href="profile.php" class="<?= $active === 'profile' ? 'active' : '' ?>">Profil</a>
        </nav>
        <div class="user-box">
            <button type="button" class="theme-toggle" id="themeToggle" aria-label="Koyu / açık mod" title="Koyu / açık mod"><span class="moon">🌙</span><span class="sun">☀️</span></button>
            <a href="profile.php" class="avatar" title="<?= e($me['username']) ?>"><?= e(mb_strtoupper(mb_substr($me['username'], 0, 1))) ?></a>
            <span class="user-name"><?= e($me['username']) ?></span>
            <form method="POST" action="logout.php">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-ghost btn-sm">Çıkış</button>
            </form>
        </div>
    </div>
</header>
<?php else: ?>
<button type="button" class="theme-toggle floating" id="themeToggle" aria-label="Koyu / açık mod" title="Koyu / açık mod"><span class="moon">🌙</span><span class="sun">☀️</span></button>
<?php endif; ?>

<main class="container">
<?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
