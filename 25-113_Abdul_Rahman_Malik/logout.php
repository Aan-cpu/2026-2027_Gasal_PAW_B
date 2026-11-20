<?php
// PROSES LOGOUT: hapus semua session lalu kembali ke halaman login
require_once __DIR__ . '/fungsi.php';

// Logout dibuat lewat POST + token CSRF supaya tidak bisa dipicu dari link/gambar situs lain
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}
cek_csrf();

$_SESSION = []; // kosongkan semua data session (unset)

// Hapus juga cookie session di browser
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy(); // hancurkan session di server

// Buka session baru hanya untuk menampilkan pesan logout di halaman login
session_start();
set_flash('sukses', 'Anda berhasil logout.');
redirect('login.php');
