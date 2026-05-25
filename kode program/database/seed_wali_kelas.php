<?php
require_once __DIR__ . '/../includes/functions.php';

writeData('guru', defaultHomeroomTeachers());
writeData('siswa', defaultStudents());

$users = array_merge([
    [
        'id' => 1,
        'username' => 'admin@sekolah.id',
        'password' => 'admin123',
        'role' => 'admin',
        'nama' => 'Administrator',
        'ref_id' => null,
    ],
], defaultHomeroomUsers(), defaultStudentUsers());

writeData('users', $users);

echo "Seed wali kelas dan siswa selesai.\n";
?>
