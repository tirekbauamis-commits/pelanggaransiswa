<?php
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'sistem_pelanggaran_siswa');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

function db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    initDatabase($pdo);
    return $pdo;
}

function initDatabase($pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
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
    ");
    addColumnIfMissing($pdo, 'users', 'profile_photo', 'VARCHAR(255) NULL');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS siswa (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nis VARCHAR(40) NOT NULL,
            nama VARCHAR(120) NOT NULL,
            kelas VARCHAR(60) NOT NULL,
            jk ENUM('L', 'P') NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY siswa_nis_unique (nis)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    dropColumnIfExists($pdo, 'siswa', 'alamat');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS guru (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nip VARCHAR(40) NOT NULL,
            nama VARCHAR(120) NOT NULL,
            kelas_ampu VARCHAR(60) NOT NULL,
            no_hp VARCHAR(30) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY guru_nip_unique (nip)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    renameColumnIfExists($pdo, 'guru', 'mapel', 'kelas_ampu', 'VARCHAR(60) NOT NULL');

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS jenis_pelanggaran (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            nama VARCHAR(180) NOT NULL,
            poin INT UNSIGNED NOT NULL,
            kategori VARCHAR(60) NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS transaksi_pelanggaran (
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
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS sp_sanksi (
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
    ");
    addColumnIfMissing($pdo, 'sp_sanksi', 'tugas_tambahan', 'TEXT NULL');

    $exists = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($exists > 0) {
        migrateDefaultAccounts($pdo);
        migrateClassesAndNames($pdo);
        return;
    }

    seedDefaultData($pdo);
}

function seedDefaultData($pdo) {
    seedTable($pdo, 'users', [
        ['id' => 1, 'username' => 'admin@sekolah.id', 'password' => 'admin123', 'role' => 'admin', 'nama' => 'Administrator', 'ref_id' => null, 'profile_photo' => null],
    ]);

    seedTable($pdo, 'siswa', defaultStudents());

    seedTable($pdo, 'guru', defaultHomeroomTeachers());
    seedTable($pdo, 'users', array_merge(defaultHomeroomUsers(), defaultStudentUsers()));

    seedTable($pdo, 'jenis_pelanggaran', [
        ['id' => 1, 'nama' => 'Terlambat masuk sekolah', 'poin' => 5, 'kategori' => 'Ringan'],
        ['id' => 2, 'nama' => 'Tidak memakai atribut lengkap', 'poin' => 10, 'kategori' => 'Ringan'],
        ['id' => 3, 'nama' => 'Tidak mengerjakan tugas', 'poin' => 15, 'kategori' => 'Sedang'],
        ['id' => 4, 'nama' => 'Membolos pelajaran', 'poin' => 25, 'kategori' => 'Sedang'],
        ['id' => 5, 'nama' => 'Berkelahi di lingkungan sekolah', 'poin' => 50, 'kategori' => 'Berat'],
    ]);

    seedTable($pdo, 'transaksi_pelanggaran', [
        ['id' => 1, 'siswa_id' => 1, 'jenis_id' => 1, 'poin' => 5, 'guru_id' => 1, 'tanggal' => '2026-05-01', 'keterangan' => 'Datang terlambat 15 menit.'],
        ['id' => 2, 'siswa_id' => 2, 'jenis_id' => 2, 'poin' => 10, 'guru_id' => 2, 'tanggal' => '2026-05-03', 'keterangan' => 'Tidak memakai dasi.'],
        ['id' => 3, 'siswa_id' => 3, 'jenis_id' => 3, 'poin' => 15, 'guru_id' => 1, 'tanggal' => '2026-05-05', 'keterangan' => 'Tugas praktikum belum dikumpulkan.'],
        ['id' => 4, 'siswa_id' => 4, 'jenis_id' => 4, 'poin' => 25, 'guru_id' => 3, 'tanggal' => '2026-05-07', 'keterangan' => 'Tidak mengikuti jam pelajaran terakhir.'],
        ['id' => 5, 'siswa_id' => 5, 'jenis_id' => 5, 'poin' => 50, 'guru_id' => 3, 'tanggal' => '2026-05-10', 'keterangan' => 'Terlibat perkelahian saat istirahat.'],
    ]);
}

function defaultClasses() {
    return [
        'X RPL 1', 'X TKJ 1', 'X Multimedia 1',
        'XI RPL 1', 'XI TKJ 1', 'XI Multimedia 1',
        'XII RPL 1', 'XII TKJ 1', 'XII Multimedia 1',
    ];
}

function defaultHomeroomTeachers() {
    $names = ['Budi Santoso', 'Nur Aisyah', 'Agus Setiawan', 'Dian Puspita', 'Rina Marlina', 'Hendra Wijaya', 'Siti Lestari', 'Fauzan Hakim', 'Mira Anggraini'];
    $rows = [];
    foreach (defaultClasses() as $index => $class) {
        $id = $index + 1;
        $rows[] = [
            'id' => $id,
            'nip' => '1988' . str_pad((string) $id, 14, '0', STR_PAD_LEFT),
            'nama' => $names[$index],
            'kelas_ampu' => $class,
            'no_hp' => '0812345678' . str_pad((string) $id, 2, '0', STR_PAD_LEFT),
        ];
    }
    return $rows;
}

function defaultHomeroomUsers() {
    $rows = [];
    foreach (defaultHomeroomTeachers() as $teacher) {
        $rows[] = [
            'id' => $teacher['id'] + 1,
            'username' => 'wali' . $teacher['id'] . '@sekolah.id',
            'password' => 'wali123',
            'role' => 'guru',
            'nama' => $teacher['nama'],
            'ref_id' => $teacher['id'],
            'profile_photo' => null,
        ];
    }
    return $rows;
}

function defaultStudents() {
    $firstNames = ['Ahmad', 'Aisyah', 'Bagas', 'Citra', 'Daffa', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hafiz', 'Indah', 'Joko', 'Kirana', 'Lutfi', 'Maya', 'Nabila', 'Oscar', 'Putri', 'Qori', 'Raka', 'Salsa', 'Tegar', 'Ulfa', 'Vino', 'Wulan', 'Yoga', 'Zahra', 'Arif', 'Bella', 'Cahya'];
    $rows = [];
    $id = 1;
    foreach (defaultClasses() as $classIndex => $class) {
        foreach ($firstNames as $nameIndex => $firstName) {
            $rows[] = [
                'id' => $id,
                'nis' => '2026' . str_pad((string) $id, 4, '0', STR_PAD_LEFT),
                'nama' => defaultStudentName($id, $classIndex, $nameIndex),
                'kelas' => $class,
                'jk' => ($nameIndex % 2 === 0) ? 'L' : 'P',
            ];
            $id++;
        }
    }
    return $rows;
}

function defaultStudentName($id, $classIndex, $nameIndex) {
    $firstNames = ['Ahmad', 'Aisyah', 'Bagas', 'Citra', 'Daffa', 'Dewi', 'Eka', 'Fajar', 'Gita', 'Hafiz', 'Indah', 'Joko', 'Kirana', 'Lutfi', 'Maya', 'Nabila', 'Oscar', 'Putri', 'Qori', 'Raka', 'Salsa', 'Tegar', 'Ulfa', 'Vino', 'Wulan', 'Yoga', 'Zahra', 'Arif', 'Bella', 'Cahya'];
    $middleNames = ['Aditya', 'Nayaka', 'Bimantara', 'Cendekia', 'Daniswara', 'Elangga', 'Fathir', 'Ganesha', 'Harmoni'];
    $familyStarts = ['Arka', 'Bima', 'Cakra', 'Dira', 'Erlang', 'Fajar', 'Giri', 'Harsa', 'Ilang', 'Janu', 'Karsa', 'Loka', 'Mada', 'Nara', 'Oka', 'Pandu', 'Raya', 'Sagara'];
    $familyEnds = ['wibawa', 'nendra', 'syah', 'wirya', 'tama', 'naya', 'langit', 'bumi', 'sakti', 'raja', 'kencana', 'prana', 'wardana', 'aksara', 'biantara'];
    $nameNumber = max(0, (int) $id - 1);
    $firstName = $firstNames[$nameIndex % count($firstNames)];
    $middleName = $middleNames[$classIndex % count($middleNames)];
    $familyName = $familyStarts[(int) floor($nameNumber / count($familyEnds)) % count($familyStarts)] . $familyEnds[$nameNumber % count($familyEnds)];
    return $firstName . ' ' . $middleName . ' ' . ucfirst($familyName);
}

function defaultStudentUsers() {
    $rows = [];
    $homeroomCount = count(defaultHomeroomTeachers());
    foreach (defaultStudents() as $student) {
        $rows[] = [
            'id' => $student['id'] + $homeroomCount + 1,
            'username' => 'siswa' . str_pad((string) $student['id'], 3, '0', STR_PAD_LEFT) . '@gmail.com',
            'password' => 'siswa123',
            'role' => 'siswa',
            'nama' => $student['nama'],
            'ref_id' => $student['id'],
            'profile_photo' => null,
        ];
    }
    return $rows;
}

function migrateDefaultAccounts($pdo) {
    $pdo->exec("UPDATE users SET username = 'admin@sekolah.id' WHERE role = 'admin' AND username = 'admin'");
    $pdo->exec("UPDATE users SET username = 'guru@sekolah.id' WHERE role = 'guru' AND username = 'guru'");
    $pdo->exec("UPDATE users SET username = 'siswa@gmail.com' WHERE role = 'siswa' AND username = 'siswa' AND id = 3");
}

function migrateClassesAndNames($pdo) {
    $classMap = [
        'X RPL 2' => 'X TKJ 1',
        'XI RPL 2' => 'XI TKJ 1',
        'XII RPL 2' => 'XII TKJ 1',
        'X RPL 5' => 'X Multimedia 1',
    ];
    foreach ($classMap as $oldClass => $newClass) {
        $stmt = $pdo->prepare('UPDATE siswa SET kelas = :new_class WHERE kelas = :old_class');
        $stmt->execute(['new_class' => $newClass, 'old_class' => $oldClass]);

        $stmt = $pdo->prepare('UPDATE guru SET kelas_ampu = :new_class WHERE kelas_ampu = :old_class');
        $stmt->execute(['new_class' => $newClass, 'old_class' => $oldClass]);
    }

    $defaultTeachers = defaultHomeroomTeachers();
    foreach ($defaultTeachers as $teacher) {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM guru WHERE id = :id');
        $stmt->execute(['id' => $teacher['id']]);
        if ((int) $stmt->fetchColumn() > 0) {
            $stmt = $pdo->prepare('UPDATE guru SET nip = :nip, nama = :nama, kelas_ampu = :kelas_ampu, no_hp = :no_hp WHERE id = :id');
            $stmt->execute($teacher);
        } else {
            insertDbRow($pdo, 'guru', $teacher);
        }

        $stmt = $pdo->prepare("UPDATE users SET username = :username, nama = :nama, ref_id = :ref_id WHERE role = 'guru' AND ref_id = :where_ref_id");
        $stmt->execute([
            'username' => 'wali' . $teacher['id'] . '@sekolah.id',
            'nama' => $teacher['nama'],
            'ref_id' => $teacher['id'],
            'where_ref_id' => $teacher['id'],
        ]);
    }

    $existingClasses = $pdo->query('SELECT kelas_ampu FROM guru')->fetchAll(PDO::FETCH_COLUMN);
    $teacherNames = ['Budi Santoso', 'Nur Aisyah', 'Agus Setiawan', 'Dian Puspita', 'Rina Marlina', 'Hendra Wijaya', 'Siti Lestari', 'Fauzan Hakim', 'Mira Anggraini'];
    foreach (defaultClasses() as $index => $class) {
        if (in_array($class, $existingClasses, true)) {
            continue;
        }
        $id = nextAutoId($pdo, 'guru');
        insertDbRow($pdo, 'guru', [
            'id' => $id,
            'nip' => '1988' . str_pad((string) $id, 14, '0', STR_PAD_LEFT),
            'nama' => $teacherNames[$index],
            'kelas_ampu' => $class,
            'no_hp' => '0812345678' . str_pad((string) $id, 2, '0', STR_PAD_LEFT),
        ]);
        insertDbRow($pdo, 'users', [
            'id' => nextAutoId($pdo, 'users'),
            'username' => 'wali' . $id . '@sekolah.id',
            'password' => 'wali123',
            'role' => 'guru',
            'nama' => $teacherNames[$index],
            'ref_id' => $id,
            'profile_photo' => null,
        ]);
    }

    $defaultClasses = defaultClasses();
    $students = $pdo->query("SELECT id FROM siswa WHERE nis LIKE '2026%' ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($students as $index => $studentId) {
        $class = $defaultClasses[$index % count($defaultClasses)];
        $stmt = $pdo->prepare('UPDATE siswa SET kelas = :kelas WHERE id = :id');
        $stmt->execute(['kelas' => $class, 'id' => $studentId]);
    }

    $students = $pdo->query("
        SELECT s.id
        FROM siswa s
        INNER JOIN users u ON u.role = 'siswa' AND u.ref_id = s.id
        WHERE s.nis LIKE '2026%'
          AND u.username REGEXP '^siswa[0-9]+@gmail\\\\.com$'
        ORDER BY s.id ASC
    ")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($students as $index => $student) {
        $classIndex = $index % count(defaultClasses());
        $nameIndex = $index % 30;
        $newName = defaultStudentName($index + 1, $classIndex, $nameIndex);
        $stmt = $pdo->prepare('UPDATE siswa SET nama = :nama WHERE id = :id');
        $stmt->execute(['nama' => $newName, 'id' => $student]);
        $stmt = $pdo->prepare("UPDATE users SET nama = :nama WHERE role = 'siswa' AND ref_id = :ref_id");
        $stmt->execute(['nama' => $newName, 'ref_id' => $student]);
    }

}

function dropColumnIfExists($pdo, $table, $column) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute(['table' => $table, 'column' => $column]);
    if ((int) $stmt->fetchColumn() > 0) {
        $pdo->exec('ALTER TABLE ' . $table . ' DROP COLUMN ' . $column);
    }
}

function renameColumnIfExists($pdo, $table, $oldColumn, $newColumn, $definition) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :old_column
    ");
    $stmt->execute(['table' => $table, 'old_column' => $oldColumn]);
    if ((int) $stmt->fetchColumn() > 0) {
        $pdo->exec('ALTER TABLE ' . $table . ' CHANGE ' . $oldColumn . ' ' . $newColumn . ' ' . $definition);
    }
}

function addColumnIfMissing($pdo, $table, $column, $definition) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
    ");
    $stmt->execute(['table' => $table, 'column' => $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
    }
}

function seedTable($pdo, $table, $rows) {
    foreach ($rows as $row) {
        insertDbRow($pdo, $table, $row);
    }
}

function nextAutoId($pdo, $table) {
    $stmt = $pdo->query('SELECT COALESCE(MAX(id), 0) + 1 FROM ' . $table);
    return (int) $stmt->fetchColumn();
}

function insertDbRow($pdo, $table, $row) {
    $columns = array_keys($row);
    $placeholders = array_map(fn($column) => ':' . $column, $columns);
    $stmt = $pdo->prepare('INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')');
    $stmt->execute($row);
}
?>
