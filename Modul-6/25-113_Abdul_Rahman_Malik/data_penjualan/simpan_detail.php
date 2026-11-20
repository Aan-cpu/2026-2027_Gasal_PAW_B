<?php
// PROSES SIMPAN DETAIL TRANSAKSI
// Soal 2: barang tidak boleh ganda dalam satu transaksi
// Soal 4: setelah detail disimpan, total transaksi dihitung ulang otomatis
require 'koneksi.php';

// File ini hanya boleh diakses lewat form (POST), bukan diketik langsung di browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }

// Ambil data dari form dan ubah jadi angka (int)
$transaksi_id = (int)($_POST['transaksi_id'] ?? 0);
$barang_id    = (int)($_POST['barang_id'] ?? 0);
$qty          = (int)($_POST['qty'] ?? 0);
$balik        = 'detail.php?id=' . $transaksi_id; // alamat tujuan setelah proses selesai

// Validasi dasar: id harus valid dan qty minimal 1
if ($transaksi_id < 1 || $barang_id < 1 || $qty < 1) {
    flash('err', 'Data tidak valid. Barang wajib dipilih dan qty minimal 1.');
    header("Location: $balik"); exit;
}

// Mulai TRANSACTION: semua langkah di bawah dianggap satu kesatuan.
// Jika salah satu gagal, semuanya dibatalkan (rollback) sehingga data tidak setengah-setengah.
$koneksi->begin_transaction();
try {
    // Langkah 1: pastikan transaksinya ada (FOR UPDATE = dikunci sementara)
    $st = $koneksi->prepare("SELECT id FROM transaksi WHERE id = ? FOR UPDATE");
    $st->bind_param('i', $transaksi_id);
    $st->execute();
    if (!$st->get_result()->fetch_assoc()) throw new Exception('Transaksi tidak ditemukan.');

    // Langkah 2: ambil harga & stok dari database (bukan dari form)
    // supaya pengguna tidak bisa memanipulasi harga lewat browser
    $st = $koneksi->prepare("SELECT harga, stok, nama_barang FROM barang WHERE id = ?");
    $st->bind_param('i', $barang_id);
    $st->execute();
    $brg = $st->get_result()->fetch_assoc();
    if (!$brg) throw new Exception('Barang tidak ditemukan.');

    // Langkah 3: qty tidak boleh melebihi stok
    if ($qty > $brg['stok']) throw new Exception("Qty melebihi stok ({$brg['stok']}) untuk {$brg['nama_barang']}.");

    // Langkah 4: cek barang belum ada di transaksi ini
    // (pengecekan di sisi server, jaga-jaga jika dropdown dimanipulasi)
    $st = $koneksi->prepare("SELECT 1 FROM transaksi_detail WHERE transaksi_id = ? AND barang_id = ?");
    $st->bind_param('ii', $transaksi_id, $barang_id);
    $st->execute();
    if ($st->get_result()->num_rows > 0) throw new Exception('Barang tersebut sudah ada di transaksi ini.');

    // Langkah 5: simpan detail (harga disimpan sebagai catatan harga saat transaksi)
    $st = $koneksi->prepare("INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES (?,?,?,?)");
    $st->bind_param('iiii', $transaksi_id, $barang_id, $brg['harga'], $qty); // iiii = 4 data bertipe integer
    $st->execute();

    // Langkah 6 (Soal 4): hitung ulang total = jumlah (harga x qty) SEMUA detail transaksi ini,
    // lalu simpan ke kolom total di tabel transaksi
    // COALESCE(..., 0) : jika tidak ada detail, hasilnya 0 bukan NULL
    $st = $koneksi->prepare(
      "UPDATE transaksi SET total = (
         SELECT COALESCE(SUM(harga * qty), 0) FROM transaksi_detail WHERE transaksi_id = ?)
       WHERE id = ?");
    $st->bind_param('ii', $transaksi_id, $transaksi_id);
    $st->execute();

    // Semua langkah berhasil, simpan permanen
    $koneksi->commit();
    flash('ok', 'Detail tersimpan dan total transaksi diperbarui.');
} catch (Throwable $ex) {
    // Ada langkah yang gagal: batalkan semua perubahan, tampilkan pesan errornya
    $koneksi->rollback();
    flash('err', $ex->getMessage());
}

// Kembali ke halaman detail transaksi
header("Location: $balik");
