<?php
/*LANGKAH 1: satpam yang hanya menjawab benar / salah

function validateName($field_list, $field_name)
{
    // Kalau kolom surname tidak ada sama sekali -> tidak lolos
    if (!isset($field_list[$field_name]))
        return false;

    // Aturan satpam: hanya boleh huruf (a-z, A-Z), tanda petik ('), dan minus (-)
    // Tanda + artinya minimal 1 karakter
    $pattern = "/^[a-zA-Z'-]+$/";

    // Cek isian dengan aturan di atas. Kalau tidak cocok (misal ada angka) -> tidak lolos
    if (!preg_match($pattern, $field_list[$field_name]))
        return false;

    // Semua pengecekan lolos -> boleh masuk
    return true;
}
*/

//LANGKAH 4: satpam yang mencatat alasan di buku catatan ($errors)
// $errors      = buku catatan (tanda & artinya buku dipinjamkan, jadi tulisannya
//                tetap ada setelah fungsi selesai dan bisa dibaca oleh 2.php)
// $field_list  = data dari form ($_POST)
// $field_name  = nama kolom yang diperiksa, misalnya 'surname'
function validateName(&$errors, $field_list, $field_name)
{
    // Aturan satpam: hanya huruf, tanda petik ('), dan minus (-), minimal 1 karakter
    $pattern = "/^[a-zA-Z'-]+$/";

    // Kalau kolom kosong -> tulis di buku: 'required' (wajib diisi)
    if (!isset($field_list[$field_name]) || empty($field_list[$field_name]))
        $errors[$field_name] = 'required';

    // Kalau ada isinya tapi tidak sesuai aturan -> tulis di buku: 'invalid' (tidak sesuai)
    else if (!preg_match($pattern, $field_list[$field_name]))
        $errors[$field_name] = 'invalid';

    // Kalau semuanya benar -> buku tidak ditulis apa-apa (tetap kosong)
}
?>