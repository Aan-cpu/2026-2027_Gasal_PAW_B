<?php
// FILE KONEKSI & FUNGSI BANTU
// File ini di-include oleh semua halaman lain (require 'koneksi.php')

// Mulai session, dipakai untuk menyimpan notifikasi (flash message) antar halaman
session_start();

// Supaya error MySQL dilempar sebagai exception (bisa ditangkap dengan try-catch)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Buat koneksi ke MySQL: host, username, password, nama database
// (XAMPP default: user root dan password kosong)
$koneksi = new mysqli('localhost', 'root', '', 'data_penjualan');

// Set karakter UTF-8 agar huruf/simbol tampil benar
$koneksi->set_charset('utf8mb4');

// Fungsi e(): mengamankan teks sebelum ditampilkan (anti XSS)
// Contoh: <script> diubah menjadi teks biasa, tidak dijalankan browser
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

// Fungsi rupiah(): format angka jadi "Rp 1.000.000"
function rupiah($n) { return 'Rp ' . number_format((int)$n, 0, ',', '.'); }

// Fungsi flash(): simpan pesan ke session
// $tipe = 'ok' (hijau, berhasil) atau 'err' (merah, gagal)
function flash($tipe, $pesan) { $_SESSION['flash'] = [$tipe, $pesan]; }

// Fungsi tampil_flash(): tampilkan pesan yang tersimpan, lalu hapus
// supaya pesan hanya muncul satu kali
function tampil_flash() {
    if (!empty($_SESSION['flash'])) {
        [$tipe, $pesan] = $_SESSION['flash']; // pecah array jadi 2 variabel
        unset($_SESSION['flash']);            // hapus pesan dari session
        echo '<div class="alert ' . e($tipe) . '">' . e($pesan) . '</div>';
    }
}

// Fungsi header_html(): mencetak bagian atas halaman (head, CSS, menu)
// supaya tidak perlu menulis ulang di setiap file
function header_html($judul) { ?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($judul) ?></title>
<style>
 /* tampilan dasar halaman */
 body{font-family:Segoe UI,Arial,sans-serif;margin:0;background:#f4f6f9;color:#222}
 /* menu navigasi atas */
 nav{background:#1f4e79;padding:12px 24px}
 nav a{color:#fff;text-decoration:none;margin-right:20px;font-weight:600}
 /* pembungkus isi halaman, lebar maksimal 960px dan di tengah */
 .wrap{max-width:960px;margin:24px auto;padding:0 16px}
 /* kotak putih untuk setiap bagian (master, detail, form) */
 .card{background:#fff;border-radius:8px;padding:20px;margin-bottom:20px;box-shadow:0 1px 4px rgba(0,0,0,.1)}
 h1,h2{margin-top:0}
 /* tabel */
 table{width:100%;border-collapse:collapse}
 th,td{padding:8px 10px;border-bottom:1px solid #e3e3e3;text-align:left}
 th{background:#eef2f7}
 td.r,th.r{text-align:right} /* kelas "r" = rata kanan (untuk angka) */
 /* input form */
 input,select,textarea{padding:7px;border:1px solid #bbb;border-radius:4px;font:inherit}
 label{display:block;margin:10px 0 4px;font-weight:600}
 /* tombol: biru (default), merah, abu-abu */
 .btn{display:inline-block;padding:7px 14px;border:0;border-radius:4px;background:#1f4e79;color:#fff;cursor:pointer;text-decoration:none;font:inherit}
 .btn.red{background:#c0392b}.btn.gray{background:#777}
 /* kotak notifikasi: hijau = berhasil, merah = gagal */
 .alert{padding:12px 16px;border-radius:6px;margin-bottom:16px}
 .alert.ok{background:#d4edda;color:#155724}.alert.err{background:#f8d7da;color:#721c24}
 /* tampilan data master (label di kiri, isi di kanan) */
 .meta{display:grid;grid-template-columns:160px 1fr;gap:6px}
 .meta b{color:#555}
</style></head><body>
<!-- menu navigasi -->
<nav><a href="index.php">Transaksi</a><a href="barang.php">Barang</a></nav>
<div class="wrap">
<?php }

// Fungsi footer_html(): menutup tag yang dibuka di header_html()
function footer_html() { echo '</div></body></html>'; }
