<?php
include 'db.php';

// Görev Ekleme İşlemi
if (isset($_POST['add_task'])) {
    $task_name = trim($_POST['task_name']);
    if (!empty($task_name)) {
        $query = $db->prepare("INSERT INTO tasks (task_name) VALUES (:task_name)");
        $query->execute(['task_name' => $task_name]);
        header("Location: index.php");
        exit;
    }
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
    <title>Modern PHP Todo Listesi</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Harici CSS Dosyamız -->
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">
    <h2>Yapılacaklar Listesi 🚀</h2>
    
    <form method="POST" action="">
        <input type="text" name="tarik" hidden> <!-- Bot koruması veya boş input -->
        <input type="text" name="task_name" placeholder="Bugün neler yapacaksın?" required autocomplete="off">
        <button type="submit" name="add_task">Ekle</button>
    </form>

    <ul>
        <?php foreach ($tasks as $task): ?>
            <li>
                <span class="task-text"><?= htmlspecialchars($task['task_name']) ?></span>
                <a href="index.php?delete=<?= $task['id'] ?>" class="delete-btn">Sil</a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (empty($tasks)): ?>
        <p class="empty-text">Harika, yapılacak hiç işin kalmamış! 🎉</p>
    <?php endif; ?>
</div>

</body>
</html>