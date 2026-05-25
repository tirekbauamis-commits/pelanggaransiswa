<?php
require_once 'includes/functions.php';

if (isset($_SESSION['user'])) {
    redirectWith('dashboard_' . $_SESSION['user']['role'] . '.php');
}

$error = '';
$mode = $_GET['mode'] ?? 'login';
$registerType = $_GET['type'] ?? 'siswa';
if ($registerType === 'staff') {
    $registerType = 'guru';
}
if (!in_array($registerType, ['siswa', 'guru', 'admin'], true)) {
    $registerType = 'siswa';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formMode = $_POST['mode'] ?? 'login';
    $nama = trim($_POST['nama'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $users = readData('users');

    if ($formMode === 'register') {
        $registerType = $_POST['type'] ?? 'siswa';
        if ($registerType === 'staff') {
            $registerType = 'guru';
        }
        $role = in_array($registerType, ['guru', 'admin'], true) ? $registerType : 'siswa';
        $email = strtolower($nama);
        $isSchoolEmail = str_ends_with(strtolower($nama), '@sekolah.id');
        $isGmail = str_ends_with(strtolower($nama), '@gmail.com');
        $exists = array_filter($users, fn($user) => strtolower($user['username']) === strtolower($nama) || strtolower($user['nama']) === strtolower($nama));
        if ($nama === '' || $password === '') {
            $error = 'Email dan password wajib diisi.';
            $mode = 'register';
        } elseif (in_array($registerType, ['guru', 'admin'], true) && !$isSchoolEmail) {
            $error = 'Admin dan wali kelas wajib memakai email sekolah @sekolah.id.';
            $mode = 'register';
        } elseif ($registerType === 'siswa' && !$isGmail) {
            $error = 'Siswa wajib memakai email Gmail masing-masing.';
            $mode = 'register';
        } elseif ($exists) {
            $error = 'Email sudah terdaftar. Silakan gunakan email lain.';
            $mode = 'register';
        } else {
            $students = readData('siswa');
            $teachers = readData('guru');
            $refId = null;
            $displayName = trim($_POST['display_name'] ?? '');
            if ($displayName === '') {
                $displayName = ucwords(str_replace(['.', '_', '-'], ' ', explode('@', $email)[0]));
            }
            if ($role === 'siswa') {
                $refId = nextId($students);
                $nis = trim($_POST['nis'] ?? '');
                $kelas = trim($_POST['kelas'] ?? '');
                $finalNis = $nis ?: 'REG' . str_pad((string) $refId, 4, '0', STR_PAD_LEFT);
                if (valueExists($students, 'nis', $finalNis)) {
                    $error = 'NIS sudah terdaftar. Gunakan NIS lain atau login dengan akun yang sudah ada.';
                    $mode = 'register';
                } elseif (valueExists($students, 'nama', $displayName)) {
                    $error = 'Nama siswa sudah terdaftar. Gunakan nama lengkap yang berbeda.';
                    $mode = 'register';
                } elseif (!isValidClass($kelas)) {
                    $error = 'Pilih kelas dari daftar yang tersedia.';
                    $mode = 'register';
                } else {
                $students[] = [
                    'id' => $refId,
                    'nis' => $finalNis,
                    'nama' => $displayName,
                    'kelas' => $kelas,
                    'jk' => $_POST['jk'] ?? '-',
                ];
                writeData('siswa', $students);
                }
            }
            if ($error) {
                $registerType = $role;
            } else {
            if ($role === 'guru') {
                $refId = nextId($teachers);
                $kelasAmpu = trim($_POST['kelas_ampu'] ?? '');
                if (!isValidClass($kelasAmpu)) {
                    $error = 'Pilih kelas ampu dari daftar yang tersedia.';
                    $mode = 'register';
                    $registerType = 'guru';
                } else {
                $teachers[] = [
                    'id' => $refId,
                    'nip' => 'G' . str_pad((string) $refId, 5, '0', STR_PAD_LEFT),
                    'nama' => $displayName,
                    'kelas_ampu' => $kelasAmpu,
                    'no_hp' => trim($_POST['no_hp'] ?? '-') ?: '-',
                ];
                writeData('guru', $teachers);
                }
            }
            if ($error) {
                $registerType = $role;
            } else {
            $newUser = [
                'id' => nextId($users),
                'username' => $email,
                'password' => $password,
                'role' => $role,
                'nama' => $displayName,
                'ref_id' => $refId,
            ];
            $users[] = $newUser;
            writeData('users', $users);
            $_SESSION['user'] = $newUser;
            redirectWith('dashboard_' . $role . '.php', 'Selamat datang, ' . $displayName . '.');
            }
            }
        }
    } else {
        foreach ($users as $user) {
            $nameMatch = strtolower($user['username']) === strtolower($nama) || strtolower($user['nama']) === strtolower($nama);
            if ($nameMatch && $user['password'] === $password) {
                $_SESSION['user'] = $user;
                redirectWith('dashboard_' . $user['role'] . '.php');
            }
        }
        $error = 'Nama atau password salah.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Informasi Pelanggaran Siswa</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="login-page">
    <div class="login-orbit" aria-hidden="true"></div>
    <section class="login-card">
        <div class="login-copy">
            <h1>Sistem Informasi Pelanggaran Siswa</h1>
        </div>
        <form method="post" class="login-form">
            <input type="hidden" name="mode" value="<?= e($mode === 'register' ? 'register' : 'login') ?>">
            <?php if ($mode === 'register'): ?><input type="hidden" name="type" value="<?= e($registerType) ?>"><?php endif; ?>
            <h2><?= $mode === 'register' ? 'Daftar Akun' : 'Login' ?></h2>
            <?php if ($error): ?><div class="alert error"><?= e($error) ?></div><?php endif; ?>
            <?php if ($mode === 'register'): ?>
            <div class="register-choice">
                <a class="<?= $registerType === 'siswa' ? 'active' : '' ?>" href="index.php?mode=register&type=siswa">Daftar Siswa</a>
                <a class="<?= $registerType === 'guru' ? 'active' : '' ?>" href="index.php?mode=register&type=guru">Daftar Wali Kelas</a>
                <a class="<?= $registerType === 'admin' ? 'active' : '' ?>" href="index.php?mode=register&type=admin">Daftar Admin</a>
            </div>
            <?php endif; ?>
            <label><?= $mode === 'register' ? (in_array($registerType, ['guru', 'admin'], true) ? 'Email Sekolah' : 'Email Gmail Siswa') : 'Email / Nama Pengguna' ?><input type="email" name="nama" required autofocus placeholder="<?= $mode === 'register' ? (in_array($registerType, ['guru', 'admin'], true) ? 'nama@sekolah.id' : 'nama@gmail.com') : '' ?>"></label>
            <label>Password<input type="password" name="password" required></label>
            <?php if ($mode === 'register' && in_array($registerType, ['guru', 'admin'], true)): ?>
            <label>Nama<input name="display_name" placeholder="Nama yang tampil di dashboard"></label>
            <?php if ($registerType === 'guru'): ?>
            <div class="register-extra" data-role-extra="guru">
                <label>Kelas Ampu<select name="kelas_ampu" required><?= renderClassOptions($_POST['kelas_ampu'] ?? '') ?></select></label>
                <label>Nomor Telepon<input name="no_hp" placeholder="Contoh: 08123456789"></label>
            </div>
            <?php endif; ?>
            <?php elseif ($mode === 'register'): ?>
            <label>NIS<input name="nis" required placeholder="Nomor induk siswa"></label>
            <label>Nama<input name="display_name" required placeholder="Nama siswa"></label>
            <label>Kelas<select name="kelas" required><?= renderClassOptions($_POST['kelas'] ?? '') ?></select></label>
            <label>Jenis Kelamin<select name="jk" required><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></label>
            <?php endif; ?>
            <button type="submit" class="btn primary"><?= $mode === 'register' ? 'Daftar' : 'Masuk' ?></button>
            <div class="login-switch">
                <?php if ($mode === 'register'): ?>
                    Sudah punya akun? <a href="index.php">Masuk</a>
                <?php else: ?>
                    Belum punya akun? <a href="index.php?mode=register&type=siswa">Siswa</a> / <a href="index.php?mode=register&type=guru">Wali Kelas</a> / <a href="index.php?mode=register&type=admin">Admin</a>
                <?php endif; ?>
            </div>
        </form>
    </section>
</body>
</html>
