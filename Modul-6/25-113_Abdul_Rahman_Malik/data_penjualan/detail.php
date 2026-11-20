<?php
// HALAMAN MASTER-DETAIL: DETAIL SATU TRANSAKSI
// Bagian atas = data master (transaksi), bagian bawah = data detail (barang)
require 'koneksi.php';

// Ambil id transaksi dari URL (detail.php?id=1)
// (int) = paksa jadi angka, mencegah SQL injection lewat URL
$id = (int)($_GET['id'] ?? 0);

// ---- 1. DATA MASTER: ambil transaksi yang dipilih + nama pelanggannya ----
$st = $koneksi->prepare(
  "SELECT t.*, p.nama AS pelanggan FROM transaksi t
   JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.id = ?");
$st->bind_param('i', $id); // i = integer
$st->execute();
$master = $st->get_result()->fetch_assoc(); // hasil 1 baris

// Jika id tidak ada di database, kembali ke halaman utama
if (!$master) { flash('err', 'Transaksi tidak ditemukan.'); header('Location: index.php'); exit; }

// ---- 2. DATA DETAIL: ambil barang-barang milik transaksi ini ----
// Relasinya lewat foreign key: transaksi_detail.transaksi_id = transaksi.id
// (harga*qty) AS subtotal : hitung subtotal tiap barang
$st = $koneksi->prepare(
  "SELECT d.barang_id, b.kode_barang, b.nama_barang, d.harga, d.qty, (d.harga*d.qty) AS subtotal
   FROM transaksi_detail d JOIN barang b ON b.id = d.barang_id
   WHERE d.transaksi_id = ? ORDER BY b.nama_barang");
$st->bind_param('i', $id);
$st->execute();
$detail = $st->get_result();

// ---- 3. DATA DROPDOWN BARANG ----
// Ambil SEMUA barang, lalu tandai mana yang sudah dipakai di transaksi ini.
// LEFT JOIN: jika barang sudah ada di detail transaksi ini, kolom d.barang_id terisi
// (dipakai = 1), jika belum maka NULL (dipakai = 0).
$st = $koneksi->prepare(
  "SELECT b.id, b.kode_barang, b.nama_barang, b.harga, b.stok,
          (d.barang_id IS NOT NULL) AS dipakai
   FROM barang b
   LEFT JOIN transaksi_detail d ON d.barang_id = b.id AND d.transaksi_id = ?
   ORDER BY b.nama_barang");
$st->bind_param('i', $id);
$st->execute();
$barang = $st->get_result();

header_html('Detail Transaksi #' . $id);
tampil_flash();
?>
<!-- BAGIAN MASTER: informasi transaksi -->
<div class="card">
  <h1>Transaksi #<?= $master['id'] ?> (Master)</h1>
  <div class="meta">
    <b>Tanggal</b><span><?= e($master['waktu_transaksi']) ?></span>
    <b>Pelanggan</b><span><?= e($master['pelanggan_id'] . ' - ' . $master['pelanggan']) ?></span>
    <b>Keterangan</b><span><?= e($master['keterangan']) ?></span>
    <!-- total ini otomatis diperbarui setiap detail baru disimpan -->
    <b>Total</b><span><strong><?= rupiah($master['total']) ?></strong></span>
  </div>
</div>

<!-- BAGIAN DETAIL: tabel barang dalam transaksi -->
<div class="card">
  <h2>Detail Transaksi</h2>
  <table>
    <tr><th>Kode</th><th>Barang</th><th class="r">Harga</th><th class="r">Qty</th><th class="r">Subtotal</th></tr>
    <?php // Jika belum ada detail, tampilkan pesan kosong ?>
    <?php if ($detail->num_rows === 0): ?>
      <tr><td colspan="5">Belum ada detail.</td></tr>
    <?php endif; ?>
    <?php // Ulangi satu baris untuk setiap barang di transaksi ini ?>
    <?php while ($d = $detail->fetch_assoc()): ?>
    <tr>
      <td><?= e($d['kode_barang']) ?></td>
      <td><?= e($d['nama_barang']) ?></td>
      <td class="r"><?= rupiah($d['harga']) ?></td>
      <td class="r"><?= $d['qty'] ?></td>
      <td class="r"><?= rupiah($d['subtotal']) ?></td>
    </tr>
    <?php endwhile; ?>
    <!-- baris total di bagian bawah tabel -->
    <tr><th colspan="4" class="r">Total</th><th class="r"><?= rupiah($master['total']) ?></th></tr>
  </table>
</div>

<!-- FORM TAMBAH DETAIL BARANG -->
<div class="card">
  <h2>Tambah Detail Barang</h2>
  <!-- data dikirim ke simpan_detail.php untuk diproses -->
  <form method="post" action="simpan_detail.php">
    <!-- hidden: id transaksi ikut dikirim tanpa terlihat oleh pengguna -->
    <input type="hidden" name="transaksi_id" value="<?= $id ?>">
    <label>Barang</label>
    <select name="barang_id" required>
      <option value="">-- pilih barang --</option>
      <?php while ($b = $barang->fetch_assoc()): ?>
        <?php // Barang yang sudah dipakai diberi atribut "disabled" sehingga tidak bisa dipilih lagi ?>
        <option value="<?= $b['id'] ?>" <?= $b['dipakai'] ? 'disabled' : '' ?>>
          <?= e($b['kode_barang'] . ' - ' . $b['nama_barang'] . ' (' . rupiah($b['harga']) . ', stok ' . $b['stok'] . ')') ?>
          <?= $b['dipakai'] ? ' [sudah dipilih]' : '' ?>
        </option>
      <?php endwhile; ?>
    </select>
    <label>Qty</label>
    <!-- min="1": qty tidak boleh kurang dari 1 -->
    <input type="number" name="qty" min="1" value="1" required><br><br>
    <button class="btn" type="submit">Simpan Detail</button>
    <a class="btn gray" href="index.php">Kembali</a>
  </form>
</div>
<?php footer_html();
