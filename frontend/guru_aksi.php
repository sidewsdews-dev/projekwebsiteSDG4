<?php
require_once 'auth.php';
$u = wajib_login('guru');
$nip = $u['nip'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ke_dashboard('guru');
}
csrf_cek();

$modul = $_POST['modul'] ?? '';
$tab   = $_POST['tab'] ?? 'ringkasan';

function cek_pengampu_guru($id_pengampu, $nip) {
    return db_satu("SELECT * FROM pengampu WHERE id_pengampu = ? AND nip = ?", "is", [(int)$id_pengampu, $nip]);
}

try {
    switch ($modul) {
        // --- MATERI ---
        case 'tambah_materi':
            $idp   = (int)($_POST['id_pengampu'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            if (!cek_pengampu_guru($idp, $nip)) throw new Exception("Anda tidak mengampu kelas/mapel tersebut.");
            if ($judul === '' || empty($_FILES['berkas']['name'])) throw new Exception("Judul dan berkas materi wajib diisi.");

            if ($_FILES['berkas']['error'] !== UPLOAD_ERR_OK) throw new Exception("Gagal mengunggah file.");
            if ($_FILES['berkas']['size'] > 15 * 1024 * 1024) throw new Exception("Ukuran file maksimal 15 MB.");

            if (!is_dir(MATERI_DIR)) mkdir(MATERI_DIR, 0755, true);
            $asli = basename($_FILES['berkas']['name']);
            $ext  = pathinfo($asli, PATHINFO_EXTENSION);
            $nama_simpan = uniqid('mat_', true) . ($ext ? '.' . strtolower($ext) : '');
            $tujuan = MATERI_DIR . '/' . $nama_simpan;

            if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $tujuan)) {
                throw new Exception("Gagal menyimpan berkas materi di server.");
            }
            db_jalankan(
                "INSERT INTO materi (id_pengampu, judul, file_path, nama_asli) VALUES (?, ?, ?, ?)",
                "isss", [$idp, $judul, $nama_simpan, $asli]
            );
            flash("Materi pelajaran berhasil diunggah.");
            break;

        case 'hapus_materi':
            $idm = (int)($_POST['id_materi'] ?? 0);
            $row = db_satu(
                "SELECT mt.* FROM materi mt JOIN pengampu p ON p.id_pengampu = mt.id_pengampu WHERE mt.id_materi = ? AND p.nip = ?",
                "is", [$idm, $nip]
            );
            if ($row) {
                $path = MATERI_DIR . '/' . $row['file_path'];
                if (is_file($path)) @unlink($path);
                db_jalankan("DELETE FROM materi WHERE id_materi = ?", "i", [$idm]);
                flash("Materi dihapus.");
            }
            break;

        // --- TUGAS ---
        case 'tambah_tugas':
            $idp   = (int)($_POST['id_pengampu'] ?? 0);
            $judul = trim($_POST['judul'] ?? '');
            $desk  = trim($_POST['deskripsi'] ?? '') ?: null;
            $dead  = str_replace('T', ' ', trim($_POST['deadline'] ?? ''));
            if (!cek_pengampu_guru($idp, $nip)) throw new Exception("Anda tidak mengampu kelas/mapel tersebut.");
            if ($judul === '' || $dead === '') throw new Exception("Judul tugas dan tenggat waktu wajib diisi.");

            $soal_path = null;
            if (!empty($_FILES['file_soal']['name']) && $_FILES['file_soal']['error'] === UPLOAD_ERR_OK) {
                if (!is_dir(SOAL_DIR)) mkdir(SOAL_DIR, 0755, true);
                $ext = pathinfo($_FILES['file_soal']['name'], PATHINFO_EXTENSION);
                $soal_path = uniqid('soal_', true) . ($ext ? '.' . strtolower($ext) : '');
                move_uploaded_file($_FILES['file_soal']['tmp_name'], SOAL_DIR . '/' . $soal_path);
            }

            db_jalankan(
                "INSERT INTO tugas (id_pengampu, judul, deskripsi, deadline, file_soal_path) VALUES (?, ?, ?, ?, ?)",
                "issss", [$idp, $judul, $desk, $dead, $soal_path]
            );
            flash("Tugas baru berhasil dibuat.");
            break;

        case 'hapus_tugas':
            $idt = (int)($_POST['id_tugas'] ?? 0);
            $row = db_satu(
                "SELECT t.* FROM tugas t JOIN pengampu p ON p.id_pengampu = t.id_pengampu WHERE t.id_tugas = ? AND p.nip = ?",
                "is", [$idt, $nip]
            );
            if ($row) {
                if ($row['file_soal_path'] && is_file(SOAL_DIR . '/' . $row['file_soal_path'])) {
                    @unlink(SOAL_DIR . '/' . $row['file_soal_path']);
                }
                db_jalankan("DELETE FROM tugas WHERE id_tugas = ?", "i", [$idt]);
                flash("Tugas dihapus.");
            }
            break;

        case 'nilai_pengumpulan':
            $idx = (int)($_POST['id_pengumpulan'] ?? 0);
            $nil = $_POST['nilai'] === '' ? null : (float)$_POST['nilai'];
            if ($nil !== null && ($nil < 0 || $nil > 100)) throw new Exception("Nilai harus di antara 0 sampai 100.");
            $cek = db_satu(
                "SELECT x.id FROM pengumpulan_tugas x
                 JOIN tugas t ON t.id_tugas = x.id_tugas
                 JOIN pengampu p ON p.id_pengampu = t.id_pengampu
                 WHERE x.id = ? AND p.nip = ?",
                "is", [$idx, $nip]
            );
            if (!$cek) throw new Exception("Data pengumpulan tidak ditemukan.");
            db_jalankan("UPDATE pengumpulan_tugas SET nilai = ? WHERE id = ?", "di", [$nil, $idx]);
            flash("Nilai pengumpulan tugas disimpan.");
            break;

        // --- NILAI MAPEL (HARIAN, UTS, UAS) ---
        case 'simpan_nilai':
            $idp    = (int)($_POST['id_pengampu'] ?? 0);
            if (!cek_pengampu_guru($idp, $nip)) throw new Exception("Akses kelas/mapel ditolak.");
            $nis_arr = $_POST['nis'] ?? [];
            $har_arr = $_POST['harian'] ?? [];
            $uts_arr = $_POST['uts'] ?? [];
            $uas_arr = $_POST['uas'] ?? [];

            foreach ($nis_arr as $i => $nis) {
                $h = ($har_arr[$i] ?? '') === '' ? null : (float)$har_arr[$i];
                $t = ($uts_arr[$i] ?? '') === '' ? null : (float)$uts_arr[$i];
                $a = ($uas_arr[$i] ?? '') === '' ? null : (float)$uas_arr[$i];
                db_jalankan(
                    "INSERT INTO nilai (nis, id_pengampu, harian, uts, uas) VALUES (?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE harian=VALUES(harian), uts=VALUES(uts), uas=VALUES(uas)",
                    "siddd", [$nis, $idp, $h, $t, $a]
                );
            }
            flash("Rekap nilai siswa berhasil disimpan.");
            break;

        // --- ABSENSI KELAS ---
        case 'simpan_absensi':
            $idp = (int)($_POST['id_pengampu'] ?? 0);
            $tgl = $_POST['tanggal'] ?? date('Y-m-d');
            if (!cek_pengampu_guru($idp, $nip)) throw new Exception("Akses kelas/mapel ditolak.");
            $status_map = $_POST['status'] ?? [];
            foreach ($status_map as $nis => $st) {
                if (!in_array($st, ['hadir', 'izin', 'sakit', 'alpa'], true)) continue;
                db_jalankan(
                    "INSERT INTO absensi (nis, id_pengampu, tanggal, status) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE status=VALUES(status)",
                    "siss", [$nis, $idp, $tgl, $st]
                );
            }
            flash("Absensi kelas tanggal " . tanggal_id($tgl) . " berhasil disimpan.");
            break;

        default:
            throw new Exception("Aksi tidak valid.");
    }
} catch (Throwable $ex) {
    flash("Gagal: " . $ex->getMessage(), "err");
}

header("Location: dashboard-guru.php#" . urlencode($tab));
exit;
