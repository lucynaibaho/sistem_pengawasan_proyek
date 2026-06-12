<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != "teamleader") {
    header("Location: ../login.php");
    exit;
}

require_once "../koneksi.php";
if (!isset($koneksi) || !$koneksi) {
    die("Koneksi gagal: file koneksi tidak ditemukan atau koneksi database bermasalah.");
}
$username = $_SESSION['username'];
$user_id = $_SESSION['id'];

// Ambil statistik dari tabel laporan_bulanan
$stats = [
    'total' => mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM laporan_bulanan"))['cnt'] ?? 0,
    'belum_eval' => mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM laporan_bulanan WHERE status = 'Belum Dievaluasi'"))['cnt'] ?? 0,
    'disetujui' => mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM laporan_bulanan WHERE status = 'Disetujui'"))['cnt'] ?? 0,
    'revisi' => mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as cnt FROM laporan_bulanan WHERE status = 'Diminta Revisi'"))['cnt'] ?? 0
];

$latest_reports_result = mysqli_query($koneksi, "SELECT id, judul, periode, deskripsi, status FROM laporan_bulanan ORDER BY created_at DESC LIMIT 3");
if ($latest_reports_result === false) {
    $latest_reports_result = [];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Team Leader | Cipta Manunggal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="asset/teamleader.css?v=20260612">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard-specific extras */
        .grid-section {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
            margin-bottom: 24px;
        }

        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
        }

        .chart-card h3 {
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 24px;
            color: var(--text-primary);
        }

        .activity-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 28px;
            display: flex;
            flex-direction: column;
        }

        .activity-card h3 {
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-primary);
        }

        .activity-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 14px;
            flex: 1;
        }

        .activity-list li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            font-size: 13px;
            color: var(--text-muted);
            padding-bottom: 14px;
            border-bottom: 1px solid var(--border);
        }

        .activity-list li:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-top: 4px;
            flex-shrink: 0;
        }

        .dot-yellow { background: #ffc107; }
        .dot-green  { background: #22c55e; }
        .dot-purple { background: #818cf8; }
        .dot-red    { background: #ef4444; }

        .activity-time {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 2px;
        }

        /* Laporan mingguan summary row */
        .weekly-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid var(--border);
        }

        .weekly-row:last-child { border-bottom: none; }

        .weekly-row-left {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .weekly-row-left strong {
            font-size: 14px;
            color: var(--text-primary);
        }

        .weekly-row-left span {
            font-size: 12px;
            color: var(--text-muted);
        }

        /* Progress bar */
        .progress-wrap {
            margin-top: 16px;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .progress-bar-bg {
            background: rgba(255,255,255,0.06);
            border-radius: 100px;
            height: 6px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 100px;
            background: var(--accent);
            transition: width 1s ease;
        }

        /* Quick action cards */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }

        .quick-card {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 22px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: border-color 0.2s, transform 0.2s;
        }

        .quick-card:hover {
            border-color: var(--border-hover);
            transform: translateY(-3px);
        }

        .quick-icon {
            width: 42px;
            height: 42px;
            background: var(--accent-dim);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .quick-card-text strong {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 2px;
        }

        .quick-card-text span {
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

<div class="dashboard-container">

    <!-- SIDEBAR -->
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
            <a href="teamleader.php" class="nav-item active">
                <svg class="nav-icon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="1" width="6" height="6" rx="1.2"/><rect x="9" y="1" width="6" height="6" rx="1.2"/><rect x="1" y="9" width="6" height="6" rx="1.2"/><rect x="9" y="9" width="6" height="6" rx="1.2"/></svg>
                Dashboard
            </a>
            <a href="evaluasi_laporan_bulanan.php" class="nav-item">
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

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="topbar-left">
                <h1>Halo, <?php echo htmlspecialchars($username); ?></h1>
                <p>Pantau evaluasi laporan bulanan dan verifikasi progres proyek.</p>
            </div>
            <span class="role-badge">TEAM LEADER</span>
        </header>

        <!-- STATS -->
        <section class="stats">
            <div class="stat-card">
                <h3><?php echo htmlspecialchars($stats['total']); ?></h3>
                <p>Total Laporan Bulanan</p>
            </div>
            <div class="stat-card">
                <h3><?php echo htmlspecialchars($stats['belum_eval']); ?></h3>
                <p>Belum Dievaluasi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo htmlspecialchars($stats['revisi']); ?></h3>
                <p>Diminta Revisi</p>
            </div>
            <div class="stat-card">
                <h3><?php echo htmlspecialchars($stats['disetujui']); ?></h3>
                <p>Disetujui</p>
            </div>
        </section>

        <!-- QUICK ACTIONS -->
        <section class="quick-actions">
            <a href="evaluasi_laporan_bulanan.php" class="quick-card">
                <div class="quick-icon">📊</div>
                <div class="quick-card-text">
                    <strong>Evaluasi Laporan</strong>
                    <span>Lihat semua laporan bulanan</span>
                </div>
            </a>
            <a href="riwayat_laporan_bulanan.php" class="quick-card">
                <div class="quick-icon">🗂</div>
                <div class="quick-card-text">
                    <strong>Riwayat Arsip</strong>
                    <span>Laporan yang sudah disetujui</span>
                </div>
            </a>
            <a href="teamleader.php" class="quick-card">
                <div class="quick-icon">📈</div>
                <div class="quick-card-text">
                    <strong>Status Terbaru</strong>
                    <span>Ringkasan evaluasi bulanan</span>
                </div>
            </a>
        </section>

        <!-- CHART + ACTIVITY -->
        <section class="grid-section">

            <!-- Bar Chart: Evaluasi per bulan -->
            <div class="chart-card">
                <h3>Progres Evaluasi Laporan (6 Bulan Terakhir)</h3>
                <canvas id="evalChart" height="110"></canvas>
            </div>

            <!-- Activity Feed -->
            <div class="activity-card">
                <h3>Aktivitas Terbaru</h3>
                <ul class="activity-list">
                    <li>
                        <span class="activity-dot dot-yellow"></span>
                        <div>
                            Lap. Bulanan April 2026 menunggu evaluasi
                            <div class="activity-time">02 Apr 2026 · Koordinator A</div>
                        </div>
                    </li>
                    <li>
                        <span class="activity-dot dot-yellow"></span>
                        <div>
                            Lap. Bulanan Maret 2026 menunggu evaluasi
                            <div class="activity-time">29 Mar 2026 · Koordinator B</div>
                        </div>
                    </li>
                    <li>
                        <span class="activity-dot dot-green"></span>
                        <div>
                            Evaluasi Februari 2026 disetujui
                            <div class="activity-time">03 Mar 2026 · <?php echo htmlspecialchars($username); ?></div>
                        </div>
                    </li>
                    <li>
                        <span class="activity-dot dot-purple"></span>
                        <div>
                            Revisi diminta — Lap. Jan W2
                            <div class="activity-time">10 Feb 2026 · <?php echo htmlspecialchars($username); ?></div>
                        </div>
                    </li>
                    <li>
                        <span class="activity-dot dot-green"></span>
                        <div>
                            Evaluasi Januari 2026 disetujui
                            <div class="activity-time">04 Feb 2026 · <?php echo htmlspecialchars($username); ?></div>
                        </div>
                    </li>
                </ul>
            </div>

        </section>

        <!-- LAPORAN BULANAN TERBARU + PROGRES PROYEK -->
        <section class="grid-section">

            <!-- Laporan Bulanan Terbaru -->
            <div class="chart-card">
                <h3>Laporan Bulanan Terbaru</h3>

                        <?php if (!empty($latest_reports_result) && mysqli_num_rows($latest_reports_result) > 0): ?>
                    <?php while ($report = mysqli_fetch_assoc($latest_reports_result)): ?>
                        <div class="weekly-row">
                            <div class="weekly-row-left">
                                <strong><?= htmlspecialchars($report['periode']) ?></strong>
                                <span><?= htmlspecialchars($report['judul']) ?></span>
                            </div>
                            <div style="display:flex; align-items:center; gap:10px;">
                                <?php if ($report['status'] === 'Disetujui'): ?>
                                    <span class="badge badge-approved">Disetujui</span>
                                    <a href="riwayat_laporan_bulanan.php" class="btn btn-secondary btn-sm">Arsip</a>
                                <?php elseif ($report['status'] === 'Diminta Revisi'): ?>
                                    <span class="badge badge-revision">Diminta Revisi</span>
                                    <a href="evaluasi_laporan_bulanan.php" class="btn btn-secondary btn-sm">Periksa</a>
                                <?php else: ?>
                                    <span class="badge badge-pending">Belum Dievaluasi</span>
                                    <a href="evaluasi_laporan_bulanan.php" class="btn btn-primary btn-sm">Evaluasi</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="weekly-row">
                        <div class="weekly-row-left">
                            <strong>Tidak ada laporan bulanan</strong>
                            <span>Belum ada data terbaru</span>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

            <!-- Progres Proyek -->
            <div class="activity-card">
                <h3>Progres Proyek</h3>

                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Struktur Lt. 1</span>
                        <span style="color:var(--accent);">100%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:100%;"></div>
                    </div>
                </div>

                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Struktur Lt. 2</span>
                        <span style="color:var(--accent);">85%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:85%;"></div>
                    </div>
                </div>

                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Struktur Lt. 3</span>
                        <span style="color:var(--accent);">40%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:40%;"></div>
                    </div>
                </div>

                <div class="progress-wrap">
                    <div class="progress-label">
                        <span>Finishing & MEP</span>
                        <span style="color:#555;">0%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width:0%;background:#333;"></div>
                    </div>
                </div>

                <div style="margin-top:24px; padding-top:16px; border-top:1px solid var(--border);">
                    <div class="progress-label" style="margin-bottom:8px;">
                        <span style="font-weight:600; color:var(--text-primary);">Total Progres</span>
                        <span style="color:var(--accent); font-weight:700; font-size:16px;">56%</span>
                    </div>
                    <div class="progress-bar-bg" style="height:8px;">
                        <div class="progress-bar-fill" style="width:56%;"></div>
                    </div>
                </div>
            </div>

        </section>

    </main>
</div>

<script>
const ctx = document.getElementById('evalChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Nov 25', 'Des 25', 'Jan 26', 'Feb 26', 'Mar 26', 'Apr 26'],
        datasets: [
            {
                label: 'Lap. Bulanan',
                data: [4, 5, 4, 4, 4, 1],
                backgroundColor: 'rgba(255,193,7,0.25)',
                borderColor: '#ffc107',
                borderWidth: 1.5,
                borderRadius: 4,
            },
            {
                label: 'Disetujui',
                data: [4, 5, 4, 4, 0, 0],
                backgroundColor: 'rgba(34,197,94,0.25)',
                borderColor: '#22c55e',
                borderWidth: 1.5,
                borderRadius: 4,
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                labels: { color: '#888', font: { size: 12 } }
            }
        },
        scales: {
            x: {
                ticks: { color: '#666' },
                grid: { color: 'rgba(255,255,255,0.04)' }
            },
            y: {
                ticks: { color: '#666', stepSize: 1 },
                grid: { color: 'rgba(255,255,255,0.04)' },
                beginAtZero: true
            }
        }
    }
});
</script>

</body>
</html>