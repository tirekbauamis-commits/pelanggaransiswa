# Sistem Informasi Pelanggaran Siswa

Website PHP native dengan database MySQL/MariaDB dari XAMPP.

## Cara Menjalankan

1. Salin folder `sistem-pelanggaran-siswa` ke `C:\xampp\htdocs\`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buat database MySQL dengan salah satu cara di bagian "Setup Database MySQL".
4. Buka `http://localhost/sistem-pelanggaran-siswa/`.

Cara paling mudah jika Apache bermasalah: klik dua kali file `buka-aplikasi.bat`.

Jika Apache XAMPP tidak bisa dibuka, jalankan lewat PHP built-in server:

```powershell
cd C:\xampp\htdocs\sistem-pelanggaran-siswa
C:\xampp\php\php.exe -S 127.0.0.1:8000
```

Lalu buka `http://127.0.0.1:8000`.

## Akun Login

- Admin: `admin@sekolah.id` / `admin123`
- Wali Kelas X RPL 1: `wali1@sekolah.id` / `wali123`
- Wali Kelas X RPL 2: `wali2@sekolah.id` / `wali123`
- Wali Kelas XI RPL 1: `wali3@sekolah.id` / `wali123`
- Wali Kelas XI RPL 2: `wali4@sekolah.id` / `wali123`
- Wali Kelas XII RPL 1: `wali5@sekolah.id` / `wali123`
- Wali Kelas XII RPL 2: `wali6@sekolah.id` / `wali123`
- Contoh Siswa: `siswa001@gmail.com` / `siswa123`

Pengguna baru bisa memilih menu `Daftar` di halaman login. Pendaftaran siswa memakai email `@gmail.com`, sedangkan wali kelas dan admin memakai email `@sekolah.id`.

Data awal berisi 6 wali kelas dan 180 siswa. Setiap kelas memiliki 30 siswa. Akun siswa tersedia dari `siswa001@gmail.com` sampai `siswa180@gmail.com`, semuanya memakai password `siswa123`.

## Setup Database MySQL

Aplikasi memakai koneksi berikut di `includes/db.php`:

- Host: `127.0.0.1`
- Port: `3306`
- Database: `sistem_pelanggaran_siswa`
- User: `root`
- Password: kosong

Cara paling mudah lewat phpMyAdmin:

1. Buka `http://localhost/phpmyadmin`.
2. Pilih tab `Import`.
3. Pilih file `database/mysql_schema.sql`.
4. Klik `Go` / `Kirim`.

Cara lewat terminal:

```powershell
C:\xampp\mysql\bin\mysql.exe -u root < C:\xampp\htdocs\sistem-pelanggaran-siswa\database\mysql_schema.sql
```

File SQL tersebut akan membuat database, tabel, relasi, dan data awal. Kalau database sudah ada dan file diimpor ulang, tabel aplikasi akan dibuat ulang sesuai isi file SQL.

## Diagram

Dua diagram class tersedia di:

- `diagram_class.md`
