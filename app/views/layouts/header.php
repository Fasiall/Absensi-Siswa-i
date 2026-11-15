<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Absensi Siswa') ?> — <?= e((string) config('name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= e(url('assets/css/app.css')) ?>" rel="stylesheet">
    <script src="<?= e(url('assets/js/app.js')) ?>" defer></script>
</head>
<body>
    <a class="skip-link" href="#main-content">Lewati ke konten utama</a>
    <header class="border-bottom bg-white py-3">
        <div class="container app-container">
            <span class="fw-semibold">SD Negeri Purwodadi Simpang</span>
            <p class="text-secondary mb-0">Sistem Absensi Siswa Berbasis QR Code</p>
        </div>
    </header>
