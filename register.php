<?php
declare(strict_types=1);
require __DIR__ . '/config/auth.php';
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Ngekos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">
    <main class="auth-page-shell">
        <a class="brand auth-brand" href="/" aria-label="Kembali ke Ngekos"><span class="brand-mark">n</span><span>Ngekos<span class="brand-dot">.</span></span></a>
        <section class="auth-card">
            <div class="auth-card-copy"><span class="section-kicker">Mulai perjalananmu</span><h1>Buat ruang untuk<br><em>cerita baru.</em></h1><p>Daftar gratis untuk menyimpan kos favorit dan menghubungi pemilik dengan mudah.</p><div class="auth-card-note"><span>♡</span><small>Lebih dekat dengan tempat pulangmu.</small></div></div>
            <div class="auth-form-panel"><div class="auth-switcher"><a href="/login">Masuk</a><a class="active" href="/register">Daftar</a></div><h2>Buat akun baru</h2><p class="form-intro">Isi data di bawah ini untuk mulai mencari kos.</p><?php if ($authError !== null): ?><p class="form-error auth-feedback"><?= htmlspecialchars($authError) ?></p><?php endif; ?><form method="post" action="/register" class="inquiry-form"><input type="hidden" name="action" value="register"><input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>"><label>Nama lengkap<input name="name" type="text" value="<?= htmlspecialchars($authOld['name']) ?>" placeholder="Contoh: Alya Putri" autocomplete="name" required></label><label>Email<input name="email" type="email" value="<?= htmlspecialchars($authOld['email']) ?>" placeholder="nama@email.com" autocomplete="email" required></label><label>Password<input name="password" type="password" placeholder="Minimal 8 karakter" minlength="8" autocomplete="new-password" required></label><label>Ulangi password<input name="password_confirmation" type="password" placeholder="Ketik ulang password" minlength="8" autocomplete="new-password" required></label><button class="primary-button" type="submit">Buat akun <span>→</span></button></form><p class="auth-bottom-link">Sudah punya akun? <a href="/login">Masuk sekarang</a></p></div>
        </section>
        <a class="back-home" href="/">← Kembali ke halaman utama</a>
    </main>
</body>
</html>
