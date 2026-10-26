<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/database.php';

$authDatabase = new SqlServerDatabase();
$authError = null;
$authSuccess = null;
$authOld = [
    'name' => '',
    'email' => '',
];
$csrfToken = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');
    $authOld['name'] = trim((string) ($_POST['name'] ?? ''));
    $authOld['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $action = (string) ($_POST['action'] ?? 'login');
    $password = (string) ($_POST['password'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        $authError = 'Sesi form sudah kedaluwarsa. Muat ulang halaman dan coba lagi.';
    } elseif (!filter_var($authOld['email'], FILTER_VALIDATE_EMAIL)) {
        $authError = 'Masukkan email yang valid.';
    } elseif ($action === 'register' && $authOld['name'] === '') {
        $authError = 'Nama lengkap wajib diisi.';
    } elseif (strlen($password) < 8) {
        $authError = 'Password minimal 8 karakter.';
    } elseif ($action === 'register' && $password !== (string) ($_POST['password_confirmation'] ?? '')) {
        $authError = 'Konfirmasi password tidak sama.';
    } else {
        try {
            if ($action === 'register') {
                $user = $authDatabase->createUser($authOld['name'], $authOld['email'], password_hash($password, PASSWORD_DEFAULT));
                $authSuccess = 'Akun berhasil dibuat. Selamat datang di Ngekos!';
            } else {
                $user = $authDatabase->findUserByEmail($authOld['email']);
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
            header('Location: /?auth=' . ($action === 'register' ? 'registered' : 'logged-in'));
            exit;
        } catch (DatabaseException $exception) {
            $authError = $exception->getMessage();
        }
    }
}

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: /');
    exit;
}

$currentUser = $_SESSION['user'] ?? null;
