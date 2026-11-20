<?php
// Data untuk konek ke MySQL (default XAMPP)
$hostname = "localhost";
$username = "root";
$password = "";
$dbname   = "modul5";

// 1. Konek ke MySQL dulu (belum pilih database)
$conn = mysqli_connect($hostname, $username, $password);
if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}

// 2. Buat database kalau belum ada, lalu pakai database itu
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $dbname");
mysqli_select_db($conn, $dbname);

// 3. Buat tabel supplier kalau belum ada
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS supplier (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    telp VARCHAR(20) NOT NULL,
    alamat VARCHAR(255) NOT NULL
)");

// 4. Isi data awal, hanya kalau tabel masih kosong
$cek = mysqli_query($conn, "SELECT id FROM supplier LIMIT 1");
if (mysqli_num_rows($cek) == 0) {
    mysqli_query($conn, "INSERT INTO supplier (nama, telp, alamat) VALUES
        ('PT. Maju Bersama', '08123456', 'Surabaya'),
        ('PT. Senang Sekali', '081515563', 'Bangkalan'),
        ('PT. Segar Segar', '0845454663', 'Surabaya')");
}
?>