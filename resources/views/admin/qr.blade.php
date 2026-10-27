@extends('layouts.admin', [
    'heading' => 'BIB Pickup Scanner',
    'subheading' => 'Scan participant QR codes to verify and collect race bibs.',
])

@section('title', 'BIB Pickup Scanner')

@section('content')

    <div class="scanner-page">
        <section class="scanner-card">
            <h2>Scan Participant QR Code</h2>

            <p class="scanner-description">
                Allow camera access, then point the camera at the participant's QR code.
            </p>

            <div id="scannerStatus" class="scanner-status" role="status" aria-live="polite">
                Scanner belum dimulai. Tekan tombol Start Scanner untuk memulai.
            </div>

            <div id="reader"></div>

            <div class="scanner-actions">
                <button type="button" id="startScannerBtn" class="scanner-btn scanner-btn-primary">
                    Start Scanner
                </button>

                <button type="button" id="stopScannerBtn" class="scanner-btn scanner-btn-secondary" disabled>
                    Stop Scanner
                </button>

                <button type="button" id="resetScannerBtn" class="scanner-btn scanner-btn-secondary">
                    Reset Result
                </button>
            </div>

            <div class="result-section">
                <h3>Scan Result</h3>

                <div id="scanResult" class="result-value" aria-live="polite">Belum ada QR Code yang dipindai.</div>

                <p class="result-note">
                    Halaman ini hanya membaca isi QR Code. Data peserta dan status kehadiran
                    belum diverifikasi ke database.
                </p>
            </div>
        </section>
    </div>

    <script src="https://unpkg.com/html5-qrcode"></script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const readerElement = document.getElementById('reader');
            const statusElement = document.getElementById('scannerStatus');
            const resultElement = document.getElementById('scanResult');

            const startButton = document.getElementById('startScannerBtn');
            const stopButton = document.getElementById('stopScannerBtn');
            const resetButton = document.getElementById('resetScannerBtn');

            let scanner = null;
            let isScanning = false;
            let isProcessing = false;

            function setStatus(message, type = '') {
                statusElement.textContent = message;
                statusElement.className = 'scanner-status';

                if (type) {
                    statusElement.classList.add(type);
                }
            }

            async function startScanner() {
                if (isScanning || isProcessing) return;

                if (typeof Html5Qrcode === 'undefined') {
                    setStatus(
                        'Library scanner gagal dimuat. Periksa koneksi internet.',
                        'error'
                    );
                    return;
                }

                isProcessing = true;
                startButton.disabled = true;

                try {
                    scanner = new Html5Qrcode('reader');

                    setStatus('Meminta akses kamera...');

                    await scanner.start({
                            facingMode: 'environment'
                        }, {
                            fps: 10,
                            qrbox: {
                                width: 250,
                                height: 250
                            },
                            aspectRatio: 1
                        },
                        async (decodedText) => {
                                if (!isScanning) return;

                                // Hindari hasil yang sama diproses berkali-kali.
                                isScanning = false;
                                stopButton.disabled = true;

                                resultElement.textContent = decodedText;
                                setStatus('QR Code berhasil terbaca!', 'success');

                                try {
                                    await scanner.stop();
                                } catch (error) {
                                    console.error('Gagal menghentikan kamera:', error);
                                }

                                startButton.disabled = false;
                            },
                            () => {
                                // Kegagalan membaca setiap frame adalah normal.
                            }
                    );

                    isScanning = true;
                    stopButton.disabled = false;
                    setStatus('Kamera aktif. Arahkan kamera ke QR Code.');
                } catch (error) {
                    console.error('Scanner error:', error);

                    setStatus(
                        'Kamera tidak dapat dibuka. Izinkan akses kamera dan pastikan kamera tidak sedang digunakan aplikasi lain.',
                        'error'
                    );

                    startButton.disabled = false;
                    stopButton.disabled = true;
                } finally {
                    isProcessing = false;
                }
            }

            async function stopScanner() {
                if (!scanner || !isScanning) return;

                isScanning = false;
                stopButton.disabled = true;

                try {
                    await scanner.stop();
                    setStatus('Scanner dihentikan.');
                } catch (error) {
                    console.error('Gagal menghentikan scanner:', error);
                    setStatus('Gagal menghentikan kamera.', 'error');
                } finally {
                    startButton.disabled = false;
                }
            }

            startButton.addEventListener('click', startScanner);
            stopButton.addEventListener('click', stopScanner);

            resetButton.addEventListener('click', () => {
                resultElement.textContent = 'Belum ada QR Code yang dipindai.';
                setStatus(
                    isScanning ?
                    'Kamera aktif. Arahkan kamera ke QR Code.' :
                    'Hasil direset. Tekan Start Scanner untuk memulai.'
                );
            });
        });
    </script>
@endsection
