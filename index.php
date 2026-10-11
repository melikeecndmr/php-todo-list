<?php
include 'db.php';

// Görev Ekleme İşlemi
if (isset($_POST['add_task'])) {
    $task_name = trim($_POST['task_name']);
    if (!empty($task_name)) {
        $query = $db->prepare("INSERT INTO tasks (task_name, status) VALUES (:task_name, 0)");
        $query->execute(['task_name' => $task_name]);
        header("Location: index.php");
        exit;
    }
}

// Görev Durumunu Güncelleme (Tamamlandı / Yapılmadı)
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    // Önce mevcut durumu öğrenelim
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

// Görev Silme İşlemi

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query = $db->prepare("DELETE FROM tasks WHERE id = :id");
    $query->execute(['id' => $id]);
    header("Location: index.php");
    exit;
}

// Görevleri Listeleme
$tasks = $db->query("SELECT * FROM tasks ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profesyonel PHP Todo Listesi</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <h2>Yapılacaklar Listesi 🚀</h2>
    
    <form method="POST" action="">
        <input type="text" name="task_name" placeholder="Bugün neler yapacaksın?" required autocomplete="off">
        <button type="submit" name="add_task">Ekle</button>
    </form>

    <ul>
        <?php foreach ($tasks as $task): ?>
            <li class="<?= $task['status'] == 1 ? 'completed' : '' ?>">
                <div class="task-info">
                    <span class="task-text"><?= htmlspecialchars($task['task_name']) ?></span>
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
        <p class="empty-text">Harika, yapılacak hiç işin kalmamış! 🎉</p>
    <?php endif; ?>
</div>

</body>
</html>