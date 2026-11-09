<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC Color Run - My BIB</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('bib.css') }}">
</head>
<body>
    <header></header>

    <main class="bib-layout">
        <div class="back-link-wrapper">
            <a href="{{ route('my-registration') }}" class="back-link">← Back to My Registration</a>
        </div>

        <section class="title">
            <div class="logo">LOGO</div>
        </section>

        <!-- Kartu Kontainer Utama -->
        <section class="bib-card">
            
            <div class="card-header">
                <h2>My BIB Number</h2>
                <p>Simpan atau screenshot nomor BIB dan QR Code berikut untuk pengambilan Race Pack resmi UC Color Run 2027.</p>
                <hr class="divider">
            </div>

            @if(!$registration || ! $registration->isPaid())
                <div id="stateUnregistered" class="bib-state">
                    <div class="cube-icon">⬡</div>
                    <h3>Your BIB Is Not Available Yet</h3>
                    <p class="state-desc">You need to complete your registration and payment before your race BIB can be issued.</p>
                    <a href="{{ route('my-registration') }}" class="action-btn">Complete Payment</a>
                </div>
            @else
                <div id="stateRegistered" class="bib-box">
                    <h3>YOUR BIB NUMBER</h3>
                    <div class="runner-name">{{ $bib['runner_name'] ?? $registration->full_name }}</div>
                    <div class="race-cat-badge">{{ $bib['category'] ?? $registration->race_category }}</div>
                    
                    <div class="bib-number">{{ $bib['bib_number'] ?? $registration->bib_number }}</div>

                    <div style="font-size: 13px; color: #64748b; margin-bottom: 14px;">
                        Start Wave: <strong>{{ $bib['wave'] ?? 'Wave 1 (05:30 AM)' }}</strong>
                    </div>

                    <p class="qr-label">RACEPACK VERIFICATION QR</p>
                    <div class="qr-box">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data={{ urlencode($registration->registration_code . '-' . ($bib['bib_number'] ?? $registration->bib_number)) }}" alt="BIB QR">
                    </div>
                </div>
            @endif

        </section>
    </main>
</body>
</html>

