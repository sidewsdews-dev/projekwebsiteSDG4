<?php
require_once 'auth.php';
$u = wajib_login('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ke_dashboard('admin');
}
csrf_cek();

$jenis = $_POST['jenis'] ?? '';
$id    = trim($_POST['id'] ?? '');
$aksi  = $_POST['aksi'] ?? '';

if ($id === '' || !in_array($jenis, ['siswa', 'guru'], true) || !in_array($aksi, ['setujui', 'tolak'], true)) {
    flash("Permintaan tidak valid.", "err");
    header("Location: dashboard-admin.php#persetujuan");
    exit;
}

if ($jenis === 'siswa') {
    if ($aksi === 'setujui') {
        $kelas = trim($_POST['ruang_kelas'] ?? '');
        if ($kelas !== '') {
            db_jalankan("UPDATE siswa SET status = 'aktif', ruang_kelas = ? WHERE nis = ?", "ss", [$kelas, $id]);
        } else {
            db_jalankan("UPDATE siswa SET status = 'aktif' WHERE nis = ?", "s", [$id]);
        }
        flash("Akun siswa ($id) berhasil disetujui dan diaktifkan.");
    } else {
        db_jalankan("DELETE FROM siswa WHERE nis = ? AND status = 'menunggu'", "s", [$id]);
        flash("Pendaftaran siswa ($id) telah ditolak dan dihapus.");
    }
} else {
    if ($aksi === 'setujui') {
        db_jalankan("UPDATE guru SET aktif = 1 WHERE nip = ?", "s", [$id]);
        flash("Akun guru ($id) berhasil disetujui dan diaktifkan.");
    } else {
        db_jalankan("DELETE FROM guru WHERE nip = ? AND aktif = 0", "s", [$id]);
        flash("Pendaftaran guru ($id) telah ditolak dan dihapus.");
    }
}

header("Location: dashboard-admin.php#persetujuan");
exit;
