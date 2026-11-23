<?php
require_once 'auth.php';
$u = wajib_login('siswa');

$wali = db_satu(
    "SELECT g.nama_guru, k.tahun_ajar FROM kelas k LEFT JOIN guru g ON g.nip = k.nip_wali WHERE k.ruang_kelas = ?",
    "s", [$u['ruang_kelas']]
);

$hari = nama_hari_ini();
$jadwal_hari = ($hari === null || !$u['ruang_kelas']) ? [] : db_semua(
    "SELECT j.jam_mulai, j.jam_selesai, m.nama_mapel, g.nama_guru
       FROM jadwal j
       JOIN pengampu p ON p.id_pengampu = j.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
       JOIN guru g ON g.nip = p.nip
      WHERE p.ruang_kelas = ? AND j.hari = ?
      ORDER BY j.jam_mulai",
    "ss", [$u['ruang_kelas'], $hari]
);

$jadwal_semua = !$u['ruang_kelas'] ? [] : db_semua(
    "SELECT j.hari, j.jam_mulai, j.jam_selesai, m.nama_mapel, g.nama_guru
       FROM jadwal j
       JOIN pengampu p ON p.id_pengampu = j.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
       JOIN guru g ON g.nip = p.nip
      WHERE p.ruang_kelas = ?
      ORDER BY FIELD(j.hari,'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai",
    "s", [$u['ruang_kelas']]
);

$materi = !$u['ruang_kelas'] ? [] : db_semua(
    "SELECT mt.id_materi, mt.judul, mt.nama_asli, mt.tanggal_unggah, m.nama_mapel, g.nama_guru
       FROM materi mt
       JOIN pengampu p ON p.id_pengampu = mt.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
       JOIN guru g ON g.nip = p.nip
      WHERE p.ruang_kelas = ?
      ORDER BY mt.tanggal_unggah DESC",
    "s", [$u['ruang_kelas']]
);

$belum = !$u['ruang_kelas'] ? [] : db_semua(
    "SELECT t.id_tugas, t.judul, t.deskripsi, t.deadline, t.file_soal_path, m.nama_mapel
       FROM tugas t
       JOIN pengampu p ON p.id_pengampu = t.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE p.ruang_kelas = ?
        AND NOT EXISTS (SELECT 1 FROM pengumpulan_tugas x WHERE x.id_tugas = t.id_tugas AND x.nis = ?)
      ORDER BY t.deadline ASC",
    "ss", [$u['ruang_kelas'], $u['nis']]
);

$sudah = db_semua(
    "SELECT x.id, x.nama_asli, x.waktu_upload, x.status, x.nilai, t.id_tugas, t.judul, m.nama_mapel
       FROM pengumpulan_tugas x
       JOIN tugas t ON t.id_tugas = x.id_tugas
       JOIN pengampu p ON p.id_pengampu = t.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
      WHERE x.nis = ? ORDER BY x.waktu_upload DESC",
    "s", [$u['nis']]
);

$nilai = db_semua(
    "SELECT m.nama_mapel, g.nama_guru, n.harian, n.uts, n.uas
       FROM nilai n
       JOIN pengampu p ON p.id_pengampu = n.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
       JOIN guru g ON g.nip = p.nip
      WHERE n.nis = ? ORDER BY m.nama_mapel",
    "s", [$u['nis']]
);

$rekap = ['hadir' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0];
foreach (db_semua("SELECT status, COUNT(*) AS jml FROM absensi WHERE nis = ? GROUP BY status", "s", [$u['nis']]) as $r) {
    $rekap[$r['status']] = (int)$r['jml'];
}
$detail_absensi = db_semua(
    "SELECT a.tanggal, a.status, m.nama_mapel, g.nama_guru
       FROM absensi a
       JOIN pengampu p ON p.id_pengampu = a.id_pengampu
       JOIN mata_pelajaran m ON m.idmapel = p.idmapel
       JOIN guru g ON g.nip = p.nip
      WHERE a.nis = ? ORDER BY a.tanggal DESC LIMIT 50",
    "s", [$u['nis']]
);

$pesan = ambil_flash();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="color-scheme" content="light dark">
  <title>Dashboard Siswa | Portal Satu Pintu</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="app">
    <aside class="sidebar" aria-label="Identitas siswa">
      <div class="avatar" aria-hidden="true"><?= e(inisial($u['_nama'])) ?></div>
      <div class="who"><strong><?= e($u['_nama']) ?></strong><span>NIS <?= e($u['nis']) ?></span></div>
      <dl>
        <div><dt>Username</dt><dd><?= e($u['username']) ?></dd></div>
        <div><dt>Kelas</dt><dd><?= e($u['ruang_kelas'] ?? 'Belum ditempatkan') ?></dd></div>
        <div><dt>Wali kelas</dt><dd><?= e($wali['nama_guru'] ?? '-') ?></dd></div>
        <div><dt>Angkatan</dt><dd><?= e($u['angkatan'] ?? '-') ?></dd></div>
        <div><dt>Email</dt><dd><?= e($u['email'] ?? '-') ?></dd></div>
        <div><dt>Status</dt><dd><?= e(ucfirst($u['status'])) ?></dd></div>
      </dl>
      <a class="logout" href="index.php" style="margin-bottom:.5rem;">Beranda Portal</a>
      <a class="logout" href="logout.php">Keluar</a>
    </aside>

    <main class="main">
      <div class="topbar">
        <div><small>Dashboard Siswa</small><h1>Halo, <?= e(explode(' ', $u['_nama'])[0]) ?></h1></div>
        <div class="pill"><?= $u['ruang_kelas'] ? 'Kelas ' . e($u['ruang_kelas']) : 'Belum ada kelas' ?></div>
      </div>

      <?php if ($pesan): ?>
        <p class="flash <?= $pesan['tipe'] === 'err' ? 'flash-err' : '' ?>" role="status"><?= e($pesan['pesan']) ?></p>
      <?php endif; ?>

      <nav class="tabs" aria-label="Menu siswa">
        <a href="#ringkasan" data-tab="ringkasan">Ringkasan &amp; Identitas</a>
        <a href="#jadwal" data-tab="jadwal">Jadwal Pelajaran</a>
        <a href="#tugas" data-tab="tugas">Tugas &amp; Materi</a>
        <a href="#nilai" data-tab="nilai">Daftar Nilai</a>
        <a href="#absensi" data-tab="absensi">Rekap Absensi</a>
      </nav>

      <!-- RINGKASAN & IDENTITAS -->
      <div class="panel" data-panel="ringkasan">
        <div class="cards">
          <section class="card">
            <h2>Identitas Akademik Siswa</h2>
            <div class="row"><span>Nama Lengkap</span><span><?= e($u['nama_siswa']) ?></span></div>
            <div class="row"><span>NIS</span><span><?= e($u['nis']) ?></span></div>
            <div class="row"><span>Kelas / Tahun Ajar</span><span><?= e($u['ruang_kelas'] ?? '-') ?> · <?= e($wali['tahun_ajar'] ?? '-') ?></span></div>
            <div class="row"><span>Wali Kelas</span><span><?= e($wali['nama_guru'] ?? '-') ?></span></div>
            <div class="row"><span>Angkatan</span><span><?= e($u['angkatan'] ?? '-') ?></span></div>
          </section>

          <section class="card">
            <h2>Jadwal Hari Ini<?= $hari ? ' (' . e($hari) . ')' : '' ?></h2>
            <?php foreach ($jadwal_hari as $j): ?>
              <div class="row"><span><?= e(substr($j['jam_mulai'], 0, 5)) ?> · <?= e($j['nama_mapel']) ?></span><span><?= e($j['nama_guru']) ?></span></div>
            <?php endforeach; ?>
            <?php if (!$jadwal_hari): ?><p class="empty">Tidak ada jadwal pelajaran hari ini.</p><?php endif; ?>
          </section>

          <section class="card">
            <h2>Ringkasan Tugas</h2>
            <div class="row"><span>Tugas belum dikumpulkan</span><span><?= count($belum) ?></span></div>
            <div class="row"><span>Tugas sudah dikumpulkan</span><span><?= count($sudah) ?></span></div>
          </section>

          <section class="card">
            <h2>Rekap Kehadiran</h2>
            <div class="row"><span>Hadir</span><span><?= $rekap['hadir'] ?></span></div>
            <div class="row"><span>Izin</span><span><?= $rekap['izin'] ?></span></div>
            <div class="row"><span>Sakit</span><span><?= $rekap['sakit'] ?></span></div>
            <div class="row"><span>Alpa</span><span><?= $rekap['alpa'] ?></span></div>
          </section>
        </div>
      </div>

      <!-- JADWAL PELAJARAN LENGKAP -->
      <div class="panel" data-panel="jadwal" hidden>
        <h2 class="panel-title">Jadwal Pelajaran Kelas <?= e($u['ruang_kelas'] ?? '-') ?></h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Hari</th><th>Jam</th><th>Mata Pelajaran</th><th>Guru Pengampu</th></tr></thead>
            <tbody>
              <?php foreach ($jadwal_semua as $j): ?>
                <tr>
                  <td><?= e($j['hari']) ?></td>
                  <td><?= e(substr($j['jam_mulai'],0,5) . '–' . substr($j['jam_selesai'],0,5)) ?></td>
                  <td><?= e($j['nama_mapel']) ?></td>
                  <td><?= e($j['nama_guru']) ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$jadwal_semua): ?><tr><td colspan="4">Belum ada jadwal pelajaran untuk kelas Anda.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- TUGAS & MATERI -->
      <div class="panel" data-panel="tugas" hidden>
        <h2 class="panel-title">Tugas &amp; Materi Pembelajaran</h2>
        <div class="cards" style="margin-bottom:1.5rem;">
          <section class="card">
            <h2>Tugas Belum Dikumpulkan (<?= count($belum) ?>)</h2>
            <?php foreach ($belum as $t): ?>
              <div class="item">
                <strong><?= e($t['judul']) ?></strong>
                <div class="meta">
                  <?= e($t['nama_mapel']) ?> · Tenggat <?= e(tanggal_id($t['deadline'], true)) ?>
                  <?= strtotime($t['deadline']) < time() ? ' · <span class="badge badge-late">Sudah lewat</span>' : '' ?>
                  <?php if ($t['file_soal_path']): ?>
                    · <a href="download.php?jenis=soal&id=<?= (int)$t['id_tugas'] ?>">Unduh Soal</a>
                  <?php endif; ?>
                </div>
                <?php if ($t['deskripsi']): ?><p style="font-size:.86rem;margin:.35rem 0;color:var(--muted);"><?= e($t['deskripsi']) ?></p><?php endif; ?>
                <form class="inline-form" method="post" action="kumpul_tugas.php" enctype="multipart/form-data">
                  <?= csrf_input() ?>
                  <input type="hidden" name="id_tugas" value="<?= (int)$t['id_tugas'] ?>">
                  <input type="file" name="berkas" required>
                  <button class="btn-small" type="submit">Kumpulkan</button>
                </form>
              </div>
            <?php endforeach; ?>
            <?php if (!$belum): ?><p class="empty">Semua tugas sudah dikumpulkan.</p><?php endif; ?>
          </section>

          <section class="card">
            <h2>Tugas Sudah Dikumpulkan (<?= count($sudah) ?>)</h2>
            <?php foreach ($sudah as $x): ?>
              <div class="item">
                <strong><?= e($x['judul']) ?> (<?= e($x['nama_mapel']) ?>)</strong>
                <div class="meta">
                  <?= e($x['nama_asli']) ?> · <?= e(tanggal_id($x['waktu_upload'], true)) ?>
                  · <span class="badge<?= $x['status'] === 'terlambat' ? ' badge-late' : '' ?>"><?= $x['status'] === 'terlambat' ? 'Terlambat' : 'Tepat waktu' ?></span>
                  · Nilai: <strong><?= e(angka($x['nilai'])) ?></strong>
                  · <a href="download.php?id=<?= (int)$x['id'] ?>">Unduh</a>
                </div>
                <?php if ($x['nilai'] === null): ?>
                  <form class="inline-form" method="post" action="kumpul_tugas.php" enctype="multipart/form-data">
                    <?= csrf_input() ?>
                    <input type="hidden" name="id_tugas" value="<?= (int)$x['id_tugas'] ?>">
                    <input type="file" name="berkas" required>
                    <button class="btn-small" type="submit">Ganti file</button>
                  </form>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
            <?php if (!$sudah): ?><p class="empty">Belum ada tugas yang dikumpulkan.</p><?php endif; ?>
          </section>
        </div>

        <h3>Materi dari Guru</h3>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Judul Materi</th><th>Mata Pelajaran</th><th>Guru</th><th>Tanggal</th><th>Berkas</th></tr></thead>
            <tbody>
              <?php foreach ($materi as $m): ?>
                <tr>
                  <td><?= e($m['judul']) ?></td>
                  <td><?= e($m['nama_mapel']) ?></td>
                  <td><?= e($m['nama_guru']) ?></td>
                  <td><?= e(tanggal_id($m['tanggal_unggah'])) ?></td>
                  <td><a href="download.php?jenis=materi&id=<?= (int)$m['id_materi'] ?>"><?= e($m['nama_asli']) ?></a></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$materi): ?><tr><td colspan="5">Belum ada materi pembelajaran yang diunggah.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- DAFTAR NILAI -->
      <div class="panel" data-panel="nilai" hidden>
        <h2 class="panel-title">Daftar Nilai Mata Pelajaran</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Mata Pelajaran</th><th>Guru Pengampu</th><th>Harian</th><th>UTS</th><th>UAS</th><th>Rata-rata</th></tr></thead>
            <tbody>
              <?php foreach ($nilai as $n):
                $komp = array_filter([$n['harian'], $n['uts'], $n['uas']], fn($v) => $v !== null);
                $rata = count($komp) ? array_sum($komp) / count($komp) : null;
              ?>
                <tr>
                  <td><strong><?= e($n['nama_mapel']) ?></strong></td>
                  <td><?= e($n['nama_guru']) ?></td>
                  <td><?= e(angka($n['harian'])) ?></td>
                  <td><?= e(angka($n['uts'])) ?></td>
                  <td><?= e(angka($n['uas'])) ?></td>
                  <td><strong><?= e(angka($rata)) ?></strong></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$nilai): ?><tr><td colspan="6">Belum ada nilai yang diinput oleh guru.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- REKAP ABSENSI -->
      <div class="panel" data-panel="absensi" hidden>
        <h2 class="panel-title">Rekap &amp; Riwayat Kehadiran</h2>
        <div class="cards" style="margin-bottom:1.25rem;">
          <section class="card">
            <h2>Total Kehadiran</h2>
            <div class="row"><span>Hadir</span><span><?= $rekap['hadir'] ?> pertemuan</span></div>
            <div class="row"><span>Izin</span><span><?= $rekap['izin'] ?> pertemuan</span></div>
            <div class="row"><span>Sakit</span><span><?= $rekap['sakit'] ?> pertemuan</span></div>
            <div class="row"><span>Alpa</span><span><?= $rekap['alpa'] ?> pertemuan</span></div>
          </section>
        </div>

        <div class="table-wrap">
          <table>
            <thead><tr><th>Tanggal</th><th>Mata Pelajaran</th><th>Guru</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($detail_absensi as $da): ?>
                <tr>
                  <td><?= e(tanggal_id($da['tanggal'])) ?></td>
                  <td><?= e($da['nama_mapel']) ?></td>
                  <td><?= e($da['nama_guru']) ?></td>
                  <td><span class="badge<?= $da['status'] === 'hadir' ? '' : ' badge-late' ?>"><?= e(ucfirst($da['status'])) ?></span></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$detail_absensi): ?><tr><td colspan="4">Belum ada catatan absensi.</td></tr><?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
  <script src="script.js"></script>
</body>
</html>
