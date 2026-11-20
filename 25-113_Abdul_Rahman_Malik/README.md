# Modul 8: Session dan Security

Aplikasi Sistem Penjualan dengan login, session, proteksi halaman, hak akses berdasarkan level, dan logout. Database memakai `penjualan` (tabel supplier, barang, pelanggan, transaksi, transaksi_detail, pembayaran, user).

## Cara menjalankan

1. Salin folder ini ke htdocs (XAMPP), misalnya htdocs/Modul-8.
2. Jalankan Apache dan MySQL, lalu import db.sql lewat phpMyAdmin (database lama bernama penjualan akan diganti).
3. Buka http://localhost/Modul-8/login.php

## Akun contoh

Owner (level 1): admin / admin123, owner / owner123
Kasir (level 2): kasir1 / kasir123, kasir2 / kasir123, gudang1 / gudang123

## Hak akses

Owner: Home, Data Master (Barang, Supplier, Pelanggan, User), Transaksi, Laporan.
Kasir: Home, Transaksi, Laporan.

## Daftar file

koneksi.php: koneksi database penjualan
fungsi.php: session, helper keamanan (escape, CSRF, cek level), rupiah, fungsi laporan
midlewire.php: proteksi halaman, wajib login
login.php: form dan proses login, set session
logout.php: unset dan destroy session
header.php, footer.php, style.css: navbar sesuai level dan tampilan
index.php: Home (ringkasan dan stok menipis)
supplier.php, barang.php, pelanggan.php, user.php: CRUD Data Master (khusus owner), masing-masing satu file berisi daftar, form tambah/edit (?aksi=tambah atau ?aksi=edit&id=...), dan hapus
transaksi.php: buat transaksi penjualan (stok otomatis berkurang), daftar transaksi, dan detail transaksi dengan pembayaran (?id=...)
laporan.php: laporan penjualan (gabungan Modul 7): filter tanggal_mulai dan tanggal_selesai, grafik batang Chart.js per hari, tabel rekap harian, ringkasan jumlah pelanggan unik dan total pendapatan, tombol Cetak (window.print dan CSS @media print)
export_excel.php: ekspor rekap penjualan ke file .xls lewat header Content-Type dan Content-Disposition

## Perubahan dari versi sebelumnya

db.sql diganti dengan database penjualan. Kolom password tabel user diperbesar menjadi 255 karakter dan password disimpan sebagai hash bcrypt (sebelumnya MD5 dengan 35 karakter), karena login memakai password_verify().

Grafik memakai Chart.js dari CDN, jadi halaman Laporan butuh koneksi internet.
