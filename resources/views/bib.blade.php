<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC RUN - My BIB</title>
    <link rel="stylesheet" href="{{ asset('bib.css') }}">
</head>
<body>
    <header>
        
    </header>

    <main class="bib-layout">
        <section class="title">
            <div class="logo">LOGO</div>
        </section>

        <!-- Kartu Kontainer Utama -->
        <section class="bib-card">
            
            <!-- Header Teks di Dalam Kartu -->
            <div class="card-header">
                <h2>My BIB</h2>
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque a tempus augue. Aliquam tincidunt cursus turpis, a tempus nisi tristique id.</p>
                <hr class="divider">
            </div>

            @if(!$registration || $registration->payment_status != 'Paid')
                <div id="stateUnregistered" class="bib-state">
                    <div class="cube-icon">⬡</div>
                    <h3>Your BIB Is Not Available Yet</h3>
                    <p class="state-desc">You need to complete your registration and payment before your race BIB can be issued.</p>
                </div>
            @else
                <div id="stateRegistered" class="bib-state">
                    <h3>YOUR BIB NUMBER</h3>
                    <div class="bib-number">{{ $registration->bib_number ?? '-' }}</div>
                    <p class="qr-label">RACEPACK QR-CODE</p>
                    <div class="qr-box">
                        QR
                    </div>
                </div>
            @endif

        </section>
    </main>
</body>
</html>