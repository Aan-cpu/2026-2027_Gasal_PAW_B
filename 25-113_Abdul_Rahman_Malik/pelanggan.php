<?php
// DATA PELANGGAN: daftar (read), tambah (create), edit (update), dan hapus (delete) dalam satu file, khusus owner
// ?aksi=tambah atau ?aksi=edit&id=... menampilkan form, tanpa aksi menampilkan daftar, hapus lewat POST
require_once __DIR__ . '/midlewire.php'; // wajib login
wajib_level([LEVEL_OWNER]);              // otorisasi: kasir ditolak

$aksi      = $_GET['aksi'] ?? '';
$mode_form = in_array($aksi, ['tambah', 'edit'], true);

// HAPUS: hanya lewat POST dengan token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    cek_csrf();

    $id = $_POST['id'] ?? '';

    // transaksi punya foreign key ke pelanggan (RESTRICT), jadi pelanggan yang sudah punya transaksi ditolak database
    try {
        $st = $koneksi->prepare('DELETE FROM pelanggan WHERE id = ?');
        $st->bind_param('s', $id);
        $st->execute();
        set_flash('sukses', 'Pelanggan berhasil dihapus.');
    } catch (mysqli_sql_exception $ex) {
        set_flash('error', 'Pelanggan tidak bisa dihapus karena sudah punya data transaksi.');
    }
    redirect('pelanggan.php');
}

if ($mode_form) {
    $error = [];

    // Mode edit jika ada ?id= di URL, mode tambah jika tidak ada
    $id_edit   = $_GET['id'] ?? ($_POST['id_asli'] ?? '');
    $mode_edit = ($id_edit !== '');

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cek_csrf();

        $id     = trim($_POST['id'] ?? '');
        $nama   = trim($_POST['nama'] ?? '');
        $jk     = $_POST['jenis_kelamin'] ?? '';
        $telp   = trim($_POST['telp'] ?? '');
        $alamat = trim($_POST['alamat'] ?? '');

        // Saat edit, ID tidak boleh diubah, pakai ID asli
        if ($mode_edit) {
            $id = $id_edit;
        }

        // VALIDASI INPUT
        if ($id === '') {
            $error['id'] = 'ID pelanggan wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_-]{1,20}$/', $id)) {
            $error['id'] = 'ID hanya huruf, angka, strip, dan garis bawah (maksimal 20 karakter).';
        } elseif (!$mode_edit) {
            // Saat tambah, pastikan ID belum dipakai
            $cek = $koneksi->prepare('SELECT 1 FROM pelanggan WHERE id = ?');
            $cek->bind_param('s', $id);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) {
                $error['id'] = 'ID pelanggan sudah dipakai.';
            }
        }

        if ($nama === '') {
            $error['nama'] = 'Nama pelanggan wajib diisi.';
        } elseif (panjang_teks($nama) > 100) {
            $error['nama'] = 'Nama pelanggan maksimal 100 karakter.';
        }
        if (!in_array($jk, ['L', 'P'], true)) {
            $error['jenis_kelamin'] = 'Pilih jenis kelamin.';
        }
        if ($telp !== '' && !preg_match('/^[0-9]{6,15}$/', $telp)) {
            $error['telp'] = 'Telp hanya angka, 6-15 digit.';
        }
        if (panjang_teks($alamat) > 500) {
            $error['alamat'] = 'Alamat maksimal 500 karakter.';
        }

        if (!$error) {
            if ($mode_edit) {
                $st = $koneksi->prepare('UPDATE pelanggan SET nama = ?, jenis_kelamin = ?, telp = ?, alamat = ? WHERE id = ?');
                $st->bind_param('sssss', $nama, $jk, $telp, $alamat, $id);
                $pesan = 'Pelanggan berhasil diperbarui.';
            } else {
                $st = $koneksi->prepare('INSERT INTO pelanggan (id, nama, jenis_kelamin, telp, alamat) VALUES (?, ?, ?, ?, ?)');
                $st->bind_param('sssss', $id, $nama, $jk, $telp, $alamat);
                $pesan = 'Pelanggan berhasil ditambahkan.';
            }
            $st->execute();
            set_flash('sukses', $pesan);
            redirect('pelanggan.php');
        }
        $data = ['id' => $id, 'nama' => $nama, 'jenis_kelamin' => $jk, 'telp' => $telp, 'alamat' => $alamat];
    } elseif ($mode_edit) {
        $st = $koneksi->prepare('SELECT id, nama, jenis_kelamin, telp, alamat FROM pelanggan WHERE id = ?');
        $st->bind_param('s', $id_edit);
        $st->execute();
        $data = $st->get_result()->fetch_assoc();
        if (!$data) {
            set_flash('error', 'Data pelanggan tidak ditemukan.');
            redirect('pelanggan.php');
        }
    } else {
        // Saran ID berikutnya, contoh PLG005 menjadi PLG006
        $terakhir = $koneksi->query("SELECT MAX(id) AS m FROM pelanggan WHERE id REGEXP '^PLG[0-9]+$'")->fetch_assoc()['m'];
        $saran    = 'PLG' . str_pad((string)((int)substr((string)$terakhir, 3) + 1), 3, '0', STR_PAD_LEFT);
        $data = ['id' => $saran, 'nama' => '', 'jenis_kelamin' => 'L', 'telp' => '', 'alamat' => ''];
    }

    $judul = $mode_edit ? 'Edit Pelanggan' : 'Tambah Pelanggan';
} else {
    $judul = 'Data Pelanggan';

    // Ambil pelanggan beserta jumlah transaksinya
    $data = $koneksi->query(
        'SELECT p.*, COUNT(t.id) AS jml_transaksi
         FROM pelanggan p
         LEFT JOIN transaksi t ON t.pelanggan_id = p.id
         GROUP BY p.id, p.nama, p.jenis_kelamin, p.telp, p.alamat
         ORDER BY p.id ASC'
    )->fetch_all(MYSQLI_ASSOC);
}

require 'header.php';
if ($mode_form):
?>
<div class="kartu" style="max-width:560px">
    <h1><?= e($judul) ?></h1>
    <form action="" method="post">
        <?= csrf_input() ?>
        <input type="hidden" name="id_asli" value="<?= e($mode_edit ? $id_edit : '') ?>">

        <label for="id">ID Pelanggan</label>
        <input type="text" id="id" name="id" maxlength="20" value="<?= e($data['id']) ?>" <?= $mode_edit ? 'readonly' : '' ?>>
        <span class="error-field"><?= e($error['id'] ?? '') ?></span>

        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="100" value="<?= e($data['nama']) ?>">
        <span class="error-field"><?= e($error['nama'] ?? '') ?></span>

        <label for="jenis_kelamin">Jenis Kelamin</label>
        <select id="jenis_kelamin" name="jenis_kelamin">
            <option value="L" <?= $data['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>L - Laki-laki</option>
            <option value="P" <?= $data['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>P - Perempuan</option>
        </select>
        <span class="error-field"><?= e($error['jenis_kelamin'] ?? '') ?></span>

        <label for="telp">Telp</label>
        <input type="text" id="telp" name="telp" maxlength="15" value="<?= e($data['telp']) ?>">
        <span class="error-field"><?= e($error['telp'] ?? '') ?></span>

        <label for="alamat">Alamat</label>
        <textarea id="alamat" name="alamat" rows="3" maxlength="500"><?= e($data['alamat']) ?></textarea>
        <span class="error-field"><?= e($error['alamat'] ?? '') ?></span>

        <p>
            <button type="submit" class="btn btn-utama">Simpan</button>
            <a href="pelanggan.php" class="btn">Batal</a>
        </p>
    </form>
</div>
<?php else: ?>
<div class="kartu">
    <div class="atas-tabel">
        <h1>Data Pelanggan</h1>
        <a href="pelanggan.php?aksi=tambah" class="btn btn-utama">+ Tambah Pelanggan</a>
    </div>

    <div class="bungkus-tabel">
    <table>
        <tr><th>ID</th><th>Nama</th><th>JK</th><th>Telp</th><th>Alamat</th><th>Jumlah Transaksi</th><th>Aksi</th></tr>
        <?php if (!$data): ?>
            <tr><td colspan="7" class="kosong">Belum ada data pelanggan.</td></tr>
        <?php endif; ?>
        <?php foreach ($data as $r): ?>
        <tr>
            <td><?= e($r['id']) ?></td>
            <td><?= e($r['nama']) ?></td>
            <td><?= e($r['jenis_kelamin']) ?></td>
            <td><?= e($r['telp']) ?></td>
            <td><?= e($r['alamat']) ?></td>
            <td><?= (int)$r['jml_transaksi'] ?></td>
            <td class="aksi">
                <a href="pelanggan.php?aksi=edit&amp;id=<?= urlencode($r['id']) ?>" class="btn btn-kecil">Edit</a>
                <form action="" method="post" class="inline"
                      onsubmit="return confirm('Hapus pelanggan ini?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= e($r['id']) ?>">
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
