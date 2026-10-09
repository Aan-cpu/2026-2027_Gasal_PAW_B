<?php
// ===== 4.1 =====
echo '<p><b>4.1 Tambahkan lima data baru ke dalam array $height, lalu tampilkan seluruh datanya dengan perulangan!</b></p>';

$height = array("Andy"=>"176", "Barry"=>"165", "Charlie"=>"170");

$height["David"] = "180";
$height["Ethan"] = "172";
$height["Frank"] = "168";
$height["George"] = "175";
$height["Harry"] = "182";

// tampilkan seluruh isi array
echo "height = ( ";
foreach ($height as $nama => $tinggi) {
    echo '"' . $nama . '"=>"' . $tinggi . '" ';
}
echo ")<br><br>";

// tampilkan data dengan perulangan
foreach ($height as $nama => $tinggi) {
    echo $nama . " is " . $tinggi . " cm tall.<br>";
}

echo "<br>";

// ===== 4.2 =====
echo '<p><b>4.2 Buat array baru dengan nama $weight yang memiliki tiga buah data! Tampilkan seluruh data dari array $weight dengan menggunakan struktur perulangan FOR!</b></p>';

$weight = array("Andy"=>"70", "Barry"=>"65", "Charlie"=>"75");

echo "weight = ( ";
foreach ($weight as $nama => $berat) {
    echo '"' . $nama . '"=>"' . $berat . '" ';
}
echo ")<br><br>";

foreach ($weight as $nama => $berat) {
    echo $nama . " is " . $berat . " kg.<br>";
}
?>