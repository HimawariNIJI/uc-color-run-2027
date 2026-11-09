<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>UC Color Run - Registration Payment</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  
  <!-- Payment CSS -->
  <link rel="stylesheet" href="{{ asset('css/payment.css') }}">
</head>
<body>

  <!-- Initial Server Data Carrier -->
  <div id="initialPaymentData" 
       data-order-id="{{ $orderId ?? '' }}"
       data-name="{{ $participantName }}" 
       data-category="{{ $raceCategory }}" 
       data-tshirt="{{ $tshirtSize }}" 
       data-amount="{{ $amount }}" 
       style="display: none;"></div>

  <!-- MAIN PAYMENT UI -->
  <div class="payment-container" id="mainPaymentView">
    
    <!-- Header -->
    <div class="header-section">
      <div class="logo-title">LOGO</div>
      <div class="logo-subtitle">UC Color Run</div>
      <div class="logo-tag">REGISTRATION PAYMENT</div>
      <hr class="header-divider">
    </div>

    <!-- Title Block -->
    <div class="title-block">
      <div class="main-title">Selesaikan Pembayaran</div>
      <div class="sub-title">Satu langkah lagi untuk mengamankan slot registrasimu!</div>
    </div>

    <!-- Box 1: Ringkasan Registrasi -->
    <div class="summary-card">
      <div class="summary-heading">RINGKASAN REGISTRASI</div>
      
      <div class="summary-row">
        <span class="label">Nama peserta</span>
        <span class="value" id="dispParticipantName">{{ $participantName }}</span>
      </div>

      <div class="summary-row">
        <span class="label">Kategori race</span>
        <span class="value" id="dispRaceCategory">{{ $raceCategory }}</span>
      </div>

      <div class="summary-row">
        <span class="label">Ukuran T-shirt</span>
        <span class="value" id="dispTshirtSize">{{ $tshirtSize }}</span>
      </div>

      <hr class="summary-divider">

      <div class="total-row">
        <span class="total-label">Total pembayaran</span>
        <span class="total-amount" id="dispTotalAmount">Rp{{ number_format($amount, 0, ',', '.') }}</span>
      </div>
    </div>

    <!-- Box 2: Bayar dengan QRIS -->
    <div class="qris-card">
      <div class="qris-header">
        <span class="qris-header-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="3" width="7" height="7"></rect>
            <rect x="14" y="3" width="7" height="7"></rect>
            <rect x="14" y="14" width="7" height="7"></rect>
            <rect x="3" y="14" width="7" height="7"></rect>
          </svg>
        </span>
        <span>Bayar dengan QRIS</span>
      </div>

      <!-- QR Code Image -->
      <div class="qr-box" id="qrContainer">
        <div class="qr-placeholder-text" id="qrLoadingText">Membuat QRIS Midtrans...</div>
        <img id="qrImageElement" src="" alt="QRIS Code" style="display: none;">
      </div>

      <div class="qr-instruction">
        Scan QR menggunakan aplikasi mobile banking atau e-wallet yang mendukung QRIS.
      </div>

      <!-- Expiry Countdown Box -->
      <div class="expiry-box">
        <div class="expiry-icon">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <polyline points="12 6 12 12 16 14"></polyline>
          </svg>
        </div>
        <div>
          <div class="expiry-title">Selesaikan sebelum kedaluwarsa</div>
          <div class="expiry-desc" id="expiryTimerText">Batas waktu pembayaran akan ditampilkan di sini.</div>
        </div>
      </div>
    </div>

    <!-- Section 3: Petunjuk Pembayaran -->
    <div class="instructions-section">
      <div class="instructions-title">Petunjuk pembayaran</div>

      <div class="instruction-item">
        <div class="step-circle">1</div>
        <div>Buka aplikasi mobile banking atau e-wallet.</div>
      </div>

      <div class="instruction-item">
        <div class="step-circle">2</div>
        <div>Pilih menu Scan QRIS, lalu pindai kode di atas.</div>
      </div>

      <div class="instruction-item">
        <div class="step-circle">3</div>
        <div>Pastikan nominal sesuai, lalu konfirmasi pembayaran.</div>
      </div>
    </div>

    <!-- Action Button -->
    <button class="btn-check-status" id="btnCheckStatus" onclick="manualCheckStatus()">
      <span id="btnSpinner" class="spinner" style="display: none;"></span>
      <span id="btnText">Cek Status Pembayaran</span>
    </button>

    <!-- Footer Help -->
    <div class="footer-help">
      Butuh bantuan? Hubungi panitia UC Color Run.
    </div>
  </div>

  <!-- SUCCESS CONFIRMATION VIEW -->
  <div class="payment-container success-container" id="successView">
    <div class="success-icon-wrap">✓</div>
    <div class="badge-paid">LUNAS / VERIFIED</div>
    
    <h2 style="font-size: 20px; font-weight: 800; margin-bottom: 4px; color: #0f172a;">
      Pembayaran Berhasil!
    </h2>
    <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">
      Slot registrasi UC Color Run Anda telah terkonfirmasi.
    </p>

    <!-- Ticket Details Box -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; text-align: left; font-size: 13px; margin-bottom: 20px;">
      <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span style="color: #64748b;">No. Transaksi:</span>
        <strong id="resOrderId" style="font-family: monospace;">-</strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span style="color: #64748b;">Nama Peserta:</span>
        <strong id="resName">-</strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span style="color: #64748b;">Kategori Race:</span>
        <strong id="resCategory">5K</strong>
      </div>
      <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
        <span style="color: #64748b;">Ukuran T-shirt:</span>
        <strong id="resTshirt">M</strong>
      </div>
      <hr style="border: none; height: 1px; background: #e2e8f0; margin: 10px 0;">
      <div style="display: flex; justify-content: space-between;">
        <span style="color: #64748b; font-weight: 600;">Total Dibayar:</span>
        <strong style="color: #16a34a; font-size: 15px;" id="resAmount">Rp150.000</strong>
      </div>
    </div>

    <a href="{{ route('my-registration') }}" class="btn-check-status" style="background: #10b981; text-decoration: none; margin-bottom: 10px;">
      Lihat Status Registrasi & BIB →
    </a>

    <button class="btn-check-status" onclick="location.reload()" style="background: #64748b;">
      Daftar Peserta Lain
    </button>
  </div>

  <!-- Developer & Sandbox Testing Toolbar -->
  <div class="dev-toolbar">
    <div class="dev-title">
      <span>🛠️ Midtrans Sandbox Testing</span>
      <span style="color: #10b981; font-weight: 700;">Online</span>
    </div>
    <div class="dev-buttons">
      <button class="dev-btn highlight" onclick="copyQrString()">📋 Salin QR String</button>
      <a class="dev-btn highlight" href="https://simulator.sandbox.midtrans.com/qris/index" target="_blank">🔗 Buka Simulator Midtrans ↗</a>
      <button class="dev-btn success" onclick="instantSimulatePaid()">⚡ Simulasi Bayar Langsung</button>
      <button class="dev-btn" onclick="promptEditData()">✏️ Ubah Data Peserta</button>
    </div>
  </div>

  <!-- Payment JavaScript -->
  <script src="{{ asset('js/payment.js') }}"></script>
</body>
</html>

