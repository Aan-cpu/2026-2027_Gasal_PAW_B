<?php
// HALAMAN DAFTAR BARANG (ada tombol hapus)
require 'koneksi.php';

// Ambil semua barang + nama supplier + jumlah pemakaian di transaksi
// JOIN supplier : ambil nama supplier dari supplier_id
// Subquery (SELECT COUNT(*) ...) : hitung barang ini dipakai di berapa detail transaksi
$barang = $koneksi->query(
  "SELECT b.*, s.nama AS supplier,
          (SELECT COUNT(*) FROM transaksi_detail d WHERE d.barang_id = b.id) AS dipakai
   FROM barang b JOIN supplier s ON s.id = b.supplier_id ORDER BY b.id");

header_html('Data Barang');
tampil_flash(); // tampilkan notifikasi hasil hapus (berhasil/gagal)
?>
<div class="card">
  <h1>Data Barang</h1>
  <table>
    <tr><th>Kode</th><th>Nama Barang</th><th>Supplier</th><th class="r">Harga</th><th class="r">Stok</th><th class="r">Dipakai</th><th></th></tr>
    <?php // Ulangi satu baris tabel untuk setiap barang ?>
    <?php while ($b = $barang->fetch_assoc()): ?>
    <tr>
      <td><?= e($b['kode_barang']) ?></td>
      <td><?= e($b['nama_barang']) ?></td>
      <td><?= e($b['supplier']) ?></td>
      <td class="r"><?= rupiah($b['harga']) ?></td>
      <td class="r"><?= $b['stok'] ?></td>
      <!-- jumlah transaksi yang memakai barang ini -->
      <td class="r"><?= $b['dipakai'] ?>x</td>
      <td>
        <!-- onsubmit: muncul konfirmasi "OK/Cancel" sebelum data dikirim -->
        <form method="post" action="hapus_barang.php"
              onsubmit="return confirm('Hapus barang <?= e(addslashes($b['nama_barang'])) ?>?')">
          <!-- id barang yang akan dihapus dikirim lewat hidden input -->
          <input type="hidden" name="id" value="<?= $b['id'] ?>">
          <button class="btn red" type="submit">Hapus</button>
        </form>
      </td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>
<?php footer_html();
