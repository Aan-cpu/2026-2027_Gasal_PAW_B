<?php
// validate.inc
// isinya fungsi-fungsi buat ngecek isian form
// semua pakai &$errors biar error yang dicatat di sini kebawa keluar fungsi

// cek surname (dari 4.3.1)
function validateName(&$errors, $field_list, $field_name)
{
    // cuma boleh huruf, tanda ' dan tanda -
    $pattern = "/^[a-zA-Z'-]+$/";

    // kalau kosong, errornya 'required'
    if (!isset($field_list[$field_name]) || empty($field_list[$field_name]))
        $errors[$field_name] = 'required';
    // kalau ada isinya tapi gak sesuai pola, errornya 'invalid'
    else if (!preg_match($pattern, $field_list[$field_name]))
        $errors[$field_name] = 'invalid';
}

//langkah 9: validasi buat field yang lain

// email: harus diisi dan formatnya harus bener (ada @ dan domain)
function validateEmail(&$errors, $field_list, $field_name)
{
    if (!isset($field_list[$field_name]) || empty($field_list[$field_name]))
        $errors[$field_name] = 'required';
    // filter_var ngecek format email
    else if (!filter_var($field_list[$field_name], FILTER_VALIDATE_EMAIL))
        $errors[$field_name] = 'invalid';
}

// password: harus diisi dan minimal 6 karakter
function validatePassword(&$errors, $field_list, $field_name)
{
    if (!isset($field_list[$field_name]) || empty($field_list[$field_name]))
        $errors[$field_name] = 'required';
    // strlen ngitung jumlah karakter
    else if (strlen($field_list[$field_name]) < 6)
        $errors[$field_name] = 'invalid (minimal 6 karakter)';
}

// street address: harus diisi
function validateAddress(&$errors, $field_list, $field_name)
{
    // trim buang spasi di awal/akhir, biar isian spasi doang tetap dianggap kosong
    if (!isset($field_list[$field_name]) || trim($field_list[$field_name]) == '')
        $errors[$field_name] = 'required';
}

// state: harus milih salah satu, gak boleh tetap di [choose your state] (-1)
function validateState(&$errors, $field_list, $field_name)
{
    if (!isset($field_list[$field_name]) || $field_list[$field_name] == '-1')
        $errors[$field_name] = 'required';
}

// gender: radio button harus dipilih salah satu
function validateGender(&$errors, $field_list, $field_name)
{
    // radio yang gak dipilih itu gak dikirim sama sekali, jadi cukup cek isset
    if (!isset($field_list[$field_name]))
        $errors[$field_name] = 'required';
}
?>