<?php
require_once "../koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=riwayat.xls");

$query = mysqli_query($koneksi, "SELECT * FROM form_izin_pekerjaan");
if (!$query) {
    die('Query gagal: ' . mysqli_error($koneksi));
}

echo "<table border='1'>";
echo "<tr><th>Jenis</th><th>Lokasi</th><th>Status</th></tr>";

while($row = mysqli_fetch_assoc($query)){
    echo '<tr>';
    echo '<td>' . htmlspecialchars($row['jenis_pekerjaan']) . '</td>';
    echo '<td>' . htmlspecialchars($row['lokasi']) . '</td>';
    echo '<td>' . htmlspecialchars($row['status']) . '</td>';
    echo '</tr>';
}
echo '</table>';
?>