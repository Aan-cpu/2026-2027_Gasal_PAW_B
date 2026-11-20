<?php
// HALAMAN HOME: ringkasan data, bisa diakses owner dan kasir
require_once __DIR__ . '/midlewire.php'; // proteksi: wajib login
$judul = 'Home';

// Hitung jumlah data untuk kartu ringkasan
$jml_barang    = $koneksi->query('SELECT COUNT(*) AS n FROM barang')->fetch_assoc()['n'];
$jml_pelanggan = $koneksi->query('SELECT COUNT(*) AS n FROM pelanggan')->fetch_assoc()['n'];
$jml_transaksi = $koneksi->query('SELECT COUNT(*) AS n FROM transaksi')->fetch_assoc()['n'];
$total_jual    = $koneksi->query('SELECT COALESCE(SUM(total), 0) AS n FROM transaksi')->fetch_assoc()['n'];

// Barang dengan stok 10 atau kurang
$stok_tipis = $koneksi->query('SELECT kode_barang, nama_barang, stok FROM barang WHERE stok <= 10 ORDER BY stok ASC')->fetch_all(MYSQLI_ASSOC);

require 'header.php';
?>
<div class="kartu">
    <h1>Selamat datang, <?= e($_SESSION['nama']) ?></h1>
    <p>Anda login sebagai <strong><?= e(nama_level($_SESSION['level'])) ?></strong>.</p>
</div>

<div class="grid">
    <div class="ringkas"><div class="kecil">Total Barang</div><div class="angka"><?= (int)$jml_barang ?></div></div>
    <div class="ringkas"><div class="kecil">Total Pelanggan</div><div class="angka"><?= (int)$jml_pelanggan ?></div></div>
    <div class="ringkas"><div class="kecil">Total Transaksi</div><div class="angka"><?= (int)$jml_transaksi ?></div></div>
    <div class="ringkas"><div class="kecil">Total Penjualan</div><div class="angka"><?= e(rupiah($total_jual)) ?></div></div>
</div>

<?php if ($stok_tipis): ?>
<div class="kartu">
    <h2>Stok Menipis (10 atau kurang)</h2>
    <div class="bungkus-tabel">
    <table>
        <tr><th>Kode</th><th>Nama Barang</th><th class="teks-kanan">Stok</th></tr>
        <?php foreach ($stok_tipis as $r): ?>
        <tr>
            <td><?= e($r['kode_barang']) ?></td>
            <td><?= e($r['nama_barang']) ?></td>
            <td class="teks-kanan"><?= (int)$r['stok'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php endif; ?>
<?php require 'footer.php'; ?>
