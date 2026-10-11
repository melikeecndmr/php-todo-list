<?php
require 'db.php';
require_login();
$uid = (int)$_SESSION['user_id'];
$priorities = ['low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek'];

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $db->prepare("SELECT * FROM tasks WHERE id = :id AND user_id = :u");
$st->execute(['id' => $id, 'u' => $uid]);
$task = $st->fetch();
if (!$task) {
    flash('error', 'Görev bulunamadı.');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name   = trim($_POST['task_name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $date   = $_POST['task_date'] ?? '';
    $prio   = $_POST['priority'] ?? 'medium';
    $status = !empty($_POST['status']) ? 1 : 0;

    if ($name === '' || mb_strlen($name) > 255) {
        flash('error', 'Görev adı boş olamaz (en fazla 255 karakter).');
    } elseif (!valid_date($date)) {
        flash('error', 'Geçerli bir tarih seçin.');
    } else {
        if (!isset($priorities[$prio])) $prio = 'medium';
        $up = $db->prepare("UPDATE tasks SET task_name = :n, description = :d, task_date = :t, priority = :p, status = :s
                            WHERE id = :id AND user_id = :u");
        $up->execute(['n' => $name, 'd' => $desc ?: null, 't' => $date, 'p' => $prio, 's' => $status, 'id' => $id, 'u' => $uid]);
        flash('success', 'Görev güncellendi. ✏️');
        redirect('index.php');
    }
    $task = array_merge($task, ['task_name' => $name, 'description' => $desc, 'task_date' => $date, 'priority' => $prio, 'status' => $status]);
}

$pageTitle = 'Görevi Düzenle';
$active = 'tasks';
include 'header.php';
?>
<section class="page-head">
    <div>
        <a href="index.php" class="back-link">← Görevlerime dön</a>
        <h1>Görevi Düzenle</h1>
    </div>
</section>

<section class="panel narrow">
    <form method="POST" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$task['id'] ?>">
        <label>Görev adı
            <input type="text" name="task_name" value="<?= e($task['task_name']) ?>" required maxlength="255">
        </label>
        <label>Açıklama
            <textarea name="description" rows="4"><?= e($task['description']) ?></textarea>
        </label>
        <div class="form-row">
            <label>Tarih
                <input type="date" name="task_date" value="<?= e($task['task_date']) ?>" required>
            </label>
            <label>Öncelik
                <select name="priority">
                    <?php foreach ($priorities as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $task['priority'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        </div>
        <label class="checkbox">
            <input type="checkbox" name="status" value="1" <?= $task['status'] ? 'checked' : '' ?>>
            Tamamlandı olarak işaretle
        </label>
        <div class="form-actions">
            <a href="index.php" class="btn btn-ghost">Vazgeç</a>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
    </form>
</section>
<?php include 'footer.php'; ?>
