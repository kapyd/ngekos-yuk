IF DB_ID(N'ngekos_db') IS NULL
BEGIN
    CREATE DATABASE [ngekos_db];
END
GO

USE [ngekos_db];
GO

IF OBJECT_ID(N'dbo.listings', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.listings (
        id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_listings PRIMARY KEY,
        name NVARCHAR(150) NOT NULL,
        location NVARCHAR(200) NOT NULL,
        city NVARCHAR(100) NOT NULL,
        price INT NOT NULL,
        period NVARCHAR(20) NOT NULL CONSTRAINT DF_listings_period DEFAULT N'bulan',
        type NVARCHAR(20) NOT NULL,
        rating DECIMAL(2,1) NOT NULL CONSTRAINT DF_listings_rating DEFAULT 0,
        reviews INT NOT NULL CONSTRAINT DF_listings_reviews DEFAULT 0,
        image_url NVARCHAR(500) NOT NULL,
        tag NVARCHAR(80) NOT NULL,
        features_json NVARCHAR(MAX) NOT NULL,
        description NVARCHAR(1000) NOT NULL,
        is_active BIT NOT NULL CONSTRAINT DF_listings_is_active DEFAULT 1,
        created_at DATETIME2 NOT NULL CONSTRAINT DF_listings_created_at DEFAULT SYSUTCDATETIME()
    );
END
GO

IF OBJECT_ID(N'dbo.inquiries', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.inquiries (
        id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_inquiries PRIMARY KEY,
        name NVARCHAR(120) NOT NULL,
        phone NVARCHAR(40) NOT NULL,
        listing_name NVARCHAR(150) NOT NULL,
        status NVARCHAR(30) NOT NULL CONSTRAINT DF_inquiries_status DEFAULT N'new',
        created_at DATETIME2 NOT NULL CONSTRAINT DF_inquiries_created_at DEFAULT SYSUTCDATETIME()
    );
END
GO

IF OBJECT_ID(N'dbo.users', N'U') IS NULL
BEGIN
    CREATE TABLE dbo.users (
        id INT IDENTITY(1,1) NOT NULL CONSTRAINT PK_users PRIMARY KEY,
        name NVARCHAR(120) NOT NULL,
        email NVARCHAR(180) NOT NULL,
        password_hash NVARCHAR(255) NOT NULL,
        role NVARCHAR(30) NOT NULL CONSTRAINT DF_users_role DEFAULT N'tenant',
        created_at DATETIME2 NOT NULL CONSTRAINT DF_users_created_at DEFAULT SYSUTCDATETIME()
    );
    CREATE UNIQUE INDEX UX_users_email ON dbo.users(email);
END
GO

IF NOT EXISTS (SELECT 1 FROM dbo.listings)
BEGIN
    INSERT INTO dbo.listings (name, location, city, price, period, type, rating, reviews, image_url, tag, features_json, description)
    VALUES
    (N'Kost Senopati House', N'Senopati, Jakarta Selatan', N'Jakarta Selatan', 2850000, N'bulan', N'Putri', 4.9, 38, N'https://images.unsplash.com/photo-1600607687920-4e2a09cf159d?auto=format&fit=crop&w=1000&q=85', N'Paling diminati', N'["WiFi 100 Mbps","AC","Kamar mandi dalam"]', N'Hunian tenang dengan desain modern, cocok untuk kamu yang ingin tinggal dekat pusat kuliner dan perkantoran Jakarta Selatan.'),
    (N'Nook Living Kemang', N'Kemang, Jakarta Selatan', N'Jakarta Selatan', 3250000, N'bulan', N'Campur', 4.8, 24, N'https://images.unsplash.com/photo-1600566753190-17f0baa2a6c3?auto=format&fit=crop&w=1000&q=85', N'Premium', N'["Coworking space","Smart lock","Cleaning mingguan"]', N'Kost coliving premium dengan area komunal yang nyaman untuk bekerja, bersantai, dan bertemu penghuni lain.'),
    (N'Paviliun Cikini', N'Cikini, Jakarta Pusat', N'Jakarta Pusat', 2200000, N'bulan', N'Putra', 4.7, 19, N'https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=1000&q=85', N'Harga terbaik', N'["Dekat stasiun","Parkir motor","Listrik termasuk"]', N'Pilihan praktis di jantung kota dengan akses cepat ke transportasi publik, kampus, dan area perkantoran.'),
    (N'Teras Living BSD', N'BSD City, Tangerang Selatan', N'Tangerang Selatan', 1950000, N'bulan', N'Campur', 4.8, 31, N'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=1000&q=85', N'New', N'["Kolam renang","Dapur bersama","Area hijau"]', N'Suasana asri dan fasilitas lengkap di area BSD, ideal untuk mahasiswa maupun pekerja muda yang aktif.'),
    (N'Rumah Teduh Seturan', N'Seturan, Yogyakarta', N'Yogyakarta', 1250000, N'bulan', N'Putri', 4.9, 46, N'https://images.unsplash.com/photo-1598928506311-c55ded91a20c?auto=format&fit=crop&w=1000&q=85', N'Favorit mahasiswa', N'["Full furnished","WiFi","Rooftop garden"]', N'Kost nyaman dengan nuansa homey, berlokasi dekat kampus dan berbagai tempat makan favorit mahasiswa.'),
    (N'Sora House Dipatiukur', N'Dipatiukur, Bandung', N'Bandung', 1800000, N'bulan', N'Putra', 4.8, 27, N'https://images.unsplash.com/photo-1600047509807-ba8f99d2cdde?auto=format&fit=crop&w=1000&q=85', N'Lokasi strategis', N'["Meja kerja","CCTV 24 jam","Air panas"]', N'Tempat tinggal compact dengan suasana modern dan akses mudah menuju kampus serta pusat aktivitas Bandung.');
END
GO
