<?php
require_once 'auth.php';

if (empty($_SESSION['id']) || empty($_SESSION['peran'])) {
    header("Location: login.php");
    exit;
}

$jenis = $_GET['jenis'] ?? 'pengumpulan';
$id    = (int)($_GET['id'] ?? 0);
$peran = $_SESSION['peran'];
$uid   = $_SESSION['id'];

if ($jenis === 'materi') {
    $row = db_satu(
        "SELECT mt.*, p.nip, p.ruang_kelas FROM materi mt JOIN pengampu p ON p.id_pengampu = mt.id_pengampu WHERE mt.id_materi = ?",
        "i", [$id]
    );
    if (!$row) { http_response_code(404); exit("Berkas tidak ditemukan."); }
    if ($peran === 'guru' && $row['nip'] !== $uid) { http_response_code(403); exit("Akses ditolak."); }
    if ($peran === 'siswa') {
        $s = db_satu("SELECT ruang_kelas FROM siswa WHERE nis = ?", "s", [$uid]);
        if (!$s || $s['ruang_kelas'] !== $row['ruang_kelas']) { http_response_code(403); exit("Akses ditolak."); }
    }
    $path = MATERI_DIR . '/' . $row['file_path'];
    $nama = $row['nama_asli'];
} elseif ($jenis === 'soal') {
    $row = db_satu(
        "SELECT t.*, p.nip, p.ruang_kelas FROM tugas t JOIN pengampu p ON p.id_pengampu = t.id_pengampu WHERE t.id_tugas = ?",
        "i", [$id]
    );
    if (!$row || !$row['file_soal_path']) { http_response_code(404); exit("Lampiran soal tidak ditemukan."); }
    if ($peran === 'guru' && $row['nip'] !== $uid) { http_response_code(403); exit("Akses ditolak."); }
    if ($peran === 'siswa') {
        $s = db_satu("SELECT ruang_kelas FROM siswa WHERE nis = ?", "s", [$uid]);
        if (!$s || $s['ruang_kelas'] !== $row['ruang_kelas']) { http_response_code(403); exit("Akses ditolak."); }
    }
    $path = SOAL_DIR . '/' . $row['file_soal_path'];
    $nama = 'Lampiran_' . preg_replace('/[^A-Za-z0-9_\-.]/', '_', $row['judul']) . '.' . pathinfo($row['file_soal_path'], PATHINFO_EXTENSION);
} else {
    $row = db_satu(
        "SELECT x.*, p.nip FROM pengumpulan_tugas x
         JOIN tugas t ON t.id_tugas = x.id_tugas
         JOIN pengampu p ON p.id_pengampu = t.id_pengampu
         WHERE x.id = ?",
        "i", [$id]
    );
    if (!$row) { http_response_code(404); exit("Berkas tidak ditemukan."); }
    if ($peran === 'siswa' && $row['nis'] !== $uid) { http_response_code(403); exit("Akses ditolak."); }
    if ($peran === 'guru' && $row['nip'] !== $uid) { http_response_code(403); exit("Akses ditolak."); }
    $path = STORAGE_DIR . '/' . $row['file_path'];
    $nama = $row['nama_asli'];
}

if (!is_file($path)) {
    http_response_code(404);
    exit("File fisik tidak ditemukan di server.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($nama) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
