<?php
// LAPORAN PENJUALAN (gabungan Modul 7 Reporting): filter rentang tanggal, grafik batang Chart.js,
// tabel rekapitulasi harian, ringkasan total, tombol Cetak dan Excel. Bisa diakses owner dan kasir.
require_once __DIR__ . '/midlewire.php';
$judul = 'Laporan';

// Baca dan validasi rentang tanggal dari URL (fungsi ada di fungsi.php)
[$mulai, $selesai, $pesan_error] = baca_rentang();
$sudah_filter = ($mulai !== '' && $selesai !== '' && $pesan_error === '');

// Saat pertama dibuka, kolom tanggal diisi dengan rentang data transaksi yang ada supaya tinggal klik Tampilkan
$input_mulai   = $mulai;
$input_selesai = $selesai;
if ($input_mulai === '' && $input_selesai === '') {
    $r = $koneksi->query('SELECT MIN(waktu_transaksi) AS a, MAX(waktu_transaksi) AS b FROM transaksi')->fetch_assoc();
    $input_mulai   = $r['a'] ?? '';
    $input_selesai = $r['b'] ?? '';
}

$rekap = [];
$jml_pelanggan = 0;
$total_pendapatan = 0;
if ($sudah_filter) {
    $hasil = ambil_rekap_harian($koneksi, $mulai, $selesai);
    $rekap            = $hasil['rekap'];
    $jml_pelanggan    = $hasil['jml_pelanggan'];
    $total_pendapatan = $hasil['total_pendapatan'];
}

// Data untuk grafik: sumbu X tanggal, sumbu Y total pendapatan harian
$labels     = array_column($rekap, 'tgl');
$data_total = array_map('intval', array_column($rekap, 'total_harian'));

require 'header.php';
?>
<div class="kartu no-print">
    <h1>Filter Laporan Penjualan</h1>
    <form action="" method="get">
        <div class="baris-filter">
            <div>
                <label for="tanggal_mulai">Tanggal mulai</label>
                <input type="date" id="tanggal_mulai" name="tanggal_mulai" value="<?= e($input_mulai) ?>" required>
            </div>
            <div>
                <label for="tanggal_selesai">Tanggal selesai</label>
                <input type="date" id="tanggal_selesai" name="tanggal_selesai" value="<?= e($input_selesai) ?>" required>
            </div>
            <div>
                <button type="submit" class="btn btn-utama">Tampilkan</button>
                <a href="laporan.php" class="btn">Reset</a>
            </div>
        </div>
    </form>
</div>

<?php if ($pesan_error): ?>
    <div class="alert alert-error no-print"><?= e($pesan_error) ?></div>
<?php elseif ($sudah_filter): ?>
<div class="kartu">
    <div class="atas-tabel">
        <h1>Rekap Laporan Penjualan <small class="kecil"><?= e($mulai) ?> sampai <?= e($selesai) ?></small></h1>
    </div>

    <div class="no-print aksi-laporan">
        <a href="laporan.php" class="btn btn-utama">&laquo; Kembali</a>
        <button type="button" class="btn btn-oranye" onclick="window.print()">Cetak</button>
        <a href="export_excel.php?tanggal_mulai=<?= e(urlencode($mulai)) ?>&amp;tanggal_selesai=<?= e(urlencode($selesai)) ?>" class="btn btn-oranye">Excel</a>
    </div>

    <?php if (!$rekap): ?>
        <p class="kosong">Tidak ada transaksi pada rentang tanggal ini.</p>
    <?php else: ?>
        <h2>Grafik Penjualan</h2>
        <div class="bungkus-grafik"><canvas id="grafik"></canvas></div>

        <h2>Rekapitulasi</h2>
        <div class="bungkus-tabel">
        <table>
            <tr><th>No</th><th>Total Pendapatan</th><th>Tanggal Transaksi</th></tr>
            <?php $no = 1; foreach ($rekap as $r): ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><?= e(rupiah($r['total_harian'])) ?></td>
                <td><?= e(tanggal_indo($r['tgl'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>

        <div class="bungkus-tabel">
        <table class="tabel-ringkas">
            <tr><th>Jumlah Pelanggan</th><th>Jumlah Pendapatan</th></tr>
            <tr>
                <td class="teks-kanan"><?= (int)$jml_pelanggan ?> Orang</td>
                <td class="teks-kanan"><?= e(rupiah($total_pendapatan)) ?></td>
            </tr>
        </table>
        </div>
    <?php endif; ?>
</div>

<?php if ($rekap): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
// Grafik batang: labels = tanggal transaksi, data = total pendapatan per hari
var rupiah = function (n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); };
new Chart(document.getElementById('grafik'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($labels, JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        datasets: [{
            label: 'Total Penjualan',
            data: <?= json_encode($data_total) ?>,
            backgroundColor: '#1c4587'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: false, // tanpa animasi supaya grafik langsung utuh saat dicetak
        scales: {
            x: { title: { display: true, text: 'Tanggal Transaksi' } },
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Total Pendapatan (Rp)' },
                ticks: { callback: function (v) { return Number(v).toLocaleString('id-ID'); } }
            }
        },
        plugins: {
            tooltip: { callbacks: { label: function (c) { return rupiah(c.parsed.y); } } }
        }
    }
});
</script>
<?php endif; ?>
<?php endif; ?>
<?php require 'footer.php'; ?>
