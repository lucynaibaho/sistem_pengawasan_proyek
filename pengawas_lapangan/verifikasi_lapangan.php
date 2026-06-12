<?php
session_start();
require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "pengawas") {
    header("Location: ../login.php");
    exit;
}

// Pastikan koneksi database tersedia
if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Periksa file koneksi.php dan server MySQL.");
}

// Get ID dari URL jika ada, untuk filter
$filter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Query utama - mengambil semua data dengan join yang benar
$query = "
    SELECT f.id, f.jenis_pekerjaan, f.volume, f.lokasi, f.status, f.created_at, f.kontraktor_id, u.username
    FROM form_izin_pekerjaan f
    LEFT JOIN users u ON f.kontraktor_id = u.id
    WHERE 1=1
";

// Jika ada filter ID tertentu
if ($filter_id > 0) {
    $query .= " AND f.id = $filter_id";
}

// Order berdasarkan priority dan tanggal
$query .= " ORDER BY 
    CASE WHEN f.status = 'Menunggu Review' THEN 1
         WHEN f.status = 'Dalam Verifikasi' THEN 2
         WHEN f.status = 'Disetujui' THEN 3
         WHEN f.status = 'Ditolak' THEN 4
         ELSE 5
    END,
    f.created_at DESC";

$result = mysqli_query($koneksi, $query);
if (!$result) {
    die("Query error: " . mysqli_error($koneksi));
}

// Hitung statistik untuk highlight
$query_count = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Menunggu Review' THEN 1 ELSE 0 END) as menunggu,
    SUM(CASE WHEN status = 'Disetujui' THEN 1 ELSE 0 END) as disetujui,
    SUM(CASE WHEN status = 'Ditolak' THEN 1 ELSE 0 END) as ditolak,
    SUM(CASE WHEN status = 'Dalam Verifikasi' THEN 1 ELSE 0 END) as verifikasi
FROM form_izin_pekerjaan";

$count_result = mysqli_query($koneksi, $query_count);
if (!$count_result) {
    die("Count query error: " . mysqli_error($koneksi));
}

$stats = mysqli_fetch_assoc($count_result);
$total_pengajuan = $stats['total'] ?? 0;
$jumlah_antrean = $stats['menunggu'] ?? 0;
$jumlah_approved = $stats['disetujui'] ?? 0;
$jumlah_ditolak = $stats['ditolak'] ?? 0;
$jumlah_verifikasi = $stats['verifikasi'] ?? 0;

// Helper function untuk status class
function getStatusClass($status) {
    if($status === 'Menunggu Review'){
        return 'status-menunggu';
    } elseif($status === 'Dalam Verifikasi'){
        return 'status-verifikasi';
    } elseif($status === 'Disetujui'){
        return 'status-disetujui';
    } elseif($status === 'Ditolak'){
        return 'status-ditolak';
    } else {
        return 'status-menunggu';
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Izin Pekerjaan | CV Cipta Manunggal Konsultan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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

        .sidebar h2 {
            font-size: 16px;
        }

        .sidebar span {
            color: #ffc107;
        }

        .sidebar nav {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .sidebar nav a {
            text-decoration: none;
            color: #cccccc;
            padding: 12px 14px;
            border-radius: 6px;
            transition: 0.3s;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .sidebar nav a:hover,
        .sidebar nav a.active {
            background: #ffc107;
            color: #111;
        }

        .badge-notif {
            background: #ff4444;
            color: #fff;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
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

        .topbar p {
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
            height: 3px;
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

        /* ── FILTER CARD ── */
        .filter-section {
            background: #1c1c1c;
            padding: 20px 24px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 28px;
        }

        .filter-form {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-form select {
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,0.1);
            background: #0f0f0f;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            transition: 0.3s;
        }

        .filter-form select:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 8px rgba(255, 193, 7, 0.2);
        }

        .btn-filter {
            background: #ffc107;
            color: #111;
            padding: 10px 20px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn-filter:hover {
            background: #ffb300;
            transform: translateY(-2px);
        }

        /* ── TABLE CARD ── */
        .activity-card {
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 14px;
            padding: 28px;
            overflow: hidden;
        }

        .activity-card h3 {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .activity-card h3::before {
            content: '';
            display: inline-block;
            width: 3px;
            height: 16px;
            background: #ffc107;
            border-radius: 2px;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        .table-izin {
            width: 100%;
            border-collapse: collapse;
        }

        .table-izin thead {
            background: #0f0f0f;
        }

        .table-izin th {
            text-align: left;
            color: #aaa;
            padding: 14px 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        .table-izin td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 13px;
        }

        .table-izin tbody tr:hover {
            background: rgba(255, 193, 7, 0.02);
        }

        /* ── STATUS BADGES ── */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .status-menunggu {
            background: rgba(255, 152, 0, 0.2);
            color: #ff9800;
        }

        .status-verifikasi {
            background: rgba(96, 165, 250, 0.2);
            color: #60a5fa;
        }

        .status-disetujui {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }

        .status-ditolak {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }

        /* ── BUTTONS ── */
        .btn-tinjau {
            background: transparent;
            color: #60a5fa;
            padding: 6px 12px;
            border-radius: 6px;
            border: 1px solid #60a5fa;
            cursor: pointer;
            font-weight: 500;
            font-size: 11px;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .btn-tinjau:hover {
            background: rgba(96, 165, 250, 0.1);
            border-color: #8bb9fc;
            color: #8bb9fc;
        }

        .empty {
            text-align: center;
            padding: 60px 20px;
            color: #666;
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
                min-width: 100px;
                justify-content: center;
                flex-direction: column;
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
                font-size: 28px;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form select,
            .btn-filter {
                width: 100%;
            }

            table {
                font-size: 12px;
            }

            th, td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>

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
            <a href="pengawas_lapangan.php">Dashboard</a>
            <a href="verifikasi_lapangan.php" class="active">
                Verifikasi
                <?php if($jumlah_antrean > 0): ?>
                    <span class="badge-notif"><?php echo $jumlah_antrean; ?></span>
                <?php endif; ?>
            </a>
            <a href="laporan_verifikasi.php">Laporan Harian</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <!-- HEADER -->
        <header class="topbar">
            <div>
                <h1>Verifikasi Izin Pekerjaan</h1>
                <p>Tinjau dan verifikasi izin pekerjaan dari kontraktor</p>
            </div>
            <div class="role-badge">PENGAWAS LAPANGAN</div>
        </header>

        <!-- STATS -->
        <section class="stats">
            <div class="stat-card">
                <h3><?php echo $total_pengajuan; ?></h3>
                <p>Total Pengajuan</p>
            </div>

            <div class="stat-card warning">
                <h3><?php echo $jumlah_antrean; ?></h3>
                <p>Menunggu Verifikasi</p>
            </div>

            <div class="stat-card info">
                <h3><?php echo $jumlah_verifikasi; ?></h3>
                <p>Dalam Verifikasi</p>
            </div>

            <div class="stat-card success">
                <h3><?php echo $jumlah_approved; ?></h3>
                <p>Disetujui</p>
            </div>

            <div class="stat-card danger">
                <h3><?php echo $jumlah_ditolak; ?></h3>
                <p>Ditolak</p>
            </div>
        </section>

        <!-- FILTER -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <select name="status">
                    <option value="">- Semua Status -</option>
                    <option value="menunggu" <?php echo (isset($_GET['status']) && $_GET['status'] === 'menunggu') ? 'selected' : ''; ?>>Menunggu Review</option>
                    <option value="verifikasi" <?php echo (isset($_GET['status']) && $_GET['status'] === 'verifikasi') ? 'selected' : ''; ?>>Dalam Verifikasi</option>
                    <option value="disetujui" <?php echo (isset($_GET['status']) && $_GET['status'] === 'disetujui') ? 'selected' : ''; ?>>Disetujui</option>
                    <option value="ditolak" <?php echo (isset($_GET['status']) && $_GET['status'] === 'ditolak') ? 'selected' : ''; ?>>Ditolak</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
            </form>
        </div>

        <!-- TABLE -->
        <section class="grid-section">
            <div class="activity-card">
                <h3>Daftar Izin Pekerjaan</h3>

                <div class="table-wrapper">
                    <table class="table-izin">
                        <thead>
                            <tr>
                                <th>Jenis Pekerjaan</th>
                                <th>Volume</th>
                                <th>Lokasi</th>
                                <th>Kontraktor</th>
                                <th>Tanggal Ajuan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['jenis_pekerjaan'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['volume'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['lokasi'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['username'] ?? '-'); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['created_at'] ?? date('Y-m-d'))); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo getStatusClass($row['status']); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="detail_verifikasi.php?id=<?php echo $row['id']; ?>" class="btn-tinjau">
                                                Tinjau
                                            </a>
                                            <?php if($row['status'] == 'Disetujui Pengawas' || $row['status'] == 'Dalam Verifikasi'): ?>
                                                <a href="laporan_verifikasi.php?id=<?php echo $row['id']; ?>" class="btn-laporan" style="margin-left: 8px; background: #22c55e; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 12px;">
                                                    Laporan
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="empty">
                                        <strong>Tidak ada data izin pekerjaan</strong>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </section>

    </main>
</div>

</body>
</html>