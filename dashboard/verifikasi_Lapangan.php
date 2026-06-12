<?php
session_start();
$koneksi = null;
require_once "../koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

// Proteksi akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != "pengawas") {
    header("Location: ../login.php");
    exit;
}

// LOGIKA PROSES VERIFIKASI (Jika form disubmit)
if (isset($_POST['submit_verifikasi'])) {
    $id_form = $_POST['id_form'];
    $status_baru = $_POST['status'];
    $catatan = mysqli_real_escape_string($koneksi, $_POST['catatan_pengawas']);
    
    $update = mysqli_query($koneksi, "UPDATE form_izin_pekerjaan SET status='$status_baru', catatan_pengawas='$catatan' WHERE id='$id_form'");
    
    if ($update) {
        echo "<script>alert('Berhasil memperbarui status pekerjaan!'); window.location='verifikasi_lapangan.php';</script>";
    } else {
        echo "<script>alert('Gagal memperbarui data.');</script>";
    }
}

// Ambil data untuk notifikasi di sidebar
$query_notif = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM form_izin_pekerjaan WHERE status='Menunggu Verifikasi'");
$jumlah_antrean = mysqli_fetch_assoc($query_notif)['total'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Verifikasi Lapangan | CV Cipta Manunggal</title>
    <link rel="stylesheet" href="../kontraktor/asset/kontraktordash.css">
    <style>
        .badge-notif { background: #ffc107; color: #000; padding: 2px 8px; border-radius: 10px; font-size: 11px; margin-left: 8px; font-weight: 700; }
        .table-container { background: #1a1a1a; padding: 20px; border-radius: 12px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; color: #fff; }
        th { text-align: left; color: #888; padding: 12px; border-bottom: 1px solid #333; }
        td { padding: 15px; border-bottom: 1px solid #222; }
        .btn-action { background: #ffc107; color: #000; border: none; padding: 8px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; }
        /* Simple Form Style inside table */
        .verif-form { background: #222; padding: 15px; border-radius: 8px; margin-top: 10px; display: none; }
        .verif-form.active { display: block; }
        textarea { width: 100%; background: #111; border: 1px solid #444; color: #fff; padding: 10px; border-radius: 6px; margin: 10px 0; }
        select { background: #111; color: #fff; border: 1px solid #444; padding: 8px; border-radius: 6px; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <svg viewBox="0 0 120 120" class="logo-arch" style="width: 50px; height: 50px;">
                <rect x="10" y="10" width="100" height="100" stroke="#ffc107" stroke-width="3" fill="none"/>
                <path d="M35 80 V40 H60" stroke="#ffc107" stroke-width="4" fill="none"/>
                <path d="M60 40 L75 60 L90 40 V80" stroke="#ffc107" stroke-width="4" fill="none"/>
            </svg>
            <h2>CIPTA<span>MANUNGGAL</span></h2>
        </div>
        <nav>
            <a href="pengawas_lapangan.php">Dashboard</a>
            <a href="verifikasi_lapangan.php" class="active">Verifikasi Lapangan 
                <?php if($jumlah_antrean > 0) echo "<span class='badge-notif'>$jumlah_antrean</span>"; ?>
            </a>
            <a href="#">Laporan Harian</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Verifikasi Lapangan</h1>
                <p>Tinjau dan berikan persetujuan pada izin pekerjaan kontraktor.</p>
            </div>
            <div class="role-badge">PENGAWAS</div>
        </header>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Pekerjaan</th>
                        <th>Kontraktor</th>
                        <th>Lokasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $q = mysqli_query($koneksi, "SELECT f.*, u.username FROM form_izin_pekerjaan f 
                                                 JOIN users u ON f.kontraktor_id = u.id 
                                                 WHERE f.status='Menunggu Verifikasi' ORDER BY f.id DESC");
                    
                    if(mysqli_num_rows($q) > 0) {
                        while($row = mysqli_fetch_assoc($q)) { ?>
                        <tr>
                            <td>
                                <strong><?= $row['jenis_pekerjaan']; ?></strong><br>
                                <small style="color: #666;"><?= $row['tanggal_mulai']; ?> s/d <?= $row['tanggal_selesai']; ?></small>
                            </td>
                            <td><?= $row['username']; ?></td>
                            <td><?= $row['lokasi']; ?></td>
                            <td>
                                <button class="btn-action" onclick="toggleForm(<?= $row['id']; ?>)">Tinjau</button>
                            </td>
                        </tr>
                        <tr id="row-form-<?= $row['id']; ?>" class="verif-form">
                            <td colspan="4">
                                <form method="POST" style="background: #252525; padding: 20px; border-radius: 10px; border-left: 4px solid #ffc107;">
                                    <input type="hidden" name="id_form" value="<?= $row['id']; ?>">
                                    <h4 style="margin-bottom: 10px; color: #ffc107;">Form Keputusan Verifikasi</h4>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <label>Status Persetujuan:</label><br>
                                        <select name="status" required>
                                            <option value="Disetujui">Setujui Pekerjaan</option>
                                            <option value="Revisi">Minta Revisi</option>
                                            <option value="Ditolak">Tolak Pekerjaan</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label>Catatan Lapangan:</label>
                                        <textarea name="catatan_pengawas" placeholder="Tambahkan instruksi teknis jika diperlukan..."></textarea>
                                    </div>

                                    <button type="submit" name="submit_verifikasi" class="btn-action">Simpan Keputusan</button>
                                    <button type="button" class="btn-action" style="background: #444; color: #fff;" onclick="toggleForm(<?= $row['id']; ?>)">Batal</button>
                                </form>
                            </td>
                        </tr>
                        <?php }
                    } else {
                        echo "<tr><td colspan='4' style='text-align:center; padding: 40px; color: #555;'>Tidak ada antrean verifikasi saat ini.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function toggleForm(id) {
    const formRow = document.getElementById('row-form-' + id);
    if (formRow.style.display === 'table-row') {
        formRow.style.display = 'none';
    } else {
        formRow.style.display = 'table-row';
    }
}
</script>

</body>
</html>