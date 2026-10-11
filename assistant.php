<?php
// Yapay zeka asistanı uç noktası (JSON). Sadece giriş yapmış kullanıcılar kullanabilir.
require 'db.php';
require 'config.php';
header('Content-Type: application/json; charset=utf-8');

function out($arr, $code = 200) {
    http_response_code($code);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['user_id'])) out(['error' => 'Giriş yapmalısın.'], 401);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'Geçersiz istek.'], 405);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (!hash_equals($_SESSION['csrf'] ?? '', $in['csrf'] ?? '')) out(['error' => 'Oturum doğrulanamadı, sayfayı yenile.'], 419);

$message = trim((string)($in['message'] ?? ''));
if ($message === '' || mb_strlen($message) > 1000) out(['error' => 'Mesaj boş olamaz (en fazla 1000 karakter).'], 400);

// Basit hız sınırı: 10 dakikada en fazla 25 mesaj
$now = time();
$_SESSION['ai_hits'] = array_values(array_filter($_SESSION['ai_hits'] ?? [], fn($t) => $t > $now - 600));
if (count($_SESSION['ai_hits']) >= 25) out(['reply' => 'Çok hızlı gidiyoruz 😅 Birkaç dakika sonra tekrar dene.']);
$_SESSION['ai_hits'][] = $now;

$uid  = (int)$_SESSION['user_id'];
$me   = current_user();
$today = date('Y-m-d');
$prioTr = ['low' => 'düşük', 'medium' => 'orta', 'high' => 'yüksek'];

$st = $db->prepare("SELECT id, task_name, description, task_date, priority, status FROM tasks
                    WHERE user_id = :u ORDER BY status ASC, task_date ASC LIMIT 100");
$st->execute(['u' => $uid]);
$tasks = $st->fetchAll();

function add_tasks($db, $uid, $list) {
    $added = 0;
    foreach (array_slice((array)$list, 0, 10) as $t) {
        if (!is_array($t)) continue;
        $name = trim((string)($t['name'] ?? ''));
        $date = (string)($t['date'] ?? date('Y-m-d'));
        $prio = (string)($t['priority'] ?? 'medium');
        $desc = trim((string)($t['description'] ?? ''));
        if ($name === '' || mb_strlen($name) > 255) continue;
        if (!valid_date($date)) $date = date('Y-m-d');
        if (!in_array($prio, ['low', 'medium', 'high'], true)) $prio = 'medium';
        $ins = $db->prepare("INSERT INTO tasks (user_id, task_name, description, task_date, priority, status)
                             VALUES (:u, :n, :d, :t, :p, 0)");
        $ins->execute(['u' => $uid, 'n' => $name, 'd' => $desc ?: null, 't' => $date, 'p' => $prio]);
        $added++;
    }
    return $added;
}

/* ---------- API anahtarı yoksa: yerel (kural tabanlı) asistan ---------- */
if (ANTHROPIC_API_KEY === '' || strpos(ANTHROPIC_API_KEY, 'BURAYA') !== false) {
    $m = mb_strtolower($message);
    $open    = array_filter($tasks, fn($t) => !$t['status']);
    $overdue = array_filter($open, fn($t) => $t['task_date'] < $today);
    $todayT  = array_filter($open, fn($t) => $t['task_date'] === $today);
    $fmt = fn($arr) => $arr ? implode("\n", array_map(fn($t) => '• ' . $t['task_name'] . ' (' . date('d.m.Y', strtotime($t['task_date'])) . ', ' . $prioTr[$t['priority']] . ')', $arr)) : '';

    if (preg_match('/^(ekle|görev ekle)\s*[:\-]?\s*(.+)$/u', $message, $mm)) {
        $n = add_tasks($db, $uid, [['name' => $mm[2], 'date' => $today]]);
        out(['reply' => $n ? "Tamam, \"{$mm[2]}\" görevini bugüne ekledim. ✅" : 'Görev eklenemedi.', 'added' => $n]);
    }
    if (strpos($m, 'gecik') !== false) {
        out(['reply' => $overdue ? "Geciken görevlerin:\n" . $fmt($overdue) : 'Geciken görevin yok, harikasın! 🎉']);
    }
    if (strpos($m, 'bugün') !== false) {
        out(['reply' => $todayT ? "Bugünkü görevlerin:\n" . $fmt($todayT) : 'Bugün için bekleyen görevin yok. ☀️']);
    }
    if (strpos($m, 'plan') !== false || strpos($m, 'öncelik') !== false) {
        $order = array_merge($overdue, $todayT);
        usort($order, fn($a, $b) => [$a['task_date'], array_search($a['priority'], ['high','medium','low'])] <=> [$b['task_date'], array_search($b['priority'], ['high','medium','low'])]);
        out(['reply' => $order ? "Önerdiğim sıra:\n" . implode("\n", array_map(fn($i, $t) => ($i + 1) . '. ' . $t['task_name'], array_keys($order), $order)) : 'Bugün için acil bir işin görünmüyor. Yeni görev ekleyebilirsin!']);
    }
    $done = count($tasks) - count($open);
    out(['reply' => "Merhaba {$me['username']}! Şu an {$done} görevi tamamladın, " . count($open) . " görev bekliyor (" . count($overdue) . " geciken).\n\n"
        . "Şunları yazabilirsin: \"bugün\", \"geciken\", \"günümü planla\" ya da \"ekle: süt al\".\n\n"
        . "💡 Gerçek yapay zeka sohbeti için config.php dosyasına Anthropic API anahtarını gir."]);
}

/* ---------- Claude API ---------- */
$lines = [];
foreach ($tasks as $t) {
    $lines[] = sprintf('- [%s] "%s" | tarih: %s | öncelik: %s%s',
        $t['status'] ? 'tamam' : 'bekliyor', $t['task_name'], $t['task_date'], $prioTr[$t['priority']],
        $t['description'] ? ' | not: ' . mb_substr($t['description'], 0, 120) : '');
}
$system = "Sen TaskFlow adlı görev yönetim sitesinin samimi, kısa ve yardımsever yapay zeka asistanısın. Her zaman Türkçe yanıt ver.\n"
    . "Bugünün tarihi: {$today}. Kullanıcının adı: {$me['username']}.\n"
    . "Kullanıcının görevleri:\n" . ($lines ? implode("\n", $lines) : '(henüz görev yok)') . "\n\n"
    . "Görevleri özetleyebilir, öncelik sırası/gün planı önerebilir, görevleri alt adımlara bölebilir ve motivasyon verebilirsin. "
    . "Kullanıcı açıkça görev eklemeni isterse (veya bir hedefi görevlere bölmeni isterse) görevleri add_tasks listesine koy. Göreli tarihleri (yarın, haftaya) bugünün tarihine göre YYYY-MM-DD'ye çevir.\n"
    . "Yanıtını SADECE şu JSON formatında ver, başka hiçbir şey yazma:\n"
    . '{"reply": "kullanıcıya gösterilecek metin", "add_tasks": [{"name": "", "date": "YYYY-MM-DD", "priority": "low|medium|high", "description": ""}]}' . "\n"
    . "Görev eklenmeyecekse add_tasks boş dizi olsun. reply içinde markdown kullanma, düz metin yaz.";

$msgs = [];
foreach (array_slice((array)($in['history'] ?? []), -10) as $h) {
    if (!is_array($h)) continue;
    $role = ($h['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
    $content = trim((string)($h['content'] ?? ''));
    if ($content === '') continue;
    if ($msgs && $msgs[count($msgs) - 1]['role'] === $role) continue; // roller dönüşümlü olmalı
    $msgs[] = ['role' => $role, 'content' => mb_substr($content, 0, 2000)];
}
// İlk mesaj "user" olmalı ve son mesajdan önceki mesaj "assistant" olmalı
while ($msgs && $msgs[0]['role'] !== 'user') array_shift($msgs);
if ($msgs && $msgs[count($msgs) - 1]['role'] === 'user') array_pop($msgs);
$msgs[] = ['role' => 'user', 'content' => $message];

$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 45,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'x-api-key: ' . ANTHROPIC_API_KEY,
        'anthropic-version: 2023-06-01',
    ],
    CURLOPT_POSTFIELDS     => json_encode([
        'model'      => ANTHROPIC_MODEL,
        'max_tokens' => 1024,
        'system'     => $system,
        'messages'   => $msgs,
    ], JSON_UNESCAPED_UNICODE),
]);
$raw  = curl_exec($ch);
$err  = curl_error($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($raw === false) {
    out(['reply' => "Yapay zekaya bağlanamadım: {$err}\n(XAMPP'te SSL sertifika hatası alıyorsan README'deki ipucuna bak.)"]);
}
$resp = json_decode($raw, true);
if ($code !== 200) {
    $detail = $resp['error']['message'] ?? ('HTTP ' . $code);
    out(['reply' => "Yapay zeka şu an yanıt veremedi: {$detail}"]);
}

$text = trim($resp['content'][0]['text'] ?? '');
$clean = preg_replace('/^```(?:json)?\s*|\s*```$/u', '', $text);
$data = json_decode($clean, true);

if (is_array($data) && isset($data['reply'])) {
    $added = add_tasks($db, $uid, $data['add_tasks'] ?? []);
    out(['reply' => (string)$data['reply'], 'added' => $added]);
}
out(['reply' => $text !== '' ? $text : 'Bir yanıt alamadım, tekrar dener misin?', 'added' => 0]);
