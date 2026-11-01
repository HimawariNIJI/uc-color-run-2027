<?php

/*
|--------------------------------------------------------------------------
| UC Color Run 2027 — Event Content
|--------------------------------------------------------------------------
|
| Central place for every event fact shown on the public site: dates,
| pricing, timeline, contact channels, FAQ and regulations. Dates use the
| application timezone (Asia/Jakarta). Values marked "placeholder" must be
| confirmed by the organizing committee before launch.
|
*/

return [

    'name' => 'UC Color Run 2027',

    'organizer' => 'Universitas Ciputra',

    // Placeholder: confirm the official race day and start time.
    'race_day' => env('EVENT_RACE_DAY', '2027-03-21 05:30:00'),

    // Placeholder: confirm the official venue.
    'venue' => [
        'name' => 'Universitas Ciputra Surabaya',
        'address' => 'CitraLand CBD Boulevard, Made, Sambikerep, Surabaya, Jawa Timur 60219',
        'maps_url' => 'https://maps.google.com/?q=Universitas+Ciputra+Surabaya',
    ],

    // Placeholder number of Early Bird slots per race category. Paid and pending registrations
    // hold a slot; failed payments give it back. Once a category is full, only Normal Price is offered.
    'early_bird_slots' => [
        '3k' => 300,
        '5k' => 300,
        '10k' => 200,
    ],

    // How long a QRIS payment stays valid before it must be renewed.
    'payment_window_minutes' => 60,

    // Placeholder prices in Rupiah, per registration type and race category.
    'pricing' => [
        'internal' => [
            '3k' => ['early_bird' => 100000, 'regular' => 125000],
            '5k' => ['early_bird' => 125000, 'regular' => 150000],
            '10k' => ['early_bird' => 175000, 'regular' => 200000],
        ],
        'external' => [
            '3k' => ['early_bird' => 125000, 'regular' => 150000],
            '5k' => ['early_bird' => 150000, 'regular' => 175000],
            '10k' => ['early_bird' => 200000, 'regular' => 225000],
        ],
        'bundling' => [
            '3k' => ['early_bird' => 115000, 'regular' => 140000],
            '5k' => ['early_bird' => 140000, 'regular' => 165000],
            '10k' => ['early_bird' => 190000, 'regular' => 215000],
        ],
    ],

    // Race details per category. Route images live in public/ (null shows a placeholder).
    'categories' => [
        '3k' => [
            'start_time' => '06.15 WIB',
            'cut_off' => '45 menit',
            'tagline' => 'Fun run santai untuk semua usia, cocok untuk pelari pemula dan keluarga.',
            'route_image' => null,
        ],
        '5k' => [
            'start_time' => '06.00 WIB',
            'cut_off' => '60 menit',
            'tagline' => 'Jarak favorit untuk menikmati color zone sambil menjaga ritme lari.',
            'route_image' => null,
        ],
        '10k' => [
            'start_time' => '05.30 WIB',
            'cut_off' => '90 menit',
            'tagline' => 'Tantangan terjauh bagi pelari yang ingin menguji ketahanan dan kecepatan.',
            'route_image' => null,
        ],
    ],

    // Placeholder timeline. Dates are inclusive.
    'timeline' => [
        [
            'title' => 'Registration Period',
            'starts_at' => '2026-11-01',
            'ends_at' => '2027-03-07',
            'description' => 'Harga Early Bird berlaku selama slot masih tersedia. Setelah slot habis, berlaku Normal Price.',
        ],
        [
            'title' => 'Racepack Collection',
            'starts_at' => '2027-03-19',
            'ends_at' => '2027-03-20',
            'description' => 'Ambil race pack di kampus Universitas Ciputra dengan menunjukkan QR racepack.',
        ],
        [
            'title' => 'Race Day',
            'starts_at' => '2027-03-21',
            'ends_at' => '2027-03-21',
            'description' => 'Saatnya berlari, bermain warna, dan merayakan garis finish bersama.',
        ],
    ],

    // Placeholder contact channels.
    'contacts' => [
        'email' => 'uccolorrun@ciputra.ac.id',
        'whatsapp' => [
            'display' => '+62 812-3456-7890',
            'url' => 'https://wa.me/6281234567890',
        ],
        'instagram' => [
            'display' => '@uccolorrun',
            'url' => 'https://instagram.com/uccolorrun',
        ],
    ],

    // Optional media. Leave null to show the designed placeholders.
    'banner_image' => null,
    'recap_video_url' => null,

    // Placeholder sponsors. Set 'logo' to a public path such as 'images/sponsors/acme.png'.
    'sponsors' => [
        ['name' => 'Sponsor 1', 'logo' => null],
        ['name' => 'Sponsor 2', 'logo' => null],
        ['name' => 'Sponsor 3', 'logo' => null],
        ['name' => 'Sponsor 4', 'logo' => null],
        ['name' => 'Sponsor 5', 'logo' => null],
        ['name' => 'Sponsor 6', 'logo' => null],
    ],

    'values' => [
        [
            'title' => 'Sehat & Aktif',
            'icon' => 'activity',
            'description' => 'Mengajak civitas akademika dan masyarakat untuk hidup lebih sehat lewat olahraga yang menyenangkan.',
        ],
        [
            'title' => 'Inklusif',
            'icon' => 'users',
            'description' => 'Terbuka untuk semua usia dan kemampuan, dari pelari pemula hingga yang sudah berpengalaman.',
        ],
        [
            'title' => 'Kreatif',
            'icon' => 'palette',
            'description' => 'Merayakan semangat kreativitas dan entrepreneurship khas Universitas Ciputra melalui warna.',
        ],
        [
            'title' => 'Kebersamaan',
            'icon' => 'heart',
            'description' => 'Mempererat hubungan antara kampus, komunitas, dan masyarakat dalam satu perayaan.',
        ],
    ],

    'faqs' => [
        [
            'category' => 'Umum',
            'items' => [
                [
                    'question' => 'Apa itu UC Color Run 2027?',
                    'answer' => 'UC Color Run 2027 adalah acara lari penuh warna yang diselenggarakan oleh Universitas Ciputra. Peserta berlari melewati color zone dan merayakan garis finish bersama.',
                ],
                [
                    'question' => 'Kapan dan di mana acara diselenggarakan?',
                    'answer' => 'Acara diselenggarakan di Universitas Ciputra Surabaya. Tanggal dan jam start lengkap dapat dilihat di halaman Race Schedule dan Race Category.',
                ],
                [
                    'question' => 'Siapa saja yang boleh mengikuti acara ini?',
                    'answer' => 'Acara terbuka untuk civitas akademika Universitas Ciputra maupun masyarakat umum. Peserta di bawah 17 tahun wajib mendapat izin orang tua atau wali.',
                ],
            ],
        ],
        [
            'category' => 'Pendaftaran',
            'items' => [
                [
                    'question' => 'Bagaimana cara mendaftar?',
                    'answer' => 'Masuk dengan akun Google, pilih tipe registrasi dan kategori lomba, lengkapi formulir, lalu selesaikan pembayaran melalui QRIS.',
                ],
                [
                    'question' => 'Apa perbedaan registrasi Internal, External, dan Bundling?',
                    'answer' => 'Internal ditujukan untuk mahasiswa, dosen, staf, dan alumni Universitas Ciputra. External untuk masyarakat umum. Bundling adalah paket registrasi dengan harga khusus.',
                ],
                [
                    'question' => 'Apakah data registrasi bisa diubah?',
                    'answer' => 'Data registrasi dapat diubah melalui halaman My Registration selama pembayaran belum dilakukan. Setelah lunas, hubungi panitia untuk perubahan data.',
                ],
                [
                    'question' => 'Bisakah saya mendaftar lebih dari satu kategori?',
                    'answer' => 'Setiap akun hanya dapat memiliki satu registrasi. Gunakan akun Google yang berbeda untuk mendaftarkan peserta lain.',
                ],
            ],
        ],
        [
            'category' => 'Pembayaran',
            'items' => [
                [
                    'question' => 'Metode pembayaran apa yang tersedia?',
                    'answer' => 'Pembayaran dilakukan melalui QRIS, sehingga dapat dibayar dengan aplikasi mobile banking atau e-wallet apa pun yang mendukung QRIS.',
                ],
                [
                    'question' => 'Berapa lama batas waktu pembayaran?',
                    'answer' => 'Kode QRIS berlaku selama 60 menit. Jika kedaluwarsa, kamu dapat membuat kode pembayaran baru dari halaman My Registration.',
                ],
                [
                    'question' => 'Apakah biaya registrasi dapat dikembalikan?',
                    'answer' => 'Biaya registrasi yang sudah dibayarkan tidak dapat dikembalikan, kecuali acara dibatalkan oleh panitia.',
                ],
            ],
        ],
        [
            'category' => 'Race Pack & BIB',
            'items' => [
                [
                    'question' => 'Kapan nomor BIB saya tersedia?',
                    'answer' => 'Nomor BIB dan QR racepack langsung tersedia di halaman My BIB setelah pembayaran terkonfirmasi.',
                ],
                [
                    'question' => 'Kapan dan di mana pengambilan race pack?',
                    'answer' => 'Pengambilan race pack dilakukan di kampus Universitas Ciputra sesuai jadwal pada timeline. Tunjukkan QR racepack dari halaman My BIB.',
                ],
                [
                    'question' => 'Apa saja isi race pack?',
                    'answer' => 'Race pack berisi nomor BIB, kaos peserta sesuai ukuran yang dipilih, dan perlengkapan color run lainnya.',
                ],
            ],
        ],
        [
            'category' => 'Hari Lomba',
            'items' => [
                [
                    'question' => 'Apakah bubuk warna aman digunakan?',
                    'answer' => 'Bubuk warna yang digunakan berbahan dasar tepung jagung yang aman, tidak beracun, dan mudah dibersihkan.',
                ],
                [
                    'question' => 'Apa yang sebaiknya saya kenakan?',
                    'answer' => 'Kenakan kaos peserta, sepatu lari yang nyaman, dan kacamata bila perlu. Hindari membawa barang elektronik yang tidak tahan debu.',
                ],
                [
                    'question' => 'Apakah tersedia titik hidrasi dan tim medis?',
                    'answer' => 'Ya. Panitia menyediakan water station di sepanjang rute serta tim medis yang siaga di area start, rute, dan finish.',
                ],
            ],
        ],
    ],

    'regulations' => [
        'Peserta wajib melakukan registrasi dan menyelesaikan pembayaran melalui website resmi UC Color Run 2027.',
        'Setiap peserta wajib mengenakan nomor BIB di bagian depan badan dan terlihat jelas selama lomba berlangsung.',
        'Nomor BIB dan race pack tidak dapat dipindahtangankan kepada orang lain dalam kondisi apa pun.',
        'Peserta wajib berada di area start paling lambat 30 menit sebelum waktu start kategori masing-masing.',
        'Peserta wajib mengikuti rute resmi yang telah ditentukan dan mematuhi arahan marshal di sepanjang rute.',
        'Peserta yang belum mencapai garis finish saat cut off time berakhir wajib menaiki kendaraan sweeper yang disediakan panitia.',
        'Peserta wajib dalam kondisi sehat dan bertanggung jawab penuh atas kondisi kesehatannya masing-masing.',
        'Peserta di bawah 17 tahun wajib didampingi atau mendapatkan izin tertulis dari orang tua atau wali.',
        'Dilarang menggunakan kendaraan, sepatu roda, atau alat bantu lain selama lomba, kecuali kursi roda yang telah terdaftar.',
        'Panitia berhak mendiskualifikasi peserta yang melanggar peraturan atau membahayakan peserta lain.',
        'Panitia berhak mengubah jadwal atau rute lomba demi keamanan peserta, dan setiap perubahan akan diumumkan melalui kanal resmi.',
    ],

    'privacy' => [
        'Kami mengumpulkan data yang kamu berikan saat registrasi, seperti nama, email, nomor telepon, tanggal lahir, nomor identitas, dan alamat.',
        'Data digunakan untuk keperluan registrasi, verifikasi peserta, pengiriman informasi acara, dan penanganan keadaan darurat saat lomba.',
        'Nomor identitas (KTP/Paspor) disimpan secara terenkripsi dan hanya dapat diakses oleh panitia yang berwenang.',
        'Kami tidak menjual atau membagikan data pribadi kepada pihak ketiga, kecuali kepada mitra pembayaran dan pihak medis bila diperlukan.',
        'Dokumentasi foto dan video selama acara dapat digunakan oleh Universitas Ciputra untuk keperluan publikasi.',
        'Kamu dapat meminta perubahan atau penghapusan data pribadi dengan menghubungi panitia melalui halaman Contact.',
        'Dengan melakukan registrasi, kamu menyetujui pengumpulan dan penggunaan data sesuai kebijakan ini.',
    ],

];
