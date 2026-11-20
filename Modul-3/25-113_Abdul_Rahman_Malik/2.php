<?php
//2.1
$fruits = array("Avocado", "Blueberry", "Cherry");

// tambah lima data baru dengan perulangan for
for ($i = 1; $i <= 5; $i++) {
    $fruits[] = "Buah Tambahan " . $i;
}

$arrlength = count($fruits);

echo "Panjang array saat ini: " . $arrlength . "<br><br>";

for ($x = 0; $x < $arrlength; $x++) {
    echo $fruits[$x];
    echo "<br>";
}

echo "<br>";
echo "<b>Alasan:</b> Perulangan for pada baris #5-#8 tidak perlu diubah, karena batas perulangannya memakai count(\$fruits) jadi akan di hitung otomatis";

echo "<br><br>";

//2.2
$vegies = array("Carrot", "Broccoli", "Spinach");
$arrlength = count($vegies);

for ($x = 0; $x < $arrlength; $x++) {
    echo $vegies[$x];
    echo "<br>";
}

echo "<br>";
echo "<b>Alasan:</b>Cukup memodifikasi skrip yang sudah ada, karena tinggal mengganti nama arraynya saja dan perulangannya tetap sama.";
?>