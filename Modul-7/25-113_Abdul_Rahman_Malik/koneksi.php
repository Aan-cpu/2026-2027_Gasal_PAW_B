<?php
// FILE KONEKSI DATABASE & FUNGSI BANTU
// File ini dipanggil di setiap halaman dengan: require 'koneksi.php';
// PENTING: jangan ada spasi/teks apa pun sebelum tag <?php di atas,
// karena export_excel.php memakai header() yang harus dikirim sebelum ada output.

session_start(); // aktifkan session, dipakai untuk menyimpan pesan notifikasi (flash)

// Buat koneksi ke MySQL: host, username, password, nama database
// Sesuaikan dengan pengaturan di komputer Anda (XAMPP biasanya root & password kosong)
$koneksi = new mysqli('localhost', 'root', '', 'penjualan');

// Jika koneksi gagal, hentikan program dan tampilkan pesan error
if ($koneksi->connect_error) {
    die('Koneksi database gagal: ' . $koneksi->connect_error);
}
$koneksi->set_charset('utf8mb4'); // supaya karakter khusus tampil dengan benar

// Fungsi e(): mengamankan teks sebelum dicetak ke HTML (anti XSS)
// Contoh: <script> diubah menjadi &lt;script&gt; sehingga tidak dijalankan browser
function e($teks) {
    return htmlspecialchars((string)$teks, ENT_QUOTES, 'UTF-8');
}

// Fungsi rupiah(): mengubah angka 169000 menjadi "Rp. 169.000"
// number_format(angka, jumlah desimal, pemisah desimal, pemisah ribuan)
function rupiah($angka) {
    return 'Rp. ' . number_format((int)$angka, 0, ',', '.');
}

// Fungsi flash(): simpan pesan sementara di session, tampil sekali lalu hilang
// $jenis: 'ok' untuk sukses, 'err' untuk error
function flash($jenis, $pesan) {
    $_SESSION['flash'] = ['jenis' => $jenis, 'pesan' => $pesan];
}

// Fungsi tampil_flash(): tampilkan pesan dari flash() lalu hapus dari session
function tampil_flash() {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        echo '<div class="alert ' . e($f['jenis']) . '">' . e($f['pesan']) . '</div>';
        unset($_SESSION['flash']); // hapus agar tidak muncul lagi saat refresh
    }
}

// Fungsi header_html(): mencetak bagian atas halaman (head + CSS + pembuka body)
function header_html($judul) {
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= e($judul) ?></title>
  <style>
    body  { font-family: Arial, sans-serif; background: #f4f6f8; margin: 0; padding: 20px; color: #222; }
    .card { background: #fff; padding: 20px; margin: 0 auto 20px; max-width: 900px;
            border-radius: 6px; box-shadow: 0 1px 4px rgba(0,0,0,.15); }
    h1, h2 { margin-top: 0; }

    /* Tabel: garis menyatu, lebar penuh */
    table { border-collapse: collapse; width: 100%; margin-top: 10px; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #d9edf7; }
    .r { text-align: right; } /* class untuk rata kanan (angka) */

    /* Tombol umum */
    .btn { display: inline-block; background: #1a73e8; color: #fff; border: 0;
           padding: 8px 14px; border-radius: 4px; text-decoration: none; cursor: pointer; font-size: 14px; }
    .btn-oranye { background: #e8590c; }

    /* Notifikasi */
    .alert { max-width: 900px; margin: 0 auto 15px; padding: 10px; border-radius: 4px; }
    .alert.ok  { background: #d4edda; }
    .alert.err { background: #f8d7da; }

    /* ATURAN CETAK: hanya berlaku saat window.print() / Save as PDF */
    @media print {
      .no-print { display: none !important; } /* sembunyikan tombol & form filter */
      body  { background: #fff; padding: 0; }  /* latar putih agar hemat tinta */
      .card { box-shadow: none; max-width: 100%; padding: 0; } /* hilangkan bayangan kartu */
    }
  </style>
</head>
<body>
<?php
}

// Fungsi footer_html(): menutup halaman
function footer_html() {
    echo "</body>\n</html>";
}
