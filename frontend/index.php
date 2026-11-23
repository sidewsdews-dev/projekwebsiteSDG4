<?php
require_once 'auth.php';

$berita = db_semua("SELECT judul, isi, dibuat_pada FROM berita WHERE status = 'terbit' ORDER BY dibuat_pada DESC LIMIT 6");
$event  = db_semua("SELECT judul, deskripsi, tanggal_mulai, tanggal_selesai, lokasi FROM event WHERE status = 'terbit' ORDER BY tanggal_mulai ASC LIMIT 6");
$sudah_login = !empty($_SESSION['id']) && !empty($_SESSION['peran']);
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title>Portal Sekolah Terpadu | SMA Negeri Blater</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="portal-nav">
    <div class="container nav-inner">
      <a class="brand" href="index.php">
        <img src="asset/logo.png" alt="Logo" class="brand-logo" style="height: 36px; width: auto; object-fit: contain;">
        <div>
          <strong>Portal SMA Negeri Blater</strong>
          <small>Sistem Informasi Akademik Sekolah</small>
        </div>
      </a>
      <nav class="nav-links">
        <a href="#berita">Berita</a>
        <a href="#event">Agenda</a>
        <?php if ($sudah_login): ?>
          <a class="btn-primary" href="dashboard-<?= e($_SESSION['peran']) ?>.php">Dashboard</a>
        <?php else: ?>
          <a class="btn-outline" href="daftar.php">Daftar Akun</a>
          <a class="btn-primary" href="login.php">Masuk</a>
        <?php endif; ?>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container hero-grid">
      <div>
        <span class="hero-tag">Sistem Akademik Terpadu</span>
        <h1>Akses Layanan Akademik Sekolah dalam Satu Portal Terpadu</h1>
        <p>Kelola jadwal pelajaran, materi, pengumpulan tugas, penilaian, absensi kelas, serta informasi resmi sekolah secara terintegrasi.</p>
        <div class="hero-actions">
          <?php if ($sudah_login): ?>
            <a class="btn-primary" href="dashboard-<?= e($_SESSION['peran']) ?>.php">Buka Dashboard Saya</a>
            <a class="btn-outline" href="logout.php">Keluar</a>
          <?php else: ?>
            <a class="btn-primary" href="login.php">Masuk ke Portal</a>
            <a class="btn-outline" href="daftar.php">Registrasi Siswa / Guru</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <main class="container portal-section">
    <section id="berita">
      <div class="section-head">
        <h2>Berita &amp; Pengumuman Terbaru</h2>
        <p>Informasi resmi yang telah diterbitkan oleh administrator sekolah.</p>
      </div>
      <div class="cards">
        <?php foreach ($berita as $b): ?>
          <article class="card">
            <small class="meta"><?= e(tanggal_id($b['dibuat_pada'])) ?></small>
            <h3 style="margin:.35rem 0 .5rem;font-size:1.05rem;"><?= e($b['judul']) ?></h3>
            <p style="margin:0;color:var(--muted);line-height:1.55;"><?= e(ringkas($b['isi'], 200)) ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (!$berita): ?><p class="empty">Belum ada berita yang diterbitkan.</p><?php endif; ?>
      </div>
    </section>

    <section id="event" style="margin-top:2.5rem;">
      <div class="section-head">
        <h2>Agenda &amp; Kegiatan Sekolah</h2>
        <p>Jadwal kegiatan akademik maupun non-akademik mendatang.</p>
      </div>
      <div class="cards">
        <?php foreach ($event as $ev): ?>
          <article class="card">
            <span class="badge"><?= e(tanggal_id($ev['tanggal_mulai'])) ?><?= $ev['tanggal_selesai'] ? ' – ' . e(tanggal_id($ev['tanggal_selesai'])) : '' ?></span>
            <h3 style="margin:.6rem 0 .35rem;font-size:1.05rem;"><?= e($ev['judul']) ?></h3>
            <p style="margin:0 0 .5rem;color:var(--muted);font-size:.9rem;">Lokasi: <?= e($ev['lokasi'] ?: '-') ?></p>
            <p style="margin:0;color:var(--muted);line-height:1.5;"><?= e(ringkas($ev['deskripsi'] ?? '', 160)) ?></p>
          </article>
        <?php endforeach; ?>
        <?php if (!$event): ?><p class="empty">Belum ada agenda kegiatan.</p><?php endif; ?>
      </div>
    </section>
  </main>

  <footer class="portal-footer">
    <div class="container">
      <span>&copy; <?= date('Y') ?> Portal Sekolah Terpadu. Seluruh hak cipta dilindungi.</span>
      <a href="login.php">Login Sistem</a>
    </div>
  </footer>
</body>
</html>