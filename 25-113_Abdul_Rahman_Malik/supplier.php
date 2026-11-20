<?php
// DATA SUPPLIER: daftar (read), tambah (create), edit (update), dan hapus (delete) dalam satu file, khusus owner
// ?aksi=tambah atau ?aksi=edit&id=... menampilkan form, tanpa aksi menampilkan daftar, hapus lewat POST
require_once __DIR__ . '/midlewire.php'; // wajib login
wajib_level([LEVEL_OWNER]);              // otorisasi: kasir ditolak

$aksi      = $_GET['aksi'] ?? '';
$mode_form = in_array($aksi, ['tambah', 'edit'], true);

// HAPUS: hanya lewat POST dengan token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    cek_csrf();

    $id = (int)($_POST['id'] ?? 0);

    // Tabel barang punya foreign key ke supplier (RESTRICT), jadi supplier yang masih punya barang akan ditolak database
    try {
        $st = $koneksi->prepare('DELETE FROM supplier WHERE id = ?');
        $st->bind_param('i', $id);
        $st->execute();
        set_flash('sukses', 'Supplier berhasil dihapus.');
    } catch (mysqli_sql_exception $ex) {
        set_flash('error', 'Supplier tidak bisa dihapus karena masih dipakai oleh data barang.');
    }
    redirect('supplier.php');
}

if ($mode_form) {
    $error     = [];
    $id_edit   = (int)($_GET['id'] ?? ($_POST['id_asli'] ?? 0));
    $mode_edit = $id_edit > 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cek_csrf();

        $nama   = trim($_POST['nama'] ?? '');
        $telp   = trim($_POST['telp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        // VALIDASI INPUT
        if ($nama === '') {
            $error['nama'] = 'Nama supplier wajib diisi.';
        } elseif (panjang_teks($nama) > 100) {
            $error['nama'] = 'Nama supplier maksimal 100 karakter.';
        }
        if ($telp !== '' && !preg_match('/^[0-9]{6,15}$/', $telp)) {
            $error['telp'] = 'Telp hanya angka, 6-15 digit.';
        }
        if (panjang_teks($alamat) > 500) {
            $error['alamat'] = 'Alamat maksimal 500 karakter.';
        }

        if (!$error) {
            if ($mode_edit) {
                $st = $koneksi->prepare('UPDATE supplier SET nama = ?, telp = ?, alamat = ? WHERE id = ?');
                $st->bind_param('sssi', $nama, $telp, $alamat, $id_edit);
                $pesan = 'Supplier berhasil diperbarui.';
            } else {
                $st = $koneksi->prepare('INSERT INTO supplier (nama, telp, alamat) VALUES (?, ?, ?)');
                $st->bind_param('sss', $nama, $telp, $alamat);
                $pesan = 'Supplier berhasil ditambahkan.';
            }
            $st->execute();
            set_flash('sukses', $pesan);
            redirect('supplier.php');
        }
        $data = ['nama' => $nama, 'telp' => $telp, 'alamat' => $alamat];
    } elseif ($mode_edit) {
        $st = $koneksi->prepare('SELECT nama, telp, alamat FROM supplier WHERE id = ?');
        $st->bind_param('i', $id_edit);
        $st->execute();
        $data = $st->get_result()->fetch_assoc();
        if (!$data) {
            set_flash('error', 'Data supplier tidak ditemukan.');
            redirect('supplier.php');
        }
    } else {
        $data = ['nama' => '', 'telp' => '', 'alamat' => ''];
    }

    $judul = $mode_edit ? 'Edit Supplier' : 'Tambah Supplier';
} else {
    $judul = 'Data Supplier';

    // Ambil supplier beserta jumlah barang yang dipasoknya
    $data = $koneksi->query(
        'SELECT s.*, COUNT(b.id) AS jml_barang
         FROM supplier s
         LEFT JOIN barang b ON b.supplier_id = s.id
         GROUP BY s.id, s.nama, s.telp, s.alamat
         ORDER BY s.id ASC'
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

        <label for="nama">Nama Supplier</label>
        <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($data['nama']) ?>">
        <span class="error-field"><?= e($error['nama'] ?? '') ?></span>

        <label for="telp">Telp</label>
        <input type="text" id="telp" name="telp" maxlength="15" value="<?= e($data['telp']) ?>">
        <span class="error-field"><?= e($error['telp'] ?? '') ?></span>

        <label for="alamat">Alamat</label>
        <textarea id="alamat" name="alamat" rows="3" maxlength="500"><?= e($data['alamat']) ?></textarea>
        <span class="error-field"><?= e($error['alamat'] ?? '') ?></span>

        <p>
            <button type="submit" class="btn btn-utama">Simpan</button>
            <a href="supplier.php" class="btn">Batal</a>
        </p>
    </form>
</div>
<?php else: ?>
<div class="kartu">
    <div class="atas-tabel">
        <h1>Data Supplier</h1>
        <a href="supplier.php?aksi=tambah" class="btn btn-utama">+ Tambah Supplier</a>
    </div>

    <div class="bungkus-tabel">
    <table>
        <tr><th>No</th><th>Nama</th><th>Telp</th><th>Alamat</th><th>Jumlah Barang</th><th>Aksi</th></tr>
        <?php if (!$data): ?>
            <tr><td colspan="6" class="kosong">Belum ada data supplier.</td></tr>
        <?php endif; ?>
        <?php $no = 1; foreach ($data as $r): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= e($r['nama']) ?></td>
            <td><?= e($r['telp']) ?></td>
            <td><?= e($r['alamat']) ?></td>
            <td><?= (int)$r['jml_barang'] ?></td>
            <td class="aksi">
                <a href="supplier.php?aksi=edit&amp;id=<?= (int)$r['id'] ?>" class="btn btn-kecil">Edit</a>
                <!-- Hapus memakai POST + token CSRF, bukan link biasa, supaya tidak bisa dipicu dari luar -->
                <form action="" method="post" class="inline"
                      onsubmit="return confirm('Hapus supplier ini?')">
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
