<?php
// ===== 3.1 =====
echo '<p><b>3.1 Tambahkan lima data baru ke dalam array $height, lalu tampilkan nilai dengan indeks terakhir! Setelah itu, hapus satu data tertentu dan tampilkan kembali nilai dengan indeks terakhir!</b></p>';

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
    $terakhir = $tinggi; // nilai terakhir tersimpan di sini
}
echo ")<br>";
echo "Nilai dengan indeks terakhir: " . $terakhir;

echo "<br><br>";

// hapus satu data (Barry)
unset($height["Barry"]);

echo "height = ( ";
foreach ($height as $nama => $tinggi) {
    echo '"' . $nama . '"=>"' . $tinggi . '" ';
    $terakhir = $tinggi;
}
echo ")<br>";
echo "Nilai dengan indeks terakhir setelah dihapus: " . $terakhir;

echo "<br><br>";

// ===== 3.2 =====
echo '<p><b>3.2 Buat array baru bernama $weight yang memiliki tiga data, lalu tampilkan data kedua dari array ini!</b></p>';

$weight = array("Andy"=>"70", "Barry"=>"65", "Charlie"=>"75");

echo "weight = ( ";
foreach ($weight as $nama => $berat) {
    echo '"' . $nama . '"=>"' . $berat . '" ';
}
echo ")<br>";
echo "Data kedua: " . $weight["Barry"];
?>