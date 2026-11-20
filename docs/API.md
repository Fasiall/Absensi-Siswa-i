# API backend tahap awal

Base path: `/api/v1`. API menggunakan session cookie PHP (same-origin) dan JSON. Kirim `Content-Type: application/json` untuk setiap `POST`. Token CSRF diambil dari `GET /api/v1/auth/csrf`, lalu dikirim pada header `X-CSRF-Token` untuk seluruh `POST`, termasuk login. Jangan letakkan token QR di URL atau log.

Contoh flow browser:

```js
const csrfResponse = await fetch('/Absensi-Siswa-i/public/api/v1/auth/csrf', {
  credentials: 'same-origin'
});
const { data: { csrf_token: csrfToken } } = await csrfResponse.json();

const loginResponse = await fetch('/Absensi-Siswa-i/public/api/v1/auth/login', {
  method: 'POST',
  credentials: 'same-origin',
  headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
  body: JSON.stringify({ username: 'guru.demo', password: 'password-lokal-yang-dibuat-sendiri' })
});
```

Jangan menaruh password contoh di kode produksi. Akun seed nonaktif dan harus diaktifkan lebih dulu melalui `bin/create-user.php`.

## Route

| Method | Route | Otorisasi | Body |
| --- | --- | --- | --- |
| GET | `/auth/csrf` | Publik | — |
| POST | `/auth/login` | Publik + CSRF | `username`, `password` |
| GET | `/auth/me` | Admin/guru | — |
| POST | `/auth/logout` | Admin/guru + CSRF | — |
| GET | `/classes` | Admin/guru | — |
| GET | `/classes/{id}/students?page=1&per_page=50` | Admin/guru; guru perlu penugasan aktif | — |
| POST | `/classes/{id}/students` | Admin + CSRF | `student_number`, `name` |
| GET | `/sessions` | Admin/guru; daftar guru dibatasi penugasannya | — |
| POST | `/sessions` | Admin/guru + CSRF | `class_id`; sesi hanya untuk tanggal hari ini |
| POST | `/sessions/{id}/close` | Admin/guru yang berwenang + CSRF | — |
| GET | `/sessions/{id}/attendances` | Admin/guru yang berwenang | — |
| POST | `/sessions/{id}/attendances` | Admin/guru yang berwenang + CSRF | `student_id`, `status`, `notes?` |
| POST | `/sessions/{id}/scan` | Admin/guru yang berwenang + CSRF | `token` |
| POST | `/students/{id}/qr-token` | Admin + CSRF | — |

Role admin dapat mengelola siswa pada kelas yang ada; role guru hanya dapat melihat kelas yang ditugaskan. Operasi sesi dan absensi guru memeriksa penugasan ke kelas terkait. Status absensi yang diterima: `hadir`, `sakit`, `izin`, `alpa`. Pemindaian QR hanya membuat status `hadir`.

## Contoh respons

Sukses:

```json
{"data":{"id":12,"status":"open"}}
```

Error (contoh token CSRF salah):

```json
{"error":{"code":"csrf_invalid","message":"Token CSRF tidak valid."}}
```

Duplikasi sesi/absensi menghasilkan HTTP `409 Conflict`; input tidak valid `422`; tidak login `401`; role atau kelas tidak berhak `403`; percobaan login berlebih `429`.

## Alur QR

1. Admin memanggil `POST /students/{id}/qr-token`.
2. Backend menghasilkan 32 byte acak, menyimpan SHA-256 saja, mencabut token aktif lama, dan mengembalikan token mentah satu kali untuk dicetak sebagai QR.
3. Guru membuka sesi kelas yang ditugaskan.
4. Browser scanner (belum dibuat) mengirim payload token melalui `POST /sessions/{id}/scan` dengan CSRF.
5. Backend memeriksa hash, masa berlaku/pencabutan, keanggotaan siswa pada tanggal sesi, sesi aktif, otorisasi guru, dan unique constraint absensi.
6. Jika berhasil, respons hanya memuat ID catatan, ID siswa, dan status—token mentah tidak dikembalikan.

Token mentah hanya boleh dipakai untuk membuat gambar QR pada penerbitan. Jangan log payload scan, masukkan ke query URL, atau simpan di `localStorage`.

## Rate limit dan setup

Login dibatasi maksimal 5 gagal per kombinasi username/IP dan 30 gagal per IP dalam 15 menit. Identitas username/IP pada `auth_login_attempts` disimpan sebagai HMAC-SHA256 menggunakan `APP_KEY`; bersihkan rekaman lama secara otomatis saat login gagal. Buat `APP_KEY` privat dengan `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` dan set hanya di `.env` lokal/server. Jangan ganti key ketika percobaan lama masih perlu dicocokkan.

Jalankan migrasi `001_initial_schema.sql`, `002_auth_login_attempts.sql`, lalu seed bila diperlukan. Endpoint yang memakai database baru dapat diakses setelah `.env`, MySQL, dan migrasi selesai disiapkan.

## Belum ada

API ini belum menyediakan edit/hapus siswa, pengelolaan tahun ajaran/kelas/guru, koreksi absensi dan ekspor laporan, serta integrasi browser dengan kamera. Semua endpoint mutasi memakai POST dan CSRF session token. Untuk akses lintas origin dibutuhkan desain CORS dan cookie secara khusus; API saat ini ditujukan untuk antarmuka same-origin.
