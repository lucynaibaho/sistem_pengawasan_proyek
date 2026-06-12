<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "teamleader") {
    header("Location: ../login.php");
    exit;
}

include "../koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Silakan periksa file koneksi.php atau jalankan MySQL.");
}

$username = $_SESSION['username'];

$archive_query = "SELECT lb.*, u.username AS koordinator_name
FROM laporan_bulanan lb
LEFT JOIN users u ON lb.koordinator_id = u.id
WHERE lb.status = 'Disetujui'
ORDER BY lb.updated_at DESC";
$archive_result = mysqli_query($koneksi, $archive_query);
if (!$archive_result) {
    die('Query gagal: ' . mysqli_error($koneksi));
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Laporan Bulanan | Team Leader</title>
    <link rel="stylesheet" href="asset/teamleader.css?v=20260612">
</head>
<body>
<div class="dashboard-container">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-inner">
                <svg viewBox="0 0 120 120" class="logo-arch">
                    <rect x="10" y="10" width="100" height="100"/>
                    <path d="M35 80 V40 H60"/>
                    <path d="M60 40 L75 60 L90 40 V80"/>
                </svg>
                <div class="brand-text">
                    <h1>CIPTA <span>MANUNGGAL</span></h1>
                    <p>Team Leader</p>
                </div>
            </div>
        </div>
        <nav class="nav-section">
            <div class="nav-label">Menu Utama</div>
            <a href="teamleader.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="6" height="6" rx="1.2"/><rect x="9" y="1" width="6" height="6" rx="1.2"/><rect x="1" y="9" width="6" height="6" rx="1.2"/><rect x="9" y="9" width="6" height="6" rx="1.2"/></svg>
                Dashboard
            </a>
            <a href="evaluasi_laporan_bulanan.php" class="nav-item">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 12h12"/><path d="M5 8h7"/><path d="M7 4h5"/></svg>
                Evaluasi Laporan
            </a>
            <a href="riwayat_laporan_bulanan.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="8" cy="8" r="6"/><path d="M8 4v4l2 1.5"/></svg>
                Riwayat Laporan
            </a>
        </nav>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="avatar">TL</div>
                <div class="user-info">
                    <p><?= htmlspecialchars($username) ?></p>
                    <span>Team Leader Aktif</span>
                </div>
            </div>
            <a href="../logout.php" class="logout-btn">
                <svg width="14" height="14" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M11 5l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                    <path d="M7 2H3a1 1 0 00-1 1v10a1 1 0 001 1h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Logout
            </a>
        </div>
    </aside>
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <h1>Riwayat Arsip Laporan Bulanan</h1>
                <p>Daftar laporan yang telah disetujui dan tersimpan sebagai arsip resmi.</p>
            </div>
            <span class="role-badge">TEAM LEADER</span>
        </header>

        <section class="table-card">
            <div class="table-card-header">
                <h2>Arsip Laporan Bulanan Disetujui</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Judul</th>
                        <th>Koordinator</th>
                        <th>Catatan Evaluasi</th>
                        <th>Disetujui Pada</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($archive_result && mysqli_num_rows($archive_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($archive_result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td><?= htmlspecialchars($row['judul']) ?></td>
                                <td><?= htmlspecialchars($row['koordinator_name'] ?: '-') ?></td>
                                <td><?= nl2br(htmlspecialchars($row['catatan_eval'] ?: '-')) ?></td>
                                <td><?= htmlspecialchars(date('d M Y H:i', strtotime($row['updated_at']))) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center;">Belum ada laporan bulanan yang disetujui dan diarsipkan.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>
