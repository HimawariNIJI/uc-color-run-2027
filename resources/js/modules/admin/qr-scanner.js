/**
 * Racepack collection scanner: reads participants' racepack QR codes with the device camera and
 * sends them to the server, which checks them and records the scan. A valid scan's details open
 * in the admin modal; while it is open no other code is read, and a code still in view when it
 * closes is not sent again. Also handles the typed registration ID form.
 *
 * Uses the browser's BarcodeDetector where available (Chrome on Android and macOS) and the
 * jsQR library everywhere else (Windows, Firefox, iPhone), loaded only when needed.
 */
const SCAN_INTERVAL_MS = 200;
// A code held in front of the camera is checked once; it is checked again only after it
// has been out of view this long (so a participant can be re-scanned on purpose).
const SAME_CODE_REAPPEAR_MS = 3000;
const PAUSE_AFTER_SCAN_MS = 1500;
const MAX_DECODE_SIZE = 720;

const CAMERA_ERRORS = {
    NotAllowedError: 'Akses kamera ditolak. Izinkan akses kamera untuk situs ini di pengaturan browser, lalu coba lagi.',
    SecurityError: 'Akses kamera ditolak. Izinkan akses kamera untuk situs ini di pengaturan browser, lalu coba lagi.',
    NotFoundError: 'Kamera tidak ditemukan di perangkat ini. Gunakan input Registration ID di bawah.',
    OverconstrainedError: 'Kamera tidak ditemukan di perangkat ini. Gunakan input Registration ID di bawah.',
    NotReadableError: 'Kamera sedang dipakai aplikasi lain. Tutup aplikasi tersebut lalu coba lagi.',
};

async function createDecoder(canvas) {
    if ('BarcodeDetector' in window) {
        try {
            const formats = await window.BarcodeDetector.getSupportedFormats();

            if (formats.includes('qr_code')) {
                const detector = new window.BarcodeDetector({ formats: ['qr_code'] });

                return async (video) => (await detector.detect(video))[0]?.rawValue ?? null;
            }
        } catch {
            // Fall back to jsQR below.
        }
    }

    const { default: jsQR } = await import('jsqr');
    const context = canvas.getContext('2d', { willReadFrequently: true });

    return async (video) => {
        const { videoWidth: width, videoHeight: height } = video;

        if (!width || !height) {
            return null;
        }

        const scale = Math.min(1, MAX_DECODE_SIZE / Math.max(width, height));
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);
        context.drawImage(video, 0, 0, canvas.width, canvas.height);

        const image = context.getImageData(0, 0, canvas.width, canvas.height);

        return jsQR(image.data, image.width, image.height, { inversionAttempts: 'dontInvert' })?.data ?? null;
    };
}

export function initQrScanner(root) {
    const scanner = root.querySelector('[data-qr-scanner]');

    if (!scanner) {
        return;
    }

    const scanUrl = scanner.dataset.scanUrl;
    const recentLimit = Number(scanner.dataset.recentLimit) || 10;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const video = scanner.querySelector('[data-scanner-video]');
    const idle = scanner.querySelector('[data-scanner-idle]');
    const live = scanner.querySelector('[data-scanner-live]');
    const startButton = scanner.querySelector('[data-scanner-start]');
    const stopButton = scanner.querySelector('[data-scanner-stop]');
    const result = scanner.querySelector('[data-scanner-result]');
    const manualForm = scanner.querySelector('[data-scanner-manual]');
    const recentRows = document.getElementById('recent-scans');
    const resultDialog = document.querySelector('[data-admin-modal]');
    const canvas = document.createElement('canvas');

    let decode = null;
    let stream = null;
    let timer = null;
    let busy = false;
    let pausedUntil = 0;
    let lastPayload = null;
    let lastPayloadSeenAt = 0;

    const showResult = (tone, message) => {
        const template = scanner.querySelector(`[data-scanner-template="${tone}"]`) ?? scanner.querySelector('[data-scanner-template="error"]');
        const alert = template.content.firstElementChild.cloneNode(true);

        alert.querySelector('[data-scanner-message]').textContent = message;
        alert.querySelector('[data-dismiss]')?.addEventListener('click', () => alert.remove());
        result.replaceChildren(alert);
    };

    const addRecentRow = (html) => {
        recentRows.querySelector('[data-scanner-empty]')?.setAttribute('hidden', '');
        recentRows.insertAdjacentHTML('afterbegin', html);
        [...recentRows.querySelectorAll('[data-scanner-row]')].slice(recentLimit).forEach((row) => row.remove());
    };

    const setState = (state) => {
        const isLive = state === 'live';

        video.hidden = !isLive;
        live.hidden = !isLive;
        idle.hidden = isLive;
        startButton.hidden = isLive;
        stopButton.hidden = !isLive;
        startButton.disabled = state === 'starting';
        startButton.toggleAttribute('aria-busy', state === 'starting');
    };

    const submit = async (body) => {
        busy = true;

        try {
            const response = await fetch(scanUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken },
                credentials: 'same-origin',
                body: JSON.stringify(body),
            });

            if (response.status === 401 || response.status === 419) {
                stop();
                showResult('error', 'Sesi admin berakhir. Muat ulang halaman lalu login kembali.');

                return false;
            }

            const data = await response.json().catch(() => ({}));

            showResult(data.tone ?? 'error', data.message ?? 'Terjadi kesalahan. Silakan coba lagi.');

            if (data.modal) {
                document.dispatchEvent(new CustomEvent('admin-modal:show', { detail: { html: data.modal } }));
            }

            if (data.row) {
                addRecentRow(data.row);

                if (navigator.userActivation?.hasBeenActive) {
                    navigator.vibrate?.(120);
                }
            }

            return Boolean(data.modal);
        } catch {
            showResult('error', 'Tidak dapat terhubung ke server. Periksa koneksi internet lalu coba lagi.');

            return false;
        } finally {
            busy = false;
            pausedUntil = Date.now() + PAUSE_AFTER_SCAN_MS;
        }
    };

    const scanFrame = async () => {
        if (!stream) {
            return;
        }

        const isShowingResult = resultDialog?.open ?? false;

        if (!busy && !isShowingResult && Date.now() >= pausedUntil && video.readyState >= HTMLMediaElement.HAVE_CURRENT_DATA) {
            try {
                const payload = await decode(video);

                if (payload) {
                    const isStillInView = payload === lastPayload && Date.now() - lastPayloadSeenAt < SAME_CODE_REAPPEAR_MS;

                    lastPayloadSeenAt = Date.now();

                    if (!isStillInView) {
                        lastPayload = payload;
                        await submit({ payload });
                        lastPayloadSeenAt = Date.now();
                    }
                }
            } catch {
                // An unreadable frame is normal while aiming; try the next one.
            }
        }

        timer = window.setTimeout(scanFrame, SCAN_INTERVAL_MS);
    };

    // A camera facing the admin (a laptop webcam or a phone's front camera) is shown mirrored, like
    // a mirror, so a QR code moved to the left also moves left on screen. A phone's back camera is
    // shown as it is. Only the preview is flipped: the decoder still reads the camera's own image.
    const mirrorPreviewFor = (cameraStream) => {
        const { facingMode } = cameraStream.getVideoTracks()[0]?.getSettings?.() ?? {};

        video.style.transform = facingMode === 'environment' ? '' : 'scaleX(-1)';
    };

    const start = async () => {
        if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
            showResult('error', 'Kamera hanya dapat digunakan melalui koneksi aman (HTTPS). Gunakan input Registration ID di bawah.');

            return;
        }

        setState('starting');

        try {
            decode ??= await createDecoder(canvas);
            stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: { ideal: 'environment' }, width: { ideal: 1280 }, height: { ideal: 720 } },
                audio: false,
            });
            mirrorPreviewFor(stream);
            video.srcObject = stream;
            await video.play();
            result.replaceChildren();
            setState('live');
            scanFrame();
        } catch (error) {
            stop();
            showResult('error', CAMERA_ERRORS[error?.name] ?? 'Kamera tidak dapat dinyalakan. Coba lagi atau gunakan input Registration ID di bawah.');
        }
    };

    function stop() {
        window.clearTimeout(timer);
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
        video.srcObject = null;
        video.style.transform = '';
        setState('idle');
    }

    startButton.addEventListener('click', start);
    stopButton.addEventListener('click', stop);

    // Ready for the next participant once the result is closed; the code just processed only
    // counts again after it has been out of view for a moment.
    resultDialog?.addEventListener('close', () => {
        lastPayloadSeenAt = Date.now();
        pausedUntil = Date.now() + PAUSE_AFTER_SCAN_MS;
    });

    // Release the camera when the tab is hidden or the page is left.
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && stream) {
            stop();
        }
    });
    window.addEventListener('pagehide', stop);

    manualForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const input = manualForm.elements.namedItem('code');
        const code = input.value.trim();

        if (code === '') {
            showResult('error', 'Masukkan Registration ID peserta.');
            input.focus();

            return;
        }

        const button = manualForm.querySelector('button[type="submit"]');
        button.disabled = true;

        if (await submit({ code })) {
            input.value = '';
        }

        button.disabled = false;
        input.focus();
    });
}
