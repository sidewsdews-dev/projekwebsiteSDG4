<?php
require_once 'auth.php';
$u = wajib_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ke_dashboard('admin');
}
csrf_cek();

$modul = $_POST['modul'] ?? '';
$tab   = $_POST['tab'] ?? 'ringkasan';

try {
    switch ($modul) {
        // --- KELOLA GURU ---
        case 'tambah_guru':
            $nip   = trim($_POST['nip'] ?? '');
            $nama  = trim($_POST['nama_guru'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '') ?: null;
            $jab   = trim($_POST['jabatan'] ?? '') ?: null;
            $stat  = trim($_POST['status_guru'] ?? '') ?: null;
            if ($nip === '' || $nama === '' || $pass === '') throw new Exception("NIP, Nama, dan Password wajib diisi.");
            db_jalankan(
                "INSERT INTO guru (nip, nama_guru, password, email, jabatan, status_guru, aktif) VALUES (?, ?, ?, ?, ?, ?, 1)",
                "ssssss", [$nip, $nama, password_hash($pass, PASSWORD_DEFAULT), $email, $jab, $stat]
            );
            flash("Data guru berhasil ditambahkan.");
            break;

        case 'edit_guru':
            $nip   = trim($_POST['nip'] ?? '');
            $nama  = trim($_POST['nama_guru'] ?? '');
            $email = trim($_POST['email'] ?? '') ?: null;
            $jab   = trim($_POST['jabatan'] ?? '') ?: null;
            $stat  = trim($_POST['status_guru'] ?? '') ?: null;
            $aktif = isset($_POST['aktif']) ? (int)$_POST['aktif'] : 1;
            $pass  = $_POST['password'] ?? '';
            if ($pass !== '') {
                db_jalankan(
                    "UPDATE guru SET nama_guru=?, email=?, jabatan=?, status_guru=?, aktif=?, password=? WHERE nip=?",
                    "ssssiss", [$nama, $email, $jab, $stat, $aktif, password_hash($pass, PASSWORD_DEFAULT), $nip]
                );
            } else {
                db_jalankan(
                    "UPDATE guru SET nama_guru=?, email=?, jabatan=?, status_guru=?, aktif=? WHERE nip=?",
                    "ssssis", [$nama, $email, $jab, $stat, $aktif, $nip]
                );
            }
            flash("Data guru berhasil diperbarui.");
            break;

        case 'hapus_guru':
            db_jalankan("DELETE FROM guru WHERE nip = ?", "s", [$_POST['nip'] ?? '']);
            flash("Data guru dihapus.");
            break;

        // --- KELOLA SISWA ---
        case 'tambah_siswa':
            $nis   = trim($_POST['nis'] ?? '');
            $nama  = trim($_POST['nama_siswa'] ?? '');
            $user  = trim($_POST['username'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '') ?: null;
            $angk  = trim($_POST['angkatan'] ?? '') ?: null;
            $kelas = trim($_POST['ruang_kelas'] ?? '') ?: null;
            $stat  = $_POST['status'] ?? 'aktif';
            if ($nis === '' || $nama === '' || $user === '' || $pass === '') throw new Exception("NIS, Nama, Username, dan Password wajib diisi.");
            db_jalankan(
                "INSERT INTO siswa (nis, nama_siswa, username, password, email, angkatan, ruang_kelas, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                "ssssssss", [$nis, $nama, $user, password_hash($pass, PASSWORD_DEFAULT), $email, $angk, $kelas, $stat]
            );
            flash("Data siswa berhasil ditambahkan.");
            break;

        case 'edit_siswa':
            $nis   = trim($_POST['nis'] ?? '');
            $nama  = trim($_POST['nama_siswa'] ?? '');
            $user  = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '') ?: null;
            $angk  = trim($_POST['angkatan'] ?? '') ?: null;
            $kelas = trim($_POST['ruang_kelas'] ?? '') ?: null;
            $stat  = $_POST['status'] ?? 'aktif';
            $pass  = $_POST['password'] ?? '';
            if ($pass !== '') {
                db_jalankan(
                    "UPDATE siswa SET nama_siswa=?, username=?, email=?, angkatan=?, ruang_kelas=?, status=?, password=? WHERE nis=?",
                    "ssssssss", [$nama, $user, $email, $angk, $kelas, $stat, password_hash($pass, PASSWORD_DEFAULT), $nis]
                );
            } else {
                db_jalankan(
                    "UPDATE siswa SET nama_siswa=?, username=?, email=?, angkatan=?, ruang_kelas=?, status=? WHERE nis=?",
                    "sssssss", [$nama, $user, $email, $angk, $kelas, $stat, $nis]
                );
            }
            flash("Data siswa berhasil diperbarui.");
            break;

        case 'hapus_siswa':
            db_jalankan("DELETE FROM siswa WHERE nis = ?", "s", [$_POST['nis'] ?? '']);
            flash("Data siswa dihapus.");
            break;

        // --- KELOLA AKUN ADMIN ---
        case 'tambah_admin':
            $nama  = trim($_POST['nama'] ?? '');
            $user  = trim($_POST['username'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $email = trim($_POST['email'] ?? '') ?: null;
            if ($nama === '' || $user === '' || $pass === '') throw new Exception("Nama, Username, dan Password admin wajib diisi.");
            db_jalankan(
                "INSERT INTO admin (nama, username, password, email, aktif) VALUES (?, ?, ?, ?, 1)",
                "ssss", [$nama, $user, password_hash($pass, PASSWORD_DEFAULT), $email]
            );
            flash("Akun administrator baru berhasil ditambahkan.");
            break;

        case 'toggle_admin':
            $id_adm = (int)($_POST['id_admin'] ?? 0);
            if ($id_adm === (int)$u['id_admin']) throw new Exception("Tidak dapat menonaktifkan akun sendiri.");
            db_jalankan("UPDATE admin SET aktif = 1 - aktif WHERE id_admin = ?", "i", [$id_adm]);
            flash("Status akun administrator diperbarui.");
            break;

        // --- KELOLA KELAS & MAPEL & PENGAMPU ---
        case 'tambah_kelas':
            $rk = trim($_POST['ruang_kelas'] ?? '');
            $ta = trim($_POST['tahun_ajar'] ?? '');
            $wl = trim($_POST['nip_wali'] ?? '') ?: null;
            if ($rk === '' || $ta === '') throw new Exception("Ruang kelas dan tahun ajar wajib diisi.");
            db_jalankan(
                "INSERT INTO kelas (ruang_kelas, tahun_ajar, nip_wali) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE tahun_ajar=VALUES(tahun_ajar), nip_wali=VALUES(nip_wali)",
                "sss", [$rk, $ta, $wl]
            );
            flash("Data kelas disimpan.");
            break;

        case 'hapus_kelas':
            db_jalankan("DELETE FROM kelas WHERE ruang_kelas = ?", "s", [$_POST['ruang_kelas'] ?? '']);
            flash("Kelas dihapus.");
            break;

        case 'tambah_mapel':
            $id = strtoupper(trim($_POST['idmapel'] ?? ''));
            $nm = trim($_POST['nama_mapel'] ?? '');
            if ($id === '' || $nm === '') throw new Exception("Kode dan nama mapel wajib diisi.");
            db_jalankan(
                "INSERT INTO mata_pelajaran (idmapel, nama_mapel) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE nama_mapel=VALUES(nama_mapel)",
                "ss", [$id, $nm]
            );
            flash("Mata pelajaran disimpan.");
            break;

        case 'tambah_pengampu':
            $nip = trim($_POST['nip'] ?? '');
            $idm = trim($_POST['idmapel'] ?? '');
            $rk  = trim($_POST['ruang_kelas'] ?? '');
            if ($nip === '' || $idm === '' || $rk === '') throw new Exception("Guru, Mapel, dan Kelas wajib dipilih.");
            db_jalankan("INSERT INTO pengampu (nip, idmapel, ruang_kelas) VALUES (?, ?, ?)", "sss", [$nip, $idm, $rk]);
            flash("Penugasan guru pengampu berhasil ditambahkan.");
            break;

        case 'hapus_pengampu':
            db_jalankan("DELETE FROM pengampu WHERE id_pengampu = ?", "i", [(int)($_POST['id_pengampu'] ?? 0)]);
            flash("Data pengampu dihapus.");
            break;

        // --- KELOLA JADWAL ---
        case 'tambah_jadwal':
            $idp  = (int)($_POST['id_pengampu'] ?? 0);
            $hari = $_POST['hari'] ?? '';
            $mul  = $_POST['jam_mulai'] ?? '';
            $sel  = $_POST['jam_selesai'] ?? '';
            if (!$idp || $hari === '' || $mul === '' || $sel === '') throw new Exception("Semua field jadwal wajib diisi.");
            if ($sel <= $mul) throw new Exception("Jam selesai harus lebih akhir dari jam mulai.");
            db_jalankan(
                "INSERT INTO jadwal (id_pengampu, hari, jam_mulai, jam_selesai) VALUES (?, ?, ?, ?)",
                "isss", [$idp, $hari, $mul, $sel]
            );
            flash("Jadwal pelajaran berhasil ditambahkan.");
            break;

        case 'hapus_jadwal':
            db_jalankan("DELETE FROM jadwal WHERE id_jadwal = ?", "i", [(int)($_POST['id_jadwal'] ?? 0)]);
            flash("Jadwal dihapus.");
            break;

        // --- KELOLA KONTEN WEBSITE (BERITA & EVENT) ---
        case 'simpan_berita':
            $idb   = (int)($_POST['id_berita'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $isi   = trim($_POST['isi'] ?? '');
            $stat  = ($_POST['status'] ?? 'draf') === 'terbit' ? 'terbit' : 'draf';
            if ($judul === '' || $isi === '') throw new Exception("Judul dan isi berita wajib diisi.");
            if ($idb > 0) {
                db_jalankan("UPDATE berita SET judul=?, isi=?, status=? WHERE id_berita=?", "sssi", [$judul, $isi, $stat, $idb]);
                flash("Berita diperbarui.");
            } else {
                db_jalankan("INSERT INTO berita (judul, isi, status, id_admin) VALUES (?, ?, ?, ?)", "sssi", [$judul, $isi, $stat, $u['id_admin']]);
                flash("Berita baru ditambahkan.");
            }
            break;

        case 'hapus_berita':
            db_jalankan("DELETE FROM berita WHERE id_berita = ?", "i", [(int)($_POST['id_berita'] ?? 0)]);
            flash("Berita dihapus.");
            break;

        case 'simpan_event':
            $ide   = (int)($_POST['id_event'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $desk  = trim($_POST['deskripsi'] ?? '') ?: null;
            $tm    = $_POST['tanggal_mulai'] ?? '';
            $ts    = ($_POST['tanggal_selesai'] ?? '') ?: null;
            $lok   = trim($_POST['lokasi'] ?? '') ?: null;
            $stat  = ($_POST['status'] ?? 'draf') === 'terbit' ? 'terbit' : 'draf';
            if ($judul === '' || $tm === '') throw new Exception("Judul dan tanggal mulai event wajib diisi.");
            if ($ide > 0) {
                db_jalankan(
                    "UPDATE event SET judul=?, deskripsi=?, tanggal_mulai=?, tanggal_selesai=?, lokasi=?, status=? WHERE id_event=?",
                    "ssssssi", [$judul, $desk, $tm, $ts, $lok, $stat, $ide]
                );
                flash("Agenda event diperbarui.");
            } else {
                db_jalankan(
                    "INSERT INTO event (judul, deskripsi, tanggal_mulai, tanggal_selesai, lokasi, status, id_admin) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    "ssssssi", [$judul, $desk, $tm, $ts, $lok, $stat, $u['id_admin']]
                );
                flash("Agenda event baru ditambahkan.");
            }
            break;

        case 'hapus_event':
            db_jalankan("DELETE FROM event WHERE id_event = ?", "i", [(int)($_POST['id_event'] ?? 0)]);
            flash("Agenda event dihapus.");
            break;

        default:
            throw new Exception("Aksi tidak dikenali.");
    }
} catch (Throwable $ex) {
    flash("Gagal: " . $ex->getMessage(), "err");
}

header("Location: dashboard-admin.php#" . urlencode($tab));
exit;
