<?php
// HALAMAN LOGIN: form dan prosesnya dalam satu file
require_once __DIR__ . '/fungsi.php';

// Jika sudah login, tidak boleh membuka halaman login lagi
if (sudah_login()) {
    redirect('index.php');
}

// PROSES LOGIN: dijalankan saat form disubmit (POST), validasi input, cek ke database, lalu set session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tombol_login'])) {
    cek_csrf(); // pastikan form berasal dari halaman login kita

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? ''; // password tidak di-trim karena spasi bisa jadi bagian password

    // Validasi dasar: tidak boleh kosong
    if ($username === '' || $password === '') {
        set_flash('error', 'Username dan password wajib diisi.');
        $_SESSION['old_username'] = $username;
        redirect('login.php');
    }

    // Cari user berdasarkan username, prepared statement mencegah SQL Injection
    $st = $koneksi->prepare('SELECT id_user, nama, username, password, level FROM `user` WHERE username = ? LIMIT 1');
    $st->bind_param('s', $username);
    $st->execute();
    $user = $st->get_result()->fetch_assoc();

    // password_verify membandingkan password yang diketik dengan hash di database
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // ganti ID session setelah login (cegah session fixation)

        // SET SESSION: data yang dipakai halaman lain untuk mengenali user
        $_SESSION['login']          = true;
        $_SESSION['id_user']        = (int)$user['id_user'];
        $_SESSION['username']       = $user['username'];
        $_SESSION['nama']           = $user['nama'];
        $_SESSION['level']          = (int)$user['level']; // 1 = owner, 2 = kasir
        $_SESSION['terakhir_aktif'] = time();

        redirect('index.php');
    }

    // Pesan sengaja disamakan agar penyerang tidak tahu apakah username-nya ada atau tidak
    set_flash('error', 'Username atau password salah.');
    $_SESSION['old_username'] = $username;
    redirect('login.php');
}

// Ambil username yang diketik sebelumnya (jika login gagal) lalu hapus dari session
$username_lama = $_SESSION['old_username'] ?? '';
unset($_SESSION['old_username']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Sistem Penjualan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="halaman-login">
    <div class="kotak-login">
        <h2>Sistem Penjualan</h2>
        <p class="kecil">Silakan login untuk melanjutkan</p>

        <?php tampil_flash(); ?>

        <form action="" method="post" autocomplete="off">
            <?= csrf_input() ?>
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?= e($username_lama) ?>" maxlength="50" required autofocus>

            <label for="password">Password</label>
            <input type="password" id="password" name="password" maxlength="100" required>

            <button type="submit" name="tombol_login" class="btn btn-utama btn-penuh">Login</button>
        </form>
    </div>
</body>
</html>
