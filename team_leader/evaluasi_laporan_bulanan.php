<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "teamleader") {
    header("Location: ../login.php");
    exit;
}

include "../koneksi.php";
$username = $_SESSION['username'];
$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

$reports_result = false;
if (!isset($koneksi) || !$koneksi) {
    $error = 'Koneksi database gagal. Silakan periksa konfigurasi koneksi atau nyalakan server MySQL.';
} else {
    $reports_query = "SELECT lb.*, u.username AS koordinator_name
FROM laporan_bulanan lb
LEFT JOIN users u ON lb.koordinator_id = u.id
ORDER BY lb.created_at DESC";
    $reports_result = mysqli_query($koneksi, $reports_query);
    if ($reports_result === false) {
        $error = 'Query gagal: ' . mysqli_error($koneksi);
    }
}

function statusBadgeClass($status) {
    if ($status === 'Disetujui') return 'badge-approved';
    if ($status === 'Diminta Revisi') return 'badge-revision';
    return 'badge-pending';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluasi Laporan Bulanan | Team Leader</title>
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
            <a href="evaluasi_laporan_bulanan.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M2 12h12"/><path d="M5 8h7"/><path d="M7 4h5"/></svg>
                Evaluasi Laporan
            </a>
            <a href="riwayat_laporan_bulanan.php" class="nav-item">
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
                <h1>Evaluasi Laporan Bulanan</h1>
                <p>Kelola evaluasi, setujui laporan bulanan, atau minta revisi ke Koordinator Pengawas.</p>
            </div>
            <span class="role-badge">TEAM LEADER</span>
        </header>

        <?php if ($message): ?>
            <div class="form-section" style="border-color:#22c55e; color:#d1fae5; background:rgba(34,197,94,0.08);">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="form-section" style="border-color:#ef4444; color:#fee2e2; background:rgba(239,68,68,0.08);">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <section class="table-card">
            <div class="table-card-header">
                <h2>Daftar Laporan Bulanan</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Periode</th>
                        <th>Judul</th>
                        <th>Koordinator</th>
                        <th>Status</th>
                        <th>Catatan Evaluasi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($reports_result && mysqli_num_rows($reports_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($reports_result)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['periode']) ?></td>
                                <td><?= htmlspecialchars($row['judul']) ?></td>
                                <td><?= htmlspecialchars($row['koordinator_name'] ?: '-') ?></td>
                                <td><span class="badge <?= statusBadgeClass($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                                <td><?= nl2br(htmlspecialchars($row['catatan_eval'] ?: '-')) ?></td>
                                <td>
                                    <?php if ($row['status'] !== 'Disetujui'): ?>
                                        <form method="post" action="proses_evaluasi_laporan.php">
                                            <input type="hidden" name="laporan_id" value="<?= $row['id'] ?>">
                                            <textarea name="catatan" placeholder="Catatan untuk koordinator atau ringkas evaluasi..." style="width:100%; min-height:84px; margin-bottom:10px; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08); border-radius:10px; color:#f7f7f7; padding:10px;"></textarea>
                                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                                <button type="submit" name="action" value="approve" class="btn btn-primary btn-sm">Setujui</button>
                                                <button type="submit" name="action" value="revision" class="btn btn-secondary btn-sm">Minta Revisi</button>
                                            </div>
                                        </form>
                                    <?php else: ?>
                                        <span style="color: #a3a3a3;">Tidak ada aksi</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align:center;">Belum ada laporan bulanan yang dapat dievaluasi.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </main>
</div>
</body>
</html>
