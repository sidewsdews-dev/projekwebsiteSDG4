# Portal Sekolah Terpadu

Aplikasi web berbasis PHP dan MySQL yang menyatukan layanan akademik sekolah (jadwal, materi, tugas, nilai, absensi) serta informasi resmi sekolah (berita dan agenda) dalam satu pintu masuk untuk **Siswa**, **Guru**, dan **Administrator**.

---

## 1. Nama dan Deskripsi Proyek

**Portal Sekolah Terpadu** adalah sistem informasi akademik sekolah. Halaman depan menampilkan berita dan agenda sekolah yang dapat dibaca publik. Setelah login, setiap pengguna masuk ke dashboard sesuai perannya:

- **Siswa** melihat jadwal, mengunduh materi, mengumpulkan tugas, serta memantau nilai dan absensi.
- **Guru** mengelola materi, tugas, penilaian, dan absensi untuk kelas yang diampu.
- **Admin** menyetujui pendaftaran akun, mengelola data master, dan menerbitkan konten website.

Akun baru (siswa maupun guru) tidak langsung aktif. Pendaftaran harus disetujui Admin terlebih dahulu.

## 2. Latar Belakang dan Tujuan Proyek

**Latar belakang.** Di banyak sekolah, layanan akademik tersebar di banyak tempat: jadwal di papan pengumuman, tugas lewat grup chat, nilai di buku, dan absensi di kertas. Akibatnya informasi mudah tercecer, sulit dilacak, dan menyulitkan guru, siswa, maupun pihak administrasi.

**Tujuan.**

1. Menyediakan satu portal dengan login terpusat untuk tiga peran pengguna.
2. Mendigitalkan alur akademik utama: jadwal, distribusi materi, pengumpulan dan penilaian tugas, rekap nilai (harian, UTS, UAS), dan absensi.
3. Memberi Admin kendali atas data master dan verifikasi akun agar hanya pengguna sah yang bisa masuk.
4. Mempublikasikan berita dan agenda sekolah dari satu tempat.
5. Menerapkan praktik keamanan dasar: password di-hash, proteksi CSRF, query terparameter, dan pembatasan akses berdasarkan peran.

## 3. Daftar Fitur / Modul

### Portal Publik (`index.php`, `login.php`, `daftar.php`)
- Beranda dengan berita terbit (6 terbaru) dan agenda kegiatan.
- Login satu pintu: Siswa dan Admin memakai *username*, Guru memakai NIP.
- Registrasi mandiri Siswa dan Guru, berstatus menunggu persetujuan Admin.

### Dashboard Admin (`dashboard-admin.php`, `admin_aksi.php`, `akun_aksi.php`)
- Ringkasan jumlah guru, siswa aktif, kelas, dan akun menunggu persetujuan.
- Persetujuan atau penolakan pendaftaran akun (siswa dapat langsung ditetapkan kelasnya).
- CRUD Data Guru dan Data Siswa (termasuk reset password dan aktif/nonaktif akun).
- Tambah akun Admin dan aktif/nonaktifkan akun Admin (tidak bisa menonaktifkan akun sendiri).
- Kelas: tambah/ubah kelas beserta wali kelas. Mata pelajaran: tambah. Guru pengampu: tambah penugasan guru-mapel-kelas.
- Jadwal pelajaran: tambah dan hapus.
- Konten website: tulis, ubah, hapus Berita dan Agenda (status draf atau terbit).

### Dashboard Guru (`dashboard-guru.php`, `guru_aksi.php`)
- Jadwal mengajar harian dan mingguan, serta identitas, mapel, dan wali kelas.
- Unggah dan hapus materi pembelajaran.
- Buat tugas (tenggat waktu, lampiran soal opsional) dan hapus tugas.
- Menilai pengumpulan tugas siswa (0-100).
- Input nilai Harian, UTS, dan UAS per kelas dan mata pelajaran.
- Input absensi per tanggal (hadir, izin, sakit, alpa) beserta rekap riwayat.

### Dashboard Siswa (`dashboard-siswa.php`, `kumpul_tugas.php`)
- Identitas akademik, wali kelas, jadwal harian dan mingguan.
- Unduh materi dan lampiran soal.
- Kumpulkan atau ganti berkas tugas sebelum dinilai (status otomatis *tepat waktu* atau *terlambat*, maksimal 15 MB).
- Lihat nilai (Harian, UTS, UAS, rata-rata), rekap, dan riwayat absensi.

### Modul Pendukung
- `auth.php`: sesi, pengecekan peran, token CSRF, dan fungsi bantu.
- `koneksi.php`: koneksi MySQL dan fungsi query terparameter.
- `download.php`: unduhan berkas dengan pengecekan hak akses.
- `logout.php`, `script.js` (navigasi tab), `style.css` (tampilan, mendukung mode terang/gelap).

## 4. Teknologi yang Digunakan

| Bagian | Teknologi |
|---|---|
| Bahasa server | PHP 7.4 atau lebih baru (diuji sintaks pada PHP 8.3), ekstensi `mysqli` (dengan `mysqlnd`) dan `mbstring` |
| Basis data | MySQL 5.7+ atau MariaDB 10.3+ (InnoDB, `utf8mb4`) |
| Frontend | HTML5, CSS3 (tanpa framework), JavaScript vanilla |
| Font | Inter dari Google Fonts (butuh internet; tanpa internet font jatuh ke font bawaan) |
| Web server | Apache (XAMPP/LAMPP) atau server bawaan PHP |
| Pengelola DB | phpMyAdmin atau klien MySQL |

## 5. Panduan Instalasi dan Konfigurasi

### Prasyarat
XAMPP (Windows/macOS) atau LAMPP/LAMP (Linux), atau PHP + MySQL/MariaDB yang dipasang terpisah.

### Langkah 1: Letakkan folder proyek
Salin folder `frontend` ke dalam `htdocs` (XAMPP) atau `/var/www/html` (Linux), sehingga alamatnya menjadi `http://localhost/frontend/`.

### Langkah 2: Siapkan database
1. Jalankan Apache dan MySQL, lalu buka `http://localhost/phpmyadmin`.
2. Tab **Import**, pilih `frontend/dataprojek.sql` untuk membuat database `dataprojek` beserta seluruh tabel.
3. Import `frontend/seed_demo.sql` untuk mengisi data contoh. Langkah ini opsional, tetapi tanpa data awal Anda tidak punya akun Admin untuk login.

Alternatif lewat terminal:

```bash
mysql -u root -p < frontend/dataprojek.sql
mysql -u root -p < frontend/seed_demo.sql
```

### Langkah 3: Sesuaikan koneksi
Buka `frontend/koneksi.php`, lalu sesuaikan dengan lingkungan Anda:

```php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_nama = "dataprojek";
```

### Langkah 4: Pastikan folder penyimpanan berkas dapat ditulis
Materi, soal, dan tugas siswa disimpan di folder **`dataprojek_storage`** yang dibuat otomatis dua tingkat di atas folder `frontend` (`dirname(__DIR__, 2)`). Contoh:

| Lokasi `frontend` | Folder penyimpanan |
|---|---|
| `C:\xampp\htdocs\frontend` | `C:\xampp\dataprojek_storage` |
| `/var/www/html/frontend` | `/var/www/dataprojek_storage` |

Folder ini sengaja berada **di luar** web root agar berkas tidak bisa diakses langsung lewat URL. Konsekuensinya:
- Jangan menaruh `frontend` lebih dalam (misalnya `htdocs/proyek/frontend`), karena folder penyimpanan akan jatuh ke dalam `htdocs` dan bisa diakses publik. Jika lokasi harus berbeda, ubah konstanta `STORAGE_DIR`, `MATERI_DIR`, dan `SOAL_DIR` di `koneksi.php`.
- Di Linux, pastikan pengguna web server dapat menulis ke folder induknya, misalnya:
  ```bash
  sudo mkdir -p /var/www/dataprojek_storage
  sudo chown -R www-data:www-data /var/www/dataprojek_storage
  ```
- Jika ingin mengunggah berkas lebih dari 2 MB, naikkan `upload_max_filesize` dan `post_max_size` di `php.ini` (batas aplikasi 15 MB).

## 6. Panduan Menjalankan Aplikasi

1. Pastikan Apache dan MySQL berjalan.
2. Buka `http://localhost/frontend/` di browser.

Tanpa Apache, Anda bisa memakai server bawaan PHP (untuk pengembangan saja). Jalankan dari folder induk `frontend`:

```bash
php -S localhost:8000 -t frontend
```

lalu buka `http://localhost:8000/`. Dengan cara ini folder penyimpanan berada di dua tingkat di atas `frontend`, jadi pastikan folder itu dapat ditulis.

### Akun demo (dari `seed_demo.sql`, semua password: `rahasia123`)

| Peran | Pilihan "Masuk Sebagai" | Identitas login |
|---|---|---|
| Admin | Admin | `admin` |
| Guru | Guru | NIP `198001012005011001` atau `197905152003121002` |
| Siswa | Siswa | `raka`, `dina`, atau `bima` |

Akun yang masih menunggu persetujuan (untuk mencoba fitur Admin): siswa `lia` (NIS `27010004`) dan guru NIP `199001012015011003`.

> **Penting:** ganti password akun demo, atau hapus data contoh, sebelum aplikasi dipakai di luar lingkungan uji.

### Alur singkat penggunaan
1. **Admin** login, lalu membuat kelas, mata pelajaran, penugasan pengampu, dan jadwal. Ia juga menyetujui pendaftaran akun baru.
2. **Guru** login, lalu mengunggah materi, membuat tugas, mengisi nilai, dan mengisi absensi.
3. **Siswa** login, lalu membuka materi, mengumpulkan tugas, dan memantau nilai serta absensi.
4. Pengguna baru mendaftar lewat `daftar.php` dan baru bisa login setelah disetujui Admin.

### Pemecahan masalah umum
- **"Tidak dapat terhubung ke database."** Periksa kredensial di `koneksi.php` dan pastikan `dataprojek.sql` sudah diimpor.
- **"Identitas atau kata sandi salah."** Pastikan memilih peran yang sesuai pada menu "Masuk Sebagai" dan seed sudah diimpor.
- **Unggahan gagal.** Periksa izin tulis folder `dataprojek_storage` dan batas `upload_max_filesize`.
