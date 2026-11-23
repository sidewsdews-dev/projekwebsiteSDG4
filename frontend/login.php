<?php
require_once 'auth.php';

if (!empty($_SESSION['id']) && !empty($_SESSION['peran']) && isset(PERAN[$_SESSION['peran']])) {
    ke_dashboard($_SESSION['peran']);
}

$error = '';
$peran_pilih = $_POST['peran'] ?? 'siswa';
$identitas   = trim($_POST['identitas'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_cek();
    $sandi = $_POST['password'] ?? '';

    if (!isset(PERAN[$peran_pilih])) {
        $error = "Peran pengguna tidak valid.";
    } elseif ($identitas === '' || $sandi === '') {
        $error = "Identitas login dan kata sandi wajib diisi.";
    } else {
        $p = PERAN[$peran_pilih];
        $akun = db_satu(
            "SELECT * FROM {$p['tabel']} WHERE {$p['login']} = ? LIMIT 1",
            "s", [$identitas]
        );

        if ($akun && verifikasi_sandi($sandi, $akun['password'])) {
            // Cek status aktif per peran
            if ($peran_pilih === 'siswa' && $akun['status'] !== 'aktif') {
                $error = $akun['status'] === 'menunggu'
                    ? "Akun siswa Anda masih menunggu persetujuan Admin."
                    : "Akun siswa Anda sedang dinonaktifkan. Hubungi Admin.";
            } elseif ($peran_pilih !== 'siswa' && (int)$akun['aktif'] !== 1) {
                $error = "Akun Anda belum aktif atau menunggu persetujuan Admin.";
            } else {
                session_regenerate_id(true);
                $_SESSION['id']    = $akun[$p['kunci']];
                $_SESSION['peran'] = $peran_pilih;
                ke_dashboard($peran_pilih);
            }
        } else {
            $error = "Identitas atau kata sandi salah.";
        }
    }
}

$pesan = ambil_flash();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title>Masuk Portal | Sistem Akademik Satu Pintu</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
  <div class="auth-box">
    <a class="back-link" href="index.php">&larr; Kembali ke Beranda</a>
    <h1>Masuk Portal</h1>
    <p class="auth-sub">Login satu pintu untuk Siswa, Guru, dan Administrator.</p>

    <?php if ($pesan): ?>
      <p class="flash <?= $pesan['tipe'] === 'err' ? 'flash-err' : '' ?>"><?= e($pesan['pesan']) ?></p>
    <?php endif; ?>
    <?php if ($error): ?>
      <p class="flash flash-err" role="alert"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="stack-form">
      <?= csrf_input() ?>
      <label>
        <span>Masuk Sebagai</span>
        <select name="peran" id="pilih-peran" onchange="ubahLabelLogin()">
          <option value="siswa" <?= $peran_pilih === 'siswa' ? 'selected' : '' ?>>Siswa</option>
          <option value="guru"  <?= $peran_pilih === 'guru'  ? 'selected' : '' ?>>Guru</option>
          <option value="admin" <?= $peran_pilih === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </label>

      <label>
        <span id="label-identitas"><?= $peran_pilih === 'guru' ? 'NIP' : 'Username' ?></span>
        <input type="text" name="identitas" id="input-identitas" value="<?= e($identitas) ?>"
               placeholder="<?= $peran_pilih === 'guru' ? 'Masukkan NIP' : 'Masukkan username' ?>" required autofocus>
      </label>

      <label>
        <span>Kata Sandi</span>
        <input type="password" name="password" placeholder="Masukkan kata sandi" required>
      </label>

      <button class="btn-primary btn-block" type="submit">Masuk</button>
    </form>

    <p class="auth-foot">Belum punya akun? <a href="daftar.php">Daftar akun baru di sini</a></p>
  </div>

  <script>
    function ubahLabelLogin() {
      const p = document.getElementById('pilih-peran').value;
      const lbl = document.getElementById('label-identitas');
      const inp = document.getElementById('input-identitas');
      if (p === 'guru') {
        lbl.textContent = 'NIP (Nomor Induk Pegawai)';
        inp.placeholder = 'Contoh: 198001012005011001';
      } else {
        lbl.textContent = 'Username';
        inp.placeholder = 'Masukkan username';
      }
    }
    ubahLabelLogin();
  </script>
</body>
</html>
