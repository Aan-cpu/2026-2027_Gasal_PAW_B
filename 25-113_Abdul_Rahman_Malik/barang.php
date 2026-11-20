<?php
// DATA BARANG: daftar (read), tambah (create), edit (update), dan hapus (delete) dalam satu file, khusus owner
// ?aksi=tambah atau ?aksi=edit&id=... menampilkan form, tanpa aksi menampilkan daftar, hapus lewat POST
require_once __DIR__ . '/midlewire.php'; // wajib login
wajib_level([LEVEL_OWNER]);              // otorisasi: kasir ditolak

$aksi      = $_GET['aksi'] ?? '';
$mode_form = in_array($aksi, ['tambah', 'edit'], true);

// HAPUS: hanya lewat POST dengan token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    cek_csrf();

    $id = (int)($_POST['id'] ?? 0);

    // transaksi_detail punya foreign key ke barang (RESTRICT), jadi barang yang sudah pernah terjual ditolak database
    try {
        $st = $koneksi->prepare('DELETE FROM barang WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        set_flash('sukses', 'Barang berhasil dihapus.');
    } catch (mysqli_sql_exception $ex) {
        set_flash('error', 'Barang tidak bisa dihapus karena sudah dipakai di data transaksi.');
    }
    redirect('barang.php');
}

if ($mode_form) {
    $error = [];

    // Daftar supplier untuk dropdown
    $supplier = $koneksi->query('SELECT id, nama FROM supplier ORDER BY nama')->fetch_all(MYSQLI_ASSOC);
    $id_supplier_valid = array_map('intval', array_column($supplier, 'id')); // dipakai untuk validasi pilihan

    $id_edit   = (int)($_GET['id'] ?? ($_POST['id_asli'] ?? 0));
    $mode_edit = $id_edit > 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cek_csrf();

        $kode        = strtoupper(trim($_POST['kode_barang'] ?? ''));
        $nama        = trim($_POST['nama_barang'] ?? '');
        $harga_txt   = trim($_POST['harga'] ?? '');
        $stok_txt    = trim($_POST['stok'] ?? '');
        $supplier_id = (int)($_POST['supplier_id'] ?? 0);

        // VALIDASI INPUT
        if ($kode === '') {
            $error['kode_barang'] = 'Kode barang wajib diisi.';
        } elseif (!preg_match('/^[A-Z0-9_-]{1,10}$/', $kode)) {
            $error['kode_barang'] = 'Kode hanya huruf, angka, strip, dan garis bawah (maksimal 10 karakter).';
        } else {
            // Kode harus unik, kecuali milik barang yang sedang diedit
            $cek = $koneksi->prepare('SELECT 1 FROM barang WHERE kode_barang = ? AND id <> ?');
            $cek->bind_param('si', $kode, $id_edit);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) {
                $error['kode_barang'] = 'Kode barang sudah dipakai.';
            }
        }

        if ($nama === '') {
            $error['nama_barang'] = 'Nama barang wajib diisi.';
        } elseif (panjang_teks($nama) > 100) {
            $error['nama_barang'] = 'Nama barang maksimal 100 karakter.';
        }

        if (!ctype_digit($harga_txt) || (int)$harga_txt < 1 || strlen($harga_txt) > 9) {
            $error['harga'] = 'Harga harus angka bulat lebih dari 0.';
        }
        if (!ctype_digit($stok_txt) || strlen($stok_txt) > 9) {
            $error['stok'] = 'Stok harus angka bulat, minimal 0.';
        }
        if (!in_array($supplier_id, $id_supplier_valid, true)) {
            $error['supplier_id'] = 'Pilih supplier yang valid.';
        }

        if (!$error) {
            $harga = (int)$harga_txt;
            $stok  = (int)$stok_txt;
            if ($mode_edit) {
                $st = $koneksi->prepare('UPDATE barang SET kode_barang = ?, nama_barang = ?, harga = ?, stok = ?, supplier_id = ? WHERE id = ?');
                $st->bind_param('ssiiii', $kode, $nama, $harga, $stok, $supplier_id, $id_edit);
                $pesan = 'Barang berhasil diperbarui.';
            } else {
                $st = $koneksi->prepare('INSERT INTO barang (kode_barang, nama_barang, harga, stok, supplier_id) VALUES (?, ?, ?, ?, ?)');
                $st->bind_param('ssiii', $kode, $nama, $harga, $stok, $supplier_id);
                $pesan = 'Barang berhasil ditambahkan.';
            }
            $st->execute();
            set_flash('sukses', $pesan);
            redirect('barang.php');
        }
        $data = ['kode_barang' => $kode, 'nama_barang' => $nama, 'harga' => $harga_txt, 'stok' => $stok_txt, 'supplier_id' => $supplier_id];
    } elseif ($mode_edit) {
        $st = $koneksi->prepare('SELECT kode_barang, nama_barang, harga, stok, supplier_id FROM barang WHERE id = ?');
        $st->bind_param('i', $id_edit);
        $st->execute();
        $data = $st->get_result()->fetch_assoc();
        if (!$data) {
            set_flash('error', 'Data barang tidak ditemukan.');
            redirect('barang.php');
        }
    } else {
        $data = ['kode_barang' => '', 'nama_barang' => '', 'harga' => '', 'stok' => '0', 'supplier_id' => 0];
    }

    $judul = $mode_edit ? 'Edit Barang' : 'Tambah Barang';
} else {
    $judul = 'Data Barang';

    $data = $koneksi->query(
        'SELECT b.*, s.nama AS nama_supplier
         FROM barang b
         LEFT JOIN supplier s ON s.id = b.supplier_id
         ORDER BY b.id ASC'
    )->fetch_all(MYSQLI_ASSOC);
}

require 'header.php';
if ($mode_form):
?>
<div class="kartu" style="max-width:560px">
    <h1><?= e($judul) ?></h1>
    <form action="" method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="id_asli" value="<?= $mode_edit ? $id_edit : 0 ?>">

        <label for="kode_barang">Kode Barang</label>
        <input type="text" id="kode_barang" name="kode_barang" maxlength="10" value="<?= e($data['kode_barang']) ?>">
        <span class="error-field"><?= e($error['kode_barang'] ?? '') ?></span>

        <label for="nama_barang">Nama Barang</label>
        <input type="text" id="nama_barang" name="nama_barang" maxlength="100" value="<?= e($data['nama_barang']) ?>">
        <span class="error-field"><?= e($error['nama_barang'] ?? '') ?></span>

        <label for="harga">Harga (Rp)</label>
        <input type="number" id="harga" name="harga" min="1" step="1" value="<?= e($data['harga']) ?>">
        <span class="error-field"><?= e($error['harga'] ?? '') ?></span>

        <label for="stok">Stok</label>
        <input type="number" id="stok" name="stok" min="0" step="1" value="<?= e($data['stok']) ?>">
        <span class="error-field"><?= e($error['stok'] ?? '') ?></span>

        <label for="supplier_id">Supplier</label>
        <select id="supplier_id" name="supplier_id">
            <option value="">Pilih supplier</option>
            <?php foreach ($supplier as $s): ?>
                <option value="<?= (int)$s['id'] ?>" <?= (int)$data['supplier_id'] === (int)$s['id'] ? 'selected' : '' ?>><?= e($s['nama']) ?></option>
            <?php endforeach; ?>
        </select>
        <span class="error-field"><?= e($error['supplier_id'] ?? '') ?></span>

        <p>
            <button type="submit" class="btn btn-utama">Simpan</button>
            <a href="barang.php" class="btn">Batal</a>
        </p>
    </form>
</div>
<?php else: ?>
<div class="kartu">
    <div class="atas-tabel">
        <h1>Data Barang</h1>
        <a href="barang.php?aksi=tambah" class="btn btn-utama">+ Tambah Barang</a>
    </div>

    <div class="bungkus-tabel">
    <table>
        <tr><th>Kode</th><th>Nama Barang</th><th class="teks-kanan">Harga</th><th class="teks-kanan">Stok</th><th>Supplier</th><th>Aksi</th></tr>
        <?php if (!$data): ?>
            <tr><td colspan="6" class="kosong">Belum ada data barang.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $r): ?>
        <tr>
            <td><?= e($r['kode_barang']) ?></td>
            <td><?= e($r['nama_barang']) ?></td>
            <td class="teks-kanan"><?= e(rupiah($r['harga'])) ?></td>
            <td class="teks-kanan"><?= (int)$r['stok'] ?></td>
            <td><?= e($r['nama_supplier'] ?? '-') ?></td>
            <td class="aksi">
                <a href="barang.php?aksi=edit&amp;id=<?= (int)$r['id'] ?>" class="btn btn-kecil">Edit</a>
                <form action="" method="post" class="inline"
                      onsubmit="return confirm('Hapus barang ini?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <button type="submit" class="btn btn-kecil btn-merah">Hapus</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php endif; ?>
<?php require 'footer.php'; ?>
