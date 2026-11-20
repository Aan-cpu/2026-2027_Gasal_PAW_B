<?php
// SKRIP EKSPOR REKAPITULASI PENJUALAN KE EXCEL (.xls)
// Dipanggil dari tombol "Excel" di laporan.php beserta filter tanggalnya
require 'koneksi.php'; // panggil koneksi database

// Ambil filter tanggal dari URL
$mulai   = $_GET['tanggal_mulai']   ?? '';
$selesai = $_GET['tanggal_selesai'] ?? '';

// Validasi sederhana: format harus YYYY-MM-DD (regex), dan mulai tidak boleh melebihi selesai
$pola = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($pola, $mulai) || !preg_match($pola, $selesai) || $mulai > $selesai) {
    die('Parameter tanggal tidak valid.'); // hentikan, jangan unduh file kosong
}

// Query harian: sama dengan yang dipakai di halaman laporan
$st = $koneksi->prepare(
    "SELECT DATE(waktu_transaksi) AS tgl, SUM(total) AS total_harian
     FROM transaksi
     WHERE waktu_transaksi BETWEEN ? AND ?
     GROUP BY DATE(waktu_transaksi)
     ORDER BY tgl ASC");
$st->bind_param('ss', $mulai, $selesai);
$st->execute();
$hasil = $st->get_result();

// Query ringkasan: pelanggan unik & total pendapatan keseluruhan
$st2 = $koneksi->prepare(
    "SELECT COUNT(DISTINCT pelanggan_id) AS jml_pelanggan,
            COALESCE(SUM(total), 0) AS total_pendapatan
     FROM transaksi
     WHERE waktu_transaksi BETWEEN ? AND ?");
$st2->bind_param('ss', $mulai, $selesai);
$st2->execute();
$ringkas = $st2->get_result()->fetch_assoc();

// Nama file unduhan, contoh: rekap_penjualan_2023-09-01_sd_2023-09-27.xls
$nama_file = "rekap_penjualan_{$mulai}_sd_{$selesai}.xls";

// HTTP RESPONSE HEADER (harus dikirim sebelum ada output apa pun)
// Content-Type: memberitahu browser bahwa isi respon adalah file Excel
header('Content-Type: application/vnd.ms-excel');
// Content-Disposition: attachment membuat browser mengunduh file, bukan menampilkannya
header('Content-Disposition: attachment; filename="' . $nama_file . '"');
// Cache dimatikan agar data yang diunduh selalu yang terbaru
header('Pragma: no-cache');
header('Expires: 0');
?>
<meta charset="UTF-8">
<!-- Excel membaca tabel HTML ini dan mengubahnya menjadi sel-sel spreadsheet -->
<h3>Rekap Laporan Penjualan</h3>
<p>Periode: <?= e($mulai) ?> sampai <?= e($selesai) ?></p>

<table border="1">
  <tr><th>No</th><th>Total Pendapatan</th><th>Tanggal Transaksi</th></tr>
  <?php $no = 1; // nomor urut ?>
  <?php while ($r = $hasil->fetch_assoc()): ?>
  <tr>
    <td><?= $no++ ?></td>
    <!-- angka dibiarkan mentah (tanpa titik ribuan) agar bisa dihitung di Excel -->
    <td><?= (int)$r['total_harian'] ?></td>
    <td><?= e($r['tgl']) ?></td>
  </tr>
  <?php endwhile; ?>
</table>

<br>
<!-- Ringkasan total kumulatif di bawah tabel -->
<table border="1">
  <tr><th>Jumlah Pelanggan Unik</th><th>Total Pendapatan Keseluruhan</th></tr>
  <tr>
    <td><?= (int)$ringkas['jml_pelanggan'] ?></td>
    <td><?= (int)$ringkas['total_pendapatan'] ?></td>
  </tr>
</table>
<?php
exit; // hentikan skrip agar tidak ada output tambahan di file Excel
