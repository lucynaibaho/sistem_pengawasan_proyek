<?php
session_start();
require_once "../koneksi.php";
if (!isset($koneksi) || !$koneksi) {
    die("Koneksi gagal: file koneksi tidak ditemukan atau koneksi database bermasalah.");
}

// Proteksi Role
if (!isset($_SESSION['role']) || $_SESSION['role'] != "koordinator") {
    header("Location: ../login.php");
    exit;
}

// Ambil statistik dinamis dari database
$stats_query = "SELECT 
    COUNT(*) as total_laporan,
    SUM(CASE WHEN status = 'Menunggu' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status = 'Tervalidasi' THEN 1 ELSE 0 END) as tervalidasi
FROM laporan_harian";

$stats_result = mysqli_query($koneksi, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

$total_laporan = $stats['total_laporan'] ?? 0;
$menunggu = $stats['menunggu'] ?? 0;
$tervalidasi = $stats['tervalidasi'] ?? 0;

// Ambil laporan harian terbaru
$laporan_query = "SELECT lh.*, p.username as kontraktor_nama
FROM laporan_harian lh
LEFT JOIN users p ON lh.kontraktor_id = p.id
ORDER BY lh.tanggal DESC
LIMIT 10";

$laporan_result = mysqli_query($koneksi, $laporan_query);
$laporan_data = mysqli_fetch_all($laporan_result, MYSQLI_ASSOC);
?>

<?php
/**
 * koordinator_pengawas.php — Dashboard Koordinator Pengawas
 * Sistem Pengawasan Proyek — Koordinator
 */
$active_page = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Koordinator Pengawas | CV Cipta Manunggal</title>
    <link rel="stylesheet" href="asset/koordinator.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<main class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <h2>Dashboard</h2>
        <div class="topbar-right">
            <div class="date-chip" id="date-chip"></div>
            <a href="Tinjau_laporan_harian.php" class="notif-btn">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="#aaa" stroke-width="1.5">
                    <path d="M8 1a5 5 0 015 5v3l1 2H2l1-2V6a5 5 0 015-5z"/>
                    <path d="M6.5 13.5a1.5 1.5 0 003 0"/>
                </svg>
                <div class="notif-dot"></div>
            </a>
        </div>
    </div>

    <!-- SECTION HEADER -->
    <div class="section-header fade-up">
        <div>
            <div class="section-title">Ringkasan Aktivitas</div>
            <div class="section-sub">Pantau laporan harian dan verifikasi progres pekerjaan</div>
        </div>
    </div>

    <!-- STATS -->
    <div class="stats-grid fade-up" style="animation-delay:.04s">
        <div class="stat-card">
            <div class="stat-label">Total Laporan</div>
            <div class="stat-val"><?= $total_laporan ?></div>
            <div class="stat-sub">Laporan harian</div>
            <div class="stat-icon">📊</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Menunggu Verifikasi</div>
            <div class="stat-val" style="color: #f59e0b;"><?= $menunggu ?></div>
            <div class="stat-sub">Perlu tinjauan</div>
            <div class="stat-icon">⏳</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Tervalidasi</div>
            <div class="stat-val" style="color: #22c55e;"><?= $tervalidasi ?></div>
            <div class="stat-sub">Sudah diverifikasi</div>
            <div class="stat-icon">✅</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Persentase</div>
            <div class="stat-val" style="color: #38bdf8;">
                <?= $total_laporan > 0 ? round(($tervalidasi / $total_laporan) * 100) : 0 ?>%
            </div>
            <div class="stat-sub">Tervalidasi</div>
            <div class="stat-icon">📈</div>
        </div>
    </div>

    <!-- ACTIVITY CARD -->
    <div class="panel fade-up" style="animation-delay:.08s">
        <div class="panel-title">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="8" cy="8" r="6"/>
                <path d="M8 5v3.5l2 1.5"/>
            </svg>
            Laporan Harian Terbaru
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kontraktor</th>
                        <th>Progres Pekerjaan</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($laporan_result) > 0): ?>
                        <?php foreach ($laporan_data as $row): 
                            $status_class = (strtolower($row['status']) === 'menunggu') ? 'badge-wait' : 'badge-done';
                        ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                            <td><?= htmlspecialchars($row['kontraktor_nama'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['progres'] ?? '-') ?></td>
                            <td>
                                <span class="badge <?= $status_class ?>">
                                    <?= htmlspecialchars($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="Tinjau_laporan_harian.php?id=<?= $row['id'] ?>" class="btn btn-outline btn-sm">
                                    Tinjau
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--muted); padding: 40px;">
                                Belum ada laporan harian
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
// Update date chip
function updateDateChip() {
    const now = new Date();
    const options = { 
        weekday: 'short', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    document.getElementById('date-chip').textContent = now.toLocaleDateString('id-ID', options);
}
updateDateChip();
setInterval(updateDateChip, 60000); // Update every minute
</script>

</body>
</html>
