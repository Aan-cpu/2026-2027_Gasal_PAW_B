<?php
// processData_form.php
// form dan proses datanya jadi satu file (self-submission)
//
// alurnya:
// - belum ada isian        -> tampilin form kosong
// - ada isian tapi error   -> tampilin pesan error + form (isiannya masih ada)
// - ada isian dan benar    -> tampilin pesan berhasil

// array buat nampung error
$errors = array();

// kalau 'surname' ada di $_POST berarti tombol submit udah ditekan
if (isset($_POST['surname']))
{
    require 'validate.inc';

    // cek semua field
    validateName($errors, $_POST, 'surname');
    // langkah 9: field lainnya
    validateEmail($errors, $_POST, 'email');
    validatePassword($errors, $_POST, 'passwd');
    validateAddress($errors, $_POST, 'address');
    validateState($errors, $_POST, 'state');
    validateGender($errors, $_POST, 'sex');

    // kalau $errors ada isinya berarti ada yang salah
    if ($errors)
    {
        echo '<h1>Invalid, correct the following errors:</h1>';
        // tampilin errornya satu-satu, contoh: surname required
        foreach ($errors as $field => $error)
            echo "$field $error</br>";

        // tampilin form lagi (isiannya masih ada, lihat form.inc)
        include 'form.inc';
    }
    else
    {
        // gak ada error, berarti semua benar
        echo 'Form submitted successfully with no errors';
    }
}
else
    // belum ada isian, tampilin form kosong
    include 'form.inc';
?>