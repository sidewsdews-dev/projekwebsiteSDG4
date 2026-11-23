<?php
require_once __DIR__ . '/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const PERAN = [
    'siswa' => ['tabel' => 'siswa', 'kunci' => 'nis',      'login' => 'username', 'nama' => 'nama_siswa', 'aktif' => "status = 'aktif'"],
    'guru'  => ['tabel' => 'guru',  'kunci' => 'nip',      'login' => 'nip',      'nama' => 'nama_guru',  'aktif' => "aktif = 1"],
    'admin' => ['tabel' => 'admin', 'kunci' => 'id_admin', 'login' => 'username', 'nama' => 'nama',       'aktif' => "aktif = 1"],
];

function wajib_login($peran) {
    if (empty($_SESSION['id']) || empty($_SESSION['peran'])) {
        header("Location: login.php");
        exit;
    }
    if ($_SESSION['peran'] !== $peran) {
        http_response_code(403);
        exit("Akses ditolak.");
    }
    $p = PERAN[$peran];
    $akun = db_satu(
        "SELECT * FROM {$p['tabel']} WHERE {$p['kunci']} = ? AND {$p['aktif']} LIMIT 1",
        "s", [$_SESSION['id']]
    );
    if ($akun === null) {
        keluar();
    }
    $akun['_nama'] = $akun[$p['nama']];
    $akun['_id']   = $akun[$p['kunci']];
    return $akun;
}

function keluar() {
    $_SESSION = [];
    session_destroy();
    header("Location: login.php");
    exit;
}

function ke_dashboard($peran) {
    header("Location: dashboard-" . $peran . ".php");
    exit;
}

function csrf_token() {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_input() {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}
function csrf_cek() {
    $kirim = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf'] ?? '', $kirim)) {
        http_response_code(400);
        exit("Permintaan tidak valid. Muat ulang halaman lalu coba lagi.");
    }
}

function flash($pesan, $tipe = 'ok') {
    $_SESSION['flash'] = ['pesan' => $pesan, 'tipe' => $tipe];
}
function ambil_flash() {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (is_string($f)) return ['pesan' => $f, 'tipe' => 'ok'];
    return $f;
}

function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

function tanggal_id($waktu, $dengan_jam = false) {
    if (!$waktu) return '-';
    $bulan = [1=>'Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
    $t = strtotime($waktu);
    $s = date('j', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
    return $dengan_jam ? $s . ', ' . date('H.i', $t) : $s;
}

function angka($nilai) {
    if ($nilai === null || $nilai === '') return '-';
    return rtrim(rtrim(number_format((float)$nilai, 2, ',', ''), '0'), ',');
}

function nama_hari_ini() {
    $hari = [1=>'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    return $hari[(int)date('N')] ?? null;
}

function inisial($nama) {
    return preg_match('/./u', (string)$nama, $m) ? mb_strtoupper($m[0]) : '?';
}

function ringkas($teks, $maks = 140) {
    $teks = trim((string)$teks);
    if (mb_strlen($teks) <= $maks) return $teks;
    return mb_substr($teks, 0, $maks) . '…';
}

function verifikasi_sandi($input, $simpan) {
    if (password_verify($input, $simpan)) return true;
    return hash_equals((string)$simpan, (string)$input);
}
