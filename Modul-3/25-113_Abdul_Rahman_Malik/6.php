<?php
echo '<p><b>6.1 Implementasikan fungsi array_push(), array_merge(), array_values(), array_search(), array_filter(), serta fungsi sorting masing-masing jenis array, lalu tampilkan hasil dari masing-masing fungsi.</b></p>';

// ===== array_push =====
$a = array("A");
echo 'Array awal: ("A")<br>';
array_push($a, "B");
echo "Hasil array_push: ";
foreach ($a as $v) {
    echo $v . " ";
}
echo "<br><br>";

// ===== array_merge =====
$a1 = array("A", "B");
$a2 = array("C");
echo 'Array awal: ("A", "B") digabung dengan ("C")<br>';
$gabung = array_merge($a1, $a2);
echo "Hasil array_merge: ";
foreach ($gabung as $v) {
    echo $v . " ";
}
echo "<br><br>";

// ===== array_values =====
$b = array("x" => 1, "y" => 2);
echo 'Array awal: ("x" => 1, "y" => 2)<br>';
$nilai = array_values($b);
echo "Hasil array_values: ";
foreach ($nilai as $v) {
    echo $v . " ";
}
echo "<br><br>";

// ===== array_search =====
$c = array("A", "B", "C");
echo 'Mencari "B" pada array: ("A", "B", "C")<br>';
echo "Hasil array_search: " . array_search("B", $c);
echo "<br><br>";

// ===== array_filter =====
$d = array(0, 1, false, 2, "", 3, "array");
echo 'Array awal: (0, 1, false, 2, "", 3, "array")<br>';
$saring = array_filter($d);
echo "Hasil array_filter: ";
foreach ($saring as $v) {
    echo $v . " ";
}
echo "<br><br>";

// ===== sort & rsort (array terindeks) =====
$e = array(3, 1, 2);
echo "Array awal: (3, 1, 2)<br>";
sort($e);
echo "Hasil sort: ";
foreach ($e as $v) {
    echo $v . " ";
}
echo "<br>";
rsort($e);
echo "Hasil rsort: ";
foreach ($e as $v) {
    echo $v . " ";
}
echo "<br><br>";

// ===== sorting array asosiatif =====
echo 'Array awal: ("Peter"=>35, "Ben"=>37, "Joe"=>43)<br>';

$f = array("Peter" => 35, "Ben" => 37, "Joe" => 43);
asort($f);
echo "Hasil asort: ";
foreach ($f as $k => $v) {
    echo $k . "=> " . $v . ", ";
}
echo "<br>";

$f = array("Peter" => 35, "Ben" => 37, "Joe" => 43);
ksort($f);
echo "Hasil ksort: ";
foreach ($f as $k => $v) {
    echo $k . "=> " . $v . ", ";
}
echo "<br>";

$f = array("Peter" => 35, "Ben" => 37, "Joe" => 43);
arsort($f);
echo "Hasil arsort: ";
foreach ($f as $k => $v) {
    echo $k . "=> " . $v . ", ";
}
echo "<br>";

$f = array("Peter" => 35, "Ben" => 37, "Joe" => 43);
krsort($f);
echo "Hasil krsort: ";
foreach ($f as $k => $v) {
    echo $k . "=> " . $v . ", ";
}
?>