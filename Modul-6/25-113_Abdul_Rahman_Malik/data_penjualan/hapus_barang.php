<?php
// PROSES HAPUS BARANG DENGAN PENGECEKAN DATA TERKAIT (Soal 3)
require 'koneksi.php';

// Hanya boleh diakses lewat form (POST), bukan diketik langsung di browser
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: barang.php'); exit; }

// Ambil id barang dari form dan ubah jadi angka
$id = (int)($_POST['id'] ?? 0);

// Pastikan barangnya ada, sekaligus ambil namanya untuk pesan notifikasi
$st = $koneksi->prepare("SELECT nama_barang FROM barang WHERE id = ?");
$st->bind_param('i', $id);
$st->execute();
$brg = $st->get_result()->fetch_assoc();
if (!$brg) { flash('err', 'Barang tidak ditemukan.'); header('Location: barang.php'); exit; }

// PENGECEKAN: hitung barang ini dipakai di berapa baris transaksi_detail
$st = $koneksi->prepare("SELECT COUNT(*) AS jml FROM transaksi_detail WHERE barang_id = ?");
$st->bind_param('i', $id);
$st->execute();
$jml = (int)$st->get_result()->fetch_assoc()['jml'];

if ($jml > 0) {
    // Sudah dipakai di transaksi -> TIDAK boleh dihapus, tampilkan notifikasi
    flash('err', "Barang \"{$brg['nama_barang']}\" tidak dapat dihapus karena masih digunakan pada $jml transaksi.");
} else {
    // Belum dipakai -> boleh dihapus
    try {
        $st = $koneksi->prepare("DELETE FROM barang WHERE id = ?");
        $st->bind_param('i', $id);
        $st->execute();
        flash('ok', "Barang \"{$brg['nama_barang']}\" berhasil dihapus.");
    } catch (mysqli_sql_exception $ex) {
        // Pengaman kedua: foreign key ON DELETE RESTRICT di database
        // juga menolak penghapusan jika masih ada data yang terhubung
        flash('err', 'Barang tidak dapat dihapus karena masih terhubung dengan data lain.');
    }
}

// Kembali ke halaman daftar barang
header('Location: barang.php');
