<?php
require_once 'auth.php';

if (!empty($_SESSION['id']) && !empty($_SESSION['peran'])) {
    ke_dashboard($_SESSION['peran']);
}

$kelas_list = db_semua("SELECT ruang_kelas, tahun_ajar FROM kelas ORDER BY ruang_kelas");
$error = '';
$jenis = $_POST['jenis'] ?? 'siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $nama     = trim($_POST['nama'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $konfirm  = $_POST['konfirmasi'] ?? '';

    if ($nama === '' || $password === '') {
        $error = "Nama lengkap dan kata sandi wajib diisi.";
    } elseif (strlen($password) < 6) {
        $error = "Kata sandi minimal 6 karakter.";
    } elseif ($password !== $konfirm) {
        $error = "Konfirmasi kata sandi tidak cocok.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            if ($jenis === 'siswa') {
                $nis      = trim($_POST['nis'] ?? '');
                $username = trim($_POST['username'] ?? '');
                $angkatan = trim($_POST['angkatan'] ?? date('Y'));
                $kelas    = trim($_POST['ruang_kelas'] ?? '');

                if ($nis === '' || $username === '') {
                    $error = "NIS dan username wajib diisi untuk pendaftaran siswa.";
                } else {
                    $cek_nis = db_satu("SELECT nis FROM siswa WHERE nis = ?", "s", [$nis]);
                    $cek_usr = db_satu("SELECT nis FROM siswa WHERE username = ?", "s", [$username]);
                    if ($cek_nis) {
                        $error = "NIS sudah terdaftar.";
                    } elseif ($cek_usr) {
                        $error = "Username sudah digunakan.";
                    } else {
                        db_jalankan(
                            "INSERT INTO siswa (nis, nama_siswa, username, password, email, angkatan, ruang_kelas, status)
                             VALUES (?, ?, ?, ?, ?, ?, ?, 'menunggu')",
                            "sssssss",
                            [
                                $nis,
                                $nama,
                                $username,
                                $hash,
                                $email !== '' ? $email : null,
                                $angkatan !== '' ? (int)$angkatan : null,
                                $kelas !== '' ? $kelas : null
                            ]
                        );
                        flash("Pendaftaran siswa berhasil! Menunggu persetujuan Administrator sebelum bisa login.");
                        header("Location: login.php");
                        exit;
                    }
                }
            } elseif ($jenis === 'guru') {
                $nip     = trim($_POST['nip'] ?? '');
                $jabatan = trim($_POST['jabatan'] ?? '');
                $status_g = trim($_POST['status_guru'] ?? '');

                if ($nip === '') {
                    $error = "NIP wajib diisi untuk pendaftaran guru.";
                } else {
                    $cek_nip = db_satu("SELECT nip FROM guru WHERE nip = ?", "s", [$nip]);
                    if ($cek_nip) {
                        $error = "NIP sudah terdaftar.";
                    } else {
                        db_jalankan(
                            "INSERT INTO guru (nip, nama_guru, password, email, jabatan, status_guru, aktif)
                             VALUES (?, ?, ?, ?, ?, ?, 0)",
                            "ssssss",
                            [
                                $nip,
                                $nama,
                                $hash,
                                $email !== '' ? $email : null,
                                $jabatan !== '' ? $jabatan : null,
                                $status_g !== '' ? $status_g : null
                            ]
                        );
                        flash("Pendaftaran guru berhasil! Menunggu persetujuan Administrator sebelum bisa login.");
                        header("Location: login.php");
                        exit;
                    }
                }
            } else {
                $error = "Jenis pendaftaran tidak valid.";
            }
        } catch (mysqli_sql_exception $ex) {
            $error = "Gagal menyimpan pendaftaran. Pastikan data unik (NIS/NIP/Username) belum terdaftar.";
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title>Registrasi Akun | Sistem Akademik Satu Pintu</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
  <div class="auth-box" style="max-width:480px;">
    <a class="back-link" href="index.php">&larr; Kembali ke Beranda</a>
    <h1>Registrasi Akun Baru</h1>
    <p class="auth-sub">Akun baru akan diverifikasi dan disetujui oleh Admin sebelum dapat digunakan.</p>

    <?php if ($error): ?>
      <p class="flash flash-err" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="stack-form">
      <?= csrf_input() ?>
      <label>
        <span>Daftar Sebagai</span>
        <select name="jenis" id="jenis-daftar" onchange="toggleFormDaftar()">
          <option value="siswa" <?= $jenis === 'siswa' ? 'selected' : '' ?>>Siswa</option>
          <option value="guru"  <?= $jenis === 'guru'  ? 'selected' : '' ?>>Guru</option>
        </select>
      </label>

      <label>
        <span>Nama Lengkap</span>
        <input type="text" name="nama" value="<?= e($_POST['nama'] ?? '') ?>" required>
      </label>

      <div id="field-siswa" class="stack-form">
        <label>
          <span>NIS (Nomor Induk Siswa)</span>
          <input type="text" name="nis" value="<?= e($_POST['nis'] ?? '') ?>" placeholder="Contoh: 27010005">
        </label>
        <label>
          <span>Username Login</span>
          <input type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" placeholder="Tanpa spasi">
        </label>
        <div class="grid-2">
          <label>
            <span>Angkatan</span>
            <input type="number" name="angkatan" value="<?= e($_POST['angkatan'] ?? date('Y')) ?>" min="2000" max="2099">
          </label>
          <label>
            <span>Kelas (Opsional)</span>
            <select name="ruang_kelas">
              <option value="">-- Ditentukan Admin --</option>
              <?php foreach ($kelas_list as $k): ?>
                <option value="<?= e($k['ruang_kelas']) ?>" <?= ($_POST['ruang_kelas'] ?? '') === $k['ruang_kelas'] ? 'selected' : '' ?>>
                  <?= e($k['ruang_kelas']) ?> (<?= e($k['tahun_ajar']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </label>
        </div>
      </div>

      <div id="field-guru" class="stack-form" hidden>
        <label>
          <span>NIP (Nomor Induk Pegawai)</span>
          <input type="text" name="nip" value="<?= e($_POST['nip'] ?? '') ?>" placeholder="18 digit NIP">
        </label>
        <div class="grid-2">
          <label>
            <span>Jabatan</span>
            <input type="text" name="jabatan" value="<?= e($_POST['jabatan'] ?? '') ?>" placeholder="Mis. Guru Fisika">
          </label>
          <label>
            <span>Status Kepegawaian</span>
            <select name="status_guru">
              <option value="PNS">PNS</option>
              <option value="PPPK">PPPK</option>
              <option value="Honorer">Honorer</option>
            </select>
          </label>
        </div>
      </div>

      <label>
        <span>Email</span>
        <input type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" placeholder="nama@contoh.sch.id">
      </label>

      <div class="grid-2">
        <label>
          <span>Kata Sandi</span>
          <input type="password" name="password" minlength="6" required>
        </label>
        <label>
          <span>Ulangi Kata Sandi</span>
          <input type="password" name="konfirmasi" minlength="6" required>
        </label>
      </div>

      <button class="btn-primary btn-block" type="submit">Kirim Pendaftaran</button>
    </form>

    <p class="auth-foot">Sudah punya akun aktif? <a href="login.php">Masuk di sini</a></p>
  </div>

  <script>
    function toggleFormDaftar() {
      const j = document.getElementById('jenis-daftar').value;
      document.getElementById('field-siswa').hidden = (j !== 'siswa');
      document.getElementById('field-guru').hidden  = (j !== 'guru');
    }
    toggleFormDaftar();
  </script>
</body>
</html>
