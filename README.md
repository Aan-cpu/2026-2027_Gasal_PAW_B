# Modul 8: Session dan Security

Aplikasi Sistem Proyek dengan login, session, proteksi halaman, hak akses berdasarkan level, dan logout.

## Cara menjalankan

1. Salin folder ini ke htdocs (XAMPP), misalnya htdocs/Modul-8.
2. Jalankan Apache dan MySQL, lalu import db.sql lewat phpMyAdmin.
3. Buka http://localhost/Modul-8/login.php

## Akun contoh

Owner (level 1): username owner, password owner123
Kasir (level 2): username kasir, password kasir123

## Hak akses

Owner: Home, Data Master (Data Proyek, Data Tugas, Data User), Transaksi, Laporan.
Kasir: Home, Transaksi, Laporan.

## Daftar file

koneksi.php: koneksi database
fungsi.php: session, helper keamanan (escape, CSRF, cek level), fungsi laporan
midlewire.php: proteksi halaman, wajib login
login.php dan proses_login.php: form dan proses login, set session
logout.php: unset dan destroy session
header.php, footer.php, style.css: navbar sesuai level dan tampilan
index.php: Home
proyek*.php, tugas*.php, user*.php: CRUD Data Master (khusus owner)
transaksi.php: tambah tugas ke proyek dan ubah status proyek
laporan.php: laporan dengan filter deadline dan status
