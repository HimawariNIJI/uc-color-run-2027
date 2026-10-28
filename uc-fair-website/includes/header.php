<?php
if (!isset($currentPage)) { $currentPage = ''; }
if (!isset($pageTitle)) { $pageTitle = 'UC Fair'; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Informasi acara, timeline, dan pembaruan UC Fair.">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="assets/js/main.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten</a>
<header class="site-header">
    <div class="header-inner section-wrap">
        <a class="brand" href="index.php" aria-label="UC Fair, halaman utama"><span class="brand-mark">UC</span><span class="brand-name">FAIR<span>EVENT WEBSITE</span></span></a>
        <button class="menu-toggle" type="button" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="primary-navigation" data-menu-toggle><span></span><span></span><span></span></button>
        <nav class="primary-nav" id="primary-navigation" data-navigation aria-label="Navigasi utama">
            <a class="nav-link <?= $currentPage === 'home' ? 'is-active' : '' ?>" href="index.php">Home</a>
            <a class="nav-link <?= $currentPage === 'about' ? 'is-active' : '' ?>" href="about.php">About</a>
            <div class="nav-dropdown" data-dropdown>
                <button class="nav-link nav-dropdown-toggle" type="button" aria-expanded="false" aria-controls="race-info-menu" data-dropdown-toggle>Race Info <span class="chevron" aria-hidden="true">⌄</span></button>
                <div class="dropdown-panel" id="race-info-menu">
                    <a href="index.php#race-category">Race Category</a>
                    <a href="index.php#timeline">Race Schedule</a>
                    <a href="index.php#race-regulation">Race Regulation</a>
                </div>
            </div>
            <a class="nav-link" href="index.php#registration">Registration</a>
            <a class="nav-link" href="index.php#faq">FAQ</a>
            <a class="nav-link" href="index.php#contact">Contact</a>
            <button class="button button-dark nav-signin" type="button" data-open-login>Sign In <span aria-hidden="true">↗</span></button>
        </nav>
    </div>
</header>
<div class="modal-backdrop" data-login-modal hidden>
    <section class="login-modal" role="dialog" aria-modal="true" aria-labelledby="login-title">
        <button class="modal-close" type="button" aria-label="Tutup" data-close-login>×</button>
        <p class="eyebrow">WELCOME BACK</p><h2 id="login-title">Sign in</h2>
        <p class="muted-copy">Tampilan login demo untuk kebutuhan frontend.</p>
        <form data-login-form>
            <label for="login-email">Email</label><input type="email" id="login-email" name="email" placeholder="nama@email.com" autocomplete="email" required>
            <label for="login-password">Password</label><input type="password" id="login-password" name="password" placeholder="Masukkan password" autocomplete="current-password" required>
            <button class="button button-dark button-full" type="submit">Sign In <span aria-hidden="true">→</span></button>
            <p class="form-note" data-login-message aria-live="polite">Belum terhubung ke sistem akun atau database.</p>
        </form>
    </section>
</div>
