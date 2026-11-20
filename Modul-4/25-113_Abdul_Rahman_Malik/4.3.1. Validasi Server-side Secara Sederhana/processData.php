<?php
// 2.php

// ambil fungsi dari validate.inc
require 'validate.inc';

// array kosong buat nampung error
$errors = array();

// cek surname dari data form
validateName($errors, $_POST, 'surname');

// kalau $errors ada isinya berarti ada yang salah
if ($errors)
{
    echo 'Errors:<br/>';

    // tampilin errornya satu-satu, contoh: surname required
    foreach ($errors as $field => $error)
        echo "$field $error</br>";
}
else
    // kalau kosong berarti datanya benar
    echo 'Data OK!';
?>