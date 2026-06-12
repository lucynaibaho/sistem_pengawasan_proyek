<?php
session_start();
$koneksi = null;
require_once '../koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

/* ================= VALIDASI LOGIN ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != "kontraktor") {
    header("Location: ../login.php");
    exit;
}

$kontraktor_id = (int)$_SESSION['id'];

/* ================= MARK AS READ ================= */
$markRead = mysqli_query($koneksi, "
    UPDATE notifikasi SET status = 'read' 
    WHERE user_id = $kontraktor_id AND status = 'unread'
");

/* ================= HITUNG IZIN MENUNGGU ================= */
$jumlah_notif = 0;
$notifCountQuery = mysqli_query($koneksi, "
    SELECT * FROM form_izin_pekerjaan 
    WHERE kontraktor_id = $kontraktor_id AND status = 'Menunggu Review'
");
if ($notifCountQuery) {
    $jumlah_notif = mysqli_num_rows($notifCountQuery);
}

/* ================= AMBIL NOTIF TERBARU ================= */
$notif_query = mysqli_query($koneksi, "
    SELECT * FROM notifikasi
    WHERE user_id = $kontraktor_id
    ORDER BY created_at DESC
    LIMIT 5
");

/* ================= STATISTIK ================= */
$stats = [ 'total' => 0, 'menunggu' => 0, 'revisi' => 0, 'disetujui' => 0, 'ditolak' => 0 ];
$statsQuery = mysqli_query($koneksi, "
    SELECT 
        COUNT(*) as total,
        SUM(status = 'Menunggu Review') as menunggu,
        SUM(status = 'Revisi') as revisi,
        SUM(status = 'Disetujui Pengawas') as disetujui,
        SUM(status = 'Ditolak') as ditolak
    FROM form_izin_pekerjaan 
    WHERE kontraktor_id = $kontraktor_id
");
if ($statsQuery) {
    $stats = mysqli_fetch_assoc($statsQuery) ?: $stats;
}

$total     = $stats['total']    ?? 0;
$menunggu  = $stats['menunggu'] ?? 0;
$revisi    = $stats['revisi']   ?? 0;
$disetujui = $stats['disetujui']?? 0;
$ditolak   = $stats['ditolak']  ?? 0;

/* ================= ACTIVITY ================= */
$activity = mysqli_query($koneksi, "
    SELECT jenis_pekerjaan, status, created_at 
    FROM form_izin_pekerjaan
    WHERE kontraktor_id = $kontraktor_id
    ORDER BY created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Kontraktor | CV Cipta Manunggal Konsultan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
        gap: 8px;
    }

    .sidebar nav a {
        text-decoration: none;
        color: #cccccc;
        padding: 10px 12px;
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

    .sidebar nav a.logout {
        margin-top: 20px;
        background: #2a2a2a;
    }

    .notif-badge {
        background: #ef4444;
        color: white;
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
    }

    /* NOTIF LIST */
    .notif-divider {
        font-size: 11px;
        color: #555;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px 0 8px;
    }

    .notif-item {
        background: #222;
        border: 1px solid rgba(255,255,255,0.05);
        padding: 10px 12px;
        margin-bottom: 6px;
        border-radius: 8px;
        font-size: 12px;
        color: #ccc;
        line-height: 1.5;
    }

    .notif-item small { color: #666; display: block; margin-top: 4px; }

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
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }

    .stat-card {
        background: #1c1c1c;
        padding: 28px 20px;
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,0.05);
        transition: 0.3s;
        position: relative;
        overflow: hidden;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 4px;
    }

    .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.3);
    }

    .stat-card h3 {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .stat-card p {
        font-size: 12px;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .stat-card.total::before   { background: linear-gradient(90deg, #ffc107, #ffb300); }
    .stat-card.total h3        { color: #ffc107; }

    .stat-card.warning::before { background: linear-gradient(90deg, #ff9800, #ff7500); }
    .stat-card.warning h3      { color: #ff9800; }

    .stat-card.revisi::before  { background: linear-gradient(90deg, #f59e0b, #d97706); }
    .stat-card.revisi h3       { color: #f59e0b; }

    .stat-card.success::before { background: linear-gradient(90deg, #22c55e, #16a34a); }
    .stat-card.success h3      { color: #22c55e; }

    .stat-card.danger::before  { background: linear-gradient(90deg, #ef4444, #dc2626); }
    .stat-card.danger h3       { color: #ef4444; }

    /* ── ACTIVITY ── */
    .activity-section {
        background: #1c1c1c;
        border: 1px solid rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 28px;
    }

    .section-title {
        font-size: 15px;
        font-weight: 600;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }

    .section-title::before {
        content: '';
        display: inline-block;
        width: 3px; height: 15px;
        background: #ffc107;
        border-radius: 2px;
    }

    .activity-item {
        background: #111;
        border: 1px solid rgba(255,255,255,0.04);
        padding: 14px 16px;
        border-radius: 8px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .activity-item:last-child { margin-bottom: 0; }

    .activity-item b { font-size: 14px; display: block; margin-bottom: 4px; }
    .activity-item small { font-size: 12px; color: #666; }

    /* ── STATUS BADGE ── */
    .status-badge {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .badge-menunggu  { background: rgba(255,152,0,0.2);   color: #ff9800; }
    .badge-revisi    { background: rgba(245,158,11,0.2);  color: #f59e0b; }
    .badge-disetujui { background: rgba(34,197,94,0.2);   color: #22c55e; }
    .badge-ditolak   { background: rgba(239,68,68,0.2);   color: #ef4444; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1024px) {
        .sidebar { width: 220px; }
        .main-content { padding: 30px; }
    }

    @media (max-width: 768px) {
        .dashboard-container { flex-direction: column; }

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
            flex-wrap: wrap;
            gap: 8px;
        }

        .sidebar nav a {
            flex: 1;
            min-width: 80px;
            text-align: center;
            justify-content: center;
        }

        .main-content { padding: 20px; }

        .topbar {
            flex-direction: column;
            gap: 16px;
            align-items: flex-start;
        }

        .stats { grid-template-columns: repeat(2, 1fr); }
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
            <a href="#" class="active">Dashboard</a>
            <a href="AjukanIzin.php">Ajukan Izin</a>
            <a href="LihatStatus.php">
                Status Izin
                <?php if($revisi > 0): ?>
                    <span class="notif-badge"><?= $revisi ?></span>
                <?php endif; ?>
            </a>
            <a href="#">
                Notifikasi
                <?php if($jumlah_notif > 0): ?>
                    <span class="notif-badge"><?= $jumlah_notif ?></span>
                <?php endif; ?>
            </a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>

        <!-- NOTIF LIST -->
        <?php if(mysqli_num_rows($notif_query) > 0): ?>
            <div class="notif-divider">Notifikasi Terbaru</div>
            <?php while($n = mysqli_fetch_assoc($notif_query)): ?>
                <a href="detail_notifikasi.php?id=<?= $n['id'] ?>" class="notif-item" style="display: block; text-decoration: none; color: inherit;">
                    <?= htmlspecialchars($n['pesan']) ?>
                    <small><?= date('d M H:i', strtotime($n['created_at'])) ?></small>
                </a>
            <?php endwhile; ?>
        <?php endif; ?>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div>
                <h1>Dashboard Kontraktor</h1>
                <p>Halo, <?= htmlspecialchars($_SESSION['username']) ?> — pantau status pengajuan izin pekerjaan Anda</p>
            </div>
            <div class="role-badge">KONTRAKTOR</div>
        </header>

        <!-- STATS -->
        <section class="stats">
            <div class="stat-card total">
                <h3><?= $total ?></h3>
                <p>Total Pengajuan</p>
            </div>
            <div class="stat-card warning">
                <h3><?= $menunggu ?></h3>
                <p>Menunggu Review</p>
            </div>
            <div class="stat-card revisi">
                <h3><?= $revisi ?></h3>
                <p>Minta Revisi</p>
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

        <!-- ACTIVITY -->
        <section class="activity-section">
            <h2 class="section-title">Aktivitas Terbaru</h2>

            <?php
            $has_activity = false;
            while($row = mysqli_fetch_assoc($activity)):
                $has_activity = true;
                $sl = strtolower($row['status']);
                $bc = 'badge-menunggu';
                if(strpos($sl, 'menunggu')      !== false) $bc = 'badge-menunggu';
                elseif(strpos($sl, 'revisi')    !== false) $bc = 'badge-revisi';
                elseif(strpos($sl, 'disetujui') !== false) $bc = 'badge-disetujui';
                elseif(strpos($sl, 'ditolak')   !== false) $bc = 'badge-ditolak';
            ?>
                <div class="activity-item">
                    <div>
                        <b><?= htmlspecialchars($row['jenis_pekerjaan']) ?></b>
                        <small><?= date('d M Y H:i', strtotime($row['created_at'])) ?></small>
                    </div>
                    <span class="status-badge <?= $bc ?>"><?= htmlspecialchars($row['status']) ?></span>
                </div>
            <?php endwhile; ?>

            <?php if(!$has_activity): ?>
                <p style="color:#666; text-align:center; padding:20px;">Belum ada aktivitas pengajuan</p>
            <?php endif; ?>
        </section>

    </main>
</div>
</body>
</html>