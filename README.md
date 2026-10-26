# Ngekos

Website landing dan pencarian kos berbasis PHP native, JavaScript vanilla, SQL Server, dan API serverless Node.js untuk Vercel.

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
- Versi Vercel menggunakan `index.html`, `auth.html`, dan API serverless di folder `api/`.
- Password user disimpan menggunakan `password_hash()` dan diverifikasi dengan `password_verify()`.
- Pencarian berdasarkan lokasi/nama, filter tipe, dan budget maksimal.
- Sortir harga termurah/termahal.
- Favorit kos dengan JavaScript.
- Modal detail kos dan form inquiry yang diproses server-side oleh PHP.
- Layout responsive tanpa framework.

## Menyiapkan SQL Server untuk Vercel

Vercel tidak dapat mengakses SQL Server `localhost` di laptop. Gunakan Azure SQL Database atau SQL Server pada VPS yang dapat diakses dari internet. API Vercel memakai package `mssql` dan SQL Authentication.

1. Pastikan service SQL Server berjalan.
2. Jalankan script schema dan seed:

```powershell
sqlcmd -S localhost -E -C -i database/setup.sql
```

3. Untuk lokal versi PHP, jika lokasi `sqlcmd` atau server berbeda, set environment variable:

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

## Deploy ke Vercel

1. Push project ke GitHub.
2. Import repository tersebut di Vercel.
3. Di **Project Settings → Environment Variables**, tambahkan semua variable dari `.env.example`:

```text
SQLSERVER_HOST=server-azure-sql.database.windows.net
SQLSERVER_PORT=1433
SQLSERVER_USER=nama-user-sql
SQLSERVER_PASSWORD=password-sql
SQLSERVER_DATABASE=ngekos_db
SQLSERVER_ENCRYPT=true
SQLSERVER_TRUST_CERT=false
SESSION_SECRET=random-string-minimal-32-karakter
```

4. Pastikan firewall Azure SQL mengizinkan koneksi dari Vercel atau gunakan konfigurasi jaringan yang sesuai.
5. Deploy ulang setelah environment variable disimpan.

Endpoint API yang tersedia:

- `GET /api/listings`
- `POST /api/auth/login`
- `POST /api/auth/register`
- `POST /api/auth/logout`
- `GET /api/auth/me`
- `POST /api/inquiries`

`index.php`, `login.php`, dan `register.php` tetap dipertahankan untuk mode PHP lokal. Vercel memakai file statis dan endpoint Node.js di `api/`.

Table yang dibuat:

- `dbo.listings` — data kos aktif.
- `dbo.inquiries` — permintaan kontak dari calon penyewa.
- `dbo.users` — akun penyewa/pemilik dengan role default `tenant`.

## Akun pertama

Klik tombol **Daftar** di halaman utama atau buka `/register`, isi nama, email, dan password minimal 8 karakter. Untuk login, buka `/login`. Setelah berhasil register/login, akun langsung aktif dan nama user tampil di header. Tombol **Keluar** menghapus session login.
