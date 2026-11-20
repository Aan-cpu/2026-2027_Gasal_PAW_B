<?php
include "koneksi.php";

// Ambil id dari URL
$id = (int) $_GET['id'];

// Hapus data sesuai id
// (popup "Anda yakin...?" sudah ada di 1.php lewat confirm())
$stmt = $conn->prepare("DELETE FROM supplier WHERE id=?");
$stmt->bind_param("i", $id);
$stmt->execute();

// Kembali ke halaman utama (1.php)
header("Location: 1.php");
exit;
?>