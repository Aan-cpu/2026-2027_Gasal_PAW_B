<?php
// MIDDLEWARE PROTEKSI HALAMAN
// Cukup tulis require 'midlewire.php'; di baris atas setiap halaman yang harus login dulu
require_once __DIR__ . '/fungsi.php';

wajib_login(); // belum login akan dilempar ke login.php, sudah login lanjut
