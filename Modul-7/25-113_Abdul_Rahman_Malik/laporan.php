<?php
// HALAMAN REPORTING LAPORAN PENJUALAN
// Berisi: form filter tanggal, grafik batang (Chart.js), tabel rekap, kartu total, tombol cetak & Excel
require 'koneksi.php'; // panggil koneksi database & fungsi bantu

// Fungsi untuk memeriksa apakah teks benar-benar tanggal valid berformat YYYY-MM-DD
function tanggal_valid($t) {
    $d = DateTime::createFromFormat('Y-m-d', $t); // coba ubah teks menjadi objek tanggal
    return $d && $d->format('Y-m-d') === $t;      // valid jika hasilnya sama persis dengan teks awal
}

// Ambil nilai filter dari URL (method GET); jika belum ada, isi dengan string kosong
$tanggal_mulai   = $_GET['tanggal_mulai']   ?? '';
$tanggal_selesai = $_GET['tanggal_selesai'] ?? '';

// Siapkan variabel hasil laporan (kosong dulu)
$labels = [];            // sumbu X grafik: daftar tanggal
$data_total = [];        // sumbu Y grafik: total pendapatan per tanggal
$rekap = [];             // baris-baris untuk tabel rekapitulasi
$jml_pelanggan = 0;      // jumlah pelanggan unik
$total_pendapatan = 0;   // total pendapatan keseluruhan periode
$error = '';             // pesan kesalahan filter
$tampilkan = false;      // penanda: apakah laporan boleh ditampilkan

// Proses laporan hanya jika kedua tanggal sudah diisi (tombol "Tampilkan" ditekan)
if ($tanggal_mulai !== '' && $tanggal_selesai !== '') {

    // Validasi 1: format tanggal harus benar
    if (!tanggal_valid($tanggal_mulai) || !tanggal_valid($tanggal_selesai)) {
        $error = 'Format tanggal tidak valid.';
    }
    // Validasi 2: tanggal mulai tidak boleh lebih besar dari tanggal selesai
    elseif ($tanggal_mulai > $tanggal_selesai) {
        $error = 'Tanggal mulai tidak boleh lebih besar dari tanggal selesai.';
    } else {
        $tampilkan = true;

        // QUERY 1: total pendapatan per hari
        // SUM(total)     : menjumlahkan total transaksi
        // GROUP BY DATE  : dikelompokkan per tanggal
        // BETWEEN ? AND ? : hanya transaksi di antara dua tanggal (kedua batas ikut dihitung)
        // Tanda ? diisi lewat prepared statement (anti SQL injection)
        $st = $koneksi->prepare(
            "SELECT DATE(waktu_transaksi) AS tgl, SUM(total) AS total_harian
             FROM transaksi
             WHERE waktu_transaksi BETWEEN ? AND ?
             GROUP BY DATE(waktu_transaksi)
             ORDER BY tgl ASC");
        $st->bind_param('ss', $tanggal_mulai, $tanggal_selesai); // ss = 2 data bertipe string
        $st->execute();
        $hasil = $st->get_result(); // ambil hasil query

        // Pindahkan hasil query ke array agar bisa dipakai di grafik dan tabel
        while ($r = $hasil->fetch_assoc()) {
            $labels[]     = $r['tgl'];                 // tanggal -> label sumbu X
            $data_total[] = (int)$r['total_harian'];   // total -> nilai sumbu Y (diubah ke integer)
            $rekap[]      = $r;                        // simpan baris utuh untuk tabel
        }

        // QUERY 2: ringkasan kumulatif periode
        // COUNT(DISTINCT pelanggan_id) : jumlah pelanggan unik (pelanggan yang sama dihitung 1x)
        // COALESCE(..., 0)             : jika tidak ada data, SUM bernilai 0 (bukan NULL)
        $st2 = $koneksi->prepare(
            "SELECT COUNT(DISTINCT pelanggan_id) AS jml_pelanggan,
                    COALESCE(SUM(total), 0) AS total_pendapatan
             FROM transaksi
             WHERE waktu_transaksi BETWEEN ? AND ?");
        $st2->bind_param('ss', $tanggal_mulai, $tanggal_selesai);
        $st2->execute();
        $ringkas = $st2->get_result()->fetch_assoc(); // hanya 1 baris hasil
        $jml_pelanggan    = (int)$ringkas['jml_pelanggan'];
        $total_pendapatan = (int)$ringkas['total_pendapatan'];
    }
}

header_html('Laporan Penjualan'); // cetak bagian atas halaman
?>
<!-- Chart.js dimuat dari CDN (internet diperlukan) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- BAGIAN 1: FORM FILTER RENTANG TANGGAL -->
<!-- class no-print: bagian ini disembunyikan saat dicetak -->
<div class="card no-print">
  <h1>Laporan Penjualan</h1>
  <!-- method GET: nilai filter muncul di URL sehingga bisa di-bookmark -->
  <form method="get">
    <label>Tanggal Mulai</label>
    <!-- value diisi ulang dengan nilai sebelumnya agar filter tidak hilang setelah submit -->
    <input type="date" name="tanggal_mulai" value="<?= e($tanggal_mulai) ?>" required>
    <label>Tanggal Selesai</label>
    <input type="date" name="tanggal_selesai" value="<?= e($tanggal_selesai) ?>" required>
    <button class="btn" type="submit">Tampilkan</button>
  </form>
</div>

<?php if ($error !== ''): ?>
  <!-- Tampilkan pesan error validasi jika ada -->
  <div class="alert err"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($tampilkan): ?>
<!-- BAGIAN 2: TOMBOL CETAK & EXCEL (tidak ikut tercetak) -->
<div class="card no-print">
  <!-- window.print() membuka dialog cetak bawaan browser -->
  <button class="btn btn-oranye" onclick="window.print()">Cetak</button>
  <!-- Tombol Excel mengarah ke export_excel.php sambil membawa filter tanggal -->
  <!-- http_build_query menyusun URL: ?tanggal_mulai=...&tanggal_selesai=... -->
  <a class="btn btn-oranye"
     href="export_excel.php?<?= e(http_build_query(['tanggal_mulai' => $tanggal_mulai, 'tanggal_selesai' => $tanggal_selesai])) ?>">Excel</a>
</div>

<div class="card">
  <!-- Judul laporan beserta periode (ikut tercetak) -->
  <h2>Rekap Laporan Penjualan
    <small style="font-weight:normal;font-size:14px;color:#666"><?= e($tanggal_mulai) ?> sampai <?= e($tanggal_selesai) ?></small>
  </h2>

  <?php if (empty($rekap)): ?>
    <!-- Jika tidak ada transaksi pada rentang tanggal tersebut -->
    <p>Tidak ada transaksi pada rentang tanggal ini.</p>
  <?php else: ?>

  <!-- BAGIAN 3: GRAFIK BATANG -->
  <!-- canvas adalah "kanvas" tempat Chart.js menggambar grafik; ukuran diatur oleh div pembungkusnya -->
  <div style="width: 600px; height: 300px;"><canvas id="myChart"></canvas></div>
  <script>
    // Ambil elemen canvas, lalu ambil "konteks 2D" sebagai alat untuk menggambar
    const ctx = document.getElementById('myChart').getContext('2d');
    // Buat grafik baru pada canvas tersebut
    new Chart(ctx, {
      type: 'bar',                            // jenis grafik: batang (bar chart)
      data: {
        labels: <?= json_encode($labels); ?>, // sumbu X: tanggal transaksi (array PHP diubah ke JSON)
        datasets: [{
          label: 'Total Penjualan',           // nama data pada legenda grafik
          data: <?= json_encode($data_total); ?> // sumbu Y: total pendapatan per tanggal
        }]
      }
    });
  </script>

  <!-- BAGIAN 4: TABEL REKAPITULASI HARIAN -->
  <table>
    <tr><th>No</th><th>Total Pendapatan</th><th>Tanggal Transaksi</th></tr>
    <?php $no = 1; // nomor urut dimulai dari 1 ?>
    <?php foreach ($rekap as $row): ?>
    <tr>
      <td><?= $no++ ?></td>                        <!-- cetak nomor lalu tambah 1 -->
      <td><?= rupiah($row['total_harian']) ?></td> <!-- format rupiah -->
      <td><?= e($row['tgl']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>

  <!-- BAGIAN 5: CARD RINGKASAN TOTAL KUMULATIF -->
  <table style="width:auto; margin-top:20px;">
    <tr><th>Jumlah Pelanggan Unik</th><th>Total Pendapatan Keseluruhan</th></tr>
    <tr>
      <td class="r"><?= $jml_pelanggan ?> Orang</td>
      <td class="r"><b><?= rupiah($total_pendapatan) ?></b></td>
    </tr>
  </table>

  <?php endif; ?>
</div>
<?php endif; ?>

<?php footer_html(); // tutup halaman ?>
