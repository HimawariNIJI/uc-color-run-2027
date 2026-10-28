<?php
$currentPage = 'about';
$pageTitle = 'UC Fair — About';
require __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="about-page">
    <section class="about-hero section-wrap" aria-labelledby="about-title">
        <div class="about-hero-copy">
            <p class="eyebrow">GET TO KNOW US</p>
            <h1 id="about-title">More than an event.<br><span>A shared experience.</span></h1>
            <p class="lead">UC Fair menjadi ruang untuk berkumpul, mengenal hal baru, dan menciptakan pengalaman bersama komunitas kampus.</p>
            <a class="button button-dark" href="#vision-mission">Kenali kami <span aria-hidden="true">↓</span></a>
        </div>
        <div class="about-hero-art placeholder-art"><span class="placeholder-kicker">MAIN VISUAL</span><span class="hero-art-title">UC FAIR</span><span class="placeholder-note">Area foto / ilustrasi acara</span></div>
    </section>

    <section class="about-story section-wrap section-block" aria-labelledby="what-title">
        <div class="story-art placeholder-art"><span class="placeholder-kicker">PHOTO / ILLUSTRATION</span><span class="story-art-title">GAMBAR<br>ACARA</span></div>
        <div class="story-copy"><p class="eyebrow">ABOUT UC FAIR</p><h2 id="what-title">Apa itu UC Fair?</h2><p>UC Fair adalah acara yang dirancang untuk mempertemukan mahasiswa dan komunitas melalui pengalaman, aktivitas, serta kolaborasi. Bagian ini bisa kamu isi dengan penjelasan resmi tentang konsep acara, penyelenggara, dan target peserta.</p><p>Gunakan bagian ini untuk menjawab pertanyaan utama pengunjung: acara ini tentang apa, siapa yang bisa ikut, dan pengalaman apa yang bisa mereka dapatkan.</p><a class="text-link" href="index.php#timeline">Lihat rangkaian acara <span aria-hidden="true">→</span></a></div>
    </section>

    <section class="vision-section" id="vision-mission" aria-labelledby="vision-title">
        <div class="section-wrap section-block">
            <div class="section-heading centered-heading"><p class="eyebrow">OUR PURPOSE</p><h2 id="vision-title">Visi misi kami</h2><p class="muted-copy">Sesuaikan teks di bawah dengan visi dan misi resmi acara.</p></div>
            <div class="vision-grid">
                <article class="vision-card"><span class="card-index">01 / VISI</span><h3>Visi</h3><p>Menciptakan acara yang inklusif, inspiratif, dan berkesan bagi seluruh peserta serta komunitas yang terlibat.</p></article>
                <article class="vision-card"><span class="card-index">02 / MISI</span><h3>Misi</h3><p>Menghadirkan kegiatan yang menarik, mendorong kolaborasi, dan membangun rasa kebersamaan melalui pengalaman positif.</p></article>
            </div>
        </div>
    </section>

    <section class="section-wrap section-block values-section" aria-labelledby="values-title">
        <div class="section-heading centered-heading"><p class="eyebrow">WHAT MATTERS TO US</p><h2 id="values-title">Our values</h2><p class="muted-copy">Nilai yang menjadi dasar pengalaman UC Fair.</p></div>
        <div class="values-grid">
            <article class="value-card"><div class="value-icon">01</div><h3>Kebersamaan</h3><p>Membangun pengalaman yang terasa lebih bermakna saat dilakukan bersama.</p></article>
            <article class="value-card"><div class="value-icon">02</div><h3>Kolaborasi</h3><p>Membuka ruang bagi ide, partisipasi, dan kerja sama antar komunitas.</p></article>
            <article class="value-card"><div class="value-icon">03</div><h3>Kreativitas</h3><p>Mendorong cara baru untuk berkarya, beraktivitas, dan saling menginspirasi.</p></article>
            <article class="value-card"><div class="value-icon">04</div><h3>Inklusivitas</h3><p>Menghargai perbedaan dan membuat lebih banyak orang merasa dilibatkan.</p></article>
        </div>
    </section>

    <section class="about-cta section-wrap" aria-labelledby="about-cta-title">
        <div class="cta-art placeholder-art"><span class="placeholder-kicker">OPTIONAL EVENT IMAGE</span><span class="story-art-title">GAMBAR<br>ACARA</span></div>
        <div class="cta-copy"><p class="eyebrow">BE PART OF IT</p><h2 id="about-cta-title">Come and join us.</h2><p>Ikuti pembaruan UC Fair dan bersiap untuk menjadi bagian dari acaranya.</p><a class="button button-dark" href="index.php#registration">Lihat pendaftaran <span aria-hidden="true">↗</span></a></div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
