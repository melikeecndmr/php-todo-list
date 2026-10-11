</main>
<footer class="footer">© <?= date('Y') ?> TaskFlow · Kişisel Görev Yönetimi</footer>

<?php if (!empty($me)): ?>
<!-- Yapay zeka asistanı -->
<button type="button" class="ai-fab" id="aiFab" aria-label="Yapay zeka asistanı">✨</button>
<div class="ai-panel" id="aiPanel" data-csrf="<?= e(csrf_token()) ?>" role="dialog" aria-label="Yapay zeka asistanı">
    <div class="ai-head">
        <div class="ai-icon">🤖</div>
        <div><strong>TaskFlow Asistan</strong><small>Görevlerin hakkında bana sor</small></div>
        <button type="button" class="ai-close" id="aiClose" aria-label="Kapat">✕</button>
    </div>
    <div class="ai-messages" id="aiMessages">
        <div class="msg bot">Merhaba <?= e($me['username']) ?>! 👋 Günlük planını yapabilir, görevlerini özetleyebilir veya yeni görev ekleyebilirim. Ne yapalım?</div>
    </div>
    <div class="ai-chips">
        <button type="button" class="ai-chip" data-q="Günümü planla">📋 Günümü planla</button>
        <button type="button" class="ai-chip" data-q="Geciken görevlerim neler?">⏰ Gecikenler</button>
        <button type="button" class="ai-chip" data-q="Görevlerimi özetle">📊 Özetle</button>
    </div>
    <form class="ai-form" id="aiForm" autocomplete="off">
        <input type="text" id="aiInput" placeholder="Bir şey yaz... (örn: yarın için 3 görev ekle)" maxlength="1000">
        <button type="submit" class="ai-send" id="aiSend" aria-label="Gönder">➤</button>
    </form>
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['confetti'])): unset($_SESSION['confetti']); ?>
<script>window.__confetti = true;</script>
<?php endif; ?>
<script src="app.js"></script>
</body>
</html>
