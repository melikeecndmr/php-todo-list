<?php
include 'db.php';

session_start();

// Kullanıcı adı kaydedildiyse oturumda sakla
if (isset($_POST['set_name'])) {
    $_SESSION['username'] = trim($_POST['username']);
    header("Location: index.php");
    exit;
}

// Çıkış yapma (ismi sıfırlama)
if (isset($_GET['logout'])) {
    unset($_SESSION['username']);
    header("Location: index.php");
    exit;
}

// Görev Ekleme İşlemi (Tarih ile birlikte)
if (isset($_POST['add_task'])) {
    $task_name = trim($_POST['task_name']);
    $task_date = $_POST['task_date'];
    
    if (!empty($task_name) && !empty($task_date)) {
        $query = $db->prepare("INSERT INTO tasks (task_name, task_date, status) VALUES (:task_name, :task_date, 0)");
        $query->execute(['task_name' => $task_name, 'task_date' => $task_date]);
        header("Location: index.php");
        exit;
    }
}

// Durum Güncelleme
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $stmt = $db->prepare("SELECT status FROM tasks WHERE id = :id");
    $stmt->execute(['id' => $id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($task) {
        $new_status = ($task['status'] == 1) ? 0 : 1;
        $update = $db->prepare("UPDATE tasks SET status = :status WHERE id = :id");
        $update->execute(['status' => $new_status, 'id' => $id]);
    }
    header("Location: index.php");
    exit;
}

// Silme İşlemi
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = $db->prepare("DELETE FROM tasks WHERE id = :id");
    $query->execute(['id' => $id]);
    header("Location: index.php");
    exit;
}

// Görevleri Listeleme (Tarihe göre sıralı)
$tasks = $db->query("SELECT * FROM tasks ORDER BY task_date DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kişisel Görev Yönetim Paneli</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <!-- EĞER KULLANICI ADI GİRİLMEDİYSE GİRİŞ EKRANI -->
    <?php if (!isset($_SESSION['username'])): ?>
        <div class="welcome-screen">
            <h2>Hoş Geldiniz ✨</h2>
            <p style="text-align: center; color: #718096; margin-bottom: 20px; font-size: 14px;">Devam etmek için lütfen adınızı girin:</p>
            <form method="POST" action="" class="name-form">
                <input type="text" name="username" placeholder="Adınız (Örn: Melike)" required autocomplete="off">
                <button type="submit" name="set_name">İleri ➔</button>
            </form>
        </div>
    <?php else: ?>
        <!-- ASIL UYGULAMA EKRANI -->
        <div class="header-bar">
            <div class="user-welcome">
                <h3>Merhaba, <?= htmlspecialchars($_SESSION['username']) ?>! 👋</h3>
                <span class="date-badge">Bugün: <?= date('d.m.Y') ?></span>
            </div>
            <a href="index.php?logout=1" class="logout-btn" title="İsim Değiştir">Çıkış</a>
        </div>

        <form method="POST" action="" class="task-form">
            <input type="text" name="task_name" placeholder="Bugün neler yapacaksın?" required autocomplete="off">
            <input type="date" name="task_date" value="<?= date('Y-m-d') ?>" required>
            <button type="submit" name="add_task">Ekle</button>
        </form>

        <ul class="task-list">
            <?php foreach ($tasks as $task): ?>
                <li class="<?= $task['status'] == 1 ? 'completed' : '' ?>">
                    <div class="task-info">
                        <span class="task-text"><?= htmlspecialchars($task['task_name']) ?></span>
                        <small class="task-date">📅 <?= date('d.m.Y', strtotime($task['task_date'])) ?></small>
                    </div>
                    <div class="actions">
                        <?php if ($task['status'] == 1): ?>
                            <a href="index.php?toggle=<?= $task['id'] ?>" class="btn undone-btn">Geri Al</a>
                        <?php else: ?>
                            <a href="index.php?toggle=<?= $task['id'] ?>" class="btn done-btn">Tamamla</a>
                        <?php endif; ?>
                        <a href="index.php?delete=<?= $task['id'] ?>" class="btn delete-btn">Sil</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if (empty($tasks)): ?>
            <p class="empty-text">Bu tarihe ait planlanmış işin yok! 🎉</p>
        <?php endif; ?>
    <?php endif; ?>
</div>

</body>
</html>