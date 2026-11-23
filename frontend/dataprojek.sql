CREATE DATABASE IF NOT EXISTS dataprojek
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE dataprojek;

CREATE TABLE IF NOT EXISTS mata_pelajaran (
    idmapel     VARCHAR(10)  NOT NULL,
    nama_mapel  VARCHAR(100) NOT NULL,
    PRIMARY KEY (idmapel)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS guru (
    nip          VARCHAR(18)  NOT NULL,           
    nama_guru    VARCHAR(100) NOT NULL,
    password     VARCHAR(255) NOT NULL,           
    email        VARCHAR(100) NULL,
    jabatan      VARCHAR(50)  NULL,
    status_guru  VARCHAR(30)  NULL,               
    foto_path    VARCHAR(255) NULL,
    aktif        TINYINT(1)   NOT NULL DEFAULT 1,  
    dibuat_pada  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,  
    PRIMARY KEY (nip)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admin (
    id_admin  INT          NOT NULL AUTO_INCREMENT,
    nama      VARCHAR(100) NOT NULL,
    username  VARCHAR(50)  NOT NULL,
    password  VARCHAR(255) NOT NULL,               
    email     VARCHAR(100) NULL,
    aktif     TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id_admin),
    UNIQUE (username)                            
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS kelas (
    ruang_kelas  VARCHAR(10) NOT NULL,             
    tahun_ajar   VARCHAR(9)  NOT NULL,             
    nip_wali     VARCHAR(18) NULL,                 
    PRIMARY KEY (ruang_kelas),
    FOREIGN KEY (nip_wali) REFERENCES guru(nip)
        ON UPDATE CASCADE
        ON DELETE SET NULL                         
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS siswa (
    nis          VARCHAR(20)  NOT NULL,
    nama_siswa   VARCHAR(100) NOT NULL,
    username     VARCHAR(50)  NOT NULL,
    password     VARCHAR(255) NOT NULL,            
    email        VARCHAR(100) NULL,
    angkatan     YEAR         NULL,
    ruang_kelas  VARCHAR(10)  NULL,                
    status       ENUM('menunggu','aktif','nonaktif') NOT NULL DEFAULT 'menunggu',
    dibuat_pada  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (nis),
    UNIQUE (username),
    FOREIGN KEY (ruang_kelas) REFERENCES kelas(ruang_kelas)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengampu (
    id_pengampu  INT         NOT NULL AUTO_INCREMENT,
    nip          VARCHAR(18) NOT NULL,
    idmapel      VARCHAR(10) NOT NULL,
    ruang_kelas  VARCHAR(10) NOT NULL,
    PRIMARY KEY (id_pengampu),
    UNIQUE (nip, idmapel, ruang_kelas),           
    FOREIGN KEY (nip)         REFERENCES guru(nip)                    ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (idmapel)     REFERENCES mata_pelajaran(idmapel)      ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (ruang_kelas) REFERENCES kelas(ruang_kelas)           ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS jadwal (
    id_jadwal     INT         NOT NULL AUTO_INCREMENT,
    id_pengampu   INT         NOT NULL,
    hari          ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') NOT NULL,
    jam_mulai     TIME        NOT NULL,           
    jam_selesai   TIME        NOT NULL,
    PRIMARY KEY (id_jadwal),
    UNIQUE (id_pengampu, hari, jam_mulai),
    FOREIGN KEY (id_pengampu) REFERENCES pengampu(id_pengampu) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS materi (
    id_materi       INT          NOT NULL AUTO_INCREMENT,
    id_pengampu     INT          NOT NULL,
    judul           VARCHAR(150) NOT NULL,
    file_path       VARCHAR(255) NOT NULL,         
    nama_asli       VARCHAR(255) NOT NULL,        
    tanggal_unggah  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_materi),
    FOREIGN KEY (id_pengampu) REFERENCES pengampu(id_pengampu) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tugas (
    id_tugas        INT          NOT NULL AUTO_INCREMENT,
    id_pengampu     INT          NOT NULL,
    judul           VARCHAR(150) NOT NULL,
    deskripsi       TEXT         NULL,
    deadline        DATETIME     NOT NULL,         
    file_soal_path  VARCHAR(255) NULL,             
    dibuat_pada     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_tugas),
    FOREIGN KEY (id_pengampu) REFERENCES pengampu(id_pengampu) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pengumpulan_tugas (
    id            INT          NOT NULL AUTO_INCREMENT,
    id_tugas      INT          NOT NULL,
    nis           VARCHAR(20)  NOT NULL,
    file_path     VARCHAR(255) NOT NULL,
    nama_asli     VARCHAR(255) NOT NULL,
    waktu_upload  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,  
    status        ENUM('tepat_waktu','terlambat') NOT NULL DEFAULT 'tepat_waktu',
    nilai         DECIMAL(5,2) NULL,               
    PRIMARY KEY (id),
    UNIQUE (id_tugas, nis),                        
    FOREIGN KEY (id_tugas) REFERENCES tugas(id_tugas) ON DELETE CASCADE,
    FOREIGN KEY (nis)      REFERENCES siswa(nis) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS nilai (
    id           INT          NOT NULL AUTO_INCREMENT,
    nis          VARCHAR(20)  NOT NULL,
    id_pengampu  INT          NOT NULL,
    harian       DECIMAL(5,2) NULL,               
    uts          DECIMAL(5,2) NULL,
    uas          DECIMAL(5,2) NULL,
    PRIMARY KEY (id),
    UNIQUE (nis, id_pengampu),                    
    FOREIGN KEY (nis)         REFERENCES siswa(nis) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_pengampu) REFERENCES pengampu(id_pengampu) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS absensi (
    id           INT         NOT NULL AUTO_INCREMENT,
    nis          VARCHAR(20) NOT NULL,
    id_pengampu  INT         NOT NULL,
    tanggal      DATE        NOT NULL,
    status       ENUM('hadir','izin','sakit','alpa') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE (nis, id_pengampu, tanggal),            
    FOREIGN KEY (nis)         REFERENCES siswa(nis) ON UPDATE CASCADE ON DELETE CASCADE,
    FOREIGN KEY (id_pengampu) REFERENCES pengampu(id_pengampu) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS berita (
    id_berita    INT          NOT NULL AUTO_INCREMENT,
    judul        VARCHAR(150) NOT NULL,
    isi          TEXT         NOT NULL,           
    gambar_path  VARCHAR(255) NULL,
    status       ENUM('draf','terbit') NOT NULL DEFAULT 'draf',
    id_admin     INT          NOT NULL,
    dibuat_pada  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_berita),
    FOREIGN KEY (id_admin) REFERENCES admin(id_admin)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS event (
    id_event         INT          NOT NULL AUTO_INCREMENT,
    judul            VARCHAR(150) NOT NULL,
    deskripsi        TEXT         NULL,
    tanggal_mulai    DATE         NOT NULL,
    tanggal_selesai  DATE         NULL,            
    lokasi           VARCHAR(100) NULL,
    status           ENUM('draf','terbit') NOT NULL DEFAULT 'draf',
    id_admin         INT          NOT NULL,
    PRIMARY KEY (id_event),
    FOREIGN KEY (id_admin) REFERENCES admin(id_admin)
) ENGINE=InnoDB;
