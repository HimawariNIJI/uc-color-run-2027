# UC Fair Website — Starter Frontend

Template website **PHP + HTML + CSS + JavaScript** berdasarkan wireframe Figma yang dikirim. Visual dibuat grayscale terlebih dahulu supaya warna, gambar, logo, dan tipografi brand resmi bisa dimasukkan belakangan.

## Struktur folder

```text
uc-fair-website/
├── index.php                 # Landing page
├── about.php                 # About page
├── includes/
│   ├── header.php            # Header, navigasi, modal Sign In
│   └── footer.php            # Footer reusable
└── assets/
    ├── css/style.css         # Styling dan responsive layout
    └── js/main.js            # Menu, dropdown, modal, countdown, sponsor carousel
```

## Cara menjalankan

**Pilihan A — PHP built-in server**
1. Pastikan PHP sudah terpasang.
2. Buka terminal/VS Code, masuk ke folder `uc-fair-website`.
3. Jalankan `php -S localhost:8000`.
4. Buka `http://localhost:8000`.

**Pilihan B — XAMPP**
1. Salin folder `uc-fair-website` ke folder `htdocs`.
2. Jalankan Apache dari XAMPP.
3. Buka `http://localhost/uc-fair-website/`.

> Jangan membuka `index.php` dengan double-click / `file://`; file PHP perlu dijalankan lewat server PHP.

## Yang sudah tersedia

- Header reusable melalui `includes/header.php`.
- Navigasi desktop dan hamburger menu untuk mobile.
- Dropdown Race Info terbuka lewat klik (bukan hover), dan bisa ditutup dengan klik di luar atau tombol Escape. Panel hamburger berada di bawah header, dapat di-scroll, dan state-nya direset saat ukuran layar melewati breakpoint.
- Modal Sign In untuk preview tampilan.
- Landing page: hero/banner, countdown, timeline, video placeholder, sponsor carousel, CTA pendaftaran, FAQ.
- About page: deskripsi acara, visi-misi, values, dan CTA.
- Footer reusable melalui `includes/footer.php`.
- Layout responsive untuk desktop, tablet, dan mobile.

## Yang perlu kamu ubah nanti

- **Logo dan identitas:** edit elemen `.brand` di `includes/header.php` dan `includes/footer.php`.
- **Warna & font:** edit variable CSS di bagian atas `assets/css/style.css` (`:root`).
- **Tanggal countdown:** buka `assets/js/main.js`, cari `const EVENT_DATE = '';`, lalu isi tanggal resmi dalam format ISO, misalnya `2027-01-01T08:00:00+07:00`. Contoh tanggal tersebut hanya format, bukan tanggal resmi UC Fair.
- **Gambar:** blok abu-abu bertuliskan `GAMBAR`, `BANNER`, atau `MAIN VISUAL` adalah placeholder yang bisa diganti dengan tag `<img>`.
- **Video:** tombol video saat ini memunculkan catatan demo. Ganti `.video-placeholder` di `index.php` dengan embed video atau tag `<video>` jika asetnya sudah ada.
- **Isi resmi:** perbarui tanggal, timeline, visi-misi, nilai acara, FAQ, sponsor, dan informasi kontak sesuai data panitia.

## Catatan backend

Proyek ini fokus pada **frontend**. Form Sign In hanya demo dan belum melakukan autentikasi, menyimpan data, atau terhubung ke database. Pendaftaran dan akun perlu disambungkan ke backend PHP/database setelah alur dan kebutuhan datanya disepakati.
