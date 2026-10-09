<?php
$fruits = array("Avocado", "Blueberry", "Cherry");

// 1.1 Tambah lima data baru
echo '<p><b>1.1 Menambahkan lima data baru dalam array $fruits! Tampilkan nilai dengan indeks tertinggi dari array $fruits!</b></p>';

$fruits[] = "Durian";
$fruits[] = "Elderberry";
$fruits[] = "Fig";
$fruits[] = "Grape";
$fruits[] = "Honeydew";

echo "fruits = ( ";
foreach ($fruits as $buah) {
    echo '"' . $buah . '" ';
}
echo ")<br>";
echo "Nilai dengan indeks tertinggi: " . $fruits[count($fruits) - 1];

echo "<br><br>";

// 1.2 Hapus satu data (Blueberry)
echo '<p><b>1.2 Hapus satu data tertentu dari array $fruits! Tampilkan nilai dengan indeks tertinggi dari array $fruits!</b></p>';

unset($fruits[1]);

echo "Data Blueberry dihapus.<br>";
echo "fruits = ( ";
foreach ($fruits as $buah) {
    echo '"' . $buah . '" ';
}
echo ")<br>";
echo "Nilai dengan indeks tertinggi: " . $fruits[count($fruits)];
?>