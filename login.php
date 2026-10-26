<?php
declare(strict_types=1);
require __DIR__ . '/config/auth.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Ngekos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">
    <main class="auth-page-shell">
        <a class="brand auth-brand" href="/" aria-label="Kembali ke Ngekos"><span class="brand-mark">n</span><span>Ngekos<span class="brand-dot">.</span></span></a>
        <section class="auth-card">
            <div class="auth-card-copy"><span class="section-kicker">Selamat datang kembali</span><h1>Masuk dan<br><em>lanjutkan carimu.</em></h1><p>Temukan kos yang terasa seperti rumah dan simpan semua pilihan favoritmu.</p><div class="auth-card-note"><span>✦</span><small>Ruang nyaman untuk cerita baru.</small></div></div>
            <div class="auth-form-panel"><div class="auth-switcher"><a class="active" href="/login">Masuk</a><a href="/register">Daftar</a></div><h2>Masuk ke akunmu</h2><p class="form-intro">Gunakan email dan password yang sudah terdaftar.</p><?php if ($authError !== null): ?><p class="form-error auth-feedback"><?= htmlspecialchars($authError) ?></p><?php endif; ?><form method="post" action="/login" class="inquiry-form"><input type="hidden" name="action" value="login"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><label>Email<input name="email" type="email" value="<?= htmlspecialchars($authOld['email']) ?>" placeholder="nama@email.com" autocomplete="email" required></label><label>Password<input name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="current-password" required></label><button class="primary-button" type="submit">Masuk <span>→</span></button></form><p class="auth-bottom-link">Belum punya akun? <a href="/register">Daftar sekarang</a></p></div>
        </section>
        <a class="back-home" href="/">← Kembali ke halaman utama</a>
    </main>
</body>
</html>
