<?php
session_start();
include "../koneksi.php";

/* ================== CEK ROLE ================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] != "pengawas") {
    header("Location: ../login.php");
    exit;
}

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Periksa file koneksi.php dan jalankan MySQL.");
}

/* ================== STATISTIK ================== */
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(status='Menunggu Review') as menunggu,
        SUM(status='Disetujui') as disetujui,
        SUM(status='Ditolak') as ditolak,
        SUM(status='Dalam Verifikasi') as verifikasi
    FROM form_izin_pekerjaan
";
$stats_result = mysqli_query($koneksi, $stats_query);
if (!$stats_result) {
    die("Query statistik gagal: " . mysqli_error($koneksi));
}
$stats = mysqli_fetch_assoc($stats_result);

$total = $stats['total'] ?? 0;
$menunggu = $stats['menunggu'] ?? 0;
$disetujui = $stats['disetujui'] ?? 0;
$ditolak = $stats['ditolak'] ?? 0;
$verifikasi = $stats['verifikasi'] ?? 0;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Pengawas Lapangan | CV Cipta Manunggal Konsultan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: #111111;
            color: #ffffff;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: 260px;
            background: #1a1a1a;
            padding: 30px 20px;
            border-right: 1px solid rgba(255,255,255,0.05);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
        }

        .logo-arch {
            width: 38px;
            height: 38px;
            stroke: #ffc107;
            stroke-width: 4;
            fill: none;
        }

        .sidebar h2 { font-size: 16px; }
        .sidebar span { color: #ffc107; }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sidebar nav a {
            text-decoration: none;
            color: #cccccc;
            padding: 10px;
            border-radius: 6px;
            transition: 0.3s;
            font-size: 14px;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #ffc107;
            color: #111;
        }

        .logout {
            margin-top: 30px;
            background: #2a2a2a;
        }

        /* ── MAIN ── */
        .main-content {
            flex: 1;
            padding: 50px;
            overflow-y: auto;
        }

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .topbar h1 { 
            font-size: 28px; 
            font-weight: 700; 
        }
        
        .topbar p  { 
            font-size: 14px; 
            color: #888; 
            margin-top: 4px; 
        }

        .role-badge {
            background: #ffc107;
            color: #111;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── STATS ── */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: #1c1c1c;
            padding: 28px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #ffc107, #ffb300);
        }

        .stat-card:hover {
            border-color: #ffc107;
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(255, 193, 7, 0.15);
        }

        .stat-card.warning::before {
            background: linear-gradient(90deg, #ff9800, #ff7500);
        }

        .stat-card.danger::before {
            background: linear-gradient(90deg, #ff4444, #cc0000);
        }

        .stat-card.success::before {
            background: linear-gradient(90deg, #22c55e, #16a34a);
        }

        .stat-card.info::before {
            background: linear-gradient(90deg, #60a5fa, #3b82f6);
        }

        .stat-card h3 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stat-card h3 {
            color: #ffc107;
        }

        .stat-card.warning h3 {
            color: #ff9800;
        }

        .stat-card.danger h3 {
            color: #ff4444;
        }

        .stat-card.success h3 {
            color: #22c55e;
        }

        .stat-card.info h3 {
            color: #60a5fa;
        }

        .stat-card p {
            font-size: 13px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── GRID SECTION ── */
        .grid-section {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
            margin-bottom: 40px;
        }

        /* ── CARD GRID IZIN ── */
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        /* ── IZIN CARD ── */
        .izin-card {
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.05);
            padding: 24px;
            border-radius: 12px;
            transition: 0.3s;
        }

        .izin-card:hover {
            transform: translateY(-4px);
            border-color: #ffc107;
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.1);
        }

        .izin-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            color: #ffc107;
        }

        .izin-card p {
            font-size: 13px;
            color: #aaa;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .izin-card p b {
            color: #ccc;
        }

        /* ── STATUS BADGES ── */
        .status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 12px;
        }

        .status.menunggu { 
            background: rgba(255, 152, 0, 0.2); 
            color: #ff9800;
        }

        .status.verifikasi { 
            background: rgba(96, 165, 250, 0.2); 
            color: #60a5fa;
        }

        .status.disetujui { 
            background: rgba(34, 197, 94, 0.2); 
            color: #22c55e;
        }

        .status.ditolak { 
            background: rgba(239, 68, 68, 0.2); 
            color: #ef4444;
        }
        .status.revisi { 
         background: rgba(168, 85, 247, 0.2); 
         color: #a855f7;
        }

        /* ── NOTIF ── */
        .notif {
            position: fixed;
            right: 20px;
            top: 20px;
            background: #1c1c1c;
            padding: 16px 24px;
            border-left: 4px solid #ff4444;
            border-radius: 10px;
            animation: slideIn 0.4s ease;
            z-index: 999;
            border: 1px solid rgba(255, 68, 68, 0.2);
            font-size: 14px;
        }

        @keyframes slideIn {
            from { 
                transform: translateX(120%); 
                opacity: 0; 
            }
            to { 
                transform: translateX(0); 
                opacity: 1; 
            }
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                padding: 30px;
            }

            .stats {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            }

            .card-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                border-right: none;
                border-bottom: 1px solid rgba(255,255,255,0.05);
                padding: 20px;
            }

            .sidebar nav {
                flex-direction: row;
                gap: 10px;
                flex-wrap: wrap;
            }

            .sidebar nav a {
                flex: 1;
                min-width: 80px;
                text-align: center;
            }

            .main-content {
                padding: 20px;
            }

            .topbar {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .stat-card {
                padding: 18px;
            }

            .stat-card h3 {
                font-size: 26px;
            }

            .card-grid {
                grid-template-columns: 1fr;
            }

            .notif {
                max-width: 90vw;
                width: auto;
            }
        }
    </style>
</head>

<body>

<!-- NOTIF -->
<?php if($menunggu > 0): ?>
<div class="notif" id="notif">
    🔔 <?= $menunggu ?> izin pekerjaan menunggu verifikasi Anda
</div>
<?php endif; ?>

<div class="dashboard-container">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-brand">
            <svg viewBox="0 0 120 120" class="logo-arch">
                <rect x="10" y="10" width="100" height="100"/>
                <path d="M35 80 V40 H60"/>
                <path d="M60 40 L75 60 L90 40 V80"/>
            </svg>
            <h2>CIPTA<span>MANUNGGAL</span></h2>
        </div>

        <nav>
            <a href="pengawas_lapangan.php" class="active">Dashboard</a>
            <a href="verifikasi_lapangan.php">Verifikasi</a>
            <a href="laporan_verifikasi.php">Laporan Harian</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div>
                <h1>Dashboard Pengawas</h1>
                <p>Pantau dan kelola verifikasi izin pekerjaan lapangan</p>
            </div>
            <div class="role-badge">PENGAWAS LAPANGAN</div>
        </header>

        <!-- STATS -->
        <section class="stats">
            <div class="stat-card">
                <h3><?= $total ?></h3>
                <p>Total Pengajuan</p>
            </div>
            <div class="stat-card warning">
                <h3><?= $menunggu ?></h3>
                <p>Menunggu Verifikasi</p>
            </div>
            <div class="stat-card info">
                <h3><?= $verifikasi ?></h3>
                <p>Dalam Verifikasi</p>
            </div>
            <div class="stat-card success">
                <h3><?= $disetujui ?></h3>
                <p>Disetujui</p>
            </div>
            <div class="stat-card danger">
                <h3><?= $ditolak ?></h3>
                <p>Ditolak</p>
            </div>
        </section>

        <!-- GRID IZIN -->
        <section class="grid-section">
            <h2 style="font-size: 18px; font-weight: 600; margin-bottom: 24px; display: flex; align-items: center; gap: 10px;">
                <span style="display: inline-block; width: 3px; height: 18px; background: #ffc107; border-radius: 2px;"></span>
                Daftar Izin
            </h2>
            <div class="card-grid">

                <?php
                $q = mysqli_query($koneksi,"SELECT f.*, u.username 
                FROM form_izin_pekerjaan f 
                LEFT JOIN users u ON f.kontraktor_id=u.id
                ORDER BY 
                    CASE WHEN f.status = 'Menunggu Review' THEN 1
                         WHEN f.status = 'Dalam Verifikasi' THEN 2
                         WHEN f.status = 'Disetujui' THEN 3
                         WHEN f.status = 'Ditolak' THEN 4
                         ELSE 5
                    END,
                    f.created_at DESC LIMIT 10");

                if(mysqli_num_rows($q) > 0) {
                    while($d = mysqli_fetch_assoc($q)):
                        $status_lower = strtolower($d['status']);
                        $status_class = '';
                        
                       
if(strpos($status_lower, 'menunggu') !== false) {
    $status_class = 'menunggu';
} elseif(strpos($status_lower, 'disetujui') !== false) {
    $status_class = 'disetujui';
} elseif(strpos($status_lower, 'ditolak') !== false) {
    $status_class = 'ditolak';
} elseif(strpos($status_lower, 'revisi') !== false) {
    $status_class = 'revisi';
}
                ?>

                <div class="izin-card">
                    <h3><?= htmlspecialchars($d['jenis_pekerjaan']) ?></h3>
                    <p><b>Kontraktor:</b> <span><?= htmlspecialchars($d['username'] ?? '-') ?></span></p>
                    <p><b>Lokasi:</b> <span><?= htmlspecialchars($d['lokasi']) ?></span></p>
                    <p><b>Volume:</b> <span><?= htmlspecialchars($d['volume']) ?></span></p>
                    <p style="margin-bottom: 16px;"><b>Tanggal:</b> <span><?= date('d M Y', strtotime($d['created_at'])) ?></span></p>

                    <span class="status <?= $status_class ?>">
                        <?= htmlspecialchars($d['status']) ?>
                    </span>
                </div>

                <?php endwhile;
                } else {
                    echo '<div style="grid-column: 1/-1; text-align: center; padding: 40px; color: #666;">Tidak ada data izin pekerjaan</div>';
                }
                ?>

            </div>
        </section>

    </main>
</div>

<script>
setTimeout(()=>{
    const n = document.getElementById("notif");
    if(n) n.style.display="none";
},5000);
</script>

</body>
</html>