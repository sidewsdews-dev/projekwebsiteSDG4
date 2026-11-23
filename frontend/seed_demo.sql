-- =====================================================================
--  DATA CONTOH (demo). Jalankan SETELAH dataprojek.sql, lewat phpMyAdmin.
--  Password SEMUA akun demo: rahasia123
--
--  Login:  Admin -> username: admin              peran: Admin
--          Guru  -> NIP:      198001012005011001 peran: Guru
--                             197905152003121002
--          Siswa -> username: raka / dina / bima peran: Siswa
--  Akun menunggu persetujuan: siswa lia (NIS 27010004), guru NIP 199001012015011003
-- =====================================================================
USE dataprojek;

INSERT INTO mata_pelajaran (idmapel, nama_mapel) VALUES
 ('MTK','Matematika'), ('BIN','Bahasa Indonesia'), ('FIS','Fisika');

-- Hash bcrypt untuk 'rahasia123'
INSERT INTO admin (nama, username, password, email) VALUES
 ('Siti Rahmawati','admin','rahasia123','admin@contoh.sch.id');

INSERT INTO guru (nip, nama_guru, password, email, jabatan, status_guru, aktif) VALUES
 ('198001012005011001','Sari Wulandari','rahasia123','sari@contoh.sch.id','Guru Matematika','PNS',1),
 ('197905152003121002','Andi Prasetyo','rahasia123','andi@contoh.sch.id','Guru Bahasa Indonesia','PNS',1),
 ('199001012015011003','Rina Marlina','rahasia123','rina@contoh.sch.id','Guru Fisika','Honorer',0);

INSERT INTO kelas (ruang_kelas, tahun_ajar, nip_wali) VALUES
 ('X-1','2026/2027','198001012005011001'),
 ('X-2','2026/2027','197905152003121002');

INSERT INTO siswa (nis, nama_siswa, username, password, email, angkatan, ruang_kelas, status) VALUES
 ('27010001','Raka Aditya Pratama','raka','rahasia123','raka@contoh.sch.id',2026,'X-1','aktif'),
 ('27010002','Dina Safitri','dina','rahasia123','dina@contoh.sch.id',2026,'X-1','aktif'),
 ('27010003','Bima Saputra','bima','rahasia123','bima@contoh.sch.id',2026,'X-2','aktif'),
 ('27010004','Lia Anggraini','lia','rahasia123','lia@contoh.sch.id',2026,NULL,'menunggu');

INSERT INTO pengampu (id_pengampu, nip, idmapel, ruang_kelas) VALUES
 (1,'198001012005011001','MTK','X-1'),
 (2,'197905152003121002','BIN','X-1'),
 (3,'198001012005011001','MTK','X-2'),
 (4,'197905152003121002','BIN','X-2');

INSERT INTO jadwal (id_pengampu, hari, jam_mulai, jam_selesai)
SELECT p.id_pengampu, h.hari,
       IF(p.idmapel='MTK','07:30:00','09:15:00'), IF(p.idmapel='MTK','09:00:00','10:45:00')
FROM pengampu p
CROSS JOIN (SELECT 'Senin' hari UNION SELECT 'Selasa' UNION SELECT 'Rabu' UNION SELECT 'Kamis' UNION SELECT 'Jumat') h;

INSERT INTO tugas (id_pengampu, judul, deskripsi, deadline) VALUES
 (1,'Latihan Soal Matematika','Kerjakan soal halaman 45-47, kumpulkan dalam PDF.','2026-12-05 23:59:00'),
 (2,'Esai Bahasa Indonesia','Esai 500 kata tentang pendidikan berkualitas.','2026-11-28 23:59:00'),
 (1,'Kuis Aljabar (contoh sudah lewat)','Tugas ini sengaja sudah lewat deadline untuk menguji status terlambat.','2026-11-10 23:59:00');

INSERT INTO nilai (nis, id_pengampu, harian, uts, uas) VALUES
 ('27010001',1,85,80,NULL), ('27010001',2,88,NULL,NULL),
 ('27010002',1,90,86,NULL), ('27010002',2,79,NULL,NULL);

INSERT INTO absensi (nis, id_pengampu, tanggal, status) VALUES
 ('27010001',1,'2026-11-16','hadir'), ('27010001',1,'2026-11-17','hadir'),
 ('27010001',2,'2026-11-18','sakit'),  ('27010001',2,'2026-11-19','hadir'),
 ('27010002',1,'2026-11-16','hadir'),  ('27010002',1,'2026-11-17','alpa');

INSERT INTO berita (judul, isi, status, id_admin) VALUES
 ('Juara Lomba Sains','Siswa kami meraih juara pada lomba sains tingkat kota. Selamat kepada para pemenang dan guru pembimbing.','terbit',1),
 ('Kegiatan Pramuka','Perkemahan akhir pekan diikuti seluruh kelas X dengan berbagai kegiatan kepramukaan.','terbit',1),
 ('Seminar Karier','Alumni berbagi pengalaman kuliah dan dunia kerja kepada siswa kelas XII.','terbit',1),
 ('Berita Draf Contoh','Berita ini masih draf dan tidak boleh tampil di portal.','draf',1);

INSERT INTO event (judul, deskripsi, tanggal_mulai, lokasi, status, id_admin) VALUES
 ('Ujian Akhir Semester','Ujian akhir semester ganjil.','2026-12-07','Seluruh kelas','terbit',1),
 ('Libur Akhir Tahun','Libur semester ganjil.','2026-12-22','-','terbit',1),
 ('Rapat Orang Tua (draf)','Belum dipublikasikan.','2026-12-15','Aula','draf',1);
