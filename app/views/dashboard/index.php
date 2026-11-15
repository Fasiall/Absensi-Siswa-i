<?php

declare(strict_types=1);

view('layouts/header', ['title' => $title ?? 'Pondasi Proyek']);
?>
<main id="main-content" class="container app-container py-5" tabindex="-1">
    <p class="text-secondary mb-2">Status pengembangan</p>
    <h1 class="h2 mb-3">Pondasi proyek absensi siswa</h1>
    <p>Struktur aplikasi sudah tersedia. Halaman ini belum menampilkan data sekolah dan tidak memerlukan koneksi database.</p>
    <section class="border rounded p-4 bg-white my-4" aria-labelledby="foundation-heading">
        <h2 id="foundation-heading" class="h5">Yang sudah disiapkan</h2>
        <ul class="mb-0">
            <li>Konfigurasi aplikasi dan koneksi PDO.</li>
            <li>Kerangka autentikasi, pembatasan role, dan proteksi CSRF.</li>
            <li>Rancangan database, data fiktif, dan dokumentasi instalasi.</li>
        </ul>
    </section>
    <h2 class="h5">Tahap berikutnya</h2>
    <p>Login, pengelolaan data, sesi absensi, pemindaian QR, dan laporan akan dikembangkan bertahap. Kamera belum diaktifkan.</p>
    <p class="text-secondary">Petunjuk konfigurasi dan impor database tersedia di README proyek.</p>
</main>
<?php view('layouts/footer'); ?>
