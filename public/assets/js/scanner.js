'use strict';

/*
 * Placeholder scanner; html5-qrcode belum dimuat dan kamera tidak diminta.
 * Tahap berikutnya: mulai kamera hanya setelah tindakan guru, kirim token QR
 * lewat POST ber-CSRF ke server, lalu hentikan kamera ketika halaman ditutup.
 * Server memeriksa penugasan guru, token aktif, kelas, sesi aktif, serta duplikasi.
 * Jangan menyimpan token mentah/data siswa di localStorage, URL, atau console log.
 */
