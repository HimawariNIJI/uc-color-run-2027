<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UC RUN - My Registration</title>
    <link rel="stylesheet" href="{{ asset('myregistration.css') }}">
</head>
<body>
    <header>
    </header>

    <main>
        <div class="back-link-wrapper">
            <a href="#" class="back-link">← Back to home</a>
        </div>
        <!-- Kartu Kontainer Utama -->
        <section class="reg-card">

            <!-- Header Title & Badge Status -->
            <div class="card-header-row">
                <h2 class="card-title">My Registration</h2>

                @if(!$registration)
                    <span class="badge empty">Empty</span>
                @elseif($registration->payment_status == 'Pending')
                    <span class="badge pending">Pending</span>
                @else  
                    <span class="badge paid">Paid</span>
                @endif
            </div>
            
            <hr class="divider">

            @if($registration-> payment_status == 'null') 
            <!-- ganti !$registration (no registration) -->
                <div class="reg-state-box" style="display: block;">
                    <div class="empty-content-box">
                        <div class="cube-icon">⬡</div>
                        <h3>No Registration Yet</h3>
                        <p>You haven't registered for UC Color Run 2027 yet. Complete your registration to secure your spot!</p>
                        <p>Register Now</p>
                    </div>
                </div>
                <a href="#" class="action-btn" style="text-align: center; text-decoration: none; display: block; line-height: 44px;">Register Now →</a>

            @elseif($registration->payment_status == 'Pending')
                <div class="reg-state-box">
                    <div class="status-alert-box pending">
                        <div class="alert-icon">⬡</div>
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
                            <strong>{{ $registration->id }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Participant Name</span>
                            <strong>{{  $registration->name }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <strong>{{ $registration-> email }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Race Category</span>
                            <strong>{{ $registration->category }}</strong>
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
                            <span class="badge unpaid" style="color: black">{{ $registration->payment_status }}</span>
                        </div>
                        <div class="detail-row amount-row">
                            <span>Amount</span>
                            <span class="amount-value">Rp{{ number_format($registration->amount ?? 150000, 0, ',', '.') }}</span>
                        </div>
                    </div>

                </div>
                <a href="#" class="action-btn" style="text-align: center; text-decoration: none; display: block; line-height: 44px;">Pay Now</a>
                <a href="#" class="action-btn secondary-btn" style="text-align: center; text-decoration: none; display: block; line-height: 44px;">Edit Registration</a>

            @else
                <div class="reg-state-box">
                    <div class="status-alert-box paid">
                        <div class="alert-icon">⬡</div>
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
                            <strong>{{ $registration->id }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Participant Name</span>
                            <strong>{{ $registration->name }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Email</span>
                            <strong>{{ $registration->email }}</strong>
                        </div>
                        <div class="detail-row">
                            <span>Race Category</span>
                            <strong>{{ $registration->category }}</strong>
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
                            <span class="badge paid" style="color: black">{{ $registration->payment_status }}</span>
                        </div>
                        <div class="detail-row amount-row">
                            <span>Amount</span>
                            <span class="amount-value">Rp{{ number_format($registration->amount ?? 150000, 0, ',', '.') }}</span>
                        </div>
                    </div>
                
                </div>
                <a href="{{ url('/my-bib') }}" class="action-btn" style="text-align: center; text-decoration: none; display: block; line-height: 44px;">View My BIB →</a>
            @endif

        </section>
    </main>
</body>
</html>