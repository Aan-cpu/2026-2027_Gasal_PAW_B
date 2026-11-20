<?php
include "koneksi.php";

// Ambil id dari URL (?id=...), (int) memastikan hanya angka
$id = (int) $_GET['id'];

// Jalankan hanya jika tombol Update ditekan
if (isset($_POST['update'])) {
    $nama   = $_POST['nama'];
    $telp   = $_POST['telp'];
    $alamat = $_POST['alamat'];

    // Ubah data yang id-nya sesuai (WHERE id=?)
    $stmt = $conn->prepare("UPDATE supplier SET nama=?, telp=?, alamat=? WHERE id=?");
    $stmt->bind_param("sssi", $nama, $telp, $alamat, $id); // i = integer
    $stmt->execute();

    header("Location: 1.php");
    exit;
}

// Ambil data lama untuk ditampilkan di form
$result = mysqli_query($conn, "SELECT * FROM supplier WHERE id=$id");
$row = mysqli_fetch_assoc($result);

// Kalau id tidak ada di database, kembali ke halaman utama
if (!$row) { header("Location: 1.php"); exit; }
?>
<!DOCTYPE html>
<html>
<head>
  <title>Edit Supplier</title>
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
<h1>Edit Data Master Supplier</h1>

<form method="post">
  <!-- value = isi data lama, supaya form sudah terisi -->
  <div class="baris"><label>Nama</label>
    <input type="text" name="nama" value="<?= htmlspecialchars($row['nama']) ?>" required></div>
  <div class="baris"><label>Telp</label>
    <input type="text" name="telp" value="<?= htmlspecialchars($row['telp']) ?>" required></div>
  <div class="baris"><label>Alamat</label>
    <input type="text" name="alamat" value="<?= htmlspecialchars($row['alamat']) ?>" required></div>

  <div class="tombol">
    <button type="submit" name="update" class="btn hijau">Update</button>
    <a href="1.php" class="btn merah">Batal</a>
  </div>
</form>
</body>
</html>