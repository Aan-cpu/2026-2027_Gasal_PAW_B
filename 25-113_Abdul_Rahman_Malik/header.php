<?php
// TEMPLATE ATAS (navbar), dipakai semua halaman setelah login
// Halaman yang memanggil harus mengisi $judul dan sudah memanggil midlewire.php
$halaman_aktif = basename($_SERVER['SCRIPT_NAME']); // nama file saat ini, untuk menandai menu aktif
$level         = (int)$_SESSION['level'];

// Daftar file yang termasuk menu Data Master (untuk menandai dropdown aktif)
$file_master = ['supplier.php', 'barang.php', 'pelanggan.php', 'user.php'];

// Fungsi kecil: kembalikan class "aktif" jika menu sedang dibuka
function aktif($cocok, $sekarang)
{
    return in_array($sekarang, (array)$cocok, true) ? 'aktif' : '';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($judul ?? 'Sistem Penjualan') ?> | Sistem Penjualan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<nav class="navbar no-print">
    <div class="nav-kiri">
        <a href="index.php" class="brand">Sistem Penjualan</a>

        <a href="index.php" class="<?= aktif('index.php', $halaman_aktif) ?>">Home</a>

        <?php if ($level === LEVEL_OWNER): ?>
            <!-- Data Master hanya tampil untuk owner (level 1) -->
            <div class="dropdown">
                <a href="#" class="<?= aktif($file_master, $halaman_aktif) ?>">Data Master &#9662;</a>
                <div class="dropdown-isi">
                    <a href="supplier.php">Data Supplier</a>
                    <a href="barang.php">Data Barang</a>
                    <a href="pelanggan.php">Data Pelanggan</a>
                    <a href="user.php">Data User</a>
                </div>
            </div>
        <?php endif; ?>

        <a href="transaksi.php" class="<?= aktif('transaksi.php', $halaman_aktif) ?>">Transaksi</a>
        <a href="laporan.php" class="<?= aktif('laporan.php', $halaman_aktif) ?>">Laporan</a>
    </div>

    <div class="nav-kanan">
        <!-- Nama user diambil dari session sesuai akun yang login -->
        <div class="dropdown">
            <a href="#"><?= e($_SESSION['nama']) ?> &#9662;</a>
            <div class="dropdown-isi kanan">
                <span class="info-level"><?= e(nama_level($level)) ?></span>
                <form action="logout.php" method="post">
                    <?= csrf_input() ?>
                    <button type="submit" name="logout" class="tombol-logout">Logout</button>
                </form>
            </div>
        </div>
    </div>
</nav>

<main class="konten">
    <?php tampil_flash(); ?>
