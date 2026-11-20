<?php
// EKSPOR EXCEL: mengunduh rekap penjualan harian dalam format .xls lewat HTTP response header
// Dipanggil dari tombol Excel di laporan.php dengan parameter tanggal_mulai dan tanggal_selesai
require_once __DIR__ . '/midlewire.php'; // wajib login

[$mulai, $selesai, $pesan_error] = baca_rentang();

// Tanggal wajib ada dan valid, jika tidak kembali ke halaman laporan
if ($pesan_error !== '' || $mulai === '' || $selesai === '') {
    set_flash('error', $pesan_error !== '' ? $pesan_error : 'Pilih rentang tanggal terlebih dahulu.');
    redirect('laporan.php');
}

$hasil = ambil_rekap_harian($koneksi, $mulai, $selesai);

// Header ini membuat browser mengunduh respon sebagai file Excel, bukan menampilkannya sebagai halaman
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="rekap_penjualan_' . $mulai . '_sd_' . $selesai . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');
?>
<html>
<head><meta charset="UTF-8"></head>
<body>
<h3>Rekap Laporan Penjualan <?= e($mulai) ?> sampai <?= e($selesai) ?></h3>
<table border="1">
    <tr><th>No</th><th>Total Pendapatan (Rp)</th><th>Tanggal Transaksi</th></tr>
    <?php $no = 1; foreach ($hasil['rekap'] as $r): ?>
    <tr>
        <td><?= $no++ ?></td>
        <td><?= (int)$r['total_harian'] ?></td>
        <td><?= e(tanggal_indo($r['tgl'])) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<br>
<table border="1">
    <tr><th>Jumlah Pelanggan</th><td><?= (int)$hasil['jml_pelanggan'] ?> Orang</td></tr>
    <tr><th>Jumlah Pendapatan (Rp)</th><td><?= (int)$hasil['total_pendapatan'] ?></td></tr>
</table>
</body>
</html>
