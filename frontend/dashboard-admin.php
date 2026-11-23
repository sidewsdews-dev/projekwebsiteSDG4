<?php
require_once 'auth.php';
$u = wajib_login('admin');

$jml_guru     = db_satu("SELECT COUNT(*) AS n FROM guru WHERE aktif = 1")['n'];
$jml_siswa    = db_satu("SELECT COUNT(*) AS n FROM siswa WHERE status = 'aktif'")['n'];
$jml_kelas    = db_satu("SELECT COUNT(*) AS n FROM kelas")['n'];
$jml_menunggu = db_satu("SELECT (SELECT COUNT(*) FROM siswa WHERE status='menunggu') + (SELECT COUNT(*) FROM guru WHERE aktif=0) AS n")['n'];

$menunggu_siswa = db_semua("SELECT * FROM siswa WHERE status = 'menunggu' ORDER BY dibuat_pada DESC");
$menunggu_guru  = db_semua("SELECT * FROM guru WHERE aktif = 0 ORDER BY dibuat_pada DESC");

$guru_list  = db_semua("SELECT * FROM guru ORDER BY aktif DESC, nama_guru ASC");
$siswa_list = db_semua("SELECT * FROM siswa ORDER BY FIELD(status,'menunggu','aktif','nonaktif'), ruang_kelas, nama_siswa");
$admin_list = db_semua("SELECT * FROM admin ORDER BY id_admin ASC");
$kelas_list = db_semua("SELECT k.*, g.nama_guru AS wali FROM kelas k LEFT JOIN guru g ON g.nip = k.nip_wali ORDER BY k.ruang_kelas");
$mapel_list = db_semua("SELECT * FROM mata_pelajaran ORDER BY nama_mapel");
$pengampu_list = db_semua(
    "SELECT p.*, g.nama_guru, m.nama_mapel FROM pengampu p
     JOIN guru g ON g.nip = p.nip
     JOIN mata_pelajaran m ON m.idmapel = p.idmapel
     ORDER BY p.ruang_kelas, m.nama_mapel"
);
$jadwal_list = db_semua(
    "SELECT j.*, p.ruang_kelas, m.nama_mapel, g.nama_guru FROM jadwal j
     JOIN pengampu p ON p.id_pengampu = j.id_pengampu
     JOIN mata_pelajaran m ON m.idmapel = p.idmapel
     JOIN guru g ON g.nip = p.nip
     ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai"
);
$berita_list = db_semua("SELECT b.*, a.nama AS penulis FROM berita b JOIN admin a ON a.id_admin = b.id_admin ORDER BY b.dibuat_pada DESC");
$event_list  = db_semua("SELECT ev.*, a.nama AS penulis FROM event ev JOIN admin a ON a.id_admin = ev.id_admin ORDER BY ev.tanggal_mulai DESC");

$edit_guru   = isset($_GET['edit_guru']) ? db_satu("SELECT * FROM guru WHERE nip = ?", "s", [$_GET['edit_guru']]) : null;
$edit_siswa  = isset($_GET['edit_siswa']) ? db_satu("SELECT * FROM siswa WHERE nis = ?", "s", [$_GET['edit_siswa']]) : null;
$edit_berita = isset($_GET['edit_berita']) ? db_satu("SELECT * FROM berita WHERE id_berita = ?", "i", [(int)$_GET['edit_berita']]) : null;
$edit_event  = isset($_GET['edit_event']) ? db_satu("SELECT * FROM event WHERE id_event = ?", "i", [(int)$_GET['edit_event']]) : null;

$pesan = ambil_flash();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title>Dashboard Admin | Portal Satu Pintu</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar" aria-label="Identitas admin">
      <div class="avatar" aria-hidden="true"><?= e(inisial($u['_nama'])) ?></div>
      <div class="who"><strong><?= e($u['_nama']) ?></strong><span>@<?= e($u['username']) ?></span></div>
      <dl>
        <div><dt>Peran</dt><dd>Administrator</dd></div>
        <div><dt>Email</dt><dd><?= e($u['email'] ?? '-') ?></dd></div>
        <div><dt>Menunggu ACC</dt><dd><?= (int)$jml_menunggu ?> akun</dd></div>
      </dl>
      <a class="logout" href="index.php" style="margin-bottom:.5rem;">Beranda Portal</a>
      <a class="logout" href="logout.php">Keluar</a>
    </aside>

    <main class="main">
      <div class="topbar">
        <div><small>Dashboard Administrator</small><h1>Halo, <?= e(explode(' ', $u['_nama'])[0]) ?></h1></div>
        <div class="pill">Administrator</div>
      </div>

      <?php if ($pesan): ?>
        <p class="flash <?= $pesan['tipe'] === 'err' ? 'flash-err' : '' ?>" role="status"><?= e($pesan['pesan']) ?></p>
      <?php endif; ?>

      <nav class="tabs" aria-label="Menu admin">
        <a href="#ringkasan" data-tab="ringkasan">Ringkasan</a>
        <a href="#persetujuan" data-tab="persetujuan">Persetujuan Akun (<?= (int)$jml_menunggu ?>)</a>
        <a href="#guru" data-tab="guru">Data Guru</a>
        <a href="#siswa" data-tab="siswa">Data Siswa</a>
        <a href="#akun" data-tab="akun">Akun Admin</a>
        <a href="#jadwal" data-tab="jadwal">Kelas &amp; Jadwal</a>
        <a href="#konten" data-tab="konten">Konten Website</a>
      </nav>

      <!-- PANEL 1: RINGKASAN -->
      <div class="panel" data-panel="ringkasan">
        <div class="cards">
          <section class="card">
            <h2>Statistik Sekolah</h2>
            <div class="row"><span>Guru aktif</span><span><?= (int)$jml_guru ?></span></div>
            <div class="row"><span>Siswa aktif</span><span><?= (int)$jml_siswa ?></span></div>
            <div class="row"><span>Rombongan kelas</span><span><?= (int)$jml_kelas ?></span></div>
            <div class="row"><span>Pendaftaran menunggu</span><span><?= (int)$jml_menunggu ?></span></div>
          </section>

          <section class="card">
            <h2>Pintasan Manajemen</h2>
            <div class="row"><span>Persetujuan akun baru</span><a href="#persetujuan" onclick="bukaTab('persetujuan')">Buka &rarr;</a></div>
            <div class="row"><span>Kelola data guru &amp; siswa</span><a href="#guru" onclick="bukaTab('guru')">Buka &rarr;</a></div>
            <div class="row"><span>Atur kelas, pengampu &amp; jadwal</span><a href="#jadwal" onclick="bukaTab('jadwal')">Buka &rarr;</a></div>
            <div class="row"><span>Publikasi berita &amp; event</span><a href="#konten" onclick="bukaTab('konten')">Buka &rarr;</a></div>
          </section>
        </div>
      </div>

      <!-- PANEL 2: PERSETUJUAN PENDAFTARAN -->
      <div class="panel" data-panel="persetujuan" hidden>
        <h2 class="panel-title">Persetujuan Pendaftaran Akun Baru</h2>
        <div class="cards">
          <section class="card">
            <h2>Pendaftaran Siswa Menunggu (<?= count($menunggu_siswa) ?>)</h2>
            <?php foreach ($menunggu_siswa as $ms): ?>
              <div class="item">
                <strong><?= e($ms['nama_siswa']) ?></strong>
                <div class="meta">NIS: <?= e($ms['nis']) ?> · User: <?= e($ms['username']) ?> · Angkatan: <?= e($ms['angkatan'] ?? '-') ?> · Daftar: <?= e(tanggal_id($ms['dibuat_pada'])) ?></div>
                <form class="inline-form" method="post" action="akun_aksi.php" style="margin-top:.5rem;">
                  <?= csrf_input() ?>
                  <input type="hidden" name="jenis" value="siswa">
                  <input type="hidden" name="id" value="<?= e($ms['nis']) ?>">
                  <select name="ruang_kelas" title="Tetapkan kelas">
                    <option value="">-- Tetapkan Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                      <option value="<?= e($k['ruang_kelas']) ?>" <?= $ms['ruang_kelas'] === $k['ruang_kelas'] ? 'selected' : '' ?>><?= e($k['ruang_kelas']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn-small" type="submit" name="aksi" value="setujui">Setujui &amp; Aktifkan</button>
                  <button class="btn-small btn-danger" type="submit" name="aksi" value="tolak" onclick="return confirm('Tolak dan hapus pendaftaran siswa ini?')">Tolak</button>
                </form>
              </div>
            <?php endforeach; ?>
            <?php if (!$menunggu_siswa): ?><p class="empty">Tidak ada pendaftaran siswa yang menunggu.</p><?php endif; ?>
          </section>

          <section class="card">
            <h2>Pendaftaran Guru Menunggu (<?= count($menunggu_guru) ?>)</h2>
            <?php foreach ($menunggu_guru as $mg): ?>
              <div class="item">
                <strong><?= e($mg['nama_guru']) ?></strong>
                <div class="meta">NIP: <?= e($mg['nip']) ?> · <?= e($mg['jabatan'] ?? '-') ?> (<?= e($mg['status_guru'] ?? '-') ?>) · <?= e(tanggal_id($mg['dibuat_pada'])) ?></div>
                <form class="inline-form" method="post" action="akun_aksi.php" style="margin-top:.5rem;">
                  <?= csrf_input() ?>
                  <input type="hidden" name="jenis" value="guru">
                  <input type="hidden" name="id" value="<?= e($mg['nip']) ?>">
                  <button class="btn-small" type="submit" name="aksi" value="setujui">Setujui &amp; Aktifkan</button>
                  <button class="btn-small btn-danger" type="submit" name="aksi" value="tolak" onclick="return confirm('Tolak dan hapus pendaftaran guru ini?')">Tolak</button>
                </form>
              </div>
            <?php endforeach; ?>
            <?php if (!$menunggu_guru): ?><p class="empty">Tidak ada pendaftaran guru yang menunggu.</p><?php endif; ?>
          </section>
        </div>
      </div>

      <!-- PANEL 3: KELOLA DATA GURU -->
      <div class="panel" data-panel="guru" hidden>
        <h2 class="panel-title">Manajemen Data Guru</h2>
        <section class="card" style="margin-bottom:1.25rem;">
          <h3><?= $edit_guru ? 'Edit Data Guru: ' . e($edit_guru['nama_guru']) : 'Tambah Guru Baru' ?></h3>
          <form method="post" action="admin_aksi.php" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="modul" value="<?= $edit_guru ? 'edit_guru' : 'tambah_guru' ?>">
            <input type="hidden" name="tab" value="guru">
            <label><span>NIP</span><input type="text" name="nip" value="<?= e($edit_guru['nip'] ?? '') ?>" <?= $edit_guru ? 'readonly' : 'required' ?>></label>
            <label><span>Nama Lengkap</span><input type="text" name="nama_guru" value="<?= e($edit_guru['nama_guru'] ?? '') ?>" required></label>
            <label><span>Email</span><input type="email" name="email" value="<?= e($edit_guru['email'] ?? '') ?>"></label>
            <label><span>Jabatan</span><input type="text" name="jabatan" value="<?= e($edit_guru['jabatan'] ?? '') ?>" placeholder="Guru Matematika"></label>
            <label><span>Status Kepegawaian</span><input type="text" name="status_guru" value="<?= e($edit_guru['status_guru'] ?? 'PNS') ?>"></label>
            <label><span>Password <?= $edit_guru ? '(Kosongkan jika tetap)' : '' ?></span><input type="password" name="password" <?= $edit_guru ? '' : 'required' ?>></label>
            <?php if ($edit_guru): ?>
              <label><span>Status Akun</span>
                <select name="aktif">
                  <option value="1" <?= (int)$edit_guru['aktif'] === 1 ? 'selected' : '' ?>>Aktif</option>
                  <option value="0" <?= (int)$edit_guru['aktif'] === 0 ? 'selected' : '' ?>>Nonaktif</option>
                </select>
              </label>
            <?php endif; ?>
            <div style="display:flex;gap:.5rem;align-items:end;">
              <button class="btn-primary" type="submit"><?= $edit_guru ? 'Simpan Perubahan' : 'Tambah Guru' ?></button>
              <?php if ($edit_guru): ?><a class="btn-outline" href="dashboard-admin.php#guru">Batal</a><?php endif; ?>
            </div>
          </form>
        </section>

        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>NIP</th><th>Nama Guru</th><th>Jabatan</th><th>Status</th><th>Akun</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($guru_list as $g): ?>
                <tr>
                  <td><?= e($g['nip']) ?></td>
                  <td><strong><?= e($g['nama_guru']) ?></strong><br><small class="meta"><?= e($g['email'] ?? '-') ?></small></td>
                  <td><?= e($g['jabatan'] ?? '-') ?></td>
                  <td><?= e($g['status_guru'] ?? '-') ?></td>
                  <td><span class="badge<?= (int)$g['aktif'] ? '' : ' badge-late' ?>"><?= (int)$g['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                  <td>
                    <a class="btn-small" href="dashboard-admin.php?edit_guru=<?= urlencode($g['nip']) ?>#guru">Edit</a>
                    <form method="post" action="admin_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_guru">
                      <input type="hidden" name="tab" value="guru">
                      <input type="hidden" name="nip" value="<?= e($g['nip']) ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus data guru ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PANEL 4: KELOLA DATA SISWA -->
      <div class="panel" data-panel="siswa" hidden>
        <h2 class="panel-title">Manajemen Data Siswa</h2>
        <section class="card" style="margin-bottom:1.25rem;">
          <h3><?= $edit_siswa ? 'Edit Data Siswa: ' . e($edit_siswa['nama_siswa']) : 'Tambah Siswa Baru' ?></h3>
          <form method="post" action="admin_aksi.php" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="modul" value="<?= $edit_siswa ? 'edit_siswa' : 'tambah_siswa' ?>">
            <input type="hidden" name="tab" value="siswa">
            <label><span>NIS</span><input type="text" name="nis" value="<?= e($edit_siswa['nis'] ?? '') ?>" <?= $edit_siswa ? 'readonly' : 'required' ?>></label>
            <label><span>Nama Siswa</span><input type="text" name="nama_siswa" value="<?= e($edit_siswa['nama_siswa'] ?? '') ?>" required></label>
            <label><span>Username</span><input type="text" name="username" value="<?= e($edit_siswa['username'] ?? '') ?>" required></label>
            <label><span>Email</span><input type="email" name="email" value="<?= e($edit_siswa['email'] ?? '') ?>"></label>
            <label><span>Angkatan</span><input type="number" name="angkatan" value="<?= e($edit_siswa['angkatan'] ?? date('Y')) ?>"></label>
            <label><span>Kelas</span>
              <select name="ruang_kelas">
                <option value="">-- Belum ditempatkan --</option>
                <?php foreach ($kelas_list as $k): ?>
                  <option value="<?= e($k['ruang_kelas']) ?>" <?= ($edit_siswa['ruang_kelas'] ?? '') === $k['ruang_kelas'] ? 'selected' : '' ?>><?= e($k['ruang_kelas']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label><span>Status</span>
              <select name="status">
                <?php foreach (['aktif','menunggu','nonaktif'] as $st): ?>
                  <option value="<?= $st ?>" <?= ($edit_siswa['status'] ?? 'aktif') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label><span>Password <?= $edit_siswa ? '(Kosongkan jika tetap)' : '' ?></span><input type="password" name="password" <?= $edit_siswa ? '' : 'required' ?>></label>
            <div style="display:flex;gap:.5rem;align-items:end;">
              <button class="btn-primary" type="submit"><?= $edit_siswa ? 'Simpan Perubahan' : 'Tambah Siswa' ?></button>
              <?php if ($edit_siswa): ?><a class="btn-outline" href="dashboard-admin.php#siswa">Batal</a><?php endif; ?>
            </div>
          </form>
        </section>

        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>NIS</th><th>Nama Siswa</th><th>Username</th><th>Kelas</th><th>Angkatan</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
              <?php foreach ($siswa_list as $s): ?>
                <tr>
                  <td><?= e($s['nis']) ?></td>
                  <td><strong><?= e($s['nama_siswa']) ?></strong><br><small class="meta"><?= e($s['email'] ?? '-') ?></small></td>
                  <td><?= e($s['username']) ?></td>
                  <td><?= e($s['ruang_kelas'] ?? '-') ?></td>
                  <td><?= e($s['angkatan'] ?? '-') ?></td>
                  <td><span class="badge<?= $s['status'] === 'aktif' ? '' : ' badge-late' ?>"><?= e(ucfirst($s['status'])) ?></span></td>
                  <td>
                    <a class="btn-small" href="dashboard-admin.php?edit_siswa=<?= urlencode($s['nis']) ?>#siswa">Edit</a>
                    <form method="post" action="admin_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_siswa">
                      <input type="hidden" name="tab" value="siswa">
                      <input type="hidden" name="nis" value="<?= e($s['nis']) ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus data siswa ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PANEL 5: KELOLA AKUN ADMIN -->
      <div class="panel" data-panel="akun" hidden>
        <h2 class="panel-title">Manajemen Akun Administrator</h2>
        <section class="card" style="margin-bottom:1.25rem;">
          <h3>Tambah Administrator Baru</h3>
          <form method="post" action="admin_aksi.php" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="modul" value="tambah_admin">
            <input type="hidden" name="tab" value="akun">
            <label><span>Nama Lengkap</span><input type="text" name="nama" required></label>
            <label><span>Username</span><input type="text" name="username" required></label>
            <label><span>Email</span><input type="email" name="email"></label>
            <label><span>Password</span><input type="password" name="password" required></label>
            <div style="display:flex;align-items:end;"><button class="btn-primary" type="submit">Tambah Admin</button></div>
          </form>
        </section>

        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Nama</th><th>Username</th><th>Email</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($admin_list as $ad): ?>
                <tr>
                  <td><?= (int)$ad['id_admin'] ?></td>
                  <td><?= e($ad['nama']) ?></td>
                  <td><?= e($ad['username']) ?></td>
                  <td><?= e($ad['email'] ?? '-') ?></td>
                  <td><span class="badge<?= (int)$ad['aktif'] ? '' : ' badge-late' ?>"><?= (int)$ad['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                  <td>
                    <?php if ((int)$ad['id_admin'] !== (int)$u['id_admin']): ?>
                      <form method="post" action="admin_aksi.php" style="display:inline;">
                        <?= csrf_input() ?>
                        <input type="hidden" name="modul" value="toggle_admin">
                        <input type="hidden" name="tab" value="akun">
                        <input type="hidden" name="id_admin" value="<?= (int)$ad['id_admin'] ?>">
                        <button class="btn-small" type="submit"><?= (int)$ad['aktif'] ? 'Nonaktifkan' : 'Aktifkan' ?></button>
                      </form>
                    <?php else: ?>
                      <small class="meta">(Akun Anda)</small>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PANEL 6: KELOLA KELAS, MAPEL, PENGAMPU & JADWAL -->
      <div class="panel" data-panel="jadwal" hidden>
        <h2 class="panel-title">Manajemen Kelas, Pengampu &amp; Jadwal Pelajaran</h2>
        <div class="cards" style="margin-bottom:1.25rem;">
          <section class="card">
            <h3>Tambah / Update Kelas</h3>
            <form method="post" action="admin_aksi.php" class="stack-form">
              <?= csrf_input() ?>
              <input type="hidden" name="modul" value="tambah_kelas">
              <input type="hidden" name="tab" value="jadwal">
              <div class="grid-2">
                <label><span>Ruang Kelas</span><input type="text" name="ruang_kelas" placeholder="Mis. X-3" required></label>
                <label><span>Tahun Ajar</span><input type="text" name="tahun_ajar" value="2026/2027" required></label>
              </div>
              <label><span>Wali Kelas</span>
                <select name="nip_wali">
                  <option value="">-- Pilih Wali Kelas --</option>
                  <?php foreach ($guru_list as $g): if ((int)$g['aktif'] !== 1) continue; ?>
                    <option value="<?= e($g['nip']) ?>"><?= e($g['nama_guru']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <button class="btn-primary" type="submit">Simpan Kelas</button>
            </form>
          </section>

          <section class="card">
            <h3>Tambah Mata Pelajaran</h3>
            <form method="post" action="admin_aksi.php" class="stack-form">
              <?= csrf_input() ?>
              <input type="hidden" name="modul" value="tambah_mapel">
              <input type="hidden" name="tab" value="jadwal">
              <div class="grid-2">
                <label><span>Kode Mapel</span><input type="text" name="idmapel" placeholder="Mis. KIM" maxlength="10" required></label>
                <label><span>Nama Mapel</span><input type="text" name="nama_mapel" placeholder="Kimia" required></label>
              </div>
              <button class="btn-primary" type="submit">Simpan Mapel</button>
            </form>
          </section>

          <section class="card">
            <h3>Tetapkan Guru Pengampu</h3>
            <form method="post" action="admin_aksi.php" class="stack-form">
              <?= csrf_input() ?>
              <input type="hidden" name="modul" value="tambah_pengampu">
              <input type="hidden" name="tab" value="jadwal">
              <label><span>Guru</span>
                <select name="nip" required>
                  <?php foreach ($guru_list as $g): if ((int)$g['aktif'] !== 1) continue; ?>
                    <option value="<?= e($g['nip']) ?>"><?= e($g['nama_guru']) ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
              <div class="grid-2">
                <label><span>Mata Pelajaran</span>
                  <select name="idmapel" required>
                    <?php foreach ($mapel_list as $m): ?>
                      <option value="<?= e($m['idmapel']) ?>"><?= e($m['nama_mapel']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label><span>Kelas</span>
                  <select name="ruang_kelas" required>
                    <?php foreach ($kelas_list as $k): ?>
                      <option value="<?= e($k['ruang_kelas']) ?>"><?= e($k['ruang_kelas']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </div>
              <button class="btn-primary" type="submit">Tambah Pengampu</button>
            </form>
          </section>

          <section class="card">
            <h3>Tambah Jadwal Pelajaran</h3>
            <form method="post" action="admin_aksi.php" class="stack-form">
              <?= csrf_input() ?>
              <input type="hidden" name="modul" value="tambah_jadwal">
              <input type="hidden" name="tab" value="jadwal">
              <label><span>Kelas · Mapel · Guru</span>
                <select name="id_pengampu" required>
                  <?php foreach ($pengampu_list as $p): ?>
                    <option value="<?= (int)$p['id_pengampu'] ?>"><?= e($p['ruang_kelas']) ?> · <?= e($p['nama_mapel']) ?> (<?= e($p['nama_guru']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </label>
              <div class="grid-3">
                <label><span>Hari</span>
                  <select name="hari">
                    <?php foreach (['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'] as $h): ?>
                      <option value="<?= $h ?>"><?= $h ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
                <label><span>Mulai</span><input type="time" name="jam_mulai" value="07:30" required></label>
                <label><span>Selesai</span><input type="time" name="jam_selesai" value="09:00" required></label>
              </div>
              <button class="btn-primary" type="submit">Tambah Jadwal</button>
            </form>
          </section>
        </div>

        <h3>Daftar Jadwal Aktif</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mata Pelajaran</th><th>Guru Pengampu</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($jadwal_list as $j): ?>
                <tr>
                  <td><?= e($j['hari']) ?></td>
                  <td><?= e(substr($j['jam_mulai'],0,5) . ' - ' . substr($j['jam_selesai'],0,5)) ?></td>
                  <td><?= e($j['ruang_kelas']) ?></td>
                  <td><?= e($j['nama_mapel']) ?></td>
                  <td><?= e($j['nama_guru']) ?></td>
                  <td>
                    <form method="post" action="admin_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_jadwal">
                      <input type="hidden" name="tab" value="jadwal">
                      <input type="hidden" name="id_jadwal" value="<?= (int)$j['id_jadwal'] ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus jadwal ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- PANEL 7: KELOLA KONTEN WEBSITE (BERITA & EVENT) -->
      <div class="panel" data-panel="konten" hidden>
        <h2 class="panel-title">Manajemen Konten Website (Berita &amp; Agenda)</h2>
        <div class="cards" style="margin-bottom:1.25rem;">
          <section class="card">
            <h3><?= $edit_berita ? 'Edit Berita' : 'Tulis Berita Baru' ?></h3>
            <form method="post" action="admin_aksi.php" class="stack-form">
              <?= csrf_input() ?>
              <input type="hidden" name="modul" value="simpan_berita">
              <input type="hidden" name="tab" value="konten">
              <input type="hidden" name="id_berita" value="<?= (int)($edit_berita['id_berita'] ?? 0) ?>">
              <label><span>Judul Berita</span><input type="text" name="judul" value="<?= e($edit_berita['judul'] ?? '') ?>" required></label>
              <label><span>Isi Berita</span><textarea name="isi" rows="4" required><?= e($edit_berita['isi'] ?? '') ?></textarea></label>
              <label><span>Status Publikasi</span>
                <select name="status">
                  <option value="terbit" <?= ($edit_berita['status'] ?? 'terbit') === 'terbit' ? 'selected' : '' ?>>Terbit (Tampil di Portal)</option>
                  <option value="draf"   <?= ($edit_berita['status'] ?? '') === 'draf' ? 'selected' : '' ?>>Draf (Sembunyikan)</option>
                </select>
              </label>
              <div style="display:flex;gap:.5rem;">
                <button class="btn-primary" type="submit">Simpan Berita</button>
                <?php if ($edit_berita): ?><a class="btn-outline" href="dashboard-admin.php#konten">Batal</a><?php endif; ?>
              </div>
            </form>
          </section>

          <section class="card">
            <h3><?= $edit_event ? 'Edit Agenda Event' : 'Tambah Agenda Event' ?></h3>
            <form method="post" action="admin_aksi.php" class="stack-form">
              <?= csrf_input() ?>
              <input type="hidden" name="modul" value="simpan_event">
              <input type="hidden" name="tab" value="konten">
              <input type="hidden" name="id_event" value="<?= (int)($edit_event['id_event'] ?? 0) ?>">
              <label><span>Nama Kegiatan</span><input type="text" name="judul" value="<?= e($edit_event['judul'] ?? '') ?>" required></label>
              <div class="grid-2">
                <label><span>Tanggal Mulai</span><input type="date" name="tanggal_mulai" value="<?= e($edit_event['tanggal_mulai'] ?? '') ?>" required></label>
                <label><span>Tanggal Selesai</span><input type="date" name="tanggal_selesai" value="<?= e($edit_event['tanggal_selesai'] ?? '') ?>"></label>
              </div>
              <label><span>Lokasi</span><input type="text" name="lokasi" value="<?= e($edit_event['lokasi'] ?? '') ?>" placeholder="Aula Sekolah"></label>
              <label><span>Deskripsi</span><textarea name="deskripsi" rows="2"><?= e($edit_event['deskripsi'] ?? '') ?></textarea></label>
              <label><span>Status Publikasi</span>
                <select name="status">
                  <option value="terbit" <?= ($edit_event['status'] ?? 'terbit') === 'terbit' ? 'selected' : '' ?>>Terbit (Tampil di Portal)</option>
                  <option value="draf"   <?= ($edit_event['status'] ?? '') === 'draf' ? 'selected' : '' ?>>Draf (Sembunyikan)</option>
                </select>
              </label>
              <div style="display:flex;gap:.5rem;">
                <button class="btn-primary" type="submit">Simpan Event</button>
                <?php if ($edit_event): ?><a class="btn-outline" href="dashboard-admin.php#konten">Batal</a><?php endif; ?>
              </div>
            </form>
          </section>
        </div>

        <h3>Daftar Berita</h3>
        <div class="table-wrap" style="margin-bottom:1.5rem;">
          <table>
            <thead><tr><th>Judul</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($berita_list as $b): ?>
                <tr>
                  <td><?= e($b['judul']) ?></td>
                  <td><?= e(tanggal_id($b['dibuat_pada'])) ?></td>
                  <td><span class="badge<?= $b['status'] === 'terbit' ? '' : ' badge-late' ?>"><?= e(ucfirst($b['status'])) ?></span></td>
                  <td>
                    <a class="btn-small" href="dashboard-admin.php?edit_berita=<?= (int)$b['id_berita'] ?>#konten">Edit</a>
                    <form method="post" action="admin_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_berita">
                      <input type="hidden" name="tab" value="konten">
                      <input type="hidden" name="id_berita" value="<?= (int)$b['id_berita'] ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus berita ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <h3>Daftar Agenda Event</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Kegiatan</th><th>Tanggal</th><th>Lokasi</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($event_list as $ev): ?>
                <tr>
                  <td><?= e($ev['judul']) ?></td>
                  <td><?= e(tanggal_id($ev['tanggal_mulai'])) ?></td>
                  <td><?= e($ev['lokasi'] ?? '-') ?></td>
                  <td><span class="badge<?= $ev['status'] === 'terbit' ? '' : ' badge-late' ?>"><?= e(ucfirst($ev['status'])) ?></span></td>
                  <td>
                    <a class="btn-small" href="dashboard-admin.php?edit_event=<?= (int)$ev['id_event'] ?>#konten">Edit</a>
                    <form method="post" action="admin_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_event">
                      <input type="hidden" name="tab" value="konten">
                      <input type="hidden" name="id_event" value="<?= (int)$ev['id_event'] ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus agenda ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <script src="script.js"></script>
</body>
</html>
