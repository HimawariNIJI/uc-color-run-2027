<?php
$currentPage = 'home';
$pageTitle = 'UC Fair — Home';
require __DIR__ . '/includes/header.php';
?>

<main id="main-content">
    <section class="hero section-wrap" aria-labelledby="hero-title">
        <div class="hero-art placeholder-art">
            <span class="placeholder-kicker">AREA BANNER / HERO IMAGE</span>
            <span class="hero-art-title">UC FAIR</span>
            <span class="placeholder-note">Ganti area ini dengan visual utama acara</span>
        </div>
        <div class="hero-copy">
            <p class="eyebrow">ACARA KAMPUS · INFORMASI RESMI</p>
            <h1 id="hero-title">Ready to make<br><span>some memories?</span></h1>
            <p class="lead">Satu acara, banyak cerita. Temukan informasi acara, rangkaian kegiatan, dan cara ikut berpartisipasi di UC Fair.</p>
            <div class="button-row">
                <a class="button button-dark" href="#registration">Daftar Sekarang <span aria-hidden="true">↗</span></a>
                <a class="button button-light" href="#timeline">Lihat Timeline</a>
            </div>
            <div class="hero-meta"><span class="meta-dot"></span> Tanggal dan detail acara segera diumumkan</div>
        </div>
    </section>

    <section class="countdown-section" aria-labelledby="countdown-title">
        <div class="section-wrap countdown-inner">
            <div class="section-heading section-heading-inline">
                <div>
                    <p class="eyebrow">SAVE THE DATE</p>
                    <h2 id="countdown-title">The countdown starts here.</h2>
                </div>
                <p class="muted-copy">Siapkan dirimu untuk rangkaian acara UC Fair.</p>
            </div>
            <div class="countdown" data-countdown>
                <div class="countdown-unit"><strong data-days>00</strong><span>Hari</span></div>
                <div class="countdown-unit"><strong data-hours>00</strong><span>Jam</span></div>
                <div class="countdown-unit"><strong data-minutes>00</strong><span>Menit</span></div>
                <div class="countdown-unit"><strong data-seconds>00</strong><span>Detik</span></div>
            </div>
            <p class="countdown-status" data-countdown-status>Tanggal acara segera diumumkan.</p>
            <a class="text-link" href="#timeline">Lihat jadwal acara <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <section class="section-wrap section-block category-section" id="race-category" aria-labelledby="category-title">
        <div class="section-heading section-heading-inline">
            <div><p class="eyebrow">CHOOSE YOUR EXPERIENCE</p><h2 id="category-title">Race category</h2></div>
            <p class="muted-copy">Kategori dan detail partisipasi akan disesuaikan dengan informasi resmi dari panitia.</p>
        </div>
        <div class="category-grid">
            <article class="category-card"><span class="category-number">CATEGORY / 01</span><h3>Kategori 01</h3><p>Deskripsi kategori akan ditambahkan setelah detail acara tersedia.</p><a class="text-link" href="#registration">Info pendaftaran <span aria-hidden="true">→</span></a></article>
            <article class="category-card"><span class="category-number">CATEGORY / 02</span><h3>Kategori 02</h3><p>Deskripsi kategori akan ditambahkan setelah detail acara tersedia.</p><a class="text-link" href="#registration">Info pendaftaran <span aria-hidden="true">→</span></a></article>
            <article class="category-card"><span class="category-number">CATEGORY / 03</span><h3>Kategori 03</h3><p>Deskripsi kategori akan ditambahkan setelah detail acara tersedia.</p><a class="text-link" href="#registration">Info pendaftaran <span aria-hidden="true">→</span></a></article>
        </div>
    </section>

    <section class="section-wrap section-block" id="timeline" aria-labelledby="timeline-title">
        <div class="section-heading section-heading-inline">
            <div><p class="eyebrow">MARK YOUR CALENDAR</p><h2 id="timeline-title">Our timeline</h2></div>
            <p class="muted-copy">Catat setiap tahap penting supaya kamu tidak ketinggalan.</p>
        </div>
        <div class="timeline-grid">
            <article class="timeline-card">
                <span class="timeline-number">01</span><span class="timeline-line"></span>
                <p class="eyebrow">COMING SOON</p><h3>Open Registration</h3><p>Informasi pembukaan pendaftaran akan diperbarui di sini.</p>
            </article>
            <article class="timeline-card">
                <span class="timeline-number">02</span><span class="timeline-line"></span>
                <p class="eyebrow">COMING SOON</p><h3>Event Preparation</h3><p>Pantau pengumuman dan persiapan sebelum hari acara.</p>
            </article>
            <article class="timeline-card">
                <span class="timeline-number">03</span><span class="timeline-line"></span>
                <p class="eyebrow">EVENT DAY</p><h3>UC Fair Day</h3><p>Waktu dan lokasi acara akan diumumkan panitia.</p>
            </article>
        </div>
    </section>

    <section class="video-section" aria-labelledby="video-title">
        <div class="section-wrap">
            <div class="section-heading section-heading-inline">
                <div><p class="eyebrow">GET A PREVIEW</p><h2 id="video-title">A little look at the event</h2></div>
                <p class="muted-copy">Area ini bisa dipakai untuk teaser, trailer, atau recap acara.</p>
            </div>
            <button class="video-placeholder" type="button" data-video-demo aria-label="Tampilkan keterangan placeholder video">
                <span class="play-button" aria-hidden="true">▶</span>
                <span class="video-label">VIDEO / EVENT TRAILER</span>
                <span class="video-caption">Klik untuk melihat catatan penggantian video</span>
            </button>
        </div>
    </section>

    <section class="section-wrap section-block sponsor-section" aria-labelledby="sponsor-title">
        <div class="section-heading section-heading-inline">
            <div><p class="eyebrow">MADE POSSIBLE TOGETHER</p><h2 id="sponsor-title">Sponsors & partners</h2></div>
            <div class="carousel-controls"><button class="icon-button" type="button" data-sponsor-prev aria-label="Sponsor sebelumnya">←</button><button class="icon-button" type="button" data-sponsor-next aria-label="Sponsor berikutnya">→</button></div>
        </div>
        <div class="sponsor-window"><div class="sponsor-track" data-sponsor-track>
            <div class="sponsor-card">LOGO PARTNER 01</div><div class="sponsor-card">LOGO PARTNER 02</div><div class="sponsor-card">LOGO PARTNER 03</div><div class="sponsor-card">LOGO PARTNER 04</div><div class="sponsor-card">LOGO PARTNER 05</div><div class="sponsor-card">LOGO PARTNER 06</div>
        </div></div>
        <p class="micro-copy">Logo sponsor di atas hanya placeholder. Ganti dengan aset resmi setelah tersedia.</p>
    </section>

    <section class="registration-band" id="registration" aria-labelledby="registration-title">
        <div class="section-wrap registration-inner">
            <div><p class="eyebrow">YOUR NEXT STORY STARTS HERE</p><h2 id="registration-title">Come and join us.</h2><p>Informasi pendaftaran resmi akan ditambahkan setelah diumumkan panitia.</p></div>
            <a class="button button-white" href="#faq">Lihat informasi <span aria-hidden="true">↗</span></a>
        </div>
    </section>

    <section class="rules-section" id="race-regulation" aria-labelledby="rules-title">
        <div class="section-wrap rules-inner">
            <div><p class="eyebrow">BEFORE YOU JOIN</p><h2 id="rules-title">Race regulation</h2></div>
            <div><p>Ketentuan peserta, panduan keselamatan, dan peraturan kegiatan akan dimuat di bagian ini setelah disetujui oleh panitia.</p><a class="text-link" href="#faq">Baca pertanyaan umum <span aria-hidden="true">→</span></a></div>
        </div>
    </section>

    <section class="section-wrap section-block faq-section" id="faq" aria-labelledby="faq-title">
        <div class="section-heading"><p class="eyebrow">NEED TO KNOW</p><h2 id="faq-title">Frequently asked questions</h2><p class="muted-copy">Jawaban berikut masih berupa template dan bisa disesuaikan dengan ketentuan acara.</p></div>
        <div class="faq-list">
            <details><summary>Kapan UC Fair akan dilaksanakan?</summary><p>Tanggal acara akan diumumkan melalui kanal resmi panitia.</p></details>
            <details><summary>Bagaimana cara mendaftar?</summary><p>Informasi pendaftaran dan tautan formulir akan ditambahkan setelah tersedia.</p></details>
            <details><summary>Di mana saya bisa melihat informasi terbaru?</summary><p>Pantau situs ini dan kanal komunikasi resmi UC Fair untuk pembaruan terbaru.</p></details>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
