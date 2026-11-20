<div align="center">
  <img src="https://capsule-render.vercel.app/api?type=waving&color=0:0D47A1,100:81D4FA&height=200&section=header&text=Absensi%20Siswa%20&%20Siswi&fontSize=34&fontColor=ffffff&fontAlignY=38&desc=KAPITA%20SELEKTA%20%7C%20Institut%20Teknologi%20Sumatera&descAlignY=55&descAlign=50&animation=twinkling" width="100%" />

  <br/>

  <b>Selamat datang di repositori Kelompok 7 - KAPITA SELEKTA! 🚀</b>
  <p>Repositori ini didedikasikan untuk kapita selekta pada tugas besar.</p>

  <p align="center">
    <img src="https://img.shields.io/badge/Kotlin-0095D5?style=for-the-badge&logo=kotlin&logoColor=white" />
    <img src="https://img.shields.io/badge/Compose_Multiplatform-4285F4?style=for-the-badge&logo=android&logoColor=white" />
    <img src="https://img.shields.io/badge/Gradle-02303A?style=for-the-badge&logo=gradle&logoColor=white" />
    <img src="https://img.shields.io/badge/Material_Design_3-3DDC84?style=for-the-badge&logo=materialdesign&logoColor=white" />
  </p>
</div>

---

## Anggota Kelompok : 7

| No | Nama | NIM | Peran |
|:--:|:-----|:---:|:-----:|
| 1 | **Faisal H. Sinambela** | 124140040 | Database |
| 2 | **Gabriel Sitorus** | 124140188 | Front End |
| 3 | **Alfino Putra R.** | 124140127 | Back End |
| 4 | **Yayan Sofian** | 124140130 | Dokumentasi / Testing |
| 5 | **Muhammad Zaki Ibnu Azizi** | 124140089 | Project Manager |

## Deskripsi Proyek


## Tahapan Pengerjaan TugasProyek
> Duplikat Folder Tugas Terakhir (Rename Jadi Tugas Saat Ini)
> -> Sync Gradle
> -> Update Code Sesuai Tugas Yang Akan Dikerjakan

> **Catatan stack:** badge Kotlin/Compose/Gradle dan tahapan “Sync Gradle” pada blok kelompok di atas dipertahankan sesuai materi yang diberikan. Aplikasi web dalam proyek ini menggunakan PHP Native, MySQL, Bootstrap 5, dan JavaScript; Gradle tidak diperlukan untuk menjalankannya.

---

# Sistem Absensi Siswa Berbasis QR Code

**SD Negeri Purwodadi Simpang**  
Proyek pengembangan aplikasi web untuk pembelajaran dan pengerjaan tugas kuliah semester 5.

> **Status: tahap fondasi, belum menjadi aplikasi absensi siap pakai.** Pada awal pengerjaan, root proyek masih kosong. Tidak ada prototype maupun dokumen `docs/SRS_Tahap_1_Analisis_Kebutuhan.md` yang dapat dijadikan acuan. README ini menjelaskan arah pengembangan dan kondisi fondasi, bukan mengarang isi SRS atau mengklaim fitur sudah selesai.

## 1. Deskripsi Proyek

Aplikasi ini direncanakan untuk membantu guru SD Negeri Purwodadi Simpang mencatat kehadiran siswa menggunakan QR Code. Guru membuka sesi absensi untuk kelas yang menjadi penugasannya, lalu memindai QR siswa melalui kamera browser smartphone. Siswa tidak perlu memiliki akun atau smartphone sendiri; pemindaian dilakukan oleh guru.

Alur yang dituju:

1. Guru login dan memilih kelas sesuai penugasannya.
2. Guru membuka sesi absensi kelas untuk tanggal sekolah yang sesuai.
3. Browser meminta izin kamera, kemudian guru memindai QR siswa.
4. Server memvalidasi token, pemilik token, keanggotaan siswa di kelas, sesi yang masih terbuka, serta kewenangan guru.
5. Server memeriksa duplikasi agar satu siswa tidak tercatat dua kali pada sesi yang sama.
6. Kehadiran disimpan sebagai `hadir`. Status `sakit`, `izin`, dan `alpa` dicatat melalui alur manual yang berizin.
7. Guru menutup sesi dan melihat rekap; koreksi harus memiliki jejak perubahan.

QR tidak berisi nama, nomor siswa, atau data pribadi lainnya. Payload berupa token acak, sedangkan database hanya menyimpan hash SHA-256 token tersebut. QR bukan pengganti login dan tidak boleh menjadi satu-satunya dasar otorisasi.

**Alur di atas masih merupakan target pengembangan. Scanner dan proses absensi penuh belum tersedia.**

## 2. Tujuan

- Mempercepat pencatatan kehadiran siswa dan mengurangi pencatatan manual.
- Menyimpan riwayat absensi lebih rapi dan membantu admin membuat rekap kehadiran.
- Mengurangi pencatatan ganda pada kelas dan tanggal yang sama.
- Memisahkan hak akses admin dan guru sesuai tanggung jawabnya.
- Menjaga riwayat kelas, penugasan guru, dan kehadiran lintas tahun ajaran.
- Menyediakan dasar rekap kehadiran serta penelusuran koreksi data.
- Menerapkan materi PHP Native, database relasional, validasi, dan keamanan web dalam proyek kuliah.

## 3. Fitur yang direncanakan

Daftar ini merupakan rencana kerja, **bukan fitur yang semuanya sudah berjalan atau SRS yang telah disahkan**.

- **Autentikasi:** halaman login/logout, akun aktif/nonaktif, pengamanan sesi, dan pembatasan akses berdasarkan role.
- **Dashboard:** ringkasan siswa, kelas, sesi, dan kehadiran sesuai hak akses.
- **Manajemen pengguna dan guru:** pengelolaan akun admin/guru, profil guru, dan status akun.
- **Tahun ajaran dan kelas:** pengelolaan tahun ajaran aktif, tingkat SD, serta kelas per tahun ajaran.
- **Penugasan guru:** hubungan guru dengan kelas beserta masa penugasannya.
- **Data siswa:** pengelolaan identitas internal siswa dan status aktif.
- **Riwayat keanggotaan kelas:** penempatan siswa, mutasi, dan kenaikan kelas tanpa menimpa riwayat lama.
- **QR siswa:** penerbitan, penampilan untuk pencetakan, pencabutan, kedaluwarsa, dan penggantian token acak.
- **Sesi absensi:** membuka, menutup, dan membatalkan sesi harian per kelas dengan penanggung jawab yang valid.
- **Pemindaian melalui smartphone:** integrasi kamera browser menggunakan `html5-qrcode`, termasuk penanganan izin kamera dan kegagalan scan.
- **Validasi absensi:** token valid, siswa sesuai kelas, sesi terbuka, guru berwenang, dan penolakan duplikasi.
- **Absensi manual:** pencatatan `hadir`, `sakit`, `izin`, dan `alpa`; belum dicatat tidak otomatis dianggap alpa.
- **Koreksi dan riwayat:** perubahan status/catatan dengan alasan dan pencatatan pada `attendance_logs`.
- **Rekap dan laporan:** filter tanggal, kelas, siswa, dan tahun ajaran; rekap harian/bulanan, tampilan cetak, serta ekspor ke Excel atau PDF. Library ekspor dipilih pada tahap implementasi.
- **Audit dan keamanan:** jejak tindakan administratif, validasi input, CSRF, dan pembatasan percobaan login saat endpoint login dibuat.
- **Antarmuka responsif:** dapat dipakai dari laptop maupun smartphone dengan informasi hasil scan yang jelas.

## 4. Tech stack

| Bagian | Teknologi / peran |
| --- | --- |
| Backend | PHP Native 8.1+, tanpa framework |
| Database | MySQL 5.7+ atau MariaDB 10.2+, InnoDB, `utf8mb4_unicode_ci` |
| Akses database | PDO dengan driver `pdo_mysql` dan prepared statement |
| Web server lokal | Apache melalui XAMPP; aktifkan `mod_rewrite` dan `AllowOverride All` |
| Antarmuka | HTML, CSS, dan JavaScript |
| CSS framework | Bootstrap 5.3.3 melalui CDN jsDelivr dengan SRI; CSS lokal sebagai fallback dasar |
| QR scanner | `html5-qrcode`, direncanakan dan **belum dimuat** |
| Dependensi PHP | Composer opsional jika nanti diperlukan; bukan prasyarat fondasi saat ini |
| Pengujian | Skrip PHP untuk lint dan smoke test, tanpa keharusan server MySQL untuk smoke test |

Internet hanya dibutuhkan untuk mengambil **Bootstrap dari CDN** pada rancangan halaman awal. CSS fallback lokal ditujukan agar kerangka dasar tetap terbaca ketika CDN tidak dapat diakses; tampilannya tidak harus identik dengan Bootstrap. Tidak ada klaim scanner sudah dapat digunakan secara offline atau online.

## 5. Struktur direktori dan fungsinya

Struktur dibuat langsung di root proyek yang terbuka, tanpa menambah folder pembungkus `Pre Build Absensi/`.

```text
Absensi-Siswa-i/
├── app/
│   ├── bootstrap.php          # Memuat konfigurasi, helper, autoload, dan sesi web
│   ├── config/
│   │   ├── .env.example       # Template; salin ke .env pada ROOT proyek
│   │   ├── app.php            # Konfigurasi aplikasi dan sesi
│   │   └── database.php       # Koneksi PDO lazy
│   ├── core/                 # Auth, Middleware, Csrf, Api, Validator, dan error API
│   ├── helpers/              # Helper umum, escaping, dan keamanan
│   ├── controllers/          # ApiController untuk endpoint JSON
│   ├── models/               # ClassModel dan StudentModel
│   ├── services/             # AttendanceService, QR token, dan throttle login
│   └── views/                # Layout, halaman status, serta folder view fitur
├── bin/
│   └── create-user.php        # Pembuatan/aktivasi akun lokal melalui CLI
├── database/
│   ├── migrations/001_initial_schema.sql
│   ├── migrations/002_auth_login_attempts.sql
│   ├── seeds/development_seed.sql
│   └── ERD.md                 # Relasi tabel dan batas validasi backend
├── docs/                     # Placeholder dokumentasi, belum ada SRS
├── prototype/                # Placeholder, belum ada prototype
├── public/
│   ├── index.php             # Entry point web
│   ├── api.php               # Entry point API JSON
│   ├── .htaccess             # Rewrite web dan API
│   └── assets/
│       ├── css/app.css       # CSS lokal/fallback
│       ├── js/
│       │   ├── app.js        # Tempat interaksi umum
│       │   └── scanner.js    # Placeholder, bukan scanner penuh
│       └── img/              # Tempat gambar aplikasi
├── tests/
│   ├── lint.php              # Pemeriksaan sintaks PHP aplikasi dan test
│   ├── run.php               # Smoke test API input, Auth, CSRF, PDO mock
├── .env                      # Dibuat sendiri dari template; tidak disertakan
├── .gitignore
├── .htaccess                 # Proteksi tambahan jika root berada di htdocs
└── README.md
```

`docs/README.md` mencatat dokumen yang perlu ditambahkan. `prototype/` hanya berisi penanda folder karena desain asli belum ditemukan di workspace. **`docs/SRS_Tahap_1_Analisis_Kebutuhan.md` juga belum tersedia.** Tidak ada isi SRS atau desain prototype yang diasumsikan maupun ditimpa. Beberapa folder fitur masih disiapkan untuk pengembangan berikutnya.

Database berisi 11 tabel wajib, tabel riwayat `student_classes`, dan tabel throttle `auth_login_attempts` (total **13 tabel** setelah kedua migrasi). Detail relasi ada di [database/ERD.md](database/ERD.md).

## 6. Menjalankan secara lokal

### A. Siapkan PHP dan Apache

1. Pasang XAMPP yang menyediakan PHP 8.1 atau versi kompatibel, Apache, dan MySQL/MariaDB.
2. Letakkan proyek di folder `htdocs/Absensi-Siswa-i`, atau gunakan VirtualHost dengan `DocumentRoot` langsung ke `public/`.
3. Aktifkan Apache `mod_rewrite` dan izinkan `AllowOverride All` pada direktori proyek/public agar aturan `.htaccess` bekerja. Restart Apache setelah mengubah konfigurasi.
4. Pastikan PHP CLI tersedia di PATH dan `pdo_mysql` aktif. Gunakan `php -v` untuk melihat versi PHP CLI; pastikan versinya sesuai dengan PHP yang dipakai Apache.
5. Nyalakan Apache. MySQL/MariaDB baru diperlukan untuk fitur database, impor, dan aktivasi akun.

### B. Siapkan `.env` pada root

Salin `app/config/.env.example` menjadi **`.env` pada root proyek**, bukan `app/config/.env`. Contoh melalui Command Prompt Windows dari root proyek:

```bat
copy app\config\.env.example .env
```

Sesuaikan konfigurasi lokal:

| Variabel | Pengaturan |
| --- | --- |
| `APP_URL` | Default `http://localhost/Absensi-Siswa-i/public`; ubah jika folder, port, atau VirtualHost berbeda |
| `APP_KEY` | Wajib untuk API login; buat secret lokal 32 byte dengan `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`, simpan hanya di `.env` |
| `DB_HOST` | Host layanan database lokal, misalnya `127.0.0.1` |
| `DB_PORT` | Port database lokal, umumnya `3306` |
| `DB_DATABASE` | Nama database yang dibuat sendiri; default konfigurasi `absensi_qr_sekolah` |
| `DB_USERNAME` | Template kosong; **wajib diisi** dengan akun database lokal yang benar |
| `DB_PASSWORD` | Boleh bernilai kosong hanya jika akun database lokal memang tanpa password; variabel tetap harus ada |

**Jangan berasumsi username database adalah `root`.** Gunakan akun yang sesuai dengan konfigurasi masing-masing dan batasi hak aksesnya. Jangan commit `.env` atau membagikan isinya. `APP_URL` hanya konfigurasi URL aplikasi, bukan pengganti pengaturan Apache.

### C. Buka halaman awal tanpa database

Untuk penempatan standar di XAMPP, buka:

```text
http://localhost/Absensi-Siswa-i/public/
http://localhost/Absensi-Siswa-i/public/index.php
```

PDO bersifat **lazy**: koneksi baru dibuat saat `database()` benar-benar dipanggil. Handler homepage dan `GET /api/v1/auth/csrf` tidak membutuhkan database; endpoint API lainnya memerlukan MySQL, `.env`, dan kedua migrasi. Request yang tidak didukung ke homepage ditolak dengan 405.

Halaman status sederhana beserta layout dan asetnya sudah tersedia. Bootstrap 5.3.3 dimuat dari jsDelivr dengan atribut `integrity`/SRI dan CSS fallback lokal. `html5-qrcode` belum dimuat; `scanner.js` hanya placeholder dan tidak meminta akses kamera. Halaman belum diuji visual di browser.

### D. Impor database melalui phpMyAdmin — opsional untuk homepage

1. Nyalakan MySQL/MariaDB dari XAMPP.
2. Buka phpMyAdmin lokal dan buat database sesuai `DB_DATABASE`, menggunakan collation `utf8mb4_unicode_ci`.
3. Pilih database kosong tersebut, lalu buka tab **Import**.
4. Impor `database/migrations/001_initial_schema.sql` terlebih dahulu.
5. Impor `database/migrations/002_auth_login_attempts.sql` untuk tabel pembatas percobaan login.
6. Buat `APP_KEY` di `.env` dengan perintah `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"` dan jangan bagikan nilai tersebut.
7. Jika memerlukan contoh data development, impor `database/seeds/development_seed.sql` setelah kedua migrasi berhasil. Seed **opsional**.
8. Periksa hasil/error impor sebelum melanjutkan. Jangan mengimpor ulang seed pada database berisi data; seed memakai ID tetap dan bukan skrip reset/upsert.

Migrasi membuat tabel, bukan database atau akun database. DDL dapat melakukan implicit commit, jadi kegagalan migrasi perlu diperiksa sebelum mengulang. Seed memakai transaksi. Data contoh seluruhnya fiktif: satu tahun ajaran `2026/2027`, kelas `4A`, satu guru, empat siswa, serta keanggotaan kelasnya.

**Impor SQL belum diuji langsung pada server MySQL/MariaDB dalam pekerjaan dokumentasi ini.** Pemeriksaan statis sebelumnya tidak sama dengan bukti bahwa impor berhasil pada lingkungan lokal Anda.

### E. Buat atau aktifkan akun lewat CLI

Tidak ada password default. Akun seed berikut sengaja **nonaktif** dengan `password_hash = NULL`:

| Username seed | Role | Keterangan |
| --- | --- | --- |
| `admin.demo` | `admin` | Belum dapat login |
| `guru.demo` | `guru` | Belum dapat login; sudah terhubung ke profil guru dan penugasan kelas |

Skrip `bin/create-user.php` tersedia dengan opsi `--role=admin|guru --username=... --name="..."`. Jalankan `php bin/create-user.php --help` untuk melihat petunjuknya.

Password dibaca **hanya dari STDIN yang di-redirect**, bukan argumen command line, environment variable, atau prompt interaktif. CLI menolak TTY. Panjang password minimal **12 byte** dan maksimal **72 byte**, tidak termasuk akhir baris; ini batas byte, bukan selalu jumlah karakter.

Langkah praktis:

1. Buat file teks berisi satu baris password menggunakan editor lokal. Simpan pada lokasi privat **di luar repositori dan webroot**, misalnya `C:/private/absensi-password.txt`.
2. Batasi izin file agar hanya pengguna lokal terkait yang dapat membacanya. Jangan simpan di folder bersama atau folder sinkronisasi publik.
3. Dari root proyek, jalankan melalui **Command Prompt (`cmd.exe`)** atau shell yang mendukung redirect `<`:

   ```sh
   php bin/create-user.php --username=admin.demo --role=admin --name="Admin Development" < "C:/private/absensi-password.txt"
   ```

4. Hapus file sementara segera setelah digunakan, termasuk jika perintah gagal. Contoh pada Command Prompt:

   ```bat
   del "C:\private\absensi-password.txt"
   ```

Untuk guru, gunakan `--username=guru.demo --role=guru --name="Guru Development"` dan file password privat tersendiri. Jangan memakai contoh password yang sama untuk beberapa akun. Operator `<` pada contoh tersebut tidak didukung langsung oleh PowerShell; jalankan melalui `cmd.exe`.

CLI meng-hash password dengan `PASSWORD_DEFAULT`. Untuk username existing, **role harus sama**, baris user diperbarui tanpa mengganti ID, dan link `teachers` tetap dijaga. Untuk guru baru, profil `teachers` dibuat bersama akun. Konflik role ditolak, bukan otomatis mengubah admin menjadi guru atau sebaliknya. Endpoint login API tersedia di `/api/v1/auth/login`; halaman login web belum dibuat.

### F. Jalankan pemeriksaan PHP

Jalankan dari root proyek:

```sh
php tests/lint.php
php tests/run.php
```

- `tests/lint.php`: memeriksa sintaks seluruh file PHP.
- `tests/run.php`: smoke test helper keamanan, parsing input API, Auth, CSRF, dan validasi awal tanpa server MySQL.

Smoke test memakai PDO double di memori untuk memeriksa alur autentikasi, role, session, CSRF, dan validasi input API. Test ini tidak membuktikan impor SQL, kamera, scanner, CRUD, aturan Apache, atau integrasi database sungguhan berhasil.

## 7. API yang tersedia

Semua endpoint memakai prefix `/api/v1`, session cookie, dan respons JSON. Untuk request `POST`, ambil token dari `GET /api/v1/auth/csrf`, lalu kirim header `X-CSRF-Token` beserta `Content-Type: application/json`. Login juga memerlukan token CSRF. Browser harus menyertakan cookie session (`credentials: same-origin`).

| Method | Endpoint | Akses | Kegunaan |
| --- | --- | --- | --- |
| `GET` | `/auth/csrf` | Publik | Membuat/mengambil token CSRF |
| `POST` | `/auth/login` | Publik + CSRF | Login memakai `username` dan `password` |
| `GET` | `/auth/me` | Admin/guru | Profil sesi aktif dan token CSRF |
| `POST` | `/auth/logout` | Admin/guru + CSRF | Mengakhiri sesi |
| `GET` | `/classes` | Admin/guru | Daftar kelas; guru hanya kelas penugasannya |
| `GET` | `/classes/{id}/students?page=1&per_page=50` | Admin/guru | Daftar siswa dengan pagination |
| `POST` | `/classes/{id}/students` | Admin + CSRF | Tambah siswa dan keanggotaan kelas |
| `GET` | `/sessions` | Admin/guru | Daftar sesi terbaru, dibatasi kelas guru |
| `POST` | `/sessions` | Admin/guru + CSRF | Buka sesi hari ini dengan `class_id` |
| `POST` | `/sessions/{id}/close` | Admin/guru pemilik kelas + CSRF | Tutup sesi aktif |
| `GET` | `/sessions/{id}/attendances` | Admin/guru pemilik kelas | Lihat catatan absensi sesi |
| `POST` | `/sessions/{id}/attendances` | Admin/guru pemilik kelas + CSRF | Catat manual: `student_id`, `status`, opsional `notes` |
| `POST` | `/sessions/{id}/scan` | Admin/guru pemilik kelas + CSRF | Validasi token QR dan catat hadir |
| `POST` | `/students/{id}/qr-token` | Admin + CSRF | Terbitkan token QR; token mentah dikembalikan satu kali |

Untuk contoh body dan respons, lihat [docs/API.md](docs/API.md). Response success memakai `{ "data": ... }`; error memakai HTTP status yang sesuai dan `{ "error": { "code", "message" } }`. Token QR hanya berisi random token, disimpan sebagai hash di database, dan tidak pernah dikembalikan pada pemindaian.

Endpoints ini merupakan backend awal, bukan seluruh sistem. CRUD edit/hapus, manajemen kelas/tahun ajaran/guru, koreksi absensi, laporan/export, UI login, dan integrasi scanner kamera belum dibuat.

## 8. Role dan hak akses

| Role | Hak akses yang direncanakan | Batas akses |
| --- | --- | --- |
| Admin | Mengelola akun, guru, tahun ajaran, kelas, penugasan, siswa, token QR, laporan, audit, dan koreksi administratif | Tetap mengikuti validasi data, konsistensi sesi, CSRF, serta pencatatan audit |
| Guru | Membuka/menutup sesi, memindai QR, mencatat absensi manual, dan melihat rekap kelas penugasannya | Tidak boleh mengakses kelas lain hanya dengan mengganti ID pada URL/request |
| Siswa | Tidak memiliki akun dalam lingkup saat ini; menerima QR untuk dipindai guru | QR bukan hak akses ke dashboard atau data siswa |

Kerangka `Middleware::admin()`, `guru()`, dan `adminOrGuru()` memeriksa role. Pemeriksaan **penugasan dan kepemilikan kelas tetap wajib dilakukan di service/query**; lolos middleware guru tidak otomatis boleh mengakses semua kelas.

## 9. Keamanan dan batas implementasi

### Fondasi yang sudah ada

- `Auth` menyediakan `attempt()`, `user()`, `check()`, `hasRole()`, dan `logout()`; endpoint JSON login/logout menggunakan helper tersebut.
- Query Auth memakai PDO prepared statement, `password_verify()`, dan rehash ketika diperlukan melalui `password_needs_rehash()`.
- ID sesi dirotasi setelah login berhasil; idle timeout default **30 menit**. Status aktif dan role dibaca ulang dari database, bukan dipercaya hanya dari sesi lama.
- Endpoint API mutasi memakai session CSRF token melalui header `X-CSRF-Token`; session cookie `HttpOnly`, `SameSite=Lax`, dan secure pada HTTPS.
- `.htaccess` root memblokir direktori privat dan dotfile sebagai fallback bila proyek ditempatkan langsung di `htdocs`. Proteksi ini bergantung pada Apache dan `AllowOverride` yang benar.

### Aturan yang harus diterapkan pada fitur lanjutan

- Token QR dibuat memakai sumber acak kriptografis; simpan hash SHA-256 saja. Jangan simpan token mentah dalam database, URL query, log, atau repositori.
- Periksa token kedaluwarsa/dicabut, kecocokan pemilik token, keanggotaan kelas pada tanggal sesi, penugasan guru, dan status sesi di backend. Validasi JavaScript tidak cukup.
- Constraint unik `(attendance_session_id, student_id)` mencegah baris ganda. Scan berulang tidak boleh diam-diam menimpa status manual.
- Gunakan transaksi dan penguncian untuk mencegah balapan antara scan, penutupan sesi, pencabutan token, atau perubahan penempatan siswa.
- Escape output HTML, validasi input, batasi percobaan login, dan hindari menampilkan detail exception/credential kepada browser.
- Jangan commit password, hash credential seed, `.env`, file password sementara, atau QR usable. Tidak ada password default pada seed.
- Kamera browser umumnya membutuhkan **HTTPS atau localhost** dan izin pengguna. Mengakses alamat IP laptop dari smartphone melalui HTTP biasa dapat membuat kamera tidak tersedia; pengujian smartphone memerlukan secure context yang sesuai. Ini belum menjadi bukti scanner bekerja.

### Catatan hosting

Arahkan **DocumentRoot ke `public/`** sebagai pilihan yang disarankan. Pada shared hosting, tempatkan kode privat `app/`, `bin/`, database/dokumentasi, dan root `.env` **di luar webroot**, dengan susunan path/bootstrap yang sesuai. Jangan memindahkan `.env` ke dalam folder publik hanya agar mudah dibaca aplikasi. Root `.htaccess` adalah perlindungan tambahan, bukan pengganti pemisahan webroot yang benar.

Belum ada deployment yang dilakukan atau diklaim berhasil. Sebelum deployment diperlukan pengujian integrasi, konfigurasi HTTPS, hak akses file/database, dan pemeriksaan akses langsung ke berkas privat.

## 10. Status pengembangan

| Bagian | Status |
| --- | --- |
| Migrasi, seed, dan ERD | Tersedia; impor SQL langsung belum diverifikasi |
| Bootstrap, konfigurasi, PDO lazy | Fondasi tersedia |
| Auth, middleware role, helper CSRF | Endpoint API login/logout dan role tersedia; UI login belum ada |
| Homepage | Entry point, layout, halaman status, dan aset tersedia |
| Bootstrap 5.3.3 + SRI + CSS fallback | Tersedia; belum diuji visual di browser |
| CLI aktivasi dan skrip test | Tersedia; aktivasi dengan database nyata perlu diuji lokal |
| Backend API | Login, daftar kelas/siswa, buka/tutup sesi, catat manual/QR, penerbitan token tersedia; perlu diuji dengan MySQL |
| CRUD penuh dan scanner kamera | Belum diimplementasikan; `html5-qrcode` belum dimuat |
| Prototype dan SRS | Tidak tersedia; hanya dialokasikan sebagai placeholder |
| Deployment | Belum dilakukan |

Proyek masih pada tahap awal: struktur proyek dan rancangan database sudah disiapkan, sedangkan prototype antarmuka menunggu materi asli. Langkah berikutnya adalah menambahkan SRS/prototype, menguji migrasi dan API di XAMPP, membuat UI login serta scanner kamera, melengkapi CRUD/laporan, lalu menyiapkan deployment secara bertahap.
