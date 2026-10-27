@extends('layouts.admin', [
    'heading' => 'Dashboard',
    'subheading' => 'Overview of UC Color Run 2027 registrations.',
])

@section('title', 'Dashboard')

@section('content')

    {{-- ===================== STAT CARDS ===================== --}}
    <section class="stats" aria-label="Summary">
        <article class="card">
            <div class="card-head">
                <h2 class="stat-title">Total Users</h2>
                <span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-placeholder"/></svg></span>
            </div>
            <p class="stat-value">{{ number_format($stats['total_users']) }}</p>
            <p class="stat-note">Accounts that have logged in</p>
        </article>

        <article class="card">
            <div class="card-head">
                <h2 class="stat-title">Total Registrations</h2>
                <span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-placeholder"/></svg></span>
            </div>
            <p class="stat-value">{{ number_format($stats['total_registrations']) }}</p>
            <p class="stat-note">{{ $stats['registration_rate'] }}% of logged-in users</p>
        </article>

        <article class="card">
            <div class="card-head">
                <h2 class="stat-title">Paid Registrations</h2>
                <span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-placeholder"/></svg></span>
            </div>
            <p class="stat-value">{{ number_format($stats['paid_registrations']) }}</p>
            <p class="stat-note">{{ $stats['paid_rate'] }}% of registrations</p>
        </article>

        <article class="card">
            <div class="card-head">
                <h2 class="stat-title">BIB Issued</h2>
                <span class="stat-icon"><svg class="icon" aria-hidden="true"><use href="#icon-placeholder"/></svg></span>
            </div>
            <p class="stat-value">{{ number_format($stats['bib_issued']) }}</p>
            <p class="stat-note">{{ number_format($stats['awaiting_bib']) }} paid registrations awaiting BIB</p>
        </article>
    </section>

    {{-- ===================== BAR + PIE ===================== --}}
    <section class="grid-2">
        <article class="card">
            <h2 class="card-title">Registrations by Category</h2>
            <p class="card-sub">Distribution of race entries</p>
            <div class="chart-box">
                <canvas id="categoryChart" role="img" aria-label="Bar chart of registrations by category"></canvas>
            </div>
        </article>

        <article class="card">
            <h2 class="card-title">Payment Overview</h2>
            <p class="card-sub">Distribution by payment status</p>
            <div class="chart-box">
                <canvas id="paymentChart" role="img" aria-label="Pie chart of payment status"></canvas>
            </div>
        </article>
    </section>

    {{-- ===================== LINE ===================== --}}
    <section class="card card-block">
        <div class="card-head">
            <div>
                <h2 class="card-title">Registration Trend</h2>
                <p class="card-sub">New registrations over the last {{ $range }} days</p>
            </div>

            <form method="GET" action="{{ route('admin.dashboard') }}">
                <select name="range" class="select-pill select-pill--sm" aria-label="Date range" onchange="this.form.submit()">
                    @foreach ([7, 14, 30] as $days)
                        <option value="{{ $days }}" @selected($range === $days)>{{ $days }} days</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="chart-box chart-box--wide">
            <canvas id="trendChart" role="img" aria-label="Line chart of new registrations"></canvas>
        </div>
    </section>

    {{-- ===================== RECENT REGISTRATIONS ===================== --}}
    <section class="card card-block">
        <div class="card-head">
            <div>
                <h2 class="card-title">Recent Registrations</h2>
                <p class="card-sub">Latest participant activity</p>
            </div>
            <a href="{{ route('admin.dashboard') }}" class="btn-pill">View All &rarr;</a>
        </div>

        <div class="filters">
            <input type="search" id="regSearch" class="input" placeholder="Search name, email, or registration ID..." aria-label="Search registrations">
            <select id="regStatus" class="select-pill" aria-label="Filter by status">
                <option value="">All Statuses</option>
                <option value="paid">Paid</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
            </select>
        </div>

        <div class="table-wrap">
            <table id="regTable">
                <thead>
                    <tr>
                        <th>PARTICIPANT</th>
                        <th>CATEGORY</th>
                        <th>STATUS</th>
                        <th>DATE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recent as $row)
                        <tr data-status="{{ $row['status'] }}"
                            data-search="{{ strtolower($row['name'].' '.$row['email'].' '.$row['reg_id']) }}">
                            <td>
                                <span class="cell-name">{{ $row['name'] }}</span>
                                <span class="cell-id">{{ $row['reg_id'] }}</span>
                            </td>
                            <td>{{ $row['category'] }}</td>
                            <td>
                                <span class="badge badge--{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span>
                            </td>
                            <td>{{ $row['date'] }}</td>
                        </tr>
                    @endforeach

                    <tr class="empty-row" id="noResults" hidden>
                        <td colspan="4">No registrations match your search.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>

@endsection

@push('scripts')
    {{-- Chart.js (pinned version) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

    <script>
        // ---------- Charts ----------
        Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
        Chart.defaults.color = '#111111';

        const palette = ['#2f2f2f', '#7a7a7a', '#a8a8a8', '#5a5a5a']; // change to your brand colors
        const gridColor = 'rgba(0, 0, 0, 0.12)';

        const category = @json($categoryChart);
        const payment  = @json($paymentChart);
        const trend    = @json($trendChart);

        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: category.labels,
                datasets: [{
                    label: 'Registrations',
                    data: category.data,
                    backgroundColor: palette[0],
                    borderRadius: 8,
                    maxBarThickness: 56,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: gridColor } },
                },
            },
        });

        new Chart(document.getElementById('paymentChart'), {
            type: 'pie',
            data: {
                labels: payment.labels,
                datasets: [{
                    data: payment.data,
                    backgroundColor: palette,
                    borderColor: '#d9d9d9',
                    borderWidth: 3,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } },
            },
        });

        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: trend.labels,
                datasets: [{
                    label: 'New registrations',
                    data: trend.data,
                    borderColor: palette[0],
                    backgroundColor: 'rgba(0, 0, 0, 0.08)',
                    fill: true,
                    tension: 0.35,
                    pointRadius: 3,
                    pointBackgroundColor: palette[0],
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, grid: { color: gridColor } },
                },
            },
        });
    </script>
@endpush