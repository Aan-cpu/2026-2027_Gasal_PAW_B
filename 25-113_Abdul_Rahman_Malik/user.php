<?php
// DATA USER: daftar (read), tambah (create), edit (update), dan hapus (delete) dalam satu file, khusus owner
// ?aksi=tambah atau ?aksi=edit&id=... menampilkan form, tanpa aksi menampilkan daftar, hapus lewat POST
require_once __DIR__ . '/midlewire.php'; // wajib login
wajib_level([LEVEL_OWNER]);              // otorisasi: kasir ditolak

$aksi      = $_GET['aksi'] ?? '';
$mode_form = in_array($aksi, ['tambah', 'edit'], true);

// HAPUS: hanya lewat POST dengan token CSRF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'hapus') {
    cek_csrf();

    $id = (int)($_POST['id'] ?? 0);

    // Akun yang sedang dipakai login tidak boleh dihapus sendiri
    if ($id === $_SESSION['id_user']) {
        set_flash('error', 'Anda tidak dapat menghapus akun yang sedang login.');
        redirect('user.php');
    }

    $st = $koneksi->prepare('DELETE FROM `user` WHERE id_user = ?');
    $st->bind_param('i', $id);
    $st->execute();

    set_flash('sukses', 'User berhasil dihapus.');
    redirect('user.php');
}

if ($mode_form) {
    $error = [];
    $id_edit   = (int)($_GET['id'] ?? ($_POST['id_asli'] ?? 0));
    $mode_edit = $id_edit > 0;

    // Mengedit akun sendiri: level dikunci supaya owner tidak sengaja menurunkan dirinya
    $akun_sendiri = $mode_edit && $id_edit === $_SESSION['id_user'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        cek_csrf();

        $nama     = trim($_POST['nama'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $alamat   = trim($_POST['alamat'] ?? '');
        $hp       = trim($_POST['hp'] ?? '');
        $level    = (int)($_POST['level'] ?? 0);
        if ($akun_sendiri) {
            $level = $_SESSION['level'];
        }

        // VALIDASI INPUT
        if ($nama === '') {
            $error['nama'] = 'Nama wajib diisi.';
        } elseif (panjang_teks($nama) > 50) {
            $error['nama'] = 'Nama maksimal 50 karakter.';
        }

        if ($username === '') {
            $error['username'] = 'Username wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) {
            $error['username'] = 'Username 3-30 karakter: huruf, angka, dan garis bawah.';
        } else {
            // Username harus unik, kecuali milik user yang sedang diedit
            $cek = $koneksi->prepare('SELECT 1 FROM `user` WHERE username = ? AND id_user <> ?');
            $cek->bind_param('si', $username, $id_edit);
            $cek->execute();
            if ($cek->get_result()->num_rows > 0) {
                $error['username'] = 'Username sudah dipakai.';
            }
        }

        // Password wajib saat tambah, saat edit boleh kosong (berarti tidak diganti)
        if (!$mode_edit && $password === '') {
            $error['password'] = 'Password wajib diisi.';
        } elseif ($password !== '' && strlen($password) < 6) {
            $error['password'] = 'Password minimal 6 karakter.';
        }

        if (panjang_teks($alamat) > 150) {
            $error['alamat'] = 'Alamat maksimal 150 karakter.';
        }
        if ($hp !== '' && !preg_match('/^[0-9]{6,20}$/', $hp)) {
            $error['hp'] = 'No HP hanya angka, 6-20 digit.';
        }

        if (!in_array($level, [LEVEL_OWNER, LEVEL_KASIR], true)) {
            $error['level'] = 'Pilih level user.';
        }

        if (!$error) {
            if ($mode_edit) {
                if ($password !== '') {
                    $hash = password_hash($password, PASSWORD_DEFAULT); // simpan hash, bukan password asli
                    $st = $koneksi->prepare('UPDATE `user` SET nama = ?, username = ?, password = ?, alamat = ?, hp = ?, level = ? WHERE id_user = ?');
                    $st->bind_param('sssssii', $nama, $username, $hash, $alamat, $hp, $level, $id_edit);
                } else {
                    $st = $koneksi->prepare('UPDATE `user` SET nama = ?, username = ?, alamat = ?, hp = ?, level = ? WHERE id_user = ?');
                    $st->bind_param('ssssii', $nama, $username, $alamat, $hp, $level, $id_edit);
                }
                $pesan = 'User berhasil diperbarui.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $st = $koneksi->prepare('INSERT INTO `user` (nama, username, password, alamat, hp, level) VALUES (?, ?, ?, ?, ?, ?)');
                $st->bind_param('sssssi', $nama, $username, $hash, $alamat, $hp, $level);
                $pesan = 'User berhasil ditambahkan.';
            }
            $st->execute();

            // Jika yang diedit adalah akun sendiri, perbarui nama di session agar navbar ikut berubah
            if ($akun_sendiri) {
                $_SESSION['nama']     = $nama;
                $_SESSION['username'] = $username;
            }
            set_flash('sukses', $pesan);
            redirect('user.php');
        }
        $data = ['nama' => $nama, 'username' => $username, 'alamat' => $alamat, 'hp' => $hp, 'level' => $level];
    } elseif ($mode_edit) {
        $st = $koneksi->prepare('SELECT nama, username, alamat, hp, level FROM `user` WHERE id_user = ?');
        $st->bind_param('i', $id_edit);
        $st->execute();
        $data = $st->get_result()->fetch_assoc();
        if (!$data) {
            set_flash('error', 'Data user tidak ditemukan.');
            redirect('user.php');
        }
    } else {
        $data = ['nama' => '', 'username' => '', 'alamat' => '', 'hp' => '', 'level' => LEVEL_KASIR];
    }

    $judul = $mode_edit ? 'Edit User' : 'Tambah User';
} else {
    $judul = 'Data User';

    // Password tidak ikut diambil karena tidak perlu ditampilkan
    $data = $koneksi->query('SELECT id_user, nama, username, alamat, hp, level FROM `user` ORDER BY id_user ASC')->fetch_all(MYSQLI_ASSOC);
}

require 'header.php';
if ($mode_form):
?>
<div class="kartu" style="max-width:560px">
    <h1><?= e($judul) ?></h1>
    <form action="" method="post" autocomplete="off">
        <?= csrf_input() ?>
        <input type="hidden" name="id_asli" value="<?= $mode_edit ? $id_edit : 0 ?>">

        <label for="nama">Nama</label>
        <input type="text" id="nama" name="nama" maxlength="50" value="<?= e($data['nama']) ?>">
        <span class="error-field"><?= e($error['nama'] ?? '') ?></span>

        <label for="username">Username</label>
        <input type="text" id="username" name="username" maxlength="30" value="<?= e($data['username']) ?>">
        <span class="error-field"><?= e($error['username'] ?? '') ?></span>

        <label for="password">Password <?= $mode_edit ? '(kosongkan jika tidak diganti)' : '' ?></label>
        <input type="password" id="password" name="password" maxlength="100">
        <span class="error-field"><?= e($error['password'] ?? '') ?></span>

        <label for="alamat">Alamat</label>
        <input type="text" id="alamat" name="alamat" maxlength="150" value="<?= e($data['alamat']) ?>">
        <span class="error-field"><?= e($error['alamat'] ?? '') ?></span>

        <label for="hp">No HP</label>
        <input type="text" id="hp" name="hp" maxlength="20" value="<?= e($data['hp']) ?>">
        <span class="error-field"><?= e($error['hp'] ?? '') ?></span>

        <label for="level">Level</label>
        <select id="level" name="level" <?= $akun_sendiri ? 'disabled' : '' ?>>
            <option value="1" <?= (int)$data['level'] === 1 ? 'selected' : '' ?>>1 - Owner</option>
            <option value="2" <?= (int)$data['level'] === 2 ? 'selected' : '' ?>>2 - Kasir</option>
        </select>
        <span class="error-field"><?= e($error['level'] ?? '') ?></span>

        <p>
            <button type="submit" class="btn btn-utama">Simpan</button>
            <a href="user.php" class="btn">Batal</a>
        </p>
    </form>
</div>
<?php else: ?>
<div class="kartu">
    <div class="atas-tabel">
        <h1>Data User</h1>
        <a href="user.php?aksi=tambah" class="btn btn-utama">+ Tambah User</a>
    </div>

    <div class="bungkus-tabel">
    <table>
        <tr><th>No</th><th>Nama</th><th>Username</th><th>Alamat</th><th>No HP</th><th>Level</th><th>Aksi</th></tr>
        <?php $no = 1; foreach ($data as $r): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td><?= e($r['nama']) ?></td>
            <td><?= e($r['username']) ?></td>
            <td><?= e($r['alamat']) ?></td>
            <td><?= e($r['hp']) ?></td>
            <td><?= e($r['level']) ?> (<?= e(nama_level($r['level'])) ?>)</td>
            <td class="aksi">
                <a href="user.php?aksi=edit&amp;id=<?= (int)$r['id_user'] ?>" class="btn btn-kecil">Edit</a>
                <?php if ((int)$r['id_user'] !== $_SESSION['id_user']): // akun sendiri tidak boleh dihapus ?>
                <form action="" method="post" class="inline" onsubmit="return confirm('Hapus user ini?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="aksi" value="hapus">
                    <input type="hidden" name="id" value="<?= (int)$r['id_user'] ?>">
                    <button type="submit" class="btn btn-kecil btn-merah">Hapus</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    </div>
</div>
<?php endif; ?>
<?php require 'footer.php'; ?>
