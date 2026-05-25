CREATE DATABASE IF NOT EXISTS sistem_pelanggaran_siswa
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE sistem_pelanggaran_siswa;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS transaksi_pelanggaran;
DROP TABLE IF EXISTS jenis_pelanggaran;
DROP TABLE IF EXISTS guru;
DROP TABLE IF EXISTS siswa;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(120) NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'guru', 'siswa') NOT NULL,
    nama VARCHAR(120) NOT NULL,
    ref_id INT UNSIGNED NULL,
    profile_photo VARCHAR(255) NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_username_unique (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE siswa (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nis VARCHAR(40) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    kelas VARCHAR(60) NOT NULL,
    jk ENUM('L', 'P') NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY siswa_nis_unique (nis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE guru (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nip VARCHAR(40) NOT NULL,
    nama VARCHAR(120) NOT NULL,
    kelas_ampu VARCHAR(60) NOT NULL,
    no_hp VARCHAR(30) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY guru_nip_unique (nip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE jenis_pelanggaran (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nama VARCHAR(180) NOT NULL,
    poin INT UNSIGNED NOT NULL,
    kategori VARCHAR(60) NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE transaksi_pelanggaran (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id INT UNSIGNED NOT NULL,
    jenis_id INT UNSIGNED NOT NULL,
    poin INT UNSIGNED NOT NULL,
    guru_id INT UNSIGNED NULL,
    tanggal DATE NOT NULL,
    keterangan TEXT NOT NULL,
    PRIMARY KEY (id),
    KEY transaksi_siswa_index (siswa_id),
    KEY transaksi_jenis_index (jenis_id),
    KEY transaksi_guru_index (guru_id),
    CONSTRAINT transaksi_siswa_fk FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT transaksi_jenis_fk FOREIGN KEY (jenis_id) REFERENCES jenis_pelanggaran(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT transaksi_guru_fk FOREIGN KEY (guru_id) REFERENCES guru(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE sp_sanksi (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    siswa_id INT UNSIGNED NOT NULL,
    tahap TINYINT UNSIGNED NOT NULL,
    konsekuensi TEXT NOT NULL,
    tugas_tambahan TEXT NULL,
    wali_id INT UNSIGNED NULL,
    tanggal DATE NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY sp_siswa_tahap_unique (siswa_id, tahap),
    KEY sp_siswa_index (siswa_id),
    KEY sp_wali_index (wali_id),
    CONSTRAINT sp_siswa_fk FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT sp_wali_fk FOREIGN KEY (wali_id) REFERENCES guru(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (id, username, password, role, nama, ref_id, profile_photo) VALUES
(1, 'admin@sekolah.id', 'admin123', 'admin', 'Administrator', NULL, NULL),
(2, 'wali1@sekolah.id', 'wali123', 'guru', 'Budi Santoso', 1, NULL),
(3, 'wali2@sekolah.id', 'wali123', 'guru', 'Nur Aisyah', 2, NULL),
(4, 'wali3@sekolah.id', 'wali123', 'guru', 'Agus Setiawan', 3, NULL),
(5, 'wali4@sekolah.id', 'wali123', 'guru', 'Dian Puspita', 4, NULL),
(6, 'wali5@sekolah.id', 'wali123', 'guru', 'Rina Marlina', 5, NULL),
(7, 'wali6@sekolah.id', 'wali123', 'guru', 'Hendra Wijaya', 6, NULL),
(8, 'wali7@sekolah.id', 'wali123', 'guru', 'Siti Lestari', 7, NULL),
(9, 'wali8@sekolah.id', 'wali123', 'guru', 'Fauzan Hakim', 8, NULL),
(10, 'wali9@sekolah.id', 'wali123', 'guru', 'Mira Anggraini', 9, NULL);

INSERT INTO guru (id, nip, nama, kelas_ampu, no_hp) VALUES
(1, '198800000000000001', 'Budi Santoso', 'X RPL 1', '081234567801'),
(2, '198800000000000002', 'Nur Aisyah', 'X TKJ 1', '081234567802'),
(3, '198800000000000003', 'Agus Setiawan', 'X Multimedia 1', '081234567803'),
(4, '198800000000000004', 'Dian Puspita', 'XI RPL 1', '081234567804'),
(5, '198800000000000005', 'Rina Marlina', 'XI TKJ 1', '081234567805'),
(6, '198800000000000006', 'Hendra Wijaya', 'XI Multimedia 1', '081234567806'),
(7, '198800000000000007', 'Siti Lestari', 'XII RPL 1', '081234567807'),
(8, '198800000000000008', 'Fauzan Hakim', 'XII TKJ 1', '081234567808'),
(9, '198800000000000009', 'Mira Anggraini', 'XII Multimedia 1', '081234567809');

INSERT INTO jenis_pelanggaran (id, nama, poin, kategori) VALUES
(1, 'Terlambat masuk sekolah', 5, 'Ringan'),
(2, 'Tidak memakai atribut lengkap', 10, 'Ringan'),
(3, 'Tidak mengerjakan tugas', 15, 'Sedang'),
(4, 'Membolos pelajaran', 25, 'Sedang'),
(5, 'Berkelahi di lingkungan sekolah', 50, 'Berat');

DELIMITER //
CREATE PROCEDURE seed_siswa_wali_kelas()
BEGIN
    DECLARE class_idx INT DEFAULT 1;
    DECLARE student_idx INT DEFAULT 1;
    DECLARE student_id INT DEFAULT 1;
    DECLARE class_name VARCHAR(60);
    DECLARE first_name VARCHAR(60);
    DECLARE middle_name VARCHAR(60);
    DECLARE family_name VARCHAR(80);

    WHILE class_idx <= 9 DO
        SET class_name = CASE class_idx
            WHEN 1 THEN 'X RPL 1'
            WHEN 2 THEN 'X TKJ 1'
            WHEN 3 THEN 'X Multimedia 1'
            WHEN 4 THEN 'XI RPL 1'
            WHEN 5 THEN 'XI TKJ 1'
            WHEN 6 THEN 'XI Multimedia 1'
            WHEN 7 THEN 'XII RPL 1'
            WHEN 8 THEN 'XII TKJ 1'
            ELSE 'XII Multimedia 1'
        END;

        SET middle_name = CASE class_idx
            WHEN 1 THEN 'Aditya'
            WHEN 2 THEN 'Nayaka'
            WHEN 3 THEN 'Bimantara'
            WHEN 4 THEN 'Cendekia'
            WHEN 5 THEN 'Daniswara'
            WHEN 6 THEN 'Elangga'
            WHEN 7 THEN 'Fathir'
            WHEN 8 THEN 'Ganesha'
            ELSE 'Harmoni'
        END;

        SET student_idx = 1;
        WHILE student_idx <= 30 DO
            SET first_name = CASE student_idx
                WHEN 1 THEN 'Ahmad' WHEN 2 THEN 'Aisyah' WHEN 3 THEN 'Bagas'
                WHEN 4 THEN 'Citra' WHEN 5 THEN 'Daffa' WHEN 6 THEN 'Dewi'
                WHEN 7 THEN 'Eka' WHEN 8 THEN 'Fajar' WHEN 9 THEN 'Gita'
                WHEN 10 THEN 'Hafiz' WHEN 11 THEN 'Indah' WHEN 12 THEN 'Joko'
                WHEN 13 THEN 'Kirana' WHEN 14 THEN 'Lutfi' WHEN 15 THEN 'Maya'
                WHEN 16 THEN 'Nabila' WHEN 17 THEN 'Oscar' WHEN 18 THEN 'Putri'
                WHEN 19 THEN 'Qori' WHEN 20 THEN 'Raka' WHEN 21 THEN 'Salsa'
                WHEN 22 THEN 'Tegar' WHEN 23 THEN 'Ulfa' WHEN 24 THEN 'Vino'
                WHEN 25 THEN 'Wulan' WHEN 26 THEN 'Yoga' WHEN 27 THEN 'Zahra'
                WHEN 28 THEN 'Arif' WHEN 29 THEN 'Bella' ELSE 'Cahya'
            END;
            SET family_name = CONCAT(
                CASE FLOOR((student_id - 1) / 15)
                    WHEN 0 THEN 'Arka' WHEN 1 THEN 'Bima' WHEN 2 THEN 'Cakra'
                    WHEN 3 THEN 'Dira' WHEN 4 THEN 'Erlang' WHEN 5 THEN 'Fajar'
                    WHEN 6 THEN 'Giri' WHEN 7 THEN 'Harsa' WHEN 8 THEN 'Ilang'
                    WHEN 9 THEN 'Janu' WHEN 10 THEN 'Karsa' WHEN 11 THEN 'Loka'
                    WHEN 12 THEN 'Mada' WHEN 13 THEN 'Nara' WHEN 14 THEN 'Oka'
                    WHEN 15 THEN 'Pandu' WHEN 16 THEN 'Raya' ELSE 'Sagara'
                END,
                CASE FLOOR((student_id - 1) / 18)
                    WHEN 0 THEN 'wibawa' WHEN 1 THEN 'nendra' WHEN 2 THEN 'syah'
                    WHEN 3 THEN 'wirya' WHEN 4 THEN 'tama' WHEN 5 THEN 'naya'
                    WHEN 6 THEN 'langit' WHEN 7 THEN 'bumi' WHEN 8 THEN 'sakti'
                    WHEN 9 THEN 'raja' WHEN 10 THEN 'kencana' WHEN 11 THEN 'prana'
                    WHEN 12 THEN 'wardana' WHEN 13 THEN 'aksara' ELSE 'biantara'
                END
            );

            INSERT INTO siswa (id, nis, nama, kelas, jk)
            VALUES (
                student_id,
                CONCAT('2026', LPAD(student_id, 4, '0')),
                CONCAT(first_name, ' ', middle_name, ' ', family_name),
                class_name,
                IF(MOD(student_idx, 2) = 1, 'L', 'P')
            );

            INSERT INTO users (id, username, password, role, nama, ref_id, profile_photo)
            VALUES (
                student_id + 10,
                CONCAT('siswa', LPAD(student_id, 3, '0'), '@gmail.com'),
                'siswa123',
                'siswa',
                CONCAT(first_name, ' ', middle_name, ' ', family_name),
                student_id,
                NULL
            );

            SET student_id = student_id + 1;
            SET student_idx = student_idx + 1;
        END WHILE;

        SET class_idx = class_idx + 1;
    END WHILE;
END//
DELIMITER ;

CALL seed_siswa_wali_kelas();
DROP PROCEDURE seed_siswa_wali_kelas;

ALTER TABLE users AUTO_INCREMENT = 281;
ALTER TABLE siswa AUTO_INCREMENT = 271;
ALTER TABLE guru AUTO_INCREMENT = 10;
ALTER TABLE jenis_pelanggaran AUTO_INCREMENT = 6;
ALTER TABLE transaksi_pelanggaran AUTO_INCREMENT = 1;
