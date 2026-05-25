<?php
if (session_status() === PHP_SESSION_NONE && PHP_SAPI !== 'cli') {
    session_start();
}
require_once __DIR__ . '/db.php';

function readData($file) {
    $tables = dataTables();
    if (!isset($tables[$file])) {
        return [];
    }
    $stmt = db()->query('SELECT * FROM ' . $tables[$file] . ' ORDER BY id ASC');
    return $stmt->fetchAll();
}

function writeData($file, $data) {
    $tables = dataTables();
    if (!isset($tables[$file])) {
        return;
    }
    $table = $tables[$file];
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
        $pdo->exec('DELETE FROM ' . $table);
        foreach (array_values($data) as $row) {
            insertDbRow($pdo, $table, $row);
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
        throw $e;
    }
}

function dataTables() {
    return [
        'users' => 'users',
        'siswa' => 'siswa',
        'guru' => 'guru',
        'jenis_pelanggaran' => 'jenis_pelanggaran',
        'transaksi_pelanggaran' => 'transaksi_pelanggaran',
        'sp_sanksi' => 'sp_sanksi',
    ];
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function nextId($items) {
    $max = 0;
    foreach ($items as $item) {
        $max = max($max, (int) ($item['id'] ?? 0));
    }
    return $max + 1;
}

function findById($items, $id) {
    foreach ($items as $item) {
        if ((string) ($item['id'] ?? '') === (string) $id) {
            return $item;
        }
    }
    return null;
}

function valueExists($items, $field, $value, $exceptId = null) {
    foreach ($items as $item) {
        if ($exceptId !== null && (string) ($item['id'] ?? '') === (string) $exceptId) {
            continue;
        }
        if (strtolower((string) ($item[$field] ?? '')) === strtolower((string) $value)) {
            return true;
        }
    }
    return false;
}

function requireLogin($role = null) {
    if (!isset($_SESSION['user'])) {
        header('Location: index.php');
        exit;
    }
    if ($role && $_SESSION['user']['role'] !== $role) {
        header('Location: dashboard_' . $_SESSION['user']['role'] . '.php');
        exit;
    }
}

function userName() {
    return $_SESSION['user']['nama'] ?? 'Pengguna';
}

function roleLabel() {
    return ucfirst($_SESSION['user']['role'] ?? '');
}

function redirectWith($url, $message = null) {
    if ($message) {
        $_SESSION['flash'] = $message;
    }
    header('Location: ' . $url);
    exit;
}

function flash() {
    if (!isset($_SESSION['flash'])) {
        return '';
    }
    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert success">' . e($message) . '</div>';
}

function totalPoin($transactions) {
    return array_sum(array_map(fn($item) => (int) ($item['poin'] ?? 0), $transactions));
}

function getStudentName($students, $id) {
    $student = findById($students, $id);
    return $student['nama'] ?? '-';
}

function getStudentClass($students, $id) {
    $student = findById($students, $id);
    return $student['kelas'] ?? '-';
}

function getTeacherName($teachers, $id) {
    $teacher = findById($teachers, $id);
    return $teacher['nama'] ?? '-';
}

function getTeacherClass($teachers, $id) {
    $teacher = findById($teachers, $id);
    return $teacher['kelas_ampu'] ?? '-';
}

function classOptions($students) {
    $classes = array_merge(defaultClasses(), array_map(fn($row) => $row['kelas'] ?? '', $students));
    sort($classes);
    return array_values(array_unique(array_filter($classes)));
}

function isValidClass($class) {
    return in_array((string) $class, classOptions(readData('siswa')), true);
}

function renderClassOptions($selected = '') {
    $html = '<option value="">Pilih kelas</option>';
    foreach (classOptions(readData('siswa')) as $class) {
        $isSelected = ((string) $selected === (string) $class) ? ' selected' : '';
        $html .= '<option value="' . e($class) . '"' . $isSelected . '>' . e($class) . '</option>';
    }
    return $html;
}

function getViolationName($types, $id) {
    $type = findById($types, $id);
    return $type['nama'] ?? '-';
}

function getViolationPoints($types, $id) {
    $type = findById($types, $id);
    return (int) ($type['poin'] ?? 0);
}

function currentUser() {
    $id = $_SESSION['user']['id'] ?? null;
    if (!$id) {
        return $_SESSION['user'] ?? [];
    }
    $user = findById(readData('users'), $id);
    if ($user) {
        $_SESSION['user'] = $user;
        return $user;
    }
    return $_SESSION['user'] ?? [];
}

function renderProfilePhoto($user) {
    return '<span>' . e(strtoupper(substr($user['nama'] ?? 'P', 0, 1))) . '</span>';
}

function spLevel($points) {
    if ($points >= 100) return 3;
    if ($points >= 75) return 2;
    if ($points >= 30) return 1;
    return 0;
}

function spLabel($level) {
    return $level > 0 ? 'SP ' . $level : 'Belum SP';
}

function spDefaultConsequence($level) {
    if ($level === 3) return 'Dikeluarkan dari sekolah.';
    if ($level === 2) return 'Home schooling selama 3 minggu, lalu dapat kembali bersekolah setelah masa pembinaan selesai.';
    if ($level === 1) return 'Konsekuensi ditentukan oleh wali kelas.';
    return 'Belum ada surat peringatan.';
}

function getSpNote($notes, $studentId, $stage) {
    foreach ($notes as $note) {
        if ((string) $note['siswa_id'] === (string) $studentId && (int) $note['tahap'] === (int) $stage) {
            return $note;
        }
    }
    return null;
}

function spConsequence($notes, $studentId, $level) {
    $note = getSpNote($notes, $studentId, $level);
    return $note['konsekuensi'] ?? spDefaultConsequence($level);
}

function spTask($notes, $studentId, $level) {
    $note = getSpNote($notes, $studentId, $level);
    return $note['tugas_tambahan'] ?? '';
}

function filterText($value, $query) {
    return $query === '' || stripos((string) $value, $query) !== false;
}

function filterTransactions($transactions, $students, $types, $filters) {
    return array_values(array_filter($transactions, function ($row) use ($students, $types, $filters) {
        $studentName = getStudentName($students, $row['siswa_id'] ?? '');
        $studentClass = getStudentClass($students, $row['siswa_id'] ?? '');
        $typeName = getViolationName($types, $row['jenis_id'] ?? '');
        $nameOk = filterText($studentName, trim($filters['nama'] ?? ''));
        $classOk = filterText($studentClass, trim($filters['kelas'] ?? ''));
        $typeOk = ($filters['jenis'] ?? '') === '' || (string) ($row['jenis_id'] ?? '') === (string) $filters['jenis'];
        $dateOk = ($filters['tanggal'] ?? '') === '' || (string) ($row['tanggal'] ?? '') === (string) $filters['tanggal'];
        $freeOk = filterText($studentName . ' ' . $studentClass . ' ' . $typeName . ' ' . ($row['keterangan'] ?? ''), trim($filters['q'] ?? ''));
        return $nameOk && $classOk && $typeOk && $dateOk && $freeOk;
    }));
}

function layoutHeader($title, $active) {
    $role = $_SESSION['user']['role'] ?? '';
    $links = [
        'admin' => [
            ['dashboard_admin.php', 'Dashboard'],
            ['dashboard_admin.php?view=siswa', 'Siswa'],
            ['dashboard_admin.php?view=guru', 'Wali Kelas'],
            ['dashboard_admin.php?view=jenis', 'Pelanggaran'],
            ['dashboard_admin.php?view=laporan', 'Laporan'],
            ['dashboard_admin.php?view=profil', 'Profil'],
        ],
        'guru' => [
            ['dashboard_guru.php', 'Dashboard'],
            ['dashboard_guru.php?view=input', 'Input'],
            ['dashboard_guru.php?view=riwayat', 'Riwayat'],
            ['dashboard_guru.php?view=profil', 'Profil'],
        ],
        'siswa' => [
            ['dashboard_siswa.php', 'Dashboard'],
            ['dashboard_siswa.php?view=profil', 'Profil'],
            ['dashboard_siswa.php?view=riwayat', 'Riwayat'],
        ],
    ];
    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . '</title><link rel="stylesheet" href="css/style.css"></head><body>';
    echo '<div class="app-shell app-enter"><aside class="sidebar"><nav>';
    foreach ($links[$role] ?? [] as $link) {
        $isActive = $active === $link[1] ? 'active' : '';
        echo '<a class="' . $isActive . '" href="' . e($link[0]) . '">' . e($link[1]) . '</a>';
    }
    echo '</nav><a class="logout js-logout" href="logout.php">Logout</a></aside>';
    echo '<main class="content"><header class="topbar"><div><p class="eyebrow">' . e(roleLabel()) . '</p><h1>' . e($title) . '</h1><p>Selamat datang, ' . e(userName()) . '.</p></div></header>';
    echo flash();
}

function layoutFooter() {
    echo '</main></div><div class="modal-backdrop" id="confirmModal" aria-hidden="true"><div class="confirm-modal"><h2 data-modal-title>Konfirmasi</h2><p data-modal-message>Apakah kamu yakin?</p><div class="modal-actions"><button class="modal-btn modal-cancel" type="button" data-modal-cancel>Batal</button><button class="modal-btn modal-confirm" type="button" data-modal-confirm>Ya, lanjutkan</button></div></div></div><script src="js/script.js"></script></body></html>';
}
?>
