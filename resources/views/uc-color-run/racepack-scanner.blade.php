@extends('uc-color-run.layout', ['pageTitle' => 'QR Scanner', 'bodyClass' => 'page-scanner'])

@section('content')
<section class="scanner"
    id="scanner"
    data-scan-url="{{ route('racepack.scan') }}"
    data-recent-url="{{ route('racepack.recent') }}"
    data-csrf="{{ csrf_token() }}"
    data-recent='@json($recentScans)'>

    <h1 class="scanner-title">QR Scanner</h1>
    <p class="scanner-sub">Scan the QR Code to collect your BIB</p>
    <hr class="scanner-line">

    <div class="scanner-box" id="scanner-box">
        <div id="reader" class="scanner-reader"></div>
        <p class="scanner-state" id="scanner-state">QR Scanner<br>OFF</p>
    </div>

    <button type="button" class="scanner-btn" id="scanner-toggle">Start Camera</button>

    <p class="scanner-msg" id="scanner-msg" role="status" aria-live="polite" hidden></p>

    <h2 class="scanner-recent-title">Recent QR Scanned</h2>
    <div class="scanner-table-wrap">
        <table class="scanner-table">
            <thead>
                <tr>
                    <th>Registration ID</th>
                    <th>Participant</th>
                    <th>Category</th>
                    <th>BIB Number</th>
                </tr>
            </thead>
            <tbody id="scanner-rows"></tbody>
        </table>
        <p class="scanner-empty" id="scanner-empty" hidden>No racepack collected yet.</p>
    </div>

    @if ($testBib)
        {{-- Hanya tampil di APP_ENV=local --}}
        <details class="scanner-dev">
            <summary>Dev: QR contoh untuk tes</summary>
            <canvas id="qr-preview" data-bib="{{ $testBib }}"></canvas>
            <p>BIB: {{ $testBib }}</p>
        </details>
    @endif
</section>
@endsection
