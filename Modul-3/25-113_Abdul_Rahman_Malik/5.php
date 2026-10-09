<?php
echo '<p><b>5.1 Buat array multidimensi yang menyimpan data pada tabel di atas, kemudian tambah lima data yang lain, lalu tampilkan seluruhnya dalam bentuk tabel.</b></p>';

// data awal
$students = array(
    array("Alex", "220401", "0812345678"),
    array("Bianca", "220402", "0812345687"),
    array("Candice", "220403", "0812345665")
);

echo "Data awal:<br>";
echo "students = (<br>";
foreach ($students as $i => $baris) {
    echo '("' . $baris[0] . '", "' . $baris[1] . '", "' . $baris[2] . '")';
    if ($i < count($students) - 1) echo ",";
    echo "<br>";
}
echo ")<br><br>";

// tambah lima data lain
$students[] = array("Daniel", "220404", "0812345611");
$students[] = array("Elena", "220405", "0812345622");
$students[] = array("Fiona", "220406", "0812345633");
$students[] = array("Gabe", "220407", "0812345644");
$students[] = array("Hannah", "220408", "0812345655");

echo "Data setelah ditambah 5 data lain:<br>";
echo "students = (<br>";
foreach ($students as $i => $baris) {
    echo '("' . $baris[0] . '", "' . $baris[1] . '", "' . $baris[2] . '")';
    if ($i < count($students) - 1) echo ",";
    echo "<br>";
}
echo ")<br><br>";

// tabel tanpa warna
echo '<table border="1" cellpadding="4" cellspacing="0" style="border-collapse: collapse;">';
echo '<tr><th>Name</th><th>NIM</th><th>Mobile</th></tr>';
foreach ($students as $baris) {
    echo '<tr>';
    foreach ($baris as $kolom) {
        echo '<td>' . $kolom . '</td>';
    }
    echo '</tr>';
}
echo '</table>';
?>