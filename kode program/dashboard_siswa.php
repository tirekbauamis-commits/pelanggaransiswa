<?php
require_once 'includes/functions.php';
requireLogin('siswa');

$students = readData('siswa');
$teachers = readData('guru');
$types = readData('jenis_pelanggaran');
$transactions = readData('transaksi_pelanggaran');
$spNotes = readData('sp_sanksi');
$currentUser = currentUser();
$studentId = $_SESSION['user']['ref_id'] ?? 1;
$student = findById($students, $studentId) ?? $students[0];
if ($student && ($currentUser['nama'] ?? '') !== ($student['nama'] ?? '')) {
    $currentUser['nama'] = $student['nama'];
    $_SESSION['user']['nama'] = $student['nama'];
}
$mine = array_values(array_filter($transactions, fn($row) => (string) $row['siswa_id'] === (string) $student['id']));
$myPoint = totalPoin($mine);
$spLevel = spLevel($myPoint);
$homeroom = null;
foreach ($teachers as $teacher) {
    if (($teacher['kelas_ampu'] ?? '') === ($student['kelas'] ?? '')) {
        $homeroom = $teacher;
        break;
    }
}
$risk = $myPoint >= 75 ? 'Pembinaan intensif' : ($myPoint >= 40 ? 'Perlu perhatian' : 'Terkendali');
$latestMine = array_slice(array_reverse($mine), 0, 3);
$view = $_GET['view'] ?? 'dashboard';
$activeMenu = $view === 'profil' ? 'Profil' : ($view === 'riwayat' ? 'Riwayat' : 'Dashboard');

layoutHeader('Dashboard Siswa', $activeMenu);
?>
<?php if ($view === 'dashboard'): ?>
<section class="stats-grid">
    <div class="stat-card accent-cyan"><span>Total Pelanggaran</span><strong><?= count($mine) ?></strong></div>
    <div class="stat-card accent-gold"><span>Total Poin</span><strong><?= e($myPoint) ?></strong></div>
    <div class="stat-card accent-purple"><span>Kelas</span><strong class="text-value"><?= e($student['kelas']) ?></strong></div>
</section>

<section class="dashboard-grid">
    <div class="panel student-summary">
        <div class="section-head"><h2>Status Kedisiplinan</h2></div>
        <div class="point-meter"><span style="width: <?= min(100, $myPoint) ?>%"></span></div>
    </div>
    <div class="panel timeline-panel">
        <div class="section-head"><h2>Catatan Terbaru</h2></div>
        <div class="timeline-list">
            <?php foreach ($latestMine as $r): ?>
            <div><span><?= e($r['tanggal']) ?></span><strong><?= e(getViolationName($types, $r['jenis_id'])) ?></strong><p><?= e($r['poin']) ?> poin oleh wali kelas <?= e(getTeacherName($teachers, $r['guru_id'])) ?></p></div>
            <?php endforeach; ?>
            <?php if (!$latestMine): ?><div><strong>Belum ada catatan</strong><p>Riwayat pelanggaran masih kosong.</p></div><?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($view === 'profil'): ?>
<section class="panel" id="profil">
    <div class="section-head"><h2>Profil Pribadi</h2></div>
    <div class="profile-dashboard simple">
        <div class="profile-grid">
            <div><span>NIS</span><strong><?= e($student['nis']) ?></strong></div>
            <div><span>Nama</span><strong><?= e($student['nama']) ?></strong></div>
            <div><span>Kelas</span><strong><?= e($student['kelas']) ?></strong></div>
            <div><span>Wali Kelas</span><strong><?= e($homeroom['nama'] ?? '-') ?></strong></div>
            <div><span>Total Poin</span><strong><?= e($myPoint) ?></strong></div>
            <div><span>Status SP</span><strong><?= e(spLabel($spLevel)) ?></strong></div>
            <div class="wide"><span>Konsekuensi</span><strong><?= e(spConsequence($spNotes, $student['id'], $spLevel)) ?></strong></div>
            <?php if ($spLevel === 2): ?>
            <div class="wide"><span>Tugas Tambahan SP 2</span><strong><?= e(spTask($spNotes, $student['id'], 2) ?: 'Belum ada tugas tambahan dari wali kelas.') ?></strong></div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($view === 'riwayat'): ?>
<section class="panel print-area" id="riwayat">
    <div class="section-head"><h2>Riwayat Pelanggaran Saya</h2><button class="btn primary no-print" onclick="window.print()">Cetak Riwayat</button></div>
    <div class="table-wrap"><table><thead><tr><th>Tanggal</th><th>Jenis Pelanggaran</th><th>Poin</th><th>Wali Kelas Pencatat</th><th>Keterangan</th></tr></thead><tbody>
        <?php foreach ($mine as $r): ?>
        <tr><td><?= e($r['tanggal']) ?></td><td><?= e(getViolationName($types, $r['jenis_id'])) ?></td><td><?= e($r['poin']) ?></td><td><?= e(getTeacherName($teachers, $r['guru_id'])) ?></td><td><?= e($r['keterangan']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$mine): ?><tr><td colspan="5">Belum ada pelanggaran.</td></tr><?php endif; ?>
    </tbody></table></div>
</section>
<?php endif; ?>
<?php layoutFooter(); ?>
