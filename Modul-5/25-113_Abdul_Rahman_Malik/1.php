<?php include "koneksi.php"; ?>
<!DOCTYPE html>
<html>
<head>
  <title>Data Master Supplier</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px auto; width: 90%; max-width: 900px; color: #333; }
    h1 { color: #337ab7; font-weight: normal; border-bottom: 1px solid #eee; padding-bottom: 12px; }
    table { width: 100%; border-collapse: collapse; font-size: 14px; }
    th { background: #d9edf7; text-align: left; }
    th, td { padding: 10px; border: 1px solid #ddd; }
    .btn { display: inline-block; padding: 6px 12px; color: #fff; border: 0; border-radius: 3px;
           text-decoration: none; font-size: 13px; cursor: pointer; }
    .hijau  { background: #5cb85c; }
    .oranye { background: #e8620c; }
    .merah  { background: #d9232d; }
    .atas   { display: flex; justify-content: space-between; align-items: center; }
  </style>
</head>
<body>

<div class="atas">
  <h1>Data Master Supplier</h1>
  <!-- Tombol menuju halaman tambah data (2.php) -->
  <a href="2.php" class="btn hijau">Tambah Data</a>
</div>

<table>
  <tr>
    <th>No</th><th>Nama</th><th>Telp</th><th>Alamat</th><th>Tindakan</th>
  </tr>

  <?php
  // Ambil semua data dari tabel supplier
  $result = mysqli_query($conn, "SELECT * FROM supplier");
  $no = 1;

  // Ulangi untuk setiap baris data
  while ($row = mysqli_fetch_assoc($result)) {
  ?>
    <tr>
      <td><?= $no++ ?></td>
      <!-- htmlspecialchars = mencegah kode berbahaya (XSS) -->
      <td><?= htmlspecialchars($row['nama']) ?></td>
      <td><?= htmlspecialchars($row['telp']) ?></td>
      <td><?= htmlspecialchars($row['alamat']) ?></td>
      <td>
        <!-- Edit: menuju 3.php, kirim id lewat URL -->
        <a href="3.php?id=<?= $row['id'] ?>" class="btn oranye">Edit</a>

        <!-- Hapus: menuju 4.php. confirm() memunculkan popup OK / Cancel -->
        <a href="4.php?id=<?= $row['id'] ?>" class="btn merah"
           onclick="return confirm('Anda yakin akan menghapus supplier ini?')">Hapus</a>
      </td>
    </tr>
  <?php } ?>
</table>

</body>
</html>