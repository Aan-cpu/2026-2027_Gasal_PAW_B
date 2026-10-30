<?php
include "koneksi.php";

// Jalankan hanya jika tombol Simpan ditekan
if (isset($_POST['simpan'])) {
    // Ambil data dari form
    $nama   = $_POST['nama'];
    $telp   = $_POST['telp'];
    $alamat = $_POST['alamat'];

    // Prepared statement: tanda ? diisi aman (anti SQL injection)
    $stmt = $conn->prepare("INSERT INTO supplier (nama, telp, alamat) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nama, $telp, $alamat); // sss = 3 data bertipe string
    $stmt->execute();

    // Setelah simpan, kembali ke halaman utama (1.php)
    header("Location: 1.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Tambah Supplier</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px auto; width: 90%; max-width: 900px; color: #333; }
    h1 { color: #337ab7; font-weight: normal; border-bottom: 1px solid #eee; padding-bottom: 12px; }
    .btn { display: inline-block; padding: 6px 12px; color: #fff; border: 0; border-radius: 3px;
           text-decoration: none; font-size: 13px; cursor: pointer; }
    .hijau  { background: #5cb85c; }
    .merah  { background: #d9232d; }
    form .baris { display: flex; margin-bottom: 14px; align-items: center; }
    form label { width: 100px; font-weight: bold; text-align: right; padding-right: 15px; }
    form input { padding: 8px; border: 1px solid #ccc; border-radius: 3px; width: 300px; }
    .tombol { margin-left: 115px; }
  </style>
</head>
<body>
<h1>Tambah Data Master Supplier Baru</h1>

<!-- method POST: data dikirim ke file ini sendiri -->
<form method="post">
  <div class="baris"><label>Nama</label>
    <input type="text" name="nama" placeholder="Nama" required></div>
  <div class="baris"><label>Telp</label>
    <input type="text" name="telp" placeholder="telp" required></div>
  <div class="baris"><label>Alamat</label>
    <input type="text" name="alamat" placeholder="alamat" required></div>

  <div class="tombol">
    <button type="submit" name="simpan" class="btn hijau">Simpan</button>
    <a href="1.php" class="btn merah">Batal</a>
  </div>
</form>
</body>
</html>