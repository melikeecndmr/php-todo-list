<?php
require 'db.php';
require_login();
$uid = (int)$_SESSION['user_id'];

$priorities = ['low' => 'Düşük', 'medium' => 'Orta', 'high' => 'Yüksek'];

/* ---------- POST işlemleri ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';
    $back   = 'index.php' . (!empty($_POST['back']) ? '?' . $_POST['back'] : '');

    if ($action === 'add') {
        $name = trim($_POST['task_name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $date = $_POST['task_date'] ?? '';
        $prio = $_POST['priority'] ?? 'medium';
        if ($name === '' || mb_strlen($name) > 255) {
            flash('error', 'Görev adı boş olamaz (en fazla 255 karakter).');
        } elseif (!valid_date($date)) {
            flash('error', 'Geçerli bir tarih seçin.');
        } else {
            if (!isset($priorities[$prio])) $prio = 'medium';
            $st = $db->prepare("INSERT INTO tasks (user_id, task_name, description, task_date, priority, status)
                                VALUES (:u, :n, :d, :t, :p, 0)");
            $st->execute(['u' => $uid, 'n' => $name, 'd' => $desc ?: null, 't' => $date, 'p' => $prio]);
            flash('success', 'Görev eklendi. ✅');
        }
    } elseif ($action === 'toggle') {
        $tid = (int)($_POST['id'] ?? 0);
        $st = $db->prepare("UPDATE tasks SET status = 1 - status WHERE id = :id AND user_id = :u");
        $st->execute(['id' => $tid, 'u' => $uid]);
        $chk = $db->prepare("SELECT status FROM tasks WHERE id = :id AND user_id = :u");
        $chk->execute(['id' => $tid, 'u' => $uid]);
        if ((int)$chk->fetchColumn() === 1) $_SESSION['confetti'] = 1; // tamamlayınca konfeti 🎉
    } elseif ($action === 'delete') {
        $st = $db->prepare("DELETE FROM tasks WHERE id = :id AND user_id = :u");
        $st->execute(['id' => (int)($_POST['id'] ?? 0), 'u' => $uid]);
        flash('success', 'Görev silindi.');
    } elseif ($action === 'clear_done') {
        $st = $db->prepare("DELETE FROM tasks WHERE user_id = :u AND status = 1");
        $st->execute(['u' => $uid]);
        flash('success', 'Tamamlanan görevler temizlendi.');
    }
    redirect($back);
}

/* ---------- İstatistikler ---------- */
$st = $db->prepare("SELECT COUNT(*) AS total,
        COALESCE(SUM(status = 1), 0) AS done,
        COALESCE(SUM(status = 0 AND task_date < CURDATE()), 0) AS overdue,
        COALESCE(SUM(status = 0 AND task_date = CURDATE()), 0) AS today
    FROM tasks WHERE user_id = :u");
$st->execute(['u' => $uid]);
$stats   = array_map('intval', $st->fetch());
$active  = $stats['total'] - $stats['done'];
$percent = $stats['total'] ? round($stats['done'] / $stats['total'] * 100) : 0;

/* ---------- Filtre / arama ---------- */
$filters = [
    'all'     => ['Tümü',        $stats['total']],
    'today'   => ['Bugün',       $stats['today']],
    'active'  => ['Bekleyen',    $active],
    'overdue' => ['Geciken',     $stats['overdue']],
    'done'    => ['Tamamlanan',  $stats['done']],
];
$filter = $_GET['filter'] ?? 'all';
if (!isset($filters[$filter])) $filter = 'all';
$q = trim($_GET['q'] ?? '');

$where  = ["user_id = :u"];
$params = ['u' => $uid];
switch ($filter) {
    case 'today':   $where[] = "status = 0 AND task_date = CURDATE()"; break;
    case 'active':  $where[] = "status = 0"; break;
    case 'overdue': $where[] = "status = 0 AND task_date < CURDATE()"; break;
    case 'done':    $where[] = "status = 1"; break;
}
if ($q !== '') {
    $where[] = "(task_name LIKE :q1 OR description LIKE :q2)";
    $params['q1'] = $params['q2'] = '%' . $q . '%';
}
$sql = "SELECT * FROM tasks WHERE " . implode(' AND ', $where) .
       " ORDER BY status ASC, task_date ASC, FIELD(priority,'high','medium','low'), id DESC";
$st = $db->prepare($sql);
$st->execute($params);
$tasks = $st->fetchAll();

$back = http_build_query(array_filter(['filter' => $filter !== 'all' ? $filter : null, 'q' => $q ?: null]));
$me   = current_user();

$pageTitle = 'Görevlerim';
$active_nav = 'tasks';
$active = 'tasks'; // header.php için
include 'header.php';
?>

<section class="page-head">
    <div>
        <h1>Merhaba, <?= e($me['username']) ?> <span class="wave">👋</span></h1>
        <p class="muted"><?= date('d.m.Y') ?> · <?= $stats['today'] ?> görev bugün seni bekliyor.</p>
    </div>
</section>

<section class="stats">
    <div class="stat"><span class="stat-num" data-count="<?= $stats['total'] ?>"><?= $stats['total'] ?></span><span class="stat-label">Toplam</span></div>
    <div class="stat"><span class="stat-num c-blue" data-count="<?= $stats['today'] ?>"><?= $stats['today'] ?></span><span class="stat-label">Bugün</span></div>
    <div class="stat"><span class="stat-num c-red" data-count="<?= $stats['overdue'] ?>"><?= $stats['overdue'] ?></span><span class="stat-label">Geciken</span></div>
    <div class="stat"><span class="stat-num c-green" data-count="<?= $stats['done'] ?>"><?= $stats['done'] ?></span><span class="stat-label">Tamamlanan</span></div>
</section>

<section class="progress-card">
    <div class="progress-top"><span>Genel ilerleme</span><strong>%<?= $percent ?></strong></div>
    <div class="progress"><div class="progress-bar" style="--w: <?= $percent ?>%"></div></div>
</section>

<section class="panel">
    <h2>Yeni görev ekle</h2>
    <form method="POST" class="add-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="back" value="<?= e($back) ?>">
        <input type="text" name="task_name" class="grow" placeholder="Ne yapacaksın?" required maxlength="255" autocomplete="off">
        <input type="date" name="task_date" value="<?= date('Y-m-d') ?>" required>
        <select name="priority">
            <?php foreach ($priorities as $k => $v): ?>
                <option value="<?= $k ?>" <?= $k === 'medium' ? 'selected' : '' ?>><?= $v ?> öncelik</option>
            <?php endforeach; ?>
        </select>
        <textarea name="description" rows="2" class="full" placeholder="Not / açıklama (isteğe bağlı)"></textarea>
        <button type="submit" class="btn btn-primary">+ Ekle</button>
    </form>
</section>

<section class="panel">
    <div class="tabs">
        <?php foreach ($filters as $key => [$label, $count]): ?>
            <a href="?<?= http_build_query(array_filter(['filter' => $key !== 'all' ? $key : null, 'q' => $q ?: null])) ?>"
               class="tab <?= $filter === $key ? 'active' : '' ?>"><?= $label ?> <span class="count"><?= $count ?></span></a>
        <?php endforeach; ?>
    </div>

    <form method="GET" class="search-form">
        <?php if ($filter !== 'all'): ?><input type="hidden" name="filter" value="<?= e($filter) ?>"><?php endif; ?>
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="🔍 Görevlerde ara...">
        <?php if ($stats['done'] > 0): ?>
            <button type="submit" form="clear-form" class="btn btn-ghost btn-sm" onclick="return confirm('Tamamlanan tüm görevler silinsin mi?')">Tamamlananları temizle</button>
        <?php endif; ?>
    </form>
    <form id="clear-form" method="POST">
        <?= csrf_field() ?><input type="hidden" name="action" value="clear_done"><input type="hidden" name="back" value="<?= e($back) ?>">
    </form>

    <?php if (!$tasks): ?>
        <div class="empty">
            <div class="empty-icon">🎉</div>
            <p><?= $q !== '' ? 'Aramanla eşleşen görev bulunamadı.' : 'Bu görünümde görev yok.' ?></p>
        </div>
    <?php else: ?>
    <ul class="task-list">
        <?php foreach ($tasks as $i => $t):
            $isOverdue = !$t['status'] && $t['task_date'] < date('Y-m-d');
            $isToday   = $t['task_date'] === date('Y-m-d');
        ?>
        <li class="task <?= $t['status'] ? 'completed' : '' ?> prio-<?= e($t['priority']) ?>" style="--i: <?= min($i, 12) ?>">
            <form method="POST" class="check-form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <input type="hidden" name="back" value="<?= e($back) ?>">
                <button type="submit" class="check" title="<?= $t['status'] ? 'Geri al' : 'Tamamla' ?>"><?= $t['status'] ? '✓' : '' ?></button>
            </form>
            <div class="task-body">
                <span class="task-title"><?= e($t['task_name']) ?></span>
                <?php if ($t['description']): ?><p class="task-desc"><?= nl2br(e($t['description'])) ?></p><?php endif; ?>
                <div class="task-meta">
                    <span class="badge <?= $isOverdue ? 'badge-red' : ($isToday ? 'badge-blue' : '') ?>">📅 <?= date('d.m.Y', strtotime($t['task_date'])) ?><?= $isOverdue ? ' · Gecikti' : ($isToday ? ' · Bugün' : '') ?></span>
                    <span class="badge badge-prio-<?= e($t['priority']) ?>"><?= $priorities[$t['priority']] ?></span>
                </div>
            </div>
            <div class="task-actions">
                <a href="edit.php?id=<?= (int)$t['id'] ?>" class="btn btn-soft btn-sm">Düzenle</a>
                <form method="POST" data-delete="Bu görev silinsin mi?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="back" value="<?= e($back) ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Sil</button>
                </form>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
    <?php endif; ?>
</section>

<?php include 'footer.php'; ?>
