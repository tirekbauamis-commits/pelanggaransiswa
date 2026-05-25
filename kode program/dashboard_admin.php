<?php
require_once 'includes/functions.php';
requireLogin('admin');

$students = readData('siswa');
$teachers = readData('guru');
$types = readData('jenis_pelanggaran');
$transactions = readData('transaksi_pelanggaran');
$currentUser = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_siswa') {
        $id = $_POST['id'] ?? '';
        $payload = ['nis' => trim($_POST['nis']), 'nama' => trim($_POST['nama']), 'kelas' => trim($_POST['kelas']), 'jk' => trim($_POST['jk'])];
        if (valueExists($students, 'nis', $payload['nis'], $id === '' ? null : $id)) {
            redirectWith('dashboard_admin.php?view=siswa', 'NIS sudah digunakan oleh siswa lain.');
        }
        if (valueExists($students, 'nama', $payload['nama'], $id === '' ? null : $id)) {
            redirectWith('dashboard_admin.php?view=siswa', 'Nama siswa sudah digunakan oleh siswa lain.');
        }
        if (!isValidClass($payload['kelas'])) {
            redirectWith('dashboard_admin.php?view=siswa', 'Pilih kelas dari daftar yang tersedia.');
        }
        if ($id === '') {
            $payload['id'] = nextId($students);
            $students[] = $payload;
        } else {
            foreach ($students as &$row) {
                if ((string) $row['id'] === (string) $id) {
                    $row = array_merge($row, $payload);
                }
            }
        }
        writeData('siswa', $students);
        redirectWith('dashboard_admin.php?view=siswa', 'Data siswa berhasil disimpan.');
    }

    if ($action === 'delete_siswa') {
        $students = array_values(array_filter($students, fn($row) => (string) $row['id'] !== (string) $_POST['id']));
        writeData('siswa', $students);
        redirectWith('dashboard_admin.php?view=siswa', 'Data siswa berhasil dihapus.');
    }

    if ($action === 'save_guru') {
        $id = $_POST['id'] ?? '';
        $payload = ['nip' => trim($_POST['nip']), 'nama' => trim($_POST['nama']), 'kelas_ampu' => trim($_POST['kelas_ampu']), 'no_hp' => trim($_POST['no_hp'])];
        if (!isValidClass($payload['kelas_ampu'])) {
            redirectWith('dashboard_admin.php?view=guru', 'Pilih kelas ampu dari daftar yang tersedia.');
        }
        if ($id === '') {
            $payload['id'] = nextId($teachers);
            $teachers[] = $payload;
        } else {
            foreach ($teachers as &$row) {
                if ((string) $row['id'] === (string) $id) {
                    $row = array_merge($row, $payload);
                }
            }
        }
        writeData('guru', $teachers);
        redirectWith('dashboard_admin.php?view=guru', 'Data wali kelas berhasil disimpan.');
    }

    if ($action === 'delete_guru') {
        $teachers = array_values(array_filter($teachers, fn($row) => (string) $row['id'] !== (string) $_POST['id']));
        writeData('guru', $teachers);
        redirectWith('dashboard_admin.php?view=guru', 'Data wali kelas berhasil dihapus.');
    }

    if ($action === 'save_jenis') {
        $id = $_POST['id'] ?? '';
        $payload = ['nama' => trim($_POST['nama']), 'poin' => (int) $_POST['poin'], 'kategori' => trim($_POST['kategori'])];
        if ($id === '') {
            $payload['id'] = nextId($types);
            $types[] = $payload;
        } else {
            foreach ($types as &$row) {
                if ((string) $row['id'] === (string) $id) {
                    $row = array_merge($row, $payload);
                }
            }
        }
        writeData('jenis_pelanggaran', $types);
        redirectWith('dashboard_admin.php?view=jenis', 'Jenis pelanggaran berhasil disimpan.');
    }

    if ($action === 'delete_jenis') {
        $types = array_values(array_filter($types, fn($row) => (string) $row['id'] !== (string) $_POST['id']));
        writeData('jenis_pelanggaran', $types);
        redirectWith('dashboard_admin.php?view=jenis', 'Jenis pelanggaran berhasil dihapus.');
    }

    if ($action === 'save_transaksi') {
        $id = $_POST['id'] ?? '';
        $jenisId = $_POST['jenis_id'] ?? '';
        $student = findById($students, $_POST['siswa_id'] ?? '');
        $teacher = findById($teachers, $_POST['guru_id'] ?? '');
        $selectedClass = trim($_POST['kelas_input'] ?? '');
        if (!$student || !$teacher || $selectedClass === '' || ($student['kelas'] ?? '') !== $selectedClass || ($teacher['kelas_ampu'] ?? '') !== $selectedClass) {
            redirectWith('dashboard_admin.php?view=laporan', 'Pilih kelas terlebih dahulu, lalu pilih siswa dari kelas tersebut.');
        }
        $payload = [
            'siswa_id' => (int) $_POST['siswa_id'],
            'jenis_id' => (int) $jenisId,
            'poin' => getViolationPoints($types, $jenisId),
            'guru_id' => (int) $_POST['guru_id'],
            'tanggal' => $_POST['tanggal'],
            'keterangan' => trim($_POST['keterangan']),
        ];
        if ($id === '') {
            $payload['id'] = nextId($transactions);
            $transactions[] = $payload;
        } else {
            foreach ($transactions as &$row) {
                if ((string) $row['id'] === (string) $id) {
                    $row = array_merge($row, $payload);
                }
            }
        }
        writeData('transaksi_pelanggaran', $transactions);
        redirectWith('dashboard_admin.php?view=laporan', 'Laporan pelanggaran berhasil disimpan.');
    }

    if ($action === 'delete_transaksi') {
        $transactions = array_values(array_filter($transactions, fn($row) => (string) $row['id'] !== (string) $_POST['id']));
        writeData('transaksi_pelanggaran', $transactions);
        redirectWith('dashboard_admin.php?view=laporan', 'Laporan pelanggaran berhasil dihapus.');
    }
}

$editSiswa = isset($_GET['edit_siswa']) ? findById($students, $_GET['edit_siswa']) : null;
$editGuru = isset($_GET['edit_guru']) ? findById($teachers, $_GET['edit_guru']) : null;
$editJenis = isset($_GET['edit_jenis']) ? findById($types, $_GET['edit_jenis']) : null;
$editReport = isset($_GET['edit_laporan']) ? findById($transactions, $_GET['edit_laporan']) : null;
$qSiswa = trim($_GET['q_siswa'] ?? '');
$qGuru = trim($_GET['q_guru'] ?? '');
$view = $_GET['view'] ?? 'dashboard';
$allowedViews = ['dashboard', 'siswa', 'guru', 'jenis', 'laporan', 'profil'];
if (!in_array($view, $allowedViews, true)) {
    $view = 'dashboard';
}
$activeMenu = ['siswa' => 'Siswa', 'guru' => 'Wali Kelas', 'jenis' => 'Pelanggaran', 'laporan' => 'Laporan', 'profil' => 'Profil'][$view] ?? 'Dashboard';
$reportFilters = ['nama' => $_GET['nama'] ?? '', 'kelas' => $_GET['kelas'] ?? '', 'tanggal' => $_GET['tanggal'] ?? '', 'jenis' => $_GET['jenis'] ?? ''];
$filteredReports = filterTransactions($transactions, $students, $types, $reportFilters);
$latestReports = array_slice(array_reverse($transactions), 0, 4);
$classes = classOptions($students);
$highestStudent = null;
$highestPoint = 0;
foreach ($students as $studentRow) {
    $point = totalPoin(array_filter($transactions, fn($row) => (string) $row['siswa_id'] === (string) $studentRow['id']));
    if ($point > $highestPoint) {
        $highestPoint = $point;
        $highestStudent = $studentRow;
    }
}

layoutHeader('Dashboard Admin', $activeMenu);
?>
<?php if ($view === 'dashboard'): ?>
<section class="stats-grid">
    <div class="stat-card accent-cyan"><span>Jumlah Siswa</span><strong><?= count($students) ?></strong></div>
    <div class="stat-card accent-gold"><span>Jumlah Wali Kelas</span><strong><?= count($teachers) ?></strong></div>
    <div class="stat-card accent-purple"><span>Jumlah Pelanggaran</span><strong><?= count($transactions) ?></strong></div>
    <div class="stat-card accent-rose"><span>Total Poin</span><strong><?= totalPoin($transactions) ?></strong></div>
</section>

<section class="dashboard-grid">
    <div class="panel insight-panel">
        <div class="section-head"><h2>Monitoring Cepat</h2></div>
        <div class="insight-grid">
            <div class="mini-card"><span>Prioritas</span><strong><?= e($highestStudent['nama'] ?? '-') ?></strong><p><?= e($highestPoint) ?> poin</p></div>
            <div class="mini-card"><span>Jenis</span><strong><?= count($types) ?></strong><p>Pelanggaran</p></div>
            <div class="mini-card"><span>Laporan</span><strong><?= count($filteredReports) ?></strong><p>Data tampil</p></div>
        </div>
    </div>
    <div class="panel timeline-panel">
        <div class="section-head"><h2>Aktivitas Terbaru</h2></div>
        <div class="timeline-list">
            <?php foreach ($latestReports as $r): ?>
            <div><span><?= e($r['tanggal']) ?></span><strong><?= e(getStudentName($students, $r['siswa_id'])) ?></strong><p><?= e(getViolationName($types, $r['jenis_id'])) ?> - <?= e($r['poin']) ?> poin</p></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($view === 'profil'): ?>
<section class="panel" id="profil">
    <div class="section-head"><h2>Profil Admin</h2></div>
    <div class="profile-dashboard simple">
        <div class="profile-grid">
            <div><span>Nama</span><strong><?= e($currentUser['nama'] ?? '-') ?></strong></div>
            <div><span>Email</span><strong><?= e($currentUser['username'] ?? '-') ?></strong></div>
            <div><span>Peran</span><strong>Admin</strong></div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($view === 'siswa'): ?>
<section class="panel" id="siswa">
    <div class="section-head"><h2>Kelola Data Siswa</h2><a class="btn ghost" href="dashboard_admin.php?view=siswa">Tambah Baru</a></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="save_siswa">
        <input type="hidden" name="id" value="<?= e($editSiswa['id'] ?? '') ?>">
        <label>NIS<input name="nis" required value="<?= e($editSiswa['nis'] ?? '') ?>"></label>
        <label>Nama<input name="nama" required value="<?= e($editSiswa['nama'] ?? '') ?>"></label>
        <label>Kelas<select name="kelas" required><?= renderClassOptions($editSiswa['kelas'] ?? '') ?></select></label>
        <label>Jenis Kelamin<select name="jk"><option <?= (($editSiswa['jk'] ?? '') === 'L') ? 'selected' : '' ?>>L</option><option <?= (($editSiswa['jk'] ?? '') === 'P') ? 'selected' : '' ?>>P</option></select></label>
        <button class="btn primary" type="submit">Simpan Siswa</button>
    </form>
    <form class="search-row" method="get"><input type="hidden" name="view" value="siswa"><input name="q_siswa" placeholder="Cari siswa..." value="<?= e($qSiswa) ?>"><button class="btn" type="submit">Cari</button></form>
    <div class="table-wrap"><table><thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>JK</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($students as $s): if (!filterText($s['nama'] . $s['nis'] . $s['kelas'], $qSiswa)) continue; ?>
        <tr><td><?= e($s['nis']) ?></td><td><?= e($s['nama']) ?></td><td><?= e($s['kelas']) ?></td><td><?= e($s['jk']) ?></td><td class="actions"><a class="btn small" href="?view=siswa&edit_siswa=<?= e($s['id']) ?>">Edit</a><form method="post" data-confirm-title="Hapus siswa?" data-confirm-message="Data siswa <?= e($s['nama']) ?> akan dihapus dari sistem."><input type="hidden" name="action" value="delete_siswa"><input type="hidden" name="id" value="<?= e($s['id']) ?>"><button class="btn small danger">Hapus</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($view === 'guru'): ?>
<section class="panel" id="guru">
    <div class="section-head"><h2>Kelola Data Wali Kelas</h2><a class="btn ghost" href="dashboard_admin.php?view=guru">Tambah Baru</a></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="save_guru">
        <input type="hidden" name="id" value="<?= e($editGuru['id'] ?? '') ?>">
        <label>NIP<input name="nip" required value="<?= e($editGuru['nip'] ?? '') ?>"></label>
        <label>Nama<input name="nama" required value="<?= e($editGuru['nama'] ?? '') ?>"></label>
        <label>Kelas Ampu<select name="kelas_ampu" required><?= renderClassOptions($editGuru['kelas_ampu'] ?? '') ?></select></label>
        <label>No HP<input name="no_hp" required value="<?= e($editGuru['no_hp'] ?? '') ?>"></label>
        <button class="btn primary" type="submit">Simpan Wali Kelas</button>
    </form>
    <form class="search-row" method="get"><input type="hidden" name="view" value="guru"><input name="q_guru" placeholder="Cari wali kelas..." value="<?= e($qGuru) ?>"><button class="btn" type="submit">Cari</button></form>
    <div class="table-wrap"><table><thead><tr><th>NIP</th><th>Nama</th><th>Kelas Ampu</th><th>No HP</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($teachers as $g): if (!filterText($g['nama'] . $g['nip'] . $g['kelas_ampu'], $qGuru)) continue; ?>
        <tr><td><?= e($g['nip']) ?></td><td><?= e($g['nama']) ?></td><td><?= e($g['kelas_ampu']) ?></td><td><?= e($g['no_hp']) ?></td><td class="actions"><a class="btn small" href="?view=guru&edit_guru=<?= e($g['id']) ?>">Edit</a><form method="post" data-confirm-title="Hapus wali kelas?" data-confirm-message="Data wali kelas <?= e($g['nama']) ?> akan dihapus dari sistem."><input type="hidden" name="action" value="delete_guru"><input type="hidden" name="id" value="<?= e($g['id']) ?>"><button class="btn small danger">Hapus</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($view === 'jenis'): ?>
<section class="panel" id="jenis">
    <div class="section-head"><h2>Kelola Jenis Pelanggaran</h2><a class="btn ghost" href="dashboard_admin.php?view=jenis">Tambah Baru</a></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="save_jenis">
        <input type="hidden" name="id" value="<?= e($editJenis['id'] ?? '') ?>">
        <label>Nama Pelanggaran<input name="nama" required value="<?= e($editJenis['nama'] ?? '') ?>"></label>
        <label>Poin<input type="number" name="poin" min="1" required value="<?= e($editJenis['poin'] ?? '') ?>"></label>
        <label>Kategori<input name="kategori" required value="<?= e($editJenis['kategori'] ?? '') ?>"></label>
        <button class="btn primary" type="submit">Simpan Jenis</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Jenis</th><th>Poin</th><th>Kategori</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($types as $t): ?>
        <tr><td><?= e($t['nama']) ?></td><td><?= e($t['poin']) ?></td><td><?= e($t['kategori']) ?></td><td class="actions"><a class="btn small" href="?view=jenis&edit_jenis=<?= e($t['id']) ?>">Edit</a><form method="post" data-confirm-title="Hapus jenis pelanggaran?" data-confirm-message="<?= e($t['nama']) ?> akan dihapus dari daftar jenis pelanggaran."><input type="hidden" name="action" value="delete_jenis"><input type="hidden" name="id" value="<?= e($t['id']) ?>"><button class="btn small danger">Hapus</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($view === 'laporan'): ?>
<section class="panel print-area" id="laporan">
    <div class="section-head"><h2>Laporan Pelanggaran</h2><button class="btn primary no-print" onclick="window.print()">Cetak Laporan</button></div>
    <form method="post" class="form-grid no-print">
        <input type="hidden" name="action" value="save_transaksi">
        <input type="hidden" name="id" value="<?= e($editReport['id'] ?? '') ?>">
        <label>Kelas<select id="kelasInput" name="kelas_input" data-student-filter required><option value="">Pilih kelas</option><?php foreach ($classes as $class): ?><option value="<?= e($class) ?>" <?= ((string) ($editReport ? getStudentClass($students, $editReport['siswa_id']) : '') === (string) $class) ? 'selected' : '' ?>><?= e($class) ?></option><?php endforeach; ?></select></label>
        <label>Siswa<select name="siswa_id" id="siswaInput" required><option value="" data-kelas="">Pilih kelas dulu</option><?php foreach ($students as $s): ?><option value="<?= e($s['id']) ?>" data-kelas="<?= e($s['kelas']) ?>" <?= ((string) ($editReport['siswa_id'] ?? '') === (string) $s['id']) ? 'selected' : '' ?>><?= e($s['nama'] . ' - ' . $s['kelas']) ?></option><?php endforeach; ?></select></label>
        <label>Jenis<select name="jenis_id" required><?php foreach ($types as $t): ?><option value="<?= e($t['id']) ?>" <?= ((string) ($editReport['jenis_id'] ?? '') === (string) $t['id']) ? 'selected' : '' ?>><?= e($t['nama']) ?></option><?php endforeach; ?></select></label>
        <label>Wali Kelas<select name="guru_id" id="waliInput" required><?php foreach ($teachers as $g): ?><option value="<?= e($g['id']) ?>" data-kelas="<?= e($g['kelas_ampu']) ?>" <?= ((string) ($editReport['guru_id'] ?? '') === (string) $g['id']) ? 'selected' : '' ?>><?= e($g['nama'] . ' - ' . $g['kelas_ampu']) ?></option><?php endforeach; ?></select></label>
        <label>Tanggal<input type="date" name="tanggal" required value="<?= e($editReport['tanggal'] ?? date('Y-m-d')) ?>"></label>
        <label class="wide">Keterangan<input name="keterangan" required value="<?= e($editReport['keterangan'] ?? '') ?>"></label>
        <button class="btn primary" type="submit"><?= $editReport ? 'Update Laporan' : 'Tambah Laporan' ?></button>
        <?php if ($editReport): ?><a class="btn ghost" href="dashboard_admin.php?view=laporan">Batal Edit</a><?php endif; ?>
    </form>
    <form class="filter-grid no-print" method="get">
        <input type="hidden" name="view" value="laporan">
        <input name="nama" placeholder="Nama siswa" value="<?= e($reportFilters['nama']) ?>">
        <select name="kelas"><?= renderClassOptions($reportFilters['kelas']) ?></select>
        <input type="date" name="tanggal" value="<?= e($reportFilters['tanggal']) ?>">
        <select name="jenis"><option value="">Semua jenis</option><?php foreach ($types as $t): ?><option value="<?= e($t['id']) ?>" <?= ((string) $reportFilters['jenis'] === (string) $t['id']) ? 'selected' : '' ?>><?= e($t['nama']) ?></option><?php endforeach; ?></select>
        <button class="btn" type="submit">Filter</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Jenis</th><th>Poin</th><th>Wali Kelas</th><th>Keterangan</th><th class="no-print">Aksi</th></tr></thead><tbody>
        <?php foreach ($filteredReports as $r): ?>
        <tr><td><?= e($r['tanggal']) ?></td><td><?= e(getStudentName($students, $r['siswa_id'])) ?></td><td><?= e(getStudentClass($students, $r['siswa_id'])) ?></td><td><?= e(getViolationName($types, $r['jenis_id'])) ?></td><td><?= e($r['poin']) ?></td><td><?= e(getTeacherName($teachers, $r['guru_id'])) ?></td><td><?= e($r['keterangan']) ?></td><td class="actions no-print"><a class="btn small" href="?view=laporan&edit_laporan=<?= e($r['id']) ?>">Edit</a><form method="post" data-confirm-title="Hapus laporan?" data-confirm-message="Riwayat pelanggaran <?= e(getStudentName($students, $r['siswa_id'])) ?> pada <?= e($r['tanggal']) ?> akan dihapus."><input type="hidden" name="action" value="delete_transaksi"><input type="hidden" name="id" value="<?= e($r['id']) ?>"><button class="btn small danger">Hapus</button></form></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>
<?php layoutFooter(); ?>
