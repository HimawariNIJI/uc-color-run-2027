<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC Color Run - My Registration</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="{{ asset('myregistration.css') }}">
</head>
<body>
    <header></header>

    <main>
        <div class="back-link-wrapper">
            <a href="{{ url('/') }}" class="back-link">← Back to home</a>
        </div>

        @if(session('success'))
            <div class="flash-alert success">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="flash-alert error">
                {{ session('error') }}
            </div>
        @endif

        <!-- Kartu Kontainer Utama -->
        <section class="reg-card">

            <!-- Header Title & Badge Status -->
            <div class="card-header-row">
                <h2 class="card-title">My Registration</h2>

                @if(!$registration)
                    <span class="badge empty">Empty</span>
                @elseif($registration->isPaid())
                    <span class="badge paid">Paid</span>
                @else  
                    <span class="badge pending">Pending</span>
                @endif
            </div>
            
            <hr class="divider">

            {{-- 1. EMPTY STATE (Image 3) --}}
            @if(!$registration)
                <div class="reg-state-box">
                    <div class="empty-content-box">
                        <div class="cube-icon">
                            <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.12 6.4-6.05-4.06a4.97 4.97 0 0 0-5.14 0L3.88 6.4a4.98 4.98 0 0 0-2.38 4.25v6.7c0 1.78.96 3.42 2.38 4.25l6.05 4.06a4.97 4.97 0 0 0 5.14 0l6.05-4.06a4.98 4.98 0 0 0 2.38-4.25v-6.7c0-1.78-.96-3.42-2.38-4.25Z"></path>
                                <polyline points="3.29 7 12 12.8 20.71 7"></polyline>
                                <line x1="12" y1="22.8" x2="12" y2="12.8"></line>
                            </svg>
                        </div>
                        <h3>No Registration Yet</h3>
                        <p>You haven't registered for UC Color Run 2027 yet. Complete your registration to secure your spot!</p>
                    </div>
                </div>
                <a href="{{ route('registration.create') }}" class="action-btn">Register Now →</a>

            {{-- 2. PENDING / UNPAID STATE (Image 2 Left) --}}
            @elseif(! $registration->isPaid())
                <div class="reg-state-box">
                    <div class="status-alert-box">
                        <div class="alert-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.12 6.4-6.05-4.06a4.97 4.97 0 0 0-5.14 0L3.88 6.4a4.98 4.98 0 0 0-2.38 4.25v6.7c0 1.78.96 3.42 2.38 4.25l6.05 4.06a4.97 4.97 0 0 0 5.14 0l6.05-4.06a4.98 4.98 0 0 0 2.38-4.25v-6.7c0-1.78-.96-3.42-2.38-4.25Z"></path>
                                <polyline points="3.29 7 12 12.8 20.71 7"></polyline>
                                <line x1="12" y1="22.8" x2="12" y2="12.8"></line>
                            </svg>
                        </div>
                        <div>
                            <strong>Registration Submitted</strong>
                            <p>Your registration has been submitted successfully. Please complete your payment to confirm your participation.</p>
                        </div>
                    </div>

                    <hr class="inner-divider">

                    <div class="details-section">
                        <h4>Registration Details</h4>
                        <div class="detail-row">
                            <span>Registration ID</span>
                            <strong>{{ $registration->registration_code }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Participant Name</span>
                            <strong>{{ $registration->full_name }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <strong>{{ $registration->email }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Race Category</span>
                            <strong>{{ $registration->race_category }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>T-shirt Size</span>
                            <strong>{{ $registration->tshirt_size }}</strong>
                        </div>
                    </div>

                    <hr class="inner-divider">

                    <div class="details-section">
                        <h4>Payment Information</h4>
                        <div class="detail-row">
                            <span>Payment Status</span>
                            <span class="badge unpaid">Unpaid</span>
                        </div>
                        <div class="detail-row amount-row">
                            <span>Amount</span>
                            <span class="amount-value">Rp{{ number_format($registration->amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <a href="{{ route('payment.index', [
                    'order_id' => $registration->order_id,
                    'name' => $registration->full_name,
                    'category' => $registration->race_category,
                    'tshirt' => $registration->tshirt_size,
                    'amount' => $registration->amount,
                ]) }}" class="action-btn">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="3" width="7" height="7"></rect>
                        <rect x="14" y="3" width="7" height="7"></rect>
                        <rect x="14" y="14" width="7" height="7"></rect>
                        <rect x="3" y="14" width="7" height="7"></rect>
                    </svg>
                    Pay Now
                </a>
                <a href="{{ route('registration.edit', $registration->id) }}" class="action-btn secondary-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    Edit Registration
                </a>

            {{-- 3. PAID STATE (Image 2 Right) --}}
            @else
                <div class="reg-state-box">
                    <div class="status-alert-box">
                        <div class="alert-icon">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m21.12 6.4-6.05-4.06a4.97 4.97 0 0 0-5.14 0L3.88 6.4a4.98 4.98 0 0 0-2.38 4.25v6.7c0 1.78.96 3.42 2.38 4.25l6.05 4.06a4.97 4.97 0 0 0 5.14 0l6.05-4.06a4.98 4.98 0 0 0 2.38-4.25v-6.7c0-1.78-.96-3.42-2.38-4.25Z"></path>
                                <polyline points="3.29 7 12 12.8 20.71 7"></polyline>
                                <line x1="12" y1="22.8" x2="12" y2="12.8"></line>
                            </svg>
                        </div>
                        <div>
                            <strong>Registration Confirmed</strong>
                            <p>Your payment has been verified and your registration is confirmed. See you at the starting line!</p>
                        </div>
                    </div>

                    <hr class="inner-divider">

                    <div class="details-section">
                        <h4>Registration Details</h4>
                        <div class="detail-row">
                            <span>Registration ID</span>
                            <strong>{{ $registration->registration_code }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Participant Name</span>
                            <strong>{{ $registration->full_name }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <strong>{{ $registration->email }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Race Category</span>
                            <strong>{{ $registration->race_category }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>T-shirt Size</span>
                            <strong>{{ $registration->tshirt_size }}</strong>
                        </div>
                    </div>

                    <hr class="inner-divider">

                    <div class="details-section">
                        <h4>Payment Information</h4>
                        <div class="detail-row">
                            <span>Payment Status</span>
                            <span class="badge paid">Paid</span>
                        </div>
                        <div class="detail-row amount-row">
                            <span>Amount</span>
                            <span class="amount-value">Rp{{ number_format($registration->amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>

                <a href="{{ route('my-bib', ['code' => $registration->registration_code]) }}" class="action-btn">View My BIB →</a>
            @endif

        </section>
    </main>
</body>
</html>

