<?php
$koneksi = null;
require_once "../koneksi.php";
if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Verifikasi Izin</title>
    <link rel="stylesheet" href="../styling/css/dashboard.css">
</head>
<body>

<div class="content">
    <h2>Verifikasi Izin Pekerjaan</h2>

    <table>
        <tr>
            <th>Nama Pekerjaan</th>
            <th>Lokasi</th>
            <th>Tanggal</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        <?php
        $data = mysqli_query($koneksi, "SELECT * FROM izin_pekerjaan");
        if ($data) {
            while($d = mysqli_fetch_array($data)){
        ?>
        <tr>
            <td><?= $d['nama_pekerjaan']; ?></td>
            <td><?= $d['lokasi']; ?></td>
            <td><?= $d['tanggal']; ?></td>
            <td><?= $d['status']; ?></td>
            <td>
                <a class="btn-approve" href="proses_verifikasi.php?id=<?= $d['id_izin']; ?>&aksi=Disetujui">Setujui</a>
                <a class="btn-reject" href="proses_verifikasi.php?id=<?= $d['id_izin']; ?>&aksi=Ditolak">Tolak</a>
            </td>
        </tr>
        <?php }
        } else {
            echo '<tr><td colspan="5" style="text-align:center; padding:20px; color:#ccc;">Tidak ada data izin pekerjaan.</td></tr>';
        }
        ?>
    </table>

</div>

</body>
</html>