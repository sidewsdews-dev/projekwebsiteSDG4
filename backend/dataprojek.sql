create database dataprojek if not exists;

use dataprojek;

create table siswa if not exists(
    id int(11) primary key auto_increment,
    nama_siswa varchar(100) not null,
    password varchar(300) not null,
    ruang_kelas varchar(50) not null,
);

create table guru if not exists(
    id int(11) primary key not null,
    nama_guru varchar(100) not null,
    mata_pelajaran_yang_diampu varchar(250), 
    password varchar(300) not null,
    
    
)

 