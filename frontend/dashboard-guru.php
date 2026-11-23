<?php
require_once 'auth.php';
$u = wajib_login('guru');
$nip = $u['nip'];

$pengampu_list = db_semua(
    "SELECT p.id_pengampu, p.ruang_kelas, p.idmapel, m.nama_mapel
       FROM pengampu p JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE p.nip = ? ORDER BY p.ruang_kelas, m.nama_mapel", "s", [$nip]
);

$mapel = db_semua(
    "SELECT DISTINCT m.nama_mapel FROM pengampu p JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE p.nip = ? ORDER BY m.nama_mapel", "s", [$nip]
);
$wali = db_semua("SELECT ruang_kelas, tahun_ajar FROM kelas WHERE nip_wali = ?", "s", [$nip]);

$urutan_hari = "FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu')";
$jadwal_semua = db_semua(
    "SELECT j.hari, j.jam_mulai, j.jam_selesai, p.ruang_kelas, m.nama_mapel
       FROM jadwal j JOIN pengampu p ON p.id_pengampu = j.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE p.nip = ? ORDER BY $urutan_hari, j.jam_mulai", "s", [$nip]
);

$hari = nama_hari_ini();
$jadwal_hari = array_values(array_filter($jadwal_semua, fn($j) => $j['hari'] === $hari));

$tugas = db_semua(
    "SELECT t.id_tugas, t.judul, t.deskripsi, t.deadline, t.file_soal_path, p.ruang_kelas, m.nama_mapel,
            COUNT(x.id) AS terkumpul,
            SUM(x.id IS NOT NULL AND x.nilai IS NULL) AS belum_dinilai
       FROM tugas t JOIN pengampu p ON p.id_pengampu = t.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
       LEFT JOIN pengumpulan_tugas x ON x.id_tugas = t.id_tugas
      WHERE p.nip = ? GROUP BY t.id_tugas ORDER BY t.deadline DESC", "s", [$nip]
);
$perlu_dinilai = array_values(array_filter($tugas, fn($t) => (int)$t['belum_dinilai'] > 0));

$pengumpulan = db_semua(
    "SELECT x.id, t.judul, p.ruang_kelas, s.nama_siswa, x.waktu_upload, x.status, x.nilai, x.nama_asli
       FROM pengumpulan_tugas x
       JOIN tugas t ON t.id_tugas = x.id_tugas
       JOIN pengampu p ON p.id_pengampu = t.id_pengampu
       JOIN siswa s ON s.nis = x.nis
      WHERE p.nip = ? ORDER BY x.waktu_upload DESC LIMIT 100", "s", [$nip]
);

$kelas = db_semua(
    "SELECT p.ruang_kelas, COUNT(DISTINCT s.nis) AS jml_siswa
       FROM pengampu p LEFT JOIN siswa s ON s.ruang_kelas = p.ruang_kelas AND s.status = 'aktif'
      WHERE p.nip = ? GROUP BY p.ruang_kelas ORDER BY p.ruang_kelas", "s", [$nip]
);

$materi = db_semua(
    "SELECT mt.id_materi, mt.judul, p.ruang_kelas, m.nama_mapel, mt.tanggal_unggah, mt.nama_asli
       FROM materi mt JOIN pengampu p ON p.id_pengampu = mt.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE p.nip = ? ORDER BY mt.tanggal_unggah DESC", "s", [$nip]
);

// Pilihan pengampu aktif untuk form input nilai & absensi
$default_idp = $pengampu_list[0]['id_pengampu'] ?? 0;
$pilih_idp_nilai = (int)($_GET['idp_nilai'] ?? $default_idp);
$pilih_idp_absen = (int)($_GET['idp_absen'] ?? $default_idp);
$pilih_tgl_absen = $_GET['tgl_absen'] ?? date('Y-m-d');

$siswa_nilai = [];
if ($pilih_idp_nilai) {
    $siswa_nilai = db_semua(
        "SELECT s.nis, s.nama_siswa, n.harian, n.uts, n.uas
           FROM pengampu p
           JOIN siswa s ON s.ruang_kelas = p.ruang_kelas AND s.status = 'aktif'
           LEFT JOIN nilai n ON n.nis = s.nis AND n.id_pengampu = p.id_pengampu
          WHERE p.id_pengampu = ? AND p.nip = ?
          ORDER BY s.nama_siswa",
        "is", [$pilih_idp_nilai, $nip]
    );
}

$siswa_absen = [];
if ($pilih_idp_absen) {
    $siswa_absen = db_semua(
        "SELECT s.nis, s.nama_siswa, a.status AS status_absen
           FROM pengampu p
           JOIN siswa s ON s.ruang_kelas = p.ruang_kelas AND s.status = 'aktif'
           LEFT JOIN absensi a ON a.nis = s.nis AND a.id_pengampu = p.id_pengampu AND a.tanggal = ?
          WHERE p.id_pengampu = ? AND p.nip = ?
          ORDER BY s.nama_siswa",
        "sis", [$pilih_tgl_absen, $pilih_idp_absen, $nip]
    );
}

$riwayat_absensi = db_semua(
    "SELECT p.ruang_kelas, m.nama_mapel, a.tanggal,
            SUM(a.status = 'hadir') AS hadir,
            SUM(a.status = 'izin') AS izin,
            SUM(a.status = 'sakit') AS sakit,
            SUM(a.status = 'alpa') AS alpa
       FROM absensi a JOIN pengampu p ON p.id_pengampu = a.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE p.nip = ? GROUP BY p.ruang_kelas, m.nama_mapel, a.tanggal ORDER BY a.tanggal DESC LIMIT 30", "s", [$nip]
);

$tgl_ini = date('Y-m-d');
$absen_hari = db_satu(
    "SELECT COUNT(DISTINCT a.id_pengampu) AS diabsen, COALESCE(SUM(a.status <> 'hadir'), 0) AS tidak_hadir
       FROM absensi a JOIN pengampu p ON p.id_pengampu = a.id_pengampu
      WHERE p.nip = ? AND a.tanggal = ?", "ss", [$nip, $tgl_ini]
);
$belum_diabsen = max(0, count($jadwal_hari) - (int)$absen_hari['diabsen']);

$pesan = ambil_flash();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title>Dashboard Guru | Portal Satu Pintu</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar profile" aria-label="Identitas guru">
      <div class="avatar" aria-hidden="true"><?= e(inisial($u['_nama'])) ?></div>
      <div class="who"><strong><?= e($u['_nama']) ?></strong><span>NIP <?= e($u['nip']) ?></span></div>
      <dl>
        <div><dt>Mata pelajaran</dt><dd><?= e($mapel ? implode(', ', array_column($mapel, 'nama_mapel')) : '-') ?></dd></div>
        <div><dt>Wali kelas</dt><dd><?= e($wali ? implode(', ', array_column($wali, 'ruang_kelas')) : '-') ?></dd></div>
        <div><dt>Jabatan</dt><dd><?= e($u['jabatan'] ?? '-') ?></dd></div>
        <div><dt>Status</dt><dd><?= e($u['status_guru'] ?? '-') ?></dd></div>
      </dl>
      <a class="logout" href="index.php" style="margin-bottom:.5rem;">Beranda Portal</a>
      <a class="logout" href="logout.php">Keluar</a>
    </aside>

    <main class="main" id="isi-utama">
      <div class="topbar">
        <div><small>Dashboard Guru</small><h1>Halo, <?= e($u['_nama']) ?></h1></div>
        <div class="pill"><?= e($wali[0]['tahun_ajar'] ?? ($kelas ? 'Guru Aktif' : '-')) ?></div>
      </div>

      <?php if ($pesan): ?>
        <p class="flash <?= $pesan['tipe'] === 'err' ? 'flash-err' : '' ?>" role="status"><?= e($pesan['pesan']) ?></p>
      <?php endif; ?>

      <nav class="tabs" aria-label="Menu guru">
        <a href="#ringkasan" data-tab="ringkasan">Ringkasan</a>
        <a href="#jadwal" data-tab="jadwal">Jadwal Mengajar</a>
        <a href="#materi" data-tab="materi">Materi</a>
        <a href="#tugas" data-tab="tugas">Tugas &amp; Penilaian</a>
        <a href="#nilai" data-tab="nilai">Input Nilai Mapel</a>
        <a href="#absensi" data-tab="absensi">Absensi Kelas</a>
      </nav>

      <!-- RINGKASAN -->
      <div class="panel" data-panel="ringkasan">
        <div class="cards">
          <section class="card">
            <h2>Jadwal Mengajar Hari Ini<?= $hari ? ' (' . e($hari) . ')' : '' ?></h2>
            <?php foreach ($jadwal_hari as $j): ?>
              <div class="row"><span><?= e(substr($j['jam_mulai'], 0, 5)) ?> · <?= e($j['ruang_kelas']) ?></span><span><?= e($j['nama_mapel']) ?></span></div>
            <?php endforeach; ?>
            <?php if (!$jadwal_hari): ?><p class="empty">Tidak ada jadwal mengajar hari ini.</p><?php endif; ?>
          </section>

          <section class="card">
            <h2>Tugas Menunggu Dinilai</h2>
            <?php foreach ($perlu_dinilai as $t): ?>
              <div class="row"><span><?= e($t['judul']) ?> · <?= e($t['ruang_kelas']) ?></span><span><?= (int)$t['belum_dinilai'] ?> berkas</span></div>
            <?php endforeach; ?>
            <?php if (!$perlu_dinilai): ?><p class="empty">Semua pengumpulan tugas sudah dinilai.</p><?php endif; ?>
          </section>

          <section class="card">
            <h2>Kelas yang Diampu</h2>
            <?php foreach ($kelas as $k): ?>
              <div class="row"><span>Kelas <?= e($k['ruang_kelas']) ?></span><span><?= (int)$k['jml_siswa'] ?> siswa</span></div>
            <?php endforeach; ?>
            <?php if (!$kelas): ?><p class="empty">Belum ada kelas yang diampu.</p><?php endif; ?>
          </section>

          <section class="card">
            <h2>Absensi Hari Ini</h2>
            <div class="row"><span>Kelas belum diabsen</span><span><?= $belum_diabsen ?></span></div>
            <div class="row"><span>Siswa tidak hadir</span><span><?= (int)$absen_hari['tidak_hadir'] ?></span></div>
          </section>
        </div>
      </div>

      <!-- JADWAL MENGAJAR -->
      <div class="panel" data-panel="jadwal" hidden>
        <h2 class="panel-title">Jadwal Mengajar Mingguan</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Hari</th><th>Jam</th><th>Kelas</th><th>Mata Pelajaran</th></tr></thead>
            <tbody>
              <?php foreach ($jadwal_semua as $j): ?>
                <tr>
                  <td><?= e($j['hari']) ?></td>
                  <td><?= e(substr($j['jam_mulai'], 0, 5) . '–' . substr($j['jam_selesai'], 0, 5)) ?></td>
                  <td><?= e($j['ruang_kelas']) ?></td>
                  <td><?= e($j['nama_mapel']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$jadwal_semua): ?><tr><td colspan="4">Belum ada jadwal mengajar.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- MATERI -->
      <div class="panel" data-panel="materi" hidden>
        <h2 class="panel-title">Kelola Materi Pembelajaran</h2>
        <section class="card" style="margin-bottom:1.25rem;">
          <h3>Unggah Materi Baru</h3>
          <form method="post" action="guru_aksi.php" enctype="multipart/form-data" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="modul" value="tambah_materi">
            <input type="hidden" name="tab" value="materi">
            <label><span>Kelas &amp; Mata Pelajaran</span>
              <select name="id_pengampu" required>
                <?php foreach ($pengampu_list as $p): ?>
                  <option value="<?= (int)$p['id_pengampu'] ?>"><?= e($p['ruang_kelas']) ?> · <?= e($p['nama_mapel']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label><span>Judul Materi</span><input type="text" name="judul" placeholder="Mis. Bab 1 - Aljabar Linear" required></label>
            <label><span>Berkas Materi (PDF/PPT/DOCX, maks 15MB)</span><input type="file" name="berkas" required></label>
            <div style="display:flex;align-items:end;"><button class="btn-primary" type="submit">Unggah Materi</button></div>
          </form>
        </section>

        <div class="table-wrap">
          <table>
            <thead><tr><th>Judul Materi</th><th>Kelas · Mapel</th><th>Tanggal Unggah</th><th>Berkas</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($materi as $m): ?>
                <tr>
                  <td><?= e($m['judul']) ?></td>
                  <td><?= e($m['ruang_kelas']) ?> · <?= e($m['nama_mapel']) ?></td>
                  <td><?= e(tanggal_id($m['tanggal_unggah'])) ?></td>
                  <td><a href="download.php?jenis=materi&id=<?= (int)$m['id_materi'] ?>"><?= e($m['nama_asli']) ?></a></td>
                  <td>
                    <form method="post" action="guru_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_materi">
                      <input type="hidden" name="tab" value="materi">
                      <input type="hidden" name="id_materi" value="<?= (int)$m['id_materi'] ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus materi ini?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$materi): ?><tr><td colspan="5">Belum ada materi yang diunggah.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TUGAS & PENILAIAN PENGUMPULAN -->
      <div class="panel" data-panel="tugas" hidden>
        <h2 class="panel-title">Kelola Tugas &amp; Penilaian Pengumpulan</h2>
        <section class="card" style="margin-bottom:1.25rem;">
          <h3>Buat Tugas Baru</h3>
          <form method="post" action="guru_aksi.php" enctype="multipart/form-data" class="form-grid">
            <?= csrf_input() ?>
            <input type="hidden" name="modul" value="tambah_tugas">
            <input type="hidden" name="tab" value="tugas">
            <label><span>Kelas &amp; Mata Pelajaran</span>
              <select name="id_pengampu" required>
                <?php foreach ($pengampu_list as $p): ?>
                  <option value="<?= (int)$p['id_pengampu'] ?>"><?= e($p['ruang_kelas']) ?> · <?= e($p['nama_mapel']) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
            <label><span>Judul Tugas</span><input type="text" name="judul" required></label>
            <label><span>Tenggat Waktu (Deadline)</span><input type="datetime-local" name="deadline" required></label>
            <label><span>Lampiran Soal (Opsional)</span><input type="file" name="file_soal"></label>
            <label style="grid-column:1/-1;"><span>Instruksi / Deskripsi Tugas</span><textarea name="deskripsi" rows="2"></textarea></label>
            <div><button class="btn-primary" type="submit">Terbitkan Tugas</button></div>
          </form>
        </section>

        <h3>Daftar Tugas Aktif</h3>
        <div class="table-wrap" style="margin-bottom:1.5rem;">
          <table>
            <thead><tr><th>Judul Tugas</th><th>Kelas · Mapel</th><th>Tenggat</th><th>Terkumpul</th><th>Aksi</th></tr></thead>
            <tbody>
              <?php foreach ($tugas as $t): ?>
                <tr>
                  <td>
                    <strong><?= e($t['judul']) ?></strong>
                    <?php if ($t['file_soal_path']): ?>
                      <br><a href="download.php?jenis=soal&id=<?= (int)$t['id_tugas'] ?>" style="font-size:.82rem;">Unduh Lampiran Soal</a>
                    <?php endif; ?>
                  </td>
                  <td><?= e($t['ruang_kelas']) ?> · <?= e($t['nama_mapel']) ?></td>
                  <td><?= e(tanggal_id($t['deadline'], true)) ?></td>
                  <td><?= (int)$t['terkumpul'] ?> berkas</td>
                  <td>
                    <form method="post" action="guru_aksi.php" style="display:inline;">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="hapus_tugas">
                      <input type="hidden" name="tab" value="tugas">
                      <input type="hidden" name="id_tugas" value="<?= (int)$t['id_tugas'] ?>">
                      <button class="btn-small btn-danger" type="submit" onclick="return confirm('Hapus tugas beserta seluruh pengumpulannya?')">Hapus</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$tugas): ?><tr><td colspan="5">Belum ada tugas.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>

        <h3>Penilaian Tugas Siswa</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Siswa</th><th>Kelas</th><th>Tugas</th><th>Waktu &amp; Status</th><th>Berkas</th><th>Nilai (0-100)</th></tr></thead>
            <tbody>
              <?php foreach ($pengumpulan as $x): ?>
                <tr>
                  <td><?= e($x['nama_siswa']) ?></td>
                  <td><?= e($x['ruang_kelas']) ?></td>
                  <td><?= e($x['judul']) ?></td>
                  <td>
                    <?= e(tanggal_id($x['waktu_upload'], true)) ?><br>
                    <span class="badge<?= $x['status'] === 'terlambat' ? ' badge-late' : '' ?>"><?= $x['status'] === 'terlambat' ? 'Terlambat' : 'Tepat waktu' ?></span>
                  </td>
                  <td><a href="download.php?id=<?= (int)$x['id'] ?>"><?= e($x['nama_asli']) ?></a></td>
                  <td>
                    <form method="post" action="guru_aksi.php" class="inline-form">
                      <?= csrf_input() ?>
                      <input type="hidden" name="modul" value="nilai_pengumpulan">
                      <input type="hidden" name="tab" value="tugas">
                      <input type="hidden" name="id_pengumpulan" value="<?= (int)$x['id'] ?>">
                      <input type="number" name="nilai" step="0.01" min="0" max="100" value="<?= e($x['nilai'] ?? '') ?>" style="width:80px;" placeholder="0-100">
                      <button class="btn-small" type="submit">Simpan</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$pengumpulan): ?><tr><td colspan="6">Belum ada tugas yang dikumpulkan siswa.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- NILAI MAPEL -->
      <div class="panel" data-panel="nilai" hidden>
        <h2 class="panel-title">Kelola Nilai Mata Pelajaran (Harian, UTS, UAS)</h2>
        <form method="get" action="dashboard-guru.php#nilai" class="inline-form" style="margin-bottom:1rem;">
          <label><span>Pilih Kelas &amp; Mapel:</span>
            <select name="idp_nilai" onchange="this.form.submit()">
              <?php foreach ($pengampu_list as $p): ?>
                <option value="<?= (int)$p['id_pengampu'] ?>" <?= (int)$p['id_pengampu'] === $pilih_idp_nilai ? 'selected' : '' ?>>
                  <?= e($p['ruang_kelas']) ?> · <?= e($p['nama_mapel']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <button class="btn-small" type="submit">Tampilkan Siswa</button>
        </form>

        <form method="post" action="guru_aksi.php">
          <?= csrf_input() ?>
          <input type="hidden" name="modul" value="simpan_nilai">
          <input type="hidden" name="tab" value="nilai">
          <input type="hidden" name="id_pengampu" value="<?= $pilih_idp_nilai ?>">
          <div class="table-wrap">
            <table>
              <thead><tr><th>NIS</th><th>Nama Siswa</th><th>Nilai Harian</th><th>Nilai UTS</th><th>Nilai UAS</th></tr></thead>
              <tbody>
                <?php foreach ($siswa_nilai as $sn): ?>
                  <tr>
                    <td><?= e($sn['nis']) ?><input type="hidden" name="nis[]" value="<?= e($sn['nis']) ?>"></td>
                    <td><?= e($sn['nama_siswa']) ?></td>
                    <td><input type="number" name="harian[]" step="0.01" min="0" max="100" value="<?= e($sn['harian'] ?? '') ?>" style="width:90px;"></td>
                    <td><input type="number" name="uts[]" step="0.01" min="0" max="100" value="<?= e($sn['uts'] ?? '') ?>" style="width:90px;"></td>
                    <td><input type="number" name="uas[]" step="0.01" min="0" max="100" value="<?= e($sn['uas'] ?? '') ?>" style="width:90px;"></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$siswa_nilai): ?><tr><td colspan="5">Tidak ada siswa aktif di kelas ini.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php if ($siswa_nilai): ?>
            <div style="margin-top:1rem;"><button class="btn-primary" type="submit">Simpan Semua Nilai</button></div>
          <?php endif; ?>
        </form>
      </div>

      <!-- ABSENSI KELAS -->
      <div class="panel" data-panel="absensi" hidden>
        <h2 class="panel-title">Kelola Absensi Kelas</h2>
        <form method="get" action="dashboard-guru.php#absensi" class="inline-form" style="margin-bottom:1rem;">
          <label><span>Kelas &amp; Mapel:</span>
            <select name="idp_absen">
              <?php foreach ($pengampu_list as $p): ?>
                <option value="<?= (int)$p['id_pengampu'] ?>" <?= (int)$p['id_pengampu'] === $pilih_idp_absen ? 'selected' : '' ?>>
                  <?= e($p['ruang_kelas']) ?> · <?= e($p['nama_mapel']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>
          <label><span>Tanggal:</span>
            <input type="date" name="tgl_absen" value="<?= e($pilih_tgl_absen) ?>">
          </label>
          <button class="btn-small" type="submit">Muat Daftar Siswa</button>
        </form>

        <form method="post" action="guru_aksi.php" style="margin-bottom:1.75rem;">
          <?= csrf_input() ?>
          <input type="hidden" name="modul" value="simpan_absensi">
          <input type="hidden" name="tab" value="absensi">
          <input type="hidden" name="id_pengampu" value="<?= $pilih_idp_absen ?>">
          <input type="hidden" name="tanggal" value="<?= e($pilih_tgl_absen) ?>">
          <div class="table-wrap">
            <table>
              <thead><tr><th>NIS</th><th>Nama Siswa</th><th>Status Kehadiran (<?= e(tanggal_id($pilih_tgl_absen)) ?>)</th></tr></thead>
              <tbody>
                <?php foreach ($siswa_absen as $sa): $st_now = $sa['status_absen'] ?? 'hadir'; ?>
                  <tr>
                    <td><?= e($sa['nis']) ?></td>
                    <td><?= e($sa['nama_siswa']) ?></td>
                    <td>
                      <div class="radio-group">
                        <?php foreach (['hadir'=>'Hadir','izin'=>'Izin','sakit'=>'Sakit','alpa'=>'Alpa'] as $val => $lbl): ?>
                          <label class="radio-pill">
                            <input type="radio" name="status[<?= e($sa['nis']) ?>]" value="<?= $val ?>" <?= $st_now === $val ? 'checked' : '' ?>>
                            <span><?= $lbl ?></span>
                          </label>
                        <?php endforeach; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$siswa_absen): ?><tr><td colspan="3">Tidak ada siswa aktif di kelas ini.</td></tr><?php endif; ?>
              </tbody>
            </table>
          </div>
          <?php if ($siswa_absen): ?>
            <div style="margin-top:1rem;"><button class="btn-primary" type="submit">Simpan Absensi Kelas</button></div>
          <?php endif; ?>
        </form>

        <h3>Riwayat Absensi Terakhir</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Tanggal</th><th>Kelas</th><th>Mapel</th><th>Hadir</th><th>Izin</th><th>Sakit</th><th>Alpa</th></tr></thead>
            <tbody>
              <?php foreach ($riwayat_absensi as $ra): ?>
                <tr>
                  <td><?= e(tanggal_id($ra['tanggal'])) ?></td>
                  <td><?= e($ra['ruang_kelas']) ?></td>
                  <td><?= e($ra['nama_mapel']) ?></td>
                  <td><?= (int)$ra['hadir'] ?></td>
                  <td><?= (int)$ra['izin'] ?></td>
                  <td><?= (int)$ra['sakit'] ?></td>
                  <td><?= (int)$ra['alpa'] ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$riwayat_absensi): ?><tr><td colspan="7">Belum ada riwayat absensi.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <script src="script.js"></script>
</body>
</html>
