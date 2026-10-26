# Ngekos

Website landing dan pencarian kos berbasis PHP native, JavaScript vanilla, dan SQL Server.

## Menjalankan lokal

```bash
php -S 127.0.0.1:8000 router.php
```

Buka `http://127.0.0.1:8000` di browser.

## Fitur

- Daftar kos dirender dari SQL Server melalui `config/database.php`.
- Daftar kos dan inquiry disimpan di SQL Server database `ngekos_db`.
- Login, register, logout, dan session user tersedia melalui tabel `dbo.users`.
- Halaman autentikasi tersedia di `/login` dan `/register`.
- Password user disimpan menggunakan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Pencarian berdasarkan lokasi/nama, filter tipe, dan budget maksimal.
- Sortir harga termurah/termahal.
- Favorit kos dengan JavaScript.
- Modal detail kos dan form inquiry yang diproses server-side oleh PHP.
- Layout responsive tanpa framework.

## Menyiapkan SQL Server

PHP pada environment ini belum memiliki driver `pdo_sqlsrv`, sehingga koneksi dilakukan melalui `sqlcmd` dengan Windows Authentication.

1. Pastikan service SQL Server berjalan.
2. Jalankan script schema dan seed:

```powershell
sqlcmd -S localhost -E -C -i database/setup.sql
```

3. Jika lokasi `sqlcmd` atau server berbeda, set environment variable sebelum menjalankan PHP:

```powershell
$env:SQLCMD_PATH = 'C:\Program Files\Microsoft SQL Server\Client SDK\ODBC\180\Tools\Binn\SQLCMD.EXE'
$env:SQLSERVER_HOST = 'localhost'
$env:SQLSERVER_DATABASE = 'ngekos_db'
php -S 127.0.0.1:8000 router.php
```

Untuk mengaktifkan route `/login` dan `/register` saat memakai PHP built-in server, jalankan router:

```powershell
php -S 127.0.0.1:8000 router.php
```

Table yang dibuat:

- `dbo.listings` — data kos aktif.
- `dbo.inquiries` — permintaan kontak dari calon penyewa.
- `dbo.users` — akun penyewa/pemilik dengan role default `tenant`.

## Akun pertama

Klik tombol **Daftar** di halaman utama atau buka `/register`, isi nama, email, dan password minimal 8 karakter. Untuk login, buka `/login`. Setelah berhasil register/login, akun langsung aktif dan nama user tampil di header. Tombol **Keluar** menghapus session login.
