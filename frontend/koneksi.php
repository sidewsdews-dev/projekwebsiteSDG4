<?php
$db_host = "localhost";
$db_user = "root";
$db_pass = "";
$db_nama = "dataprojek";

define('STORAGE_DIR', dirname(__DIR__, 2) . '/dataprojek_storage/pengumpulan');
define('MATERI_DIR',  dirname(__DIR__, 2) . '/dataprojek_storage/materi');
define('SOAL_DIR',    dirname(__DIR__, 2) . '/dataprojek_storage/soal');

date_default_timezone_set('Asia/Jakarta');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = mysqli_connect($db_host, $db_user, $db_pass, $db_nama);
    mysqli_set_charset($koneksi, "utf8mb4");
} catch (mysqli_sql_exception $ex) {
    http_response_code(500);
    exit("Tidak dapat terhubung ke database.");
}

function db_semua($sql, $tipe = "", $param = []) {
    global $koneksi;
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($tipe !== "") {
        mysqli_stmt_bind_param($stmt, $tipe, ...$param);
    }
    mysqli_stmt_execute($stmt);
    $hasil = mysqli_stmt_get_result($stmt);
    $baris = mysqli_fetch_all($hasil, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
    return $baris;
}

function db_satu($sql, $tipe = "", $param = []) {
    $baris = db_semua($sql, $tipe, $param);
    return $baris[0] ?? null;
}

function db_jalankan($sql, $tipe = "", $param = []) {
    global $koneksi;
    $stmt = mysqli_prepare($koneksi, $sql);
    if ($tipe !== "") {
        mysqli_stmt_bind_param($stmt, $tipe, ...$param);
    }
    mysqli_stmt_execute($stmt);
    $berubah = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $berubah;
}
