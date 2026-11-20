<?php
// KONEKSI DATABASE
// File ini di-require oleh fungsi.php, jadi semua halaman otomatis terhubung ke database

$host     = 'localhost';        // alamat server MySQL
$username = 'root';             // user bawaan XAMPP
$password = '';                 // password bawaan XAMPP kosong
$dbname   = 'penjualan';       // nama database dari db.sql

// Jika query error, mysqli akan melempar exception (bukan diam-diam gagal)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $koneksi = new mysqli($host, $username, $password, $dbname);
    $koneksi->set_charset('utf8mb4'); // supaya huruf dan simbol tampil benar
} catch (mysqli_sql_exception $ex) {
    // Pesan dibuat singkat, detail error asli tidak ditampilkan ke pengunjung
    die('Gagal koneksi ke database.');
}
