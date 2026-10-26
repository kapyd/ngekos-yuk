<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config/database.php';

$database = new SqlServerDatabase();
$databaseError = null;
$authState = null;
$authError = null;
$authSuccess = null;
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /');
    exit;
}

try {
    $listings = $database->getListings();
} catch (DatabaseException $exception) {
    $listings = [];
    $databaseError = $exception->getMessage();
}

$submitted = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'inquiry');
    $postedToken = (string) ($_POST['csrf_token'] ?? '');
    if (!hash_equals($csrfToken, $postedToken)) {
        $authError = 'Sesi form sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif ($action === 'login' || $action === 'register') {
        $authState = $action;
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $name = trim((string) ($_POST['name'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $authError = 'Masukkan email yang valid.';
        } elseif ($action === 'register' && $name === '') {
            $authError = 'Nama lengkap wajib diisi.';
        } elseif (strlen($password) < 8) {
            $authError = 'Password minimal 8 karakter.';
        } elseif ($action === 'register' && $password !== $passwordConfirmation) {
            $authError = 'Konfirmasi password tidak sama.';
        } elseif ($databaseError !== null) {
            $authError = 'Database belum siap. Jalankan database/setup.sql terlebih dahulu.';
        } else {
            try {
                if ($action === 'register') {
                    $user = $database->createUser($name, $email, password_hash($password, PASSWORD_DEFAULT));
                    $authSuccess = 'Akun berhasil dibuat. Selamat datang di Ngekos!';
                } else {
                    $user = $database->findUserByEmail($email);
                    if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
                        throw new DatabaseException('Email atau password salah.');
                    }
                    $authSuccess = 'Login berhasil. Selamat datang kembali!';
                }

                session_regenerate_id(true);
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'name' => (string) $user['name'],
                    'email' => (string) $user['email'],
                    'role' => (string) ($user['role'] ?? 'tenant'),
                ];
            } catch (DatabaseException $exception) {
                $authError = $exception->getMessage();
            }
        }
    } else {
        $submitted = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'phone' => trim((string) ($_POST['phone'] ?? '')),
            'listing' => trim((string) ($_POST['listing'] ?? '')),
        ];

        if ($submitted['name'] === '' || $submitted['phone'] === '') {
            $submitted['error'] = 'Nama dan nomor WhatsApp wajib diisi.';
        } elseif ($databaseError !== null) {
            $submitted['error'] = 'Database belum siap. Jalankan database/setup.sql terlebih dahulu.';
        } else {
            try {
                $database->saveInquiry($submitted['name'], $submitted['phone'], $submitted['listing']);
                $submitted['success'] = true;
            } catch (DatabaseException $exception) {
                $submitted['error'] = 'Permintaan belum tersimpan: ' . $exception->getMessage();
            }
        }
    }
}

$currentUser = $_SESSION['user'] ?? null;

function rupiah(int $amount): string
{
    return 'Rp' . number_format($amount, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Temukan kos nyaman, aman, dan sesuai budget bersama Ngekos.">
    <title>Ngekos — Cari kos yang terasa seperti rumah</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <div class="page-shell">
        <header class="site-header">
            <a class="brand" href="#top" aria-label="Ngekos beranda">
                <span class="brand-mark">n</span>
                <span>Ngekos<span class="brand-dot">.</span></span>
            </a>
            <nav class="desktop-nav" aria-label="Navigasi utama">
                <a class="active" href="#explore">Cari kos</a>
                <a href="#why-us">Kenapa Ngekos?</a>
                <a href="#partner">Jadi pemilik kos</a>
            </nav>
            <div class="header-actions">
                <?php if ($currentUser !== null): ?>
                    <span class="user-greeting">Hai, <?= htmlspecialchars((string) $currentUser['name']) ?></span>
                    <a class="outline-button" href="/login?logout=1">Keluar</a>
                <?php else: ?>
                    <a class="text-button" href="/login">Masuk</a>
                    <a class="outline-button" href="/register">Daftar</a>
                <?php endif; ?>
                <button class="outline-button owner-button" type="button" data-toast="Fitur pasang iklan pemilik kos segera hadir.">Pasang iklan</button>
                <button class="menu-toggle" type="button" aria-label="Buka menu" aria-expanded="false"><span></span><span></span></button>
            </div>
        </header>

        <main id="top">
            <?php if ($databaseError !== null): ?>
                <div class="database-alert"><strong>Mode database belum aktif.</strong> <?= htmlspecialchars($databaseError) ?> Jalankan <code>database/setup.sql</code> menggunakan SQL Server.</div>
            <?php endif; ?>
            <section class="hero-section">
                <div class="hero-copy">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> #1 platform cari kos di Indonesia</span>
                    <h1>Tinggal nyaman,<br><em>hidup tenang.</em></h1>
                    <p>Temukan ruang yang pas untuk cerita barumu. Dari kamar minimalis sampai hunian premium, semua ada di sini.</p>
                    <div class="hero-stats">
                        <div><strong>1.2K+</strong><span>Kos terverifikasi</span></div>
                        <div><strong>18 kota</strong><span>Di seluruh Indonesia</span></div>
                        <div><strong>4.9/5</strong><span>Rating pengguna</span></div>
                    </div>
                </div>
                <div class="hero-visual">
                    <div class="hero-photo photo-main">
                        <img src="https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?auto=format&fit=crop&w=1200&q=85" alt="Interior kamar kos yang hangat">
                    </div>
                    <div class="hero-photo photo-small">
                        <img src="https://images.unsplash.com/photo-1600566753086-00f18fb6b3ea?auto=format&fit=crop&w=700&q=85" alt="Area duduk di hunian">
                    </div>
                    <div class="floating-card rating-card"><span class="star">★</span><div><strong>4.9 / 5</strong><small>2,400+ reviews</small></div></div>
                    <div class="floating-card verified-card"><span class="verified-icon">✓</span><div><strong>100% terverifikasi</strong><small>Foto & fasilitas sesuai</small></div></div>
                    <span class="scribble scribble-one">✦</span><span class="scribble scribble-two">✧</span>
                </div>
            </section>

            <section class="search-panel" id="explore">
                <div class="search-heading">
                    <span class="section-kicker">Mulai dari sini</span>
                    <h2>Cari tempat tinggalmu</h2>
                </div>
                <div class="search-fields">
                    <label class="search-field search-location">
                        <span class="field-icon">⌖</span>
                        <span><small>Lokasi</small><input id="searchInput" type="search" placeholder="Kota atau area" autocomplete="off"></span>
                    </label>
                    <label class="search-field">
                        <span class="field-icon">▣</span>
                        <span><small>Tipe kos</small><select id="typeFilter"><option value="all">Semua tipe</option><option value="Putri">Putri</option><option value="Putra">Putra</option><option value="Campur">Campur</option></select></span>
                    </label>
                    <label class="search-field">
                        <span class="field-icon">↗</span>
                        <span><small>Budget maksimal</small><select id="priceFilter"><option value="all">Tanpa batas</option><option value="1500000">Rp1,5 juta</option><option value="2500000">Rp2,5 juta</option><option value="3500000">Rp3,5 juta</option></select></span>
                    </label>
                    <button class="primary-button search-button" id="searchButton" type="button">Cari kos <span>→</span></button>
                </div>
            </section>

            <section class="listing-section" id="listings">
                <div class="section-topline">
                    <div><span class="section-kicker">Pilihan untukmu</span><h2>Kos yang lagi ramai dicari <span class="hand-underline"></span></h2></div>
                    <div class="results-meta"><span id="resultCount"><?= count($listings) ?> kos ditemukan</span><button class="sort-button" id="sortButton" type="button">Urutkan <span>⌄</span></button></div>
                </div>
                <div class="listing-grid" id="listingGrid">
                    <?php foreach ($listings as $listing): ?>
                        <article class="listing-card" data-name="<?= htmlspecialchars(strtolower($listing['name'] . ' ' . $listing['location'])) ?>" data-city="<?= htmlspecialchars($listing['city']) ?>" data-type="<?= htmlspecialchars($listing['type']) ?>" data-price="<?= $listing['price'] ?>">
                            <div class="listing-image-wrap">
                                <img src="<?= htmlspecialchars($listing['image']) ?>" alt="<?= htmlspecialchars($listing['name']) ?>" loading="lazy">
                                <span class="listing-tag"><?= htmlspecialchars($listing['tag']) ?></span>
                                <button class="favorite-button" type="button" aria-label="Simpan <?= htmlspecialchars($listing['name']) ?>" aria-pressed="false">♡</button>
                            </div>
                            <div class="listing-content">
                                <div class="listing-title-row"><h3><?= htmlspecialchars($listing['name']) ?></h3><span class="rating"><span>★</span> <?= number_format($listing['rating'], 1) ?></span></div>
                                <p class="listing-location"><span>⌖</span> <?= htmlspecialchars($listing['location']) ?></p>
                                <div class="feature-list"><?php foreach ($listing['features'] as $feature): ?><span><?= htmlspecialchars($feature) ?></span><?php endforeach; ?></div>
                                <div class="listing-footer"><div><strong><?= rupiah($listing['price']) ?></strong><small>/<?= htmlspecialchars($listing['period']) ?></small></div><button class="detail-button" type="button" data-detail='<?= htmlspecialchars(json_encode($listing, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?>'>Lihat detail <span>→</span></button></div>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
                <div class="empty-state" id="emptyState" hidden><span>⌕</span><h3>Belum ada kos yang cocok</h3><p>Coba ubah kata kunci atau pilih budget yang lebih luas.</p><button class="outline-button" id="resetFilters" type="button">Reset filter</button></div>
            </section>

            <section class="why-section" id="why-us">
                <div class="why-intro"><span class="section-kicker">Bukan cuma kamar</span><h2>Ruang untuk bertumbuh,<br><em>tempat untuk pulang.</em></h2><p>Kami percaya tempat tinggal yang baik bisa membuat hari-hari terasa lebih ringan. Karena itu, setiap kos di Ngekos dipilih dengan hati.</p><a class="arrow-link" href="#partner">Kenali Ngekos <span>↗</span></a></div>
                <div class="why-cards"><div class="why-card"><span class="why-number">01</span><div class="why-icon mint">✦</div><h3>Sudah terverifikasi</h3><p>Foto, fasilitas, dan informasi kos kami cek langsung agar kamu booking tanpa rasa ragu.</p></div><div class="why-card accent-card"><span class="why-number">02</span><div class="why-icon cream">♡</div><h3>Dibuat untukmu</h3><p>Filter yang membantu kamu menemukan tempat yang cocok dengan gaya hidup dan budget.</p></div><div class="why-card"><span class="why-number">03</span><div class="why-icon lilac">↗</div><h3>Lebih dari sekadar sewa</h3><p>Temukan lingkungan baru, kenalan baru, dan cerita baru di kota pilihanmu.</p></div></div>
            </section>

            <section class="partner-banner" id="partner"><div><span class="section-kicker light-kicker">Punya properti kosong?</span><h2>Jadikan ruangmu<br>berarti bagi seseorang.</h2><p>Gabung bersama ribuan pemilik kos yang membantu orang menemukan rumah keduanya.</p></div><button class="light-button" type="button" data-toast="Tim kami akan menghubungi kamu segera.">Mulai pasang iklan <span>↗</span></button><span class="banner-sparkle">✳</span></section>
        </main>

        <footer class="site-footer"><a class="brand footer-brand" href="#top"><span class="brand-mark">n</span><span>Ngekos<span class="brand-dot">.</span></span></a><p>Tempat tinggal yang terasa seperti rumah.</p><div class="footer-links"><a href="#explore">Cari kos</a><a href="#why-us">Tentang kami</a><a href="#partner">Untuk pemilik</a><a href="mailto:halo@ngekos.test">Hubungi kami</a></div><span class="copyright">© <?= date('Y') ?> Ngekos</span></footer>
    </div>

    <div class="modal-backdrop" id="detailModal" hidden><div class="detail-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle"><button class="modal-close" type="button" aria-label="Tutup">×</button><img id="modalImage" src="" alt=""><div class="modal-body"><span class="listing-tag" id="modalTag"></span><h2 id="modalTitle"></h2><p class="listing-location" id="modalLocation"></p><p id="modalDescription"></p><div class="feature-list" id="modalFeatures"></div><div class="modal-bottom"><div><strong id="modalPrice"></strong><small>/bulan</small></div><button class="primary-button" id="openInquiry" type="button">Tanya pemilik <span>→</span></button></div></div></div></div>

    <div class="modal-backdrop" id="inquiryModal" hidden><div class="inquiry-modal" role="dialog" aria-modal="true" aria-labelledby="inquiryTitle"><button class="modal-close" type="button" aria-label="Tutup">×</button><div class="inquiry-copy"><span class="section-kicker">Langkah pertama</span><h2 id="inquiryTitle">Tertarik dengan kos ini?</h2><p>Isi data singkatmu. Pemilik kos akan menghubungi melalui WhatsApp untuk mengatur jadwal survei.</p></div><?php if ($submitted && !empty($submitted['success'])): ?><div class="success-message"><span>✓</span><div><strong>Pesanmu sudah terkirim!</strong><p>Terima kasih, <?= htmlspecialchars($submitted['name']) ?>. Kami akan menghubungi kamu di <?= htmlspecialchars($submitted['phone']) ?>.</p></div></div><?php else: ?><form method="post" class="inquiry-form"><input type="hidden" name="action" value="inquiry"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><input type="hidden" name="listing" id="inquiryListing"><label>Nama lengkap<input name="name" type="text" placeholder="Contoh: Alya Putri" required></label><label>Nomor WhatsApp<input name="phone" type="tel" placeholder="08xx xxxx xxxx" required></label><button class="primary-button" type="submit">Kirim permintaan <span>→</span></button><?php if ($submitted && !empty($submitted['error'])): ?><p class="form-error"><?= htmlspecialchars($submitted['error']) ?></p><?php endif; ?></form><?php endif; ?></div></div>

    <div class="modal-backdrop auth-backdrop" id="authModal" hidden><div class="auth-modal" role="dialog" aria-modal="true" aria-labelledby="authTitle"><button class="modal-close" type="button" aria-label="Tutup">×</button><div class="auth-switcher"><button type="button" data-auth-switch="login">Masuk</button><button type="button" data-auth-switch="register">Daftar</button></div><div class="auth-panel" data-auth-panel="login"><span class="section-kicker">Selamat datang kembali</span><h2 id="authTitle">Masuk ke Ngekos</h2><p>Kelola pencarian kos dan inquiry-mu dalam satu tempat.</p><form method="post" class="inquiry-form"><input type="hidden" name="action" value="login"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><label>Email<input name="email" type="email" placeholder="nama@email.com" required></label><label>Password<input name="password" type="password" placeholder="Minimal 8 karakter" required></label><button class="primary-button" type="submit">Masuk <span>→</span></button></form></div><div class="auth-panel" data-auth-panel="register" hidden><span class="section-kicker">Mulai perjalananmu</span><h2>Daftar akun baru</h2><p>Buat akun gratis untuk menyimpan kos favorit dan menghubungi pemilik.</p><form method="post" class="inquiry-form"><input type="hidden" name="action" value="register"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><label>Nama lengkap<input name="name" type="text" placeholder="Contoh: Alya Putri" required></label><label>Email<input name="email" type="email" placeholder="nama@email.com" required></label><label>Password<input name="password" type="password" placeholder="Minimal 8 karakter" minlength="8" required></label><label>Ulangi password<input name="password_confirmation" type="password" placeholder="Ketik ulang password" minlength="8" required></label><button class="primary-button" type="submit">Buat akun <span>→</span></button></form></div><?php if ($authError !== null): ?><p class="form-error auth-feedback"><?= htmlspecialchars($authError) ?></p><?php elseif ($authSuccess !== null): ?><p class="success-text auth-feedback"><?= htmlspecialchars($authSuccess) ?></p><?php endif; ?></div></div>

    <div class="toast" id="toast" role="status"></div>
    <script src="assets/app.js" defer></script>
</body>
</html>
