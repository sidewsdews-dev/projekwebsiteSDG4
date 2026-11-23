<?php
require_once 'auth.php';
$u = wajib_login('siswa');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ke_dashboard('siswa');
}
csrf_cek();

$id_tugas = (int)($_POST['id_tugas'] ?? 0);
$tugas = db_satu(
    "SELECT t.* FROM tugas t
     JOIN pengampu p ON p.id_pengampu = t.id_pengampu
     WHERE t.id_tugas = ? AND p.ruang_kelas = ?",
    "is", [$id_tugas, $u['ruang_kelas']]
);

if (!$tugas) {
    flash("Tugas tidak ditemukan atau bukan untuk kelas Anda.", "err");
    ke_dashboard('siswa');
}

$lama = db_satu("SELECT * FROM pengumpulan_tugas WHERE id_tugas = ? AND nis = ?", "is", [$id_tugas, $u['nis']]);
if ($lama && $lama['nilai'] !== null) {
    flash("Tugas sudah dinilai oleh guru dan tidak dapat diganti.", "err");
    ke_dashboard('siswa');
}

if (empty($_FILES['berkas']['name']) || $_FILES['berkas']['error'] !== UPLOAD_ERR_OK) {
    flash("Pilih berkas tugas terlebih dahulu.", "err");
    ke_dashboard('siswa');
}

if ($_FILES['berkas']['size'] > 15 * 1024 * 1024) {
    flash("Ukuran berkas maksimal 15 MB.", "err");
    ke_dashboard('siswa');
}

if (!is_dir(STORAGE_DIR)) {
    mkdir(STORAGE_DIR, 0755, true);
}

$asli = basename($_FILES['berkas']['name']);
$ext  = pathinfo($asli, PATHINFO_EXTENSION);
$nama_simpan = $u['nis'] . '_t' . $id_tugas . '_' . bin2hex(random_bytes(6)) . ($ext ? '.' . strtolower($ext) : '');
$tujuan = STORAGE_DIR . '/' . $nama_simpan;

if (!move_uploaded_file($_FILES['berkas']['tmp_name'], $tujuan)) {
    flash("Gagal menyimpan berkas ke server.", "err");
    ke_dashboard('siswa');
}

if ($lama && is_file(STORAGE_DIR . '/' . $lama['file_path'])) {
    @unlink(STORAGE_DIR . '/' . $lama['file_path']);
}

$status = (time() > strtotime($tugas['deadline'])) ? 'terlambat' : 'tepat_waktu';

db_jalankan(
    "INSERT INTO pengumpulan_tugas (id_tugas, nis, file_path, nama_asli, waktu_upload, status)
     VALUES (?, ?, ?, ?, NOW(), ?)
     ON DUPLICATE KEY UPDATE file_path=VALUES(file_path), nama_asli=VALUES(nama_asli), waktu_upload=NOW(), status=VALUES(status)",
    "issss", [$id_tugas, $u['nis'], $nama_simpan, $asli, $status]
);

flash("Tugas berhasil dikumpulkan (" . ($status === 'terlambat' ? 'Terlambat' : 'Tepat waktu') . ").");
header("Location: dashboard-siswa.php#tugas");
exit;
