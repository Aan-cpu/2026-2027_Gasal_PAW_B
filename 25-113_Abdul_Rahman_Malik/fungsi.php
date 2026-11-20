<?php
// FILE FUNGSI BERSAMA
// Di-require oleh semua halaman: berisi session, helper keamanan, dan fungsi laporan penjualan

require_once __DIR__ . '/koneksi.php'; // sambungkan database ($koneksi)

// Atur cookie session sebelum session_start() dipanggil
if (session_status() === PHP_SESSION_NONE) {
    // Nama session khusus aplikasi ini, supaya tidak bentrok dengan session proyek lain di localhost
    session_name('SESI_PENJUALAN');
    session_set_cookie_params([
        'httponly' => true,   // cookie tidak bisa dibaca JavaScript (cegah pencurian lewat XSS)
        'samesite' => 'Lax',  // cookie tidak ikut terkirim dari situs lain (bantu cegah CSRF)
    ]);
    session_start(); // mulai session, wajib sebelum memakai $_SESSION
}

// Batas diam sebelum otomatis logout (detik), di sini 30 menit
const BATAS_IDLE = 1800;

// Level user sesuai kolom level di tabel user
const LEVEL_OWNER = 1;
const LEVEL_KASIR = 2;

// Escape output ke HTML supaya input user tidak dijalankan sebagai script (cegah XSS)
function e($teks)
{
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Hitung panjang teks, memakai mb_strlen jika tersedia agar huruf non-ASCII dihitung benar
function panjang_teks($teks)
{
    return function_exists('mb_strlen') ? mb_strlen($teks) : strlen($teks);
}

// Pindah halaman lalu hentikan skrip
function redirect($url)
{
    header('Location: ' . $url);
    exit();
}

// Ubah angka level menjadi nama yang mudah dibaca
function nama_level($level)
{
    return ((int)$level === LEVEL_OWNER) ? 'Owner' : 'Kasir';
}

// FLASH MESSAGE: pesan yang hanya tampil satu kali setelah redirect
function set_flash($tipe, $pesan)
{
    $_SESSION['flash'] = ['tipe' => $tipe, 'pesan' => $pesan];
}

function tampil_flash()
{
    if (!isset($_SESSION['flash'])) {
        return; // tidak ada pesan
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']); // hapus supaya tidak muncul lagi saat refresh
    echo '<div class="alert alert-' . e($f['tipe']) . '">' . e($f['pesan']) . '</div>';
}

// CSRF: token acak yang menempel di setiap form POST untuk memastikan form berasal dari situs ini
function csrf_token()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // 64 karakter acak
    }
    return $_SESSION['csrf'];
}

// Cetak input hidden berisi token, dipasang di dalam setiap <form method="post">
function csrf_input()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// Periksa token kiriman form dengan token di session
function cek_csrf()
{
    $kiriman = $_POST['csrf'] ?? '';
    if (!hash_equals(csrf_token(), $kiriman)) { // hash_equals aman dari timing attack
        http_response_code(419);
        die('Token keamanan tidak valid. Silakan kembali dan muat ulang halaman.');
    }
}

// Cek apakah user sudah login
function sudah_login()
{
    global $koneksi;

    if (!isset($_SESSION['login']) || $_SESSION['login'] !== true) {
        return false;
    }

    // Pastikan akun di session benar-benar ada di tabel user, supaya session sisa dari proyek
    // atau database lain tidak dianggap login. Nama dan level juga disegarkan dari database.
    $id       = (int)($_SESSION['id_user'] ?? 0);
    $username = (string)($_SESSION['username'] ?? '');
    $st = $koneksi->prepare('SELECT nama, level FROM `user` WHERE id_user = ? AND username = ?');
    $st->bind_param('is', $id, $username);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();

    if (!$u) {
        $_SESSION = []; // akun tidak ditemukan, anggap belum login
        return false;
    }
    $_SESSION['nama']  = $u['nama'];
    $_SESSION['level'] = (int)$u['level'];
    return true;
}

// PROTEKSI HALAMAN: dipanggil di midlewire.php
function wajib_login()
{
    // Cegah browser menyimpan halaman, jadi tombol Back setelah logout tidak menampilkan data lama
    header('Cache-Control: no-store, no-cache, must-revalidate');

    if (!sudah_login()) {
        redirect('login.php'); // belum login, kembali ke halaman login
    }

    // Logout otomatis jika terlalu lama tidak aktif
    if (isset($_SESSION['terakhir_aktif']) && (time() - $_SESSION['terakhir_aktif']) > BATAS_IDLE) {
        $_SESSION = [];
        session_destroy();
        session_start();
        set_flash('error', 'Sesi berakhir karena tidak aktif, silakan login lagi.');
        redirect('login.php');
    }
    $_SESSION['terakhir_aktif'] = time(); // perbarui waktu aktivitas terakhir
}

// OTORISASI: batasi halaman hanya untuk level tertentu, contoh wajib_level([LEVEL_OWNER])
function wajib_level(array $level_boleh)
{
    if (!in_array((int)($_SESSION['level'] ?? 0), $level_boleh, true)) {
        set_flash('error', 'Anda tidak memiliki hak akses ke halaman tersebut.');
        redirect('index.php');
    }
}

// Cek tanggal format YYYY-MM-DD dan benar-benar ada di kalender
function tanggal_valid($tgl)
{
    $d = DateTime::createFromFormat('Y-m-d', $tgl);
    return $d && $d->format('Y-m-d') === $tgl;
}

// Format angka menjadi rupiah, contoh 147000 menjadi Rp 147.000
function rupiah($angka)
{
    return 'Rp ' . number_format((int)$angka, 0, ',', '.');
}

// Tentukan status pembayaran transaksi dari total tagihan dan total yang sudah dibayar
function status_bayar($total, $dibayar)
{
    if ((int)$total > 0 && (int)$dibayar >= (int)$total) {
        return 'Lunas';
    }
    return ((int)$dibayar > 0) ? 'Sebagian' : 'Belum Bayar';
}

// Daftar transaksi beserta nama pelanggan, jumlah item, dan total yang sudah dibayar (untuk halaman Transaksi)
function ambil_transaksi($koneksi)
{
    $sql = 'SELECT t.id, t.waktu_transaksi, t.keterangan, t.total, t.pelanggan_id,
                   p.nama AS nama_pelanggan,
                   COALESCE(b.dibayar, 0) AS dibayar
            FROM transaksi t
            LEFT JOIN pelanggan p ON p.id = t.pelanggan_id
            LEFT JOIN (SELECT transaksi_id, SUM(total) AS dibayar
                       FROM pembayaran GROUP BY transaksi_id) b ON b.transaksi_id = t.id
            ORDER BY t.waktu_transaksi DESC, t.id DESC';
    return $koneksi->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Ubah tanggal YYYY-MM-DD menjadi format Indonesia, contoh 2026-10-01 menjadi 01 Okt 2026
function tanggal_indo($tgl)
{
    $bulan = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = DateTime::createFromFormat('Y-m-d', $tgl);
    return $d ? $d->format('d') . ' ' . $bulan[(int)$d->format('n')] . ' ' . $d->format('Y') : (string)$tgl;
}

// Validasi rentang tanggal laporan dari URL, hasilnya [mulai, selesai, pesan_error]
// Rentang dianggap diisi jika salah satu atau kedua tanggal ada; keduanya wajib
function baca_rentang()
{
    $mulai   = trim($_GET['tanggal_mulai'] ?? '');
    $selesai = trim($_GET['tanggal_selesai'] ?? '');
    $error   = '';

    if ($mulai === '' && $selesai === '') {
        return ['', '', '']; // belum memfilter, tidak ada pesan
    }
    if ($mulai === '' || $selesai === '') {
        $error = 'Tanggal mulai dan tanggal selesai wajib diisi.';
    } elseif (!tanggal_valid($mulai) || !tanggal_valid($selesai)) {
        $error = 'Format tanggal tidak valid.';
    } elseif ($mulai > $selesai) {
        $error = 'Tanggal mulai tidak boleh melebihi tanggal selesai.';
    }
    return [$mulai, $selesai, $error];
}

// Rekap penjualan per hari pada rentang tanggal, beserta ringkasan total
// Hasil: ['rekap' => [[tgl, total_harian], ...], 'jml_pelanggan' => int, 'total_pendapatan' => int]
function ambil_rekap_harian($koneksi, $mulai, $selesai)
{
    // Agregasi: SUM per hari dengan GROUP BY DATE(waktu_transaksi)
    $st = $koneksi->prepare(
        'SELECT DATE(waktu_transaksi) AS tgl, SUM(total) AS total_harian
         FROM transaksi
         WHERE waktu_transaksi BETWEEN ? AND ?
         GROUP BY DATE(waktu_transaksi)
         ORDER BY tgl ASC'
    );
    $st->bind_param('ss', $mulai, $selesai);
    $st->execute();
    $rekap = $st->get_result()->fetch_all(MYSQLI_ASSOC);

    // Ringkasan: pelanggan unik dengan COUNT(DISTINCT) dan total pendapatan keseluruhan dengan SUM
    $st = $koneksi->prepare(
        'SELECT COUNT(DISTINCT pelanggan_id) AS jml, COALESCE(SUM(total), 0) AS total
         FROM transaksi
         WHERE waktu_transaksi BETWEEN ? AND ?'
    );
    $st->bind_param('ss', $mulai, $selesai);
    $st->execute();
    $r = $st->get_result()->fetch_assoc();

    return [
        'rekap'            => $rekap,
        'jml_pelanggan'    => (int)$r['jml'],
        'total_pendapatan' => (int)$r['total'],
    ];
}
