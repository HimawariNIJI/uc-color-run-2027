// Initial Participant Data (can be initialized from server rendered data if present)
const initialDataEl = document.getElementById('initialPaymentData');
let currentParticipant = {
  name: initialDataEl?.dataset?.name || "Nama Lengkap",
  category: initialDataEl?.dataset?.category || "5K",
  tshirt: initialDataEl?.dataset?.tshirt || "M",
  amount: parseInt(initialDataEl?.dataset?.amount || "150000", 10)
};

let currentOrderId = initialDataEl?.dataset?.orderId || null;
let currentQrString = null;
let pollInterval = null;
let countdownInterval = null;

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

// Load and Generate QRIS when page loads
window.addEventListener('DOMContentLoaded', () => {
  createNewOrder();
});

async function createNewOrder() {
  const qrText = document.getElementById('qrLoadingText');
  const qrImg = document.getElementById('qrImageElement');
  
  if (qrText) {
    qrText.style.display = 'block';
    qrText.innerText = 'Membuat QRIS Midtrans...';
  }
  if (qrImg) qrImg.style.display = 'none';

  try {
    const response = await fetch('/payment/create-order', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        order_id: currentOrderId,
        participant_name: currentParticipant.name,
        race_category: currentParticipant.category,
        tshirt_size: currentParticipant.tshirt,
        amount: currentParticipant.amount
      })
    });

    const data = await response.json();

    if (!response.ok || data.error) {
      throw new Error(data.error || 'Gagal menghasilkan QRIS Midtrans');
    }

    currentOrderId = data.order_id;
    currentQrString = data.qr_string;

    // Render QR Code Image
    if (qrImg) {
      qrImg.src = data.qr_url;
      qrImg.onload = () => {
        if (qrText) qrText.style.display = 'none';
        qrImg.style.display = 'block';
      };
    }

    // Start Expiry Countdown (15 minutes)
    startCountdown(15 * 60);

    // Start Auto Background Polling every 3s
    startAutoPolling(data.order_id);

  } catch (err) {
    if (qrText) qrText.innerText = 'Gagal memuat QRIS';
    console.error('Error creating order:', err);
    alert('Midtrans Error: ' + err.message);
  }
}

// Auto-polling every 3 seconds to check if Midtrans has received the payment
function startAutoPolling(orderId) {
  if (pollInterval) clearInterval(pollInterval);

  pollInterval = setInterval(async () => {
    await checkStatusSilently(orderId);
  }, 3000);
}

async function checkStatusSilently(orderId) {
  if (!orderId) return;
  try {
    const res = await fetch(`/payment/check-status?order_id=${encodeURIComponent(orderId)}`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();

    if (data.status === 'PAID') {
      handleSuccess(data);
    }
  } catch (e) {
    console.warn('Status poll warning:', e);
  }
}

// Manual click on "Cek Status Pembayaran" button
async function manualCheckStatus() {
  if (!currentOrderId) {
    alert('Order belum dibuat!');
    return;
  }

  const btn = document.getElementById('btnCheckStatus');
  const spinner = document.getElementById('btnSpinner');
  const btnText = document.getElementById('btnText');

  if (btn) btn.disabled = true;
  if (spinner) spinner.style.display = 'inline-block';
  if (btnText) btnText.innerText = 'Memeriksa status...';

  try {
    const res = await fetch(`/payment/check-status?order_id=${encodeURIComponent(currentOrderId)}`, {
      headers: { 'Accept': 'application/json' }
    });
    const data = await res.json();

    if (data.status === 'PAID') {
      handleSuccess(data);
    } else {
      alert('Status: Belum dibayar.\nSilakan selesaikan pembayaran melalui aplikasi mobile banking atau e-wallet.');
    }
  } catch (err) {
    alert('Gagal memeriksa status: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (spinner) spinner.style.display = 'none';
    if (btnText) btnText.innerText = 'Cek Status Pembayaran';
  }
}

function handleSuccess(orderData) {
  if (pollInterval) clearInterval(pollInterval);
  if (countdownInterval) clearInterval(countdownInterval);

  const resOrderId = document.getElementById('resOrderId');
  const resName = document.getElementById('resName');
  const resCategory = document.getElementById('resCategory');
  const resTshirt = document.getElementById('resTshirt');
  const resAmount = document.getElementById('resAmount');

  if (resOrderId) resOrderId.innerText = orderData.order_id;
  if (resName) resName.innerText = orderData.participant_name || currentParticipant.name;
  if (resCategory) resCategory.innerText = orderData.race_category || currentParticipant.category;
  if (resTshirt) resTshirt.innerText = orderData.tshirt_size || currentParticipant.tshirt;
  if (resAmount) resAmount.innerText = 'Rp' + parseInt(orderData.amount || currentParticipant.amount, 10).toLocaleString('id-ID');

  const mainView = document.getElementById('mainPaymentView');
  const successView = document.getElementById('successView');
  if (mainView) mainView.style.display = 'none';
  if (successView) successView.style.display = 'block';
}

function startCountdown(seconds) {
  if (countdownInterval) clearInterval(countdownInterval);
  let remaining = seconds;
  const timerEl = document.getElementById('expiryTimerText');

  countdownInterval = setInterval(() => {
    const m = Math.floor(remaining / 60);
    const s = remaining % 60;
    const formatted = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
    
    if (timerEl) {
      timerEl.innerText = `Sisa waktu pembayaran: ${formatted}`;
    }

    if (--remaining < 0) {
      clearInterval(countdownInterval);
      clearInterval(pollInterval);
      if (timerEl) timerEl.innerText = 'Pembayaran kedaluwarsa. Silakan refresh.';
    }
  }, 1000);
}

// Sandbox Testing Helpers
function copyQrString() {
  if (!currentQrString) {
    alert('QR code belum siap!');
    return;
  }
  navigator.clipboard.writeText(currentQrString).then(() => {
    alert('✓ QR String berhasil disalin!\nBuka simulator Midtrans dan paste string ini untuk simulasi pembayaran.');
  }).catch(() => {
    prompt('Salin QR String berikut:', currentQrString);
  });
}

async function instantSimulatePaid() {
  if (!currentOrderId) {
    alert('Order belum dibuat!');
    return;
  }

  const btn = event?.target;
  if (btn) { btn.disabled = true; btn.innerText = '⏳ Memproses...'; }

  try {
    // 1. Mark as paid via mock endpoint
    const res = await fetch('/payment/mock-pay', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ order_id: currentOrderId })
    });

    if (!res.ok) {
      const err = await res.json();
      throw new Error(err.error || 'Gagal simulasi');
    }

    const data = await res.json();
    if (!data.success) throw new Error('Respons tidak berhasil');

    // 2. Immediately handle success using data we already have
    handleSuccess({
      order_id: currentOrderId,
      participant_name: currentParticipant.name,
      race_category: currentParticipant.category,
      tshirt_size: currentParticipant.tshirt,
      amount: currentParticipant.amount,
      status: 'PAID'
    });

  } catch (err) {
    alert('Simulasi error: ' + err.message);
    if (btn) { btn.disabled = false; btn.innerText = '⚡ Simulasi Bayar Langsung'; }
  }
}

function promptEditData() {
  const newName = prompt("Nama Peserta:", currentParticipant.name);
  if (newName === null) return;
  const newCat = prompt("Kategori Race (e.g. 5K / 10K):", currentParticipant.category);
  if (newCat === null) return;
  const newTshirt = prompt("Ukuran T-shirt (S / M / L / XL / XXL):", currentParticipant.tshirt);
  if (newTshirt === null) return;
  const newAmount = prompt("Nominal Pembayaran (Rp):", currentParticipant.amount);
  if (newAmount === null) return;

  currentParticipant.name = newName || "Nama Lengkap";
  currentParticipant.category = newCat || "5K";
  currentParticipant.tshirt = newTshirt || "M";
  currentParticipant.amount = parseInt(newAmount, 10) || 150000;

  const dispName = document.getElementById('dispParticipantName');
  const dispCat = document.getElementById('dispRaceCategory');
  const dispTshirt = document.getElementById('dispTshirtSize');
  const dispAmount = document.getElementById('dispTotalAmount');

  if (dispName) dispName.innerText = currentParticipant.name;
  if (dispCat) dispCat.innerText = currentParticipant.category;
  if (dispTshirt) dispTshirt.innerText = currentParticipant.tshirt;
  if (dispAmount) dispAmount.innerText = 'Rp' + currentParticipant.amount.toLocaleString('id-ID');

  createNewOrder();
}

