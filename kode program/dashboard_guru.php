<?php
require_once 'includes/functions.php';
requireLogin('guru');

$students = readData('siswa');
$teachers = readData('guru');
$types = readData('jenis_pelanggaran');
$transactions = readData('transaksi_pelanggaran');
$spNotes = readData('sp_sanksi');
$currentUser = currentUser();
$currentTeacherId = $_SESSION['user']['ref_id'] ?? 1;
$currentTeacher = findById($teachers, $currentTeacherId);
$currentClass = $currentTeacher['kelas_ampu'] ?? '';
$classStudents = array_values(array_filter($students, fn($row) => $currentClass === '' || (string) ($row['kelas'] ?? '') === (string) $currentClass));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_sp_sanksi') {
    $studentId = (int) ($_POST['siswa_id'] ?? 0);
    $stage = (int) ($_POST['tahap'] ?? 1);
    if (!in_array($stage, [1, 2], true)) {
        $stage = 1;
    }
    $student = findById($classStudents, $studentId);
    if (!$student) {
        redirectWith('dashboard_guru.php?view=profil', 'Siswa tidak ditemukan di kelas ampu.');
    }
    $saved = false;
    foreach ($spNotes as &$note) {
        if ((string) $note['siswa_id'] === (string) $studentId && (int) $note['tahap'] === $stage) {
            $note['konsekuensi'] = trim($_POST['konsekuensi'] ?? spDefaultConsequence($stage));
            $note['tugas_tambahan'] = trim($_POST['tugas_tambahan'] ?? '');
            $note['wali_id'] = (int) $currentTeacherId;
            $note['tanggal'] = date('Y-m-d');
            $saved = true;
            break;
        }
    }
    if (!$saved) {
        $spNotes[] = [
            'id' => nextId($spNotes),
            'siswa_id' => $studentId,
            'tahap' => $stage,
            'konsekuensi' => trim($_POST['konsekuensi'] ?? spDefaultConsequence($stage)),
            'tugas_tambahan' => trim($_POST['tugas_tambahan'] ?? ''),
            'wali_id' => (int) $currentTeacherId,
            'tanggal' => date('Y-m-d'),
        ];
    }
    writeData('sp_sanksi', $spNotes);
    redirectWith('dashboard_guru.php?view=profil', 'Data SP ' . $stage . ' berhasil disimpan.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_pelanggaran') {
    $jenisId = $_POST['jenis_id'];
    $transactions[] = [
        'id' => nextId($transactions),
        'siswa_id' => (int) $_POST['siswa_id'],
        'jenis_id' => (int) $jenisId,
        'poin' => getViolationPoints($types, $jenisId),
        'guru_id' => (int) $currentTeacherId,
        'tanggal' => $_POST['tanggal'],
        'keterangan' => trim($_POST['keterangan']),
    ];
    writeData('transaksi_pelanggaran', $transactions);
    redirectWith('dashboard_guru.php?view=riwayat', 'Pelanggaran siswa berhasil disimpan.');
}

$view = $_GET['view'] ?? 'dashboard';
if (!in_array($view, ['dashboard', 'input', 'riwayat', 'profil'], true)) {
    $view = 'dashboard';
}
$activeMenu = ['input' => 'Input', 'riwayat' => 'Riwayat', 'profil' => 'Profil'][$view] ?? 'Dashboard';
$filters = ['q' => $_GET['q'] ?? '', 'kelas' => $_GET['kelas'] ?? $currentClass, 'tanggal' => $_GET['tanggal'] ?? '', 'jenis' => $_GET['jenis'] ?? ''];
$filtered = filterTransactions($transactions, $students, $types, $filters);
$teacherTransactions = array_values(array_filter($transactions, fn($row) => (string) ($row['guru_id'] ?? '') === (string) $currentTeacherId));
$latestTeacherReports = array_slice(array_reverse($teacherTransactions), 0, 4);
layoutHeader('Dashboard Wali Kelas', $activeMenu);
?>
<?php if ($view === 'dashboard'): ?>
<section class="stats-grid">
    <div class="stat-card accent-cyan"><span>Total Catatan</span><strong><?= count($transactions) ?></strong></div>
    <div class="stat-card accent-gold"><span>Total Poin</span><strong><?= totalPoin($transactions) ?></strong></div>
    <div class="stat-card accent-purple"><span>Siswa Kelas Ini</span><strong><?= count($classStudents) ?></strong></div>
    <div class="stat-card accent-rose"><span>Catatan Saya</span><strong><?= count($teacherTransactions) ?></strong></div>
</section>

<section class="dashboard-grid">
    <div class="panel guide-panel">
        <div class="section-head"><h2>Ruang Kerja Wali Kelas</h2></div>
        <div class="insight-grid">
            <div class="mini-card"><span>Kelas Ampu</span><strong class="text-value"><?= e($currentClass ?: '-') ?></strong></div>
            <div class="mini-card"><span>Poin</span><strong>Otomatis</strong></div>
            <div class="mini-card"><span>Laporan</span><strong>Cetak</strong></div>
        </div>
    </div>
    <div class="panel timeline-panel">
        <div class="section-head"><h2>Catatan Saya</h2></div>
        <div class="timeline-list">
            <?php foreach ($latestTeacherReports as $r): ?>
            <div><span><?= e($r['tanggal']) ?></span><strong><?= e(getStudentName($students, $r['siswa_id'])) ?></strong><p><?= e(getViolationName($types, $r['jenis_id'])) ?> - <?= e($r['poin']) ?> poin</p></div>
            <?php endforeach; ?>
            <?php if (!$latestTeacherReports): ?><div><strong>Belum ada catatan</strong><p>Input pelanggaran pertama melalui form di bawah.</p></div><?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($view === 'profil'): ?>
<section class="panel" id="profil">
    <div class="section-head"><h2>Profil Wali Kelas</h2></div>
    <div class="profile-dashboard simple">
        <div class="profile-grid">
            <div><span>Nama</span><strong><?= e($currentUser['nama'] ?? '-') ?></strong></div>
            <div><span>Email</span><strong><?= e($currentUser['username'] ?? '-') ?></strong></div>
            <div><span>Kelas Ampu</span><strong><?= e($currentClass ?: '-') ?></strong></div>
            <div><span>Jumlah Siswa</span><strong><?= count($classStudents) ?></strong></div>
        </div>
    </div>
</section>

<section class="panel">
    <div class="section-head"><h2>Konsekuensi SP 1</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Siswa</th><th>Kelas</th><th>Poin</th><th>SP</th><th>Konsekuensi</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($classStudents as $studentRow): $studentPoint = totalPoin(array_filter($transactions, fn($row) => (string) $row['siswa_id'] === (string) $studentRow['id'])); $level = spLevel($studentPoint); if ($level !== 1) continue; $note = getSpNote($spNotes, $studentRow['id'], 1); ?>
        <tr>
            <td><?= e($studentRow['nama']) ?></td>
            <td><?= e($studentRow['kelas']) ?></td>
            <td><?= e($studentPoint) ?></td>
            <td><?= e(spLabel($level)) ?></td>
            <td><?= e($note['konsekuensi'] ?? spDefaultConsequence(1)) ?></td>
            <td>
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="save_sp_sanksi">
                    <input type="hidden" name="tahap" value="1">
                    <input type="hidden" name="siswa_id" value="<?= e($studentRow['id']) ?>">
                    <textarea name="konsekuensi" required placeholder="Tulis konsekuensi SP 1"><?= e($note['konsekuensi'] ?? '') ?></textarea>
                    <button class="btn small primary" type="submit">Simpan</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>

<section class="panel">
    <div class="section-head"><h2>Keterangan dan Tugas SP 2</h2></div>
    <div class="table-wrap"><table><thead><tr><th>Siswa</th><th>Kelas</th><th>Poin</th><th>Keterangan</th><th>Tugas Tambahan</th><th>Aksi</th></tr></thead><tbody>
        <?php foreach ($classStudents as $studentRow): $studentPoint = totalPoin(array_filter($transactions, fn($row) => (string) $row['siswa_id'] === (string) $studentRow['id'])); $level = spLevel($studentPoint); if ($level !== 2) continue; $note = getSpNote($spNotes, $studentRow['id'], 2); ?>
        <tr>
            <td><?= e($studentRow['nama']) ?></td>
            <td><?= e($studentRow['kelas']) ?></td>
            <td><?= e($studentPoint) ?></td>
            <td><?= e($note['konsekuensi'] ?? spDefaultConsequence(2)) ?></td>
            <td><?= e($note['tugas_tambahan'] ?? 'Belum ada tugas tambahan.') ?></td>
            <td>
                <form method="post" class="inline-form">
                    <input type="hidden" name="action" value="save_sp_sanksi">
                    <input type="hidden" name="tahap" value="2">
                    <input type="hidden" name="siswa_id" value="<?= e($studentRow['id']) ?>">
                    <textarea name="konsekuensi" required><?= e($note['konsekuensi'] ?? spDefaultConsequence(2)) ?></textarea>
                    <textarea name="tugas_tambahan" required placeholder="Tulis tugas tambahan SP 2"><?= e($note['tugas_tambahan'] ?? '') ?></textarea>
                    <button class="btn small primary" type="submit">Simpan</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($view === 'input'): ?>
<section class="panel" id="input">
    <div class="section-head"><h2>Input Pelanggaran Siswa</h2></div>
    <form method="post" class="form-grid">
        <input type="hidden" name="action" value="save_pelanggaran">
        <label>Kelas<input readonly value="<?= e($currentClass) ?>"></label>
        <label>Pilih Siswa<select name="siswa_id" required><?php foreach ($classStudents as $s): ?><option value="<?= e($s['id']) ?>"><?= e($s['nama'] . ' - ' . $s['kelas']) ?></option><?php endforeach; ?></select></label>
        <label>Jenis Pelanggaran<select name="jenis_id" required id="jenisPelanggaran"><?php foreach ($types as $t): ?><option value="<?= e($t['id']) ?>" data-poin="<?= e($t['poin']) ?>"><?= e($t['nama']) ?></option><?php endforeach; ?></select></label>
        <label>Poin Otomatis<input id="poinOtomatis" readonly value="<?= e($types[0]['poin'] ?? 0) ?>"></label>
        <label>Tanggal<input type="date" name="tanggal" required value="<?= date('Y-m-d') ?>"></label>
        <label class="wide">Keterangan<textarea name="keterangan" required placeholder="Tulis keterangan pelanggaran"></textarea></label>
        <button class="btn primary" type="submit">Simpan Pelanggaran</button>
    </form>
</section>
<?php endif; ?>

<?php if ($view === 'riwayat'): ?>
<section class="panel print-area" id="riwayat">
    <div class="section-head"><h2>Riwayat Pelanggaran</h2><button class="btn primary no-print" onclick="window.print()">Cetak Laporan</button></div>
    <form class="filter-grid no-print" method="get">
        <input type="hidden" name="view" value="riwayat">
        <input name="q" placeholder="Cari nama/keterangan..." value="<?= e($filters['q']) ?>">
        <input name="kelas" placeholder="Kelas" value="<?= e($filters['kelas']) ?>">
        <input type="date" name="tanggal" value="<?= e($filters['tanggal']) ?>">
        <select name="jenis"><option value="">Semua jenis</option><?php foreach ($types as $t): ?><option value="<?= e($t['id']) ?>" <?= ((string) $filters['jenis'] === (string) $t['id']) ? 'selected' : '' ?>><?= e($t['nama']) ?></option><?php endforeach; ?></select>
        <button class="btn" type="submit">Cari / Filter</button>
    </form>
    <div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Siswa</th><th>Kelas</th><th>Jenis</th><th>Poin</th><th>Wali Kelas</th><th>Keterangan</th></tr></thead><tbody>
        <?php foreach ($filtered as $r): ?>
        <tr><td><?= e($r['tanggal']) ?></td><td><?= e(getStudentName($students, $r['siswa_id'])) ?></td><td><?= e(getStudentClass($students, $r['siswa_id'])) ?></td><td><?= e(getViolationName($types, $r['jenis_id'])) ?></td><td><?= e($r['poin']) ?></td><td><?= e(getTeacherName($teachers, $r['guru_id'])) ?></td><td><?= e($r['keterangan']) ?></td></tr>
        <?php endforeach; ?>
    </tbody></table></div>
</section>
<?php endif; ?>
<?php layoutFooter(); ?>
