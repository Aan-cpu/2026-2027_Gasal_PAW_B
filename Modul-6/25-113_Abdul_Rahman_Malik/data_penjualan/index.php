<?php
// HALAMAN UTAMA: DAFTAR TRANSAKSI (MASTER)
require 'koneksi.php'; // panggil koneksi database & fungsi bantu

// Jalankan hanya jika form "Tambah Transaksi" dikirim (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form
    $tgl = $_POST['waktu_transaksi'] ?? '';
    $ket = trim($_POST['keterangan'] ?? ''); // trim = buang spasi di awal/akhir
    $plg = $_POST['pelanggan_id'] ?? '';

    // Validasi: tanggal dan pelanggan wajib diisi
    if ($tgl === '' || $plg === '') {
        flash('err', 'Tanggal dan pelanggan wajib diisi.');
        header('Location: index.php'); exit; // kembali ke halaman ini
    }

    // Prepared statement: tanda ? diisi aman (anti SQL injection)
    // total diisi 0 dulu, nanti dihitung otomatis saat detail ditambahkan
    $st = $koneksi->prepare("INSERT INTO transaksi (waktu_transaksi, keterangan, total, pelanggan_id) VALUES (?,?,0,?)");
    $st->bind_param('sss', $tgl, $ket, $plg); // sss = 3 data bertipe string
    $st->execute();

    // Setelah simpan, langsung buka halaman detail transaksi yang baru dibuat
    // insert_id = id otomatis dari transaksi yang barusan disimpan
    flash('ok', 'Transaksi dibuat. Silakan tambahkan detail barang.');
    header('Location: detail.php?id=' . $koneksi->insert_id); exit;
}

// Ambil semua transaksi + nama pelanggan + jumlah item
// JOIN pelanggan      : untuk mengambil nama pelanggan dari pelanggan_id
// LEFT JOIN detail    : transaksi yang belum punya detail tetap tampil (jumlah item 0)
// COUNT + GROUP BY    : menghitung jumlah barang per transaksi
$transaksi = $koneksi->query(
  "SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, p.nama AS pelanggan,
          COUNT(d.barang_id) AS jml_item
   FROM transaksi t
   JOIN pelanggan p ON p.id = t.pelanggan_id
   LEFT JOIN transaksi_detail d ON d.transaksi_id = t.id
   GROUP BY t.id ORDER BY t.id DESC");

// Ambil data pelanggan untuk pilihan dropdown di form tambah transaksi
$pelanggan = $koneksi->query("SELECT id, nama FROM pelanggan ORDER BY nama");

header_html('Data Transaksi'); // cetak bagian atas halaman
tampil_flash();                // tampilkan notifikasi jika ada
?>
<!-- BAGIAN 1: TABEL DAFTAR TRANSAKSI -->
<div class="card">
  <h1>Data Transaksi (Master)</h1>
  <table>
    <tr><th>ID</th><th>Tanggal</th><th>Pelanggan</th><th>Keterangan</th><th class="r">Item</th><th class="r">Total</th><th></th></tr>
    <?php // Ulangi satu baris tabel untuk setiap transaksi ?>
    <?php while ($r = $transaksi->fetch_assoc()): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><?= e($r['waktu_transaksi']) ?></td>
      <td><?= e($r['pelanggan']) ?></td>
      <td><?= e($r['keterangan']) ?></td>
      <td class="r"><?= $r['jml_item'] ?></td>
      <td class="r"><?= rupiah($r['total']) ?></td>
      <!-- tombol ke halaman detail, id transaksi dikirim lewat URL (GET) -->
      <td><a class="btn" href="detail.php?id=<?= $r['id'] ?>">Lihat Detail</a></td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<!-- BAGIAN 2: FORM TAMBAH TRANSAKSI BARU -->
<div class="card">
  <h2>Tambah Transaksi Baru</h2>
  <!-- method POST: data dikirim ke file ini sendiri -->
  <form method="post">
    <label>Tanggal</label>
    <input type="date" name="waktu_transaksi" value="<?= date('Y-m-d') ?>" required>
    <label>Pelanggan</label>
    <!-- dropdown pelanggan diambil dari tabel pelanggan -->
    <select name="pelanggan_id" required>
      <option value="">-- pilih pelanggan --</option>
      <?php while ($p = $pelanggan->fetch_assoc()): ?>
        <option value="<?= e($p['id']) ?>"><?= e($p['id'] . ' - ' . $p['nama']) ?></option>
      <?php endwhile; ?>
    </select>
    <label>Keterangan</label>
    <textarea name="keterangan" rows="2" cols="40"></textarea><br><br>
    <button class="btn" type="submit">Simpan &amp; Isi Detail</button>
  </form>
</div>
<?php footer_html(); // tutup halaman
