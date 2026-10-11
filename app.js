/* TaskFlow – tema, animasyonlar, konfeti ve yapay zeka asistanı */
(function () {
    'use strict';
    var root = document.documentElement;
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Koyu / açık mod ---------- */
    var toggle = document.getElementById('themeToggle');
    if (toggle) {
        toggle.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            try { localStorage.setItem('theme', next); } catch (e) {}
        });
    }

    /* ---------- Sayıların yukarı doğru sayması ---------- */
    document.querySelectorAll('[data-count]').forEach(function (el) {
        var target = parseInt(el.getAttribute('data-count'), 10) || 0;
        if (reduceMotion || target === 0) { el.textContent = target; return; }
        var start = null, dur = 900;
        el.textContent = '0';
        function step(ts) {
            if (start === null) start = ts;
            var p = Math.min((ts - start) / dur, 1);
            var eased = 1 - Math.pow(1 - p, 3);
            el.textContent = Math.round(target * eased);
            if (p < 1) requestAnimationFrame(step);
        }
        requestAnimationFrame(step);
    });

    /* ---------- Silerken kayarak kaybolma ---------- */
    document.querySelectorAll('form[data-delete]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (!confirm(form.getAttribute('data-delete'))) return;
            var item = form.closest('.task');
            if (item) item.classList.add('removing');
            setTimeout(function () { form.submit(); }, reduceMotion ? 0 : 350);
        });
    });

    /* ---------- Düğmelerde dalga efekti ---------- */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.btn');
        if (!btn || reduceMotion) return;
        var rect = btn.getBoundingClientRect();
        var r = document.createElement('span');
        r.className = 'ripple';
        r.style.left = (e.clientX - rect.left) + 'px';
        r.style.top = (e.clientY - rect.top) + 'px';
        btn.appendChild(r);
        setTimeout(function () { r.remove(); }, 600);
    });

    /* ---------- Konfeti ---------- */
    function confetti() {
        if (reduceMotion) return;
        var canvas = document.createElement('canvas');
        canvas.id = 'confetti';
        document.body.appendChild(canvas);
        var ctx = canvas.getContext('2d');
        var W = canvas.width = window.innerWidth;
        var H = canvas.height = window.innerHeight;
        var colors = ['#6366f1', '#8b5cf6', '#ec4899', '#22c55e', '#f59e0b', '#38bdf8'];
        var parts = [];
        for (var i = 0; i < 140; i++) {
            parts.push({
                x: W / 2 + (Math.random() - .5) * 120,
                y: H * 0.35,
                vx: (Math.random() - .5) * 16,
                vy: Math.random() * -14 - 4,
                size: Math.random() * 7 + 4,
                rot: Math.random() * 6.28,
                vr: (Math.random() - .5) * .35,
                color: colors[(Math.random() * colors.length) | 0]
            });
        }
        var frames = 0;
        (function loop() {
            ctx.clearRect(0, 0, W, H);
            parts.forEach(function (p) {
                p.vy += .38; p.vx *= .992;
                p.x += p.vx; p.y += p.vy; p.rot += p.vr;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.globalAlpha = Math.max(0, 1 - frames / 150);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.size / 2, -p.size / 2, p.size, p.size * .55);
                ctx.restore();
            });
            frames++;
            if (frames < 150) requestAnimationFrame(loop); else canvas.remove();
        })();
    }
    if (window.__confetti) setTimeout(confetti, 150);

    /* ---------- Yapay zeka asistanı ---------- */
    var fab = document.getElementById('aiFab');
    var panel = document.getElementById('aiPanel');
    if (!fab || !panel) return;

    var box = document.getElementById('aiMessages');
    var form = document.getElementById('aiForm');
    var input = document.getElementById('aiInput');
    var sendBtn = document.getElementById('aiSend');
    var csrf = panel.getAttribute('data-csrf');
    var history = [];
    var busy = false;

    function setOpen(open) {
        panel.classList.toggle('open', open);
        fab.classList.toggle('open', open);
        fab.textContent = open ? '✕' : '✨';
        if (open) setTimeout(function () { input.focus(); }, 250);
    }
    fab.addEventListener('click', function () { setOpen(!panel.classList.contains('open')); });
    document.getElementById('aiClose').addEventListener('click', function () { setOpen(false); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });

    function addMsg(kind, text) {
        var d = document.createElement('div');
        d.className = 'msg ' + kind;
        d.textContent = text;
        box.appendChild(d);
        box.scrollTop = box.scrollHeight;
        return d;
    }
    function showTyping() {
        var d = document.createElement('div');
        d.className = 'msg bot typing';
        d.innerHTML = '<span></span><span></span><span></span>';
        box.appendChild(d);
        box.scrollTop = box.scrollHeight;
        return d;
    }

    async function ask(text) {
        if (busy || !text.trim()) return;
        busy = true; sendBtn.disabled = true;
        addMsg('user', text);
        input.value = '';
        var typing = showTyping();
        try {
            var res = await fetch('assistant.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ csrf: csrf, message: text, history: history.slice(-10) })
            });
            var data = await res.json();
            typing.remove();
            var reply = data.reply || data.error || 'Bir sorun oluştu.';
            addMsg('bot', reply);
            history.push({ role: 'user', content: text });
            history.push({ role: 'assistant', content: reply });
            if (data.added > 0) {
                addMsg('note', '✅ ' + data.added + ' görev eklendi — liste yenileniyor...');
                setTimeout(function () { location.reload(); }, 1800);
            }
        } catch (err) {
            typing.remove();
            addMsg('bot', 'Bağlantı hatası oluştu, lütfen tekrar dene.');
        }
        busy = false; sendBtn.disabled = false;
        input.focus();
    }

    form.addEventListener('submit', function (e) { e.preventDefault(); ask(input.value); });
    document.querySelectorAll('.ai-chip').forEach(function (chip) {
        chip.addEventListener('click', function () { ask(chip.getAttribute('data-q')); });
    });
})();
