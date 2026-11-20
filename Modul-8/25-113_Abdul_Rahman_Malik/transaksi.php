<?php
// TRANSAKSI: bisa diakses owner dan kasir
// Tanpa parameter: buat transaksi penjualan baru dan daftar transaksi
// ?id=...: detail transaksi, riwayat pembayaran, dan tambah pembayaran
require_once __DIR__ . '/midlewire.php'; // cukup wajib login, tanpa batas level

$id            = (int)($_GET['id'] ?? ($_POST['id'] ?? 0));
$mode_detail   = $id > 0;
$daftar_metode = ['TUNAI', 'TRANSFER', 'EDC'];

if ($mode_detail) {
    $st = $koneksi->prepare(
        'SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, t.pelanggan_id, p.nama AS nama_pelanggan
         FROM transaksi t
         LEFT JOIN pelanggan p ON p.id = t.pelanggan_id
         WHERE t.id = ?'
    );
    $st->bind_param('i', $id);
    $st->execute();
    $trx = $st->get_result()->fetch_assoc();
    if (!$trx) {
        set_flash('error', 'Transaksi tidak ditemukan.');
        redirect('transaksi.php');
    }

    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cek_csrf();

        $metode    = $_POST['metode'] ?? '';
        $jumlah_tx = trim($_POST['jumlah'] ?? '');

        if (!in_array($metode, $daftar_metode, true)) {
            $error = 'Pilih metode pembayaran.';
        } elseif (!ctype_digit($jumlah_tx) || (int)$jumlah_tx < 1 || strlen($jumlah_tx) > 9) {
            $error = 'Jumlah bayar harus angka bulat lebih dari 0.';
        } else {
            $jumlah = (int)$jumlah_tx;
            $koneksi->begin_transaction();
            try {
                // Kunci transaksi lalu hitung sisa tagihan, supaya pembayaran tidak melebihi total
                $k = $koneksi->prepare('SELECT total FROM transaksi WHERE id = ? FOR UPDATE');
                $k->bind_param('i', $id);
                $k->execute();
                $total = (int)$k->get_result()->fetch_assoc()['total'];

                $s = $koneksi->prepare('SELECT COALESCE(SUM(total), 0) AS n FROM pembayaran WHERE transaksi_id = ?');
                $s->bind_param('i', $id);
                $s->execute();
                $sisa = $total - (int)$s->get_result()->fetch_assoc()['n'];

                if ($jumlah > $sisa) {
                    $koneksi->rollback();
                    $error = 'Jumlah bayar melebihi sisa tagihan (' . rupiah($sisa) . ').';
                } else {
                    $p = $koneksi->prepare('INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id) VALUES (NOW(), ?, ?, ?)');
                    $p->bind_param('isi', $jumlah, $metode, $id);
                    $p->execute();
                    $koneksi->commit();
                    set_flash('sukses', 'Pembayaran ' . rupiah($jumlah) . ' berhasil dicatat.');
                    redirect('transaksi.php?id=' . $id);
                }
            } catch (mysqli_sql_exception $ex) {
                $koneksi->rollback();
                $error = 'Pembayaran gagal disimpan, silakan coba lagi.';
            }
        }
    }

    $st = $koneksi->prepare(
        'SELECT b.kode_barang, b.nama_barang, d.harga, d.qty
         FROM transaksi_detail d
         JOIN barang b ON b.id = d.barang_id
         WHERE d.transaksi_id = ?
         ORDER BY b.nama_barang'
    );
    $st->bind_param('i', $id);
    $st->execute();
    $detail = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $st = $koneksi->prepare('SELECT waktu_bayar, total, metode FROM pembayaran WHERE transaksi_id = ? ORDER BY waktu_bayar, id');
    $st->bind_param('i', $id);
    $st->execute();
    $bayar = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    $dibayar = array_sum(array_column($bayar, 'total'));
    $sisa    = (int)$trx['total'] - (int)$dibayar;
    $sb      = status_bayar($trx['total'], $dibayar);

    $judul = 'Detail Transaksi #' . $trx['id'];
} else {
    $judul = 'Transaksi';
    $baris_awal = 3; // jumlah baris barang yang tampil di form

    $error = [];

    $pelanggan = $koneksi->query('SELECT id, nama FROM pelanggan ORDER BY nama')->fetch_all(MYSQLI_ASSOC);
    $id_pelanggan_valid = array_column($pelanggan, 'id');
    $barang = $koneksi->query('SELECT id, kode_barang, nama_barang, harga, stok FROM barang ORDER BY nama_barang')->fetch_all(MYSQLI_ASSOC);

    $lama = ['pelanggan_id' => '', 'tanggal' => date('Y-m-d'), 'keterangan' => '', 'metode' => '', 'item' => []];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cek_csrf();

        $lama['pelanggan_id'] = $_POST['pelanggan_id'] ?? '';
        $lama['tanggal']      = trim($_POST['tanggal'] ?? '');
        $lama['keterangan']   = trim($_POST['keterangan'] ?? '');
        $lama['metode']       = $_POST['metode'] ?? '';

        // Kumpulkan baris barang: gabungkan barang yang sama, abaikan baris kosong
        $item = [];
        $id_barang_post = $_POST['barang_id'] ?? [];
        $qty_post       = $_POST['qty'] ?? [];
        if (is_array($id_barang_post) && is_array($qty_post)) {
            foreach ($id_barang_post as $i => $bid) {
                $bid = (int)$bid;
                $qty_txt = trim((string)($qty_post[$i] ?? ''));
                $lama['item'][] = ['barang_id' => $bid, 'qty' => $qty_txt];
                if ($bid === 0 && $qty_txt === '') {
                    continue; // baris kosong
                }
                if ($bid === 0 || !ctype_digit($qty_txt) || (int)$qty_txt < 1 || strlen($qty_txt) > 6) {
                    $error['item'] = 'Setiap baris harus memilih barang dan jumlah bulat lebih dari 0.';
                    continue;
                }
                $item[$bid] = ($item[$bid] ?? 0) + (int)$qty_txt;
            }
        }

        // VALIDASI INPUT
        if (!in_array($lama['pelanggan_id'], $id_pelanggan_valid, true)) {
            $error['pelanggan_id'] = 'Pilih pelanggan yang valid.';
        }
        if ($lama['tanggal'] === '' || !tanggal_valid($lama['tanggal'])) {
            $error['tanggal'] = 'Tanggal tidak valid.';
        }
        if (panjang_teks($lama['keterangan']) > 500) {
            $error['keterangan'] = 'Keterangan maksimal 500 karakter.';
        }
        if ($lama['metode'] !== '' && !in_array($lama['metode'], $daftar_metode, true)) {
            $error['metode'] = 'Metode pembayaran tidak valid.';
        }
        if (!$item && !isset($error['item'])) {
            $error['item'] = 'Pilih minimal satu barang.';
        }

        if (!$error) {
            // Semua langkah dibungkus transaksi database: berhasil bersama atau batal bersama
            $koneksi->begin_transaction();
            try {
                $st = $koneksi->prepare('INSERT INTO transaksi (waktu_transaksi, keterangan, total, pelanggan_id) VALUES (?, ?, 0, ?)');
                $st->bind_param('sss', $lama['tanggal'], $lama['keterangan'], $lama['pelanggan_id']);
                $st->execute();
                $id_transaksi = $koneksi->insert_id;

                $total = 0;
                foreach ($item as $bid => $qty) {
                    // Kunci baris barang agar stok tidak terpakai dua transaksi sekaligus
                    $q = $koneksi->prepare('SELECT nama_barang, harga, stok FROM barang WHERE id = ? FOR UPDATE');
                    $q->bind_param('i', $bid);
                    $q->execute();
                    $b = $q->get_result()->fetch_assoc();
                    if (!$b) {
                        throw new RuntimeException('Barang tidak ditemukan.');
                    }
                    if ((int)$b['stok'] < $qty) {
                        throw new RuntimeException('Stok ' . $b['nama_barang'] . ' tidak cukup (sisa ' . $b['stok'] . ').');
                    }
                    // Harga diambil dari database, bukan dari kiriman form
                    $harga = (int)$b['harga'];
                    $d = $koneksi->prepare('INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES (?, ?, ?, ?)');
                    $d->bind_param('iiii', $id_transaksi, $bid, $harga, $qty);
                    $d->execute();

                    $u = $koneksi->prepare('UPDATE barang SET stok = stok - ? WHERE id = ?');
                    $u->bind_param('ii', $qty, $bid);
                    $u->execute();

                    $total += $harga * $qty;
                }

                $u = $koneksi->prepare('UPDATE transaksi SET total = ? WHERE id = ?');
                $u->bind_param('ii', $total, $id_transaksi);
                $u->execute();

                // Jika kasir memilih metode, transaksi langsung dibayar lunas
                if ($lama['metode'] !== '') {
                    $p = $koneksi->prepare('INSERT INTO pembayaran (waktu_bayar, total, metode, transaksi_id) VALUES (NOW(), ?, ?, ?)');
                    $p->bind_param('isi', $total, $lama['metode'], $id_transaksi);
                    $p->execute();
                }

                $koneksi->commit();
                set_flash('sukses', 'Transaksi #' . $id_transaksi . ' berhasil disimpan, total ' . rupiah($total) . '.');
                redirect('transaksi.php?id=' . $id_transaksi);
            } catch (RuntimeException $ex) {
                $koneksi->rollback();
                $error['item'] = $ex->getMessage(); // pesan stok kurang dan sejenisnya aman ditampilkan
            } catch (mysqli_sql_exception $ex) {
                $koneksi->rollback();
                $error['umum'] = 'Transaksi gagal disimpan, silakan coba lagi.';
            }
        }
    }

    // Pastikan minimal $baris_awal baris tampil di form
    while (count($lama['item']) < $baris_awal) {
        $lama['item'][] = ['barang_id' => 0, 'qty' => ''];
    }

    $daftar = ambil_transaksi($koneksi);
}

require 'header.php';
if ($mode_detail):
?>
<div class="kartu">
    <div class="atas-tabel">
        <h1>Transaksi #<?= (int)$trx['id'] ?></h1>
        <a href="transaksi.php" class="btn">Kembali</a>
    </div>
    <p>
        Tanggal: <strong><?= e($trx['waktu_transaksi']) ?></strong><br>
        Pelanggan: <strong><?= e($trx['pelanggan_id']) ?> - <?= e($trx['nama_pelanggan']) ?></strong><br>
        Keterangan: <?= e($trx['keterangan']) ?><br>
        Status: <span class="badge badge-<?= e(str_replace(' ', '', $sb)) ?>"><?= e($sb) ?></span>
    </p>

    <div class="bungkus-tabel">
    <table>
        <tr><th>Kode</th><th>Nama Barang</th><th class="teks-kanan">Harga</th><th class="teks-kanan">Qty</th><th class="teks-kanan">Subtotal</th></tr>
        <?php foreach ($detail as $r): ?>
        <tr>
            <td><?= e($r['kode_barang']) ?></td>
            <td><?= e($r['nama_barang']) ?></td>
            <td class="teks-kanan"><?= e(rupiah($r['harga'])) ?></td>
            <td class="teks-kanan"><?= (int)$r['qty'] ?></td>
            <td class="teks-kanan"><?= e(rupiah($r['harga'] * $r['qty'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr><th colspan="4" class="teks-kanan">Total</th><th class="teks-kanan"><?= e(rupiah($trx['total'])) ?></th></tr>
    </table>
    </div>
</div>

<div class="kartu">
    <h2>Pembayaran</h2>
    <div class="bungkus-tabel">
    <table>
        <tr><th>Waktu Bayar</th><th>Metode</th><th class="teks-kanan">Jumlah</th></tr>
        <?php if (!$bayar): ?>
            <tr><td colspan="3" class="kosong">Belum ada pembayaran.</td></tr>
        <?php endif; ?>
        <?php foreach ($bayar as $r): ?>
        <tr>
            <td><?= e($r['waktu_bayar']) ?></td>
            <td><?= e($r['metode']) ?></td>
            <td class="teks-kanan"><?= e(rupiah($r['total'])) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr><th colspan="2" class="teks-kanan">Sudah dibayar</th><th class="teks-kanan"><?= e(rupiah($dibayar)) ?></th></tr>
        <tr><th colspan="2" class="teks-kanan">Sisa tagihan</th><th class="teks-kanan"><?= e(rupiah($sisa)) ?></th></tr>
    </table>
    </div>

    <?php if ($sisa > 0): ?>
    <h2 style="margin-top:20px">Tambah Pembayaran</h2>
    <?php if ($error !== ''): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endif; ?>
    <form action="" method="post" style="max-width:420px">
        <?= csrf_input() ?>
        <input type="hidden" name="id" value="<?= (int)$trx['id'] ?>">

        <label for="metode">Metode</label>
        <select id="metode" name="metode">
            <?php foreach ($daftar_metode as $m): ?>
                <option value="<?= e($m) ?>"><?= e($m) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="jumlah">Jumlah Bayar (Rp)</label>
        <input type="number" id="jumlah" name="jumlah" min="1" max="<?= (int)$sisa ?>" step="1" value="<?= (int)$sisa ?>">

        <p><button type="submit" class="btn btn-hijau">Simpan Pembayaran</button></p>
    </form>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="kartu">
    <h2>Buat Transaksi Baru</h2>
    <?php if (isset($error['umum'])): ?>
        <div class="alert alert-error"><?= e($error['umum']) ?></div>
    <?php endif; ?>
    <form action="" method="post">
        <?= csrf_input() ?>

        <div class="baris-filter">
            <div>
                <label for="pelanggan_id">Pelanggan</label>
                <select id="pelanggan_id" name="pelanggan_id">
                    <option value="">Pilih pelanggan</option>
                    <?php foreach ($pelanggan as $p): ?>
                        <option value="<?= e($p['id']) ?>" <?= $lama['pelanggan_id'] === $p['id'] ? 'selected' : '' ?>><?= e($p['id']) ?> - <?= e($p['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="error-field"><?= e($error['pelanggan_id'] ?? '') ?></span>
            </div>
            <div>
                <label for="tanggal">Tanggal</label>
                <input type="date" id="tanggal" name="tanggal" value="<?= e($lama['tanggal']) ?>">
                <span class="error-field"><?= e($error['tanggal'] ?? '') ?></span>
            </div>
        </div>

        <label for="keterangan">Keterangan</label>
        <input type="text" id="keterangan" name="keterangan" maxlength="500" value="<?= e($lama['keterangan']) ?>">
        <span class="error-field"><?= e($error['keterangan'] ?? '') ?></span>

        <label>Barang yang dibeli</label>
        <div class="bungkus-tabel">
        <table id="tabel-item">
            <tr><th>Barang</th><th style="width:130px">Jumlah</th></tr>
            <?php foreach ($lama['item'] as $it): ?>
            <tr>
                <td>
                    <select name="barang_id[]">
                        <option value="0">Pilih barang</option>
                        <?php foreach ($barang as $b): ?>
                            <option value="<?= (int)$b['id'] ?>" <?= (int)$it['barang_id'] === (int)$b['id'] ? 'selected' : '' ?>>
                                <?= e($b['kode_barang']) ?> - <?= e($b['nama_barang']) ?> (<?= e(rupiah($b['harga'])) ?>, stok <?= (int)$b['stok'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
                <td><input type="number" name="qty[]" min="1" step="1" value="<?= e($it['qty']) ?>"></td>
            </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <span class="error-field"><?= e($error['item'] ?? '') ?></span>
        <p><button type="button" class="btn btn-kecil" id="tambah-baris">+ Tambah baris barang</button></p>

        <label for="metode">Pembayaran</label>
        <select id="metode" name="metode">
            <option value="">Belum dibayar</option>
            <?php foreach ($daftar_metode as $m): ?>
                <option value="<?= e($m) ?>" <?= $lama['metode'] === $m ? 'selected' : '' ?>>Bayar lunas sekarang (<?= e($m) ?>)</option>
            <?php endforeach; ?>
        </select>
        <span class="error-field"><?= e($error['metode'] ?? '') ?></span>

        <p><button type="submit" class="btn btn-utama">Simpan Transaksi</button></p>
    </form>
</div>

<div class="kartu">
    <h2>Daftar Transaksi</h2>
    <div class="bungkus-tabel">
    <table>
        <tr><th>No</th><th>Tanggal</th><th>Pelanggan</th><th>Keterangan</th><th class="teks-kanan">Total</th><th>Status</th><th>Aksi</th></tr>
        <?php if (!$daftar): ?>
            <tr><td colspan="7" class="kosong">Belum ada transaksi.</td></tr>
        <?php endif; ?>
        <?php foreach ($daftar as $r): $sb = status_bayar($r['total'], $r['dibayar']); ?>
        <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= e($r['waktu_transaksi']) ?></td>
            <td><?= e($r['nama_pelanggan']) ?></td>
            <td><?= e($r['keterangan']) ?></td>
            <td class="teks-kanan"><?= e(rupiah($r['total'])) ?></td>
            <td><span class="badge badge-<?= e(str_replace(' ', '', $sb)) ?>"><?= e($sb) ?></span></td>
            <td class="aksi"><a href="transaksi.php?id=<?= (int)$r['id'] ?>" class="btn btn-kecil">Detail</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>

<script>
// Tombol untuk menambah baris barang baru dengan menyalin baris terakhir
document.getElementById('tambah-baris').addEventListener('click', function () {
    var tabel = document.getElementById('tabel-item');
    var baris = tabel.rows[tabel.rows.length - 1].cloneNode(true);
    baris.querySelector('select').selectedIndex = 0;
    baris.querySelector('input').value = '';
    tabel.appendChild(baris);
});
</script>
<?php endif; ?>
<?php require 'footer.php'; ?>
