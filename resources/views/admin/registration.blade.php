@extends('layouts.admin', [
    'heading' => 'Registration Management',
    'subheading' => 'A complete overview of race registrations',
])

@section('title', 'Registration Management')

@section('content')
    <div class="registration-container">
        <header class="page-header">
            <div>
                <h1>Participant Registrations</h1>
                <p>Review and manage individual entries</p>
            </div>

            <button id="exportBtn" class="btn export-btn">
                Export CSV
            </button>
        </header>

        <hr class="divider">

        <section class="filters">
            <input type="search" id="searchInput" placeholder="Search name or email..." aria-label="Search name or email">

            <select id="statusFilter" aria-label="Filter payment status">
                <option value="">All Status</option>
                <option value="Paid">Paid</option>
                <option value="Pending">Pending</option>
                <option value="Unpaid">Unpaid</option>
            </select>

            <select id="categoryFilter" aria-label="Filter category">
                <option value="">All Categories</option>
                <option value="10K">10K</option>
                <option value="5K">5K</option>
                <option value="Half Marathon">Half Marathon</option>
            </select>

            <button id="resetBtn" class="btn reset-btn">
                Reset
            </button>
        </section>

        <div class="entries-info">
            <span id="entriesCount">8 sample entries shown</span>
        </div>

        <section class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>REGISTRATION<br>ID</th>
                        <th>PARTICIPANT</th>
                        <th>CATEGORY</th>
                        <th>PAYMENT<br>STATUS</th>
                        <th>BIB<br>NUMBER</th>
                        <th>ACTION</th>
                    </tr>
                </thead>

                <tbody id="registrationTable">
                    @forelse ($registrations as $registration)
                        <tr>
                            <td>
                                <span class="registration-id">
                                    {{ $registration['id'] }}
                                </span>
                            </td>

                            <td>
                                <div class="user-profile">
                                    <div class="user-avatar">
                                        {{ strtoupper(substr($registration['name'], 0, 1)) }}
                                    </div>

                                    <div class="user-identity">
                                        <span class="user-name">
                                            {{ $registration['name'] }}
                                        </span>

                                        <span class="user-email">
                                            {{ $registration['email'] }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="category-badge">
                                    {{ $registration['category'] }}
                                </span>
                            </td>

                            <td>
                                <span class="status-badge {{ strtolower($registration['payment_status']) }}">
                                    {{ $registration['payment_status'] }}
                                </span>
                            </td>

                            <td>
                                <span class="bib-number">
                                    {{ $registration['bib_number'] ?: '-' }}
                                </span>
                            </td>

                            <td>
                                <button type="button" class="btn-view" data-registration-id="{{ $registration['id'] }}"
                                    data-name="{{ $registration['name'] }}" data-email="{{ $registration['email'] }}"
                                    data-category="{{ $registration['category'] }}"
                                    data-payment-status="{{ $registration['payment_status'] }}"
                                    data-bib-number="{{ $registration['bib_number'] }}"
                                    onclick="openRegistrationModal(this)">
                                    View <span aria-hidden="true">&rarr;</span>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                No registrations found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <footer class="table-footer">
            <span id="pageInfo">Page 1 of 4</span>

            <nav class="pagination" aria-label="Pagination">
                <button class="page-btn arrow-btn" id="prevBtn" aria-label="Previous page">
                    ‹
                </button>

                <div id="pageNumbers" class="page-numbers"></div>

                <button class="page-btn arrow-btn" id="nextBtn" aria-label="Next page">
                    ›
                </button>
            </nav>
        </footer>
    </div>

    <div class="modal-overlay" id="registrationModal">

        <div class="modal-container" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

            <!-- Header -->
            <header class="modal-header">
                <div class="modal-title">
                    <h1 id="modalTitle">Registration Details</h1>
                    <p id="registrationId">UCR-2601</p>
                </div>

                <button class="close-btn" id="closeModal" aria-label="Close">
                    <span class="close-icon">&times;</span>
                    <span>Close</span>
                </button>
            </header>

            <hr class="header-divider">

            <!-- 1. Personal Information -->
            <section class="detail-section">
                <h2>1. Personal Information</h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Full Name</span>
                        <p class="detail-value" id="fullName">
                            Ethan Cannavaro Lauda
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Email Address</span>
                        <p class="detail-value" id="email">
                            ethancanavaro@gmail.com
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Phone Number</span>
                        <p class="detail-value" id="phone">
                            0812345678
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Date of Birth</span>
                        <p class="detail-value" id="birthDate">
                            17 Oktober 2026
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Gender</span>
                        <p class="detail-value" id="gender">Male</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">KTP/Passport</span>
                        <p class="detail-value" id="identityNumber">
                            1234567890
                        </p>
                    </div>
                </div>
            </section>

            <!-- 2. Race Information -->
            <section class="detail-section">
                <h2>2. Race Information</h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Race Category</span>
                        <p class="detail-value" id="raceCategory">10K</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">T-shirt Size</span>
                        <p class="detail-value" id="shirtSize">XL</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Emergency Contact Name</span>
                        <p class="detail-value" id="emergencyName">Edward</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Emergency Contact Phone</span>
                        <p class="detail-value" id="emergencyPhone">
                            123456789
                        </p>
                    </div>
                </div>
            </section>

            <!-- 3. Address Information -->
            <section class="detail-section">
                <h2>3. Address Information</h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Province</span>
                        <p class="detail-value" id="province">East Java</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">City</span>
                        <p class="detail-value" id="city">Surabaya</p>
                    </div>

                    <div class="detail-item full-width">
                        <span class="detail-label">Full Address</span>
                        <p class="detail-value" id="address">Lorem Ipsum</p>
                    </div>
                </div>
            </section>

            <!-- 4. Registration Information -->
            <section class="detail-section">
                <h2>4. Registration Information</h2>

                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">Registration Date</span>
                        <p class="detail-value" id="registrationDate">
                            20 Oct 2026
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Amount</span>
                        <p class="detail-value" id="amount">Rp150.000</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Payment Date</span>
                        <p class="detail-value" id="paymentDate">
                            20 Oct 2026
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Payment Status</span>
                        <p class="detail-value" id="paymentStatus">Paid</p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">Transaction ID</span>
                        <p class="detail-value" id="transactionId">
                            PAY-UCR-2601
                        </p>
                    </div>

                    <div class="detail-item">
                        <span class="detail-label">BIB Number</span>
                        <p class="detail-value" id="bibNumber">1024</p>
                    </div>
                </div>
            </section>

            <!-- 5. QR Code -->
            <section class="detail-section">
                <h2>5. QR Code</h2>

                <div class="qr-placeholder" id="qrCode">
                    QR
                </div>
            </section>

        </div>
    </div>

<script>
const modal = document.getElementById('registrationModal');
const closeBtn = document.getElementById('closeModal');

let lastTrigger = null;

// Mengisi detail dan membuka modal
function openRegistrationModal(button) {
    lastTrigger = button;

    const data = {
        id: button.dataset.registrationId,
        name: button.dataset.name,
        email: button.dataset.email,
        category: button.dataset.category,
        status: button.dataset.paymentStatus,
        bib: button.dataset.bibNumber,

        // Belum tersedia pada dummy data saat ini
        phone: '-',
        birthDate: '-',
        gender: '-',
        identityNumber: '-',
        shirtSize: '-',
        emergencyName: '-',
        emergencyPhone: '-',
        province: '-',
        city: '-',
        address: '-',
        registrationDate: '-',
        amount: '-',
        paymentDate: '-',
        transactionId: '-',
    };

    const fieldMap = {
        id: 'registrationId',
        name: 'fullName',
        email: 'email',
        phone: 'phone',
        birthDate: 'birthDate',
        gender: 'gender',
        identityNumber: 'identityNumber',
        category: 'raceCategory',
        shirtSize: 'shirtSize',
        emergencyName: 'emergencyName',
        emergencyPhone: 'emergencyPhone',
        province: 'province',
        city: 'city',
        address: 'address',
        registrationDate: 'registrationDate',
        amount: 'amount',
        paymentDate: 'paymentDate',
        status: 'paymentStatus',
        transactionId: 'transactionId',
        bib: 'bibNumber',
    };

    Object.entries(fieldMap).forEach(([key, elementId]) => {
        const element = document.getElementById(elementId);

        if (element) {
            element.textContent = data[key] ?? '-';
        }
    });

    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    closeBtn.focus();
}

// Menutup modal
function closeRegistrationModal() {
    modal.classList.remove('show');
    document.body.style.overflow = '';

    if (lastTrigger) {
        lastTrigger.focus();
    }
}

// Tombol Close
closeBtn.addEventListener('click', closeRegistrationModal);

// Klik area gelap di luar modal
modal.addEventListener('click', (event) => {
    if (event.target === modal) {
        closeRegistrationModal();
    }
});

// Tombol Escape
document.addEventListener('keydown', (event) => {
    if (
        event.key === 'Escape' &&
        modal.classList.contains('show')
    ) {
        closeRegistrationModal();
    }
});
</script>
@endsection
