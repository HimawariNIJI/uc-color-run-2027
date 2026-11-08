import { Html5Qrcode } from 'html5-qrcode';

const root = document.getElementById('scanner');

if (root) {
    const scanUrl = root.dataset.scanUrl;
    const recentUrl = root.dataset.recentUrl;
    const csrf = root.dataset.csrf;

    const box = document.getElementById('scanner-box');
    const state = document.getElementById('scanner-state');
    const toggle = document.getElementById('scanner-toggle');
    const msg = document.getElementById('scanner-msg');
    const rows = document.getElementById('scanner-rows');
    const empty = document.getElementById('scanner-empty');

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => (
        { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]
    ));

    /* ---------- Tabel "Recent QR Scanned" ---------- */
    function renderRecent(list) {
        rows.innerHTML = list.map((r) => `
            <tr>
                <td>${esc(r.registration_id || '-')}</td>
                <td>${esc(r.name)}<small>${esc(r.email || '')}</small></td>
                <td><span class="scanner-chip">${esc(r.category)}</span></td>
                <td>${esc(r.bib_number)}</td>
            </tr>`).join('');
        empty.hidden = list.length > 0;
    }

    async function refreshRecent() {
        try {
            const res = await fetch(recentUrl, { headers: { Accept: 'application/json' } });
            if (res.ok) renderRecent((await res.json()).data);
        } catch { /* biarkan tabel apa adanya */ }
    }

    try {
        renderRecent(JSON.parse(root.dataset.recent || '[]'));
    } catch {
        renderRecent([]);
    }

    /* ---------- Pesan status ---------- */
    function setMessage(type, text) {
        msg.textContent = text;
        msg.className = 'scanner-msg is-' + type;
        msg.hidden = !text;
    }

    /* ---------- Kirim hasil scan ke server ---------- */
    let busy = false;
    let lastCode = '';
    let lastAt = 0;

    async function onScan(decodedText) {
        const code = String(decodedText).trim();

        // Kamera membaca QR yang sama berkali-kali per detik; abaikan duplikat cepat.
        if (busy || !code || (code === lastCode && Date.now() - lastAt < 4000)) return;

        busy = true;
        lastCode = code;
        lastAt = Date.now();
        setMessage('info', 'Processing...');

        try {
            const res = await fetch(scanUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ bib_number: code }),
            });

            const data = await res.json().catch(() => ({}));

            if (res.ok && data.success) {
                setMessage('success', data.message);
                if (data.recent) renderRecent(data.recent);
            } else if (res.status === 419) {
                setMessage('error', 'Session expired. Reload this page and scan again.');
            } else {
                setMessage('error', data.message || 'Scan failed. Please try again.');
            }
        } catch (err) {
            console.error(err);
            setMessage('error', 'Cannot reach the server. Check the connection and scan again.');
        } finally {
            setTimeout(() => { busy = false; }, 1500);
        }
    }

    /* ---------- Kamera ---------- */
    let scanner = null;
    let running = false;
    let switching = false;

    function showState(on) {
        box.classList.toggle('is-on', on);
        state.innerHTML = on ? 'QR Scanner<br>ON' : 'QR Scanner<br>OFF';
        toggle.textContent = on ? 'Stop Camera' : 'Start Camera';
    }

    async function startCamera() {
        if (!window.isSecureContext) {
            setMessage('error', 'Camera only works on HTTPS (or localhost).');
            return;
        }

        scanner = scanner || new Html5Qrcode('reader');

        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 }, aspectRatio: 1 },
                onScan,
                () => { /* frame tanpa QR: abaikan */ }
            );
            running = true;
            showState(true);
            setMessage('', '');
        } catch (err) {
            console.error(err);
            const denied = err && (err.name === 'NotAllowedError' || /permission/i.test(String(err)));
            setMessage('error', denied
                ? 'Camera permission denied. Allow camera access in the browser and try again.'
                : 'Camera could not be started. Make sure no other app is using it.');
        }
    }

    async function stopCamera() {
        try {
            await scanner.stop();
            scanner.clear();
        } catch (err) {
            console.error(err);
        }
        running = false;
        showState(false);
    }

    toggle.addEventListener('click', async () => {
        if (switching) return;
        switching = true;
        toggle.disabled = true;
        try {
            running ? await stopCamera() : await startCamera();
        } finally {
            toggle.disabled = false;
            switching = false;
        }
    });

    // Sinkronkan tabel saat tab kembali aktif (mis. ada petugas lain yang scan).
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) refreshRecent();
    });

    /* ---------- QR contoh (hanya muncul di APP_ENV=local) ---------- */
    const preview = document.getElementById('qr-preview');
    if (preview) {
        import('qrcode').then(({ default: QRCode }) => {
            QRCode.toCanvas(preview, preview.dataset.bib, { width: 220, margin: 2 });
        });
    }
}
