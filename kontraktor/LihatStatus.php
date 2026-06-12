<?php
session_start();
$koneksi = null;
if (!isset($_SESSION['role']) || $_SESSION['role'] != "kontraktor") {
    header("Location: ../login.php");
    exit;
}

require_once '../koneksi.php'; 

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

if (!isset($_SESSION['id'])) {
    die("Session kontraktor tidak ditemukan. Silakan login ulang.");
}

$kontraktor_id = $_SESSION['id']; 

// Ambil semua pengajuan izin milik kontraktor ini menggunakan MySQLi
$query = "SELECT * FROM form_izin_pekerjaan 
          WHERE kontraktor_id = ? 
          ORDER BY created_at DESC";

$stmt = mysqli_prepare($koneksi, $query);
if (!$stmt) {
    die('Query gagal: ' . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmt, "i", $kontraktor_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$izin_list = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

// Hitung statistik
$total    = count($izin_list);
$disetujui = 0; $pending = 0; $ditolak = 0; $review = 0;
foreach ($izin_list as $row) {
    $s = strtolower($row['status'] ?? '');
    if (str_contains($s, 'setuju') || str_contains($s, 'approved')) $disetujui++;
    elseif (str_contains($s, 'tolak') || str_contains($s, 'reject'))  $ditolak++;
    elseif (str_contains($s, 'review'))                                $review++;
    else                                                               $pending++;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Izin | CV Cipta Manunggal Konsultan</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }

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

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }

        .topbar h1 { font-size: 24px; font-weight: 700; }
        .topbar p  { font-size: 14px; color: #888; margin-top: 4px; }

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
            padding: 24px;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: 0.3s;
        }

        .stat-card:hover {
            border-color: #ffc107;
            transform: translateY(-4px);
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: #ffc107;
        }

        .stat-card p {
            font-size: 13px;
            color: #888;
            margin-top: 4px;
        }

        /* ── FILTER BAR ── */
        .filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            flex-wrap: wrap;
        }

        .filter-bar input {
            flex: 1;
            min-width: 220px;
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            outline: none;
            transition: 0.3s;
        }

        .filter-bar input:focus {
            border-color: #ffc107;
        }

        .filter-bar select {
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 14px;
            cursor: pointer;
            outline: none;
            transition: 0.3s;
        }

        .filter-bar select:focus {
            border-color: #ffc107;
        }

        /* ── CARDS GRID ── */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 22px;
        }

        .izin-card {
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.06);
            border-radius: 14px;
            padding: 24px;
            transition: 0.3s;
            position: relative;
            overflow: hidden;
        }

        .izin-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            border-radius: 14px 0 0 14px;
        }

        .izin-card.status-disetujui::before { background: #22c55e; }
        .izin-card.status-pending::before   { background: #ffc107; }
        .izin-card.status-ditolak::before   { background: #ef4444; }
        .izin-card.status-review::before    { background: #3b82f6; }

        .izin-card:hover {
            border-color: rgba(255,193,7,0.3);
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(0,0,0,0.4);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .card-id {
            font-size: 11px;
            color: #555;
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .status-badge {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-disetujui { background: rgba(34,197,94,0.15);  color: #22c55e; }
        .badge-pending   { background: rgba(255,193,7,0.15);  color: #ffc107; }
        .badge-ditolak   { background: rgba(239,68,68,0.15);  color: #ef4444; }
        .badge-review    { background: rgba(59,130,246,0.15); color: #60a5fa; }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 12px;
            line-height: 1.4;
        }

        .card-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 16px;
            margin-bottom: 16px;
        }

        .meta-item label {
            display: block;
            font-size: 10px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            margin-bottom: 2px;
        }

        .meta-item span {
            font-size: 13px;
            color: #ccc;
        }

        .card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 14px;
            border-top: 1px solid rgba(255,255,255,0.05);
        }

        .card-footer .date {
            font-size: 11px;
            color: #555;
        }

        .btn-detail {
            font-size: 12px;
            font-weight: 600;
            color: #ffc107;
            text-decoration: none;
            padding: 6px 14px;
            border: 1px solid rgba(255,193,7,0.3);
            border-radius: 6px;
            transition: 0.3s;
        }

        .btn-detail:hover {
            background: #ffc107;
            color: #111;
        }

        .catatan-box {
            background: rgba(255,255,255,0.04);
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 14px;
            font-size: 13px;
            color: #aaa;
            border-left: 2px solid rgba(255,193,7,0.4);
        }

        .catatan-box strong {
            color: #ffc107;
            font-size: 11px;
            display: block;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ── EMPTY STATE ── */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #444;
        }

        .empty-state svg {
            width: 64px;
            height: 64px;
            stroke: #333;
            margin-bottom: 20px;
        }

        .empty-state p {
            font-size: 15px;
        }

        /* ── SECTION TITLE ── */
        .section-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title span {
            background: rgba(255,193,7,0.1);
            color: #ffc107;
            font-size: 13px;
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 500;
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
            <a href="../kontraktor/dashboard.php">Dashboard</a>
            <a href="../kontraktor/AjukanIzin.php">Ajukan Izin</a>
            <a href="../menu_Kontraktor/LihatStatus.php" class="active">Status Izin</a>
            <a href="#">Riwayat</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Status Izin Pekerjaan</h1>
                <p>Pantau semua pengajuan izin yang telah kamu kirimkan</p>
            </div>
            <div class="role-badge">KONTRAKTOR</div>
        </header>

        <!-- STATISTIK -->
        <section class="stats">
            <div class="stat-card">
                <h3><?= $total ?></h3>
                <p>Total Pengajuan</p>
            </div>
            <div class="stat-card">
                <h3><?= $pending ?></h3>
                <p>Menunggu</p>
            </div>
            <div class="stat-card">
                <h3><?= $review ?></h3>
                <p>Dalam Review</p>
            </div>
            <div class="stat-card">
                <h3><?= $disetujui ?></h3>
                <p>Disetujui</p>
            </div>
            <div class="stat-card">
                <h3><?= $ditolak ?></h3>
                <p>Ditolak</p>
            </div>
        </section>

        <!-- FILTER -->
        <div class="filter-bar">
            <input type="text" id="searchInput" placeholder="🔍  Cari pekerjaan, material, lokasi...">
            <select id="filterStatus">
                <option value="">Semua Status</option>
                <option value="disetujui">Disetujui</option>
                <option value="review">Dalam Review</option>
                <option value="pending">Menunggu</option>
                <option value="ditolak">Ditolak</option>
            </select>
        </div>

        <!-- CARDS -->
        <div class="section-title">
            Riwayat Pengajuan <span id="countLabel"><?= $total ?> izin</span>
        </div>

        <?php if (empty($izin_list)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                </svg>
                <p>Belum ada pengajuan izin yang ditemukan.</p>
            </div>
        <?php else: ?>
            <div class="cards-grid" id="cardsGrid">
                <?php foreach ($izin_list as $row):
                    $status_raw = strtolower($row['status'] ?? 'pending');
                    if (str_contains($status_raw, 'setuju') || str_contains($status_raw, 'approved')) {
                        $status_key = 'disetujui'; $badge_class = 'badge-disetujui'; $status_label = 'Disetujui';
                    } elseif (str_contains($status_raw, 'tolak') || str_contains($status_raw, 'reject')) {
                        $status_key = 'ditolak'; $badge_class = 'badge-ditolak'; $status_label = 'Ditolak';
                    } elseif (str_contains($status_raw, 'review')) {
                        $status_key = 'review'; $badge_class = 'badge-review'; $status_label = 'Dalam Review';
                    } else {
                        $status_key = 'pending'; $badge_class = 'badge-pending'; $status_label = 'Menunggu';
                    }

                    $tgl_mulai    = $row['tanggal_mulai']    ? date('d M Y', strtotime($row['tanggal_mulai']))    : '-';
                    $tgl_selesai  = $row['tanggal_selesai']  ? date('d M Y', strtotime($row['tanggal_selesai']))  : '-';
                    $created_at   = $row['created_at']       ? date('d M Y, H:i', strtotime($row['created_at']))  : '-';
                ?>
                <div class="izin-card status-<?= $status_key ?>" data-status="<?= $status_key ?>" data-search="<?= strtolower(htmlspecialchars($row['jenis_pekerjaan'].' '.$row['material'].' '.$row['lokasi'])) ?>">

                    <div class="card-header">
                        <span class="card-id">#IZN-<?= str_pad($row['id'], 4, '0', STR_PAD_LEFT) ?></span>
                        <span class="status-badge <?= $badge_class ?>"><?= $status_label ?></span>
                    </div>

                    <div class="card-title"><?= htmlspecialchars($row['jenis_pekerjaan']) ?></div>

                    <div class="card-meta">
                        <div class="meta-item">
                            <label>Material</label>
                            <span><?= htmlspecialchars($row['material'] ?? '-') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Lokasi</label>
                            <span><?= htmlspecialchars($row['lokasi'] ?? '-') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Volume</label>
                            <span><?= htmlspecialchars($row['volume'] ?? '-') ?> <?= htmlspecialchars($row['satuan'] ?? '') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Metode Kerja</label>
                            <span><?= htmlspecialchars($row['metode_kerja'] ?? '-') ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Mulai</label>
                            <span><?= $tgl_mulai ?></span>
                        </div>
                        <div class="meta-item">
                            <label>Selesai</label>
                            <span><?= $tgl_selesai ?></span>
                        </div>
                    </div>

                    <?php if (isset($row['catatan']) && !empty($row['catatan'])): ?>
                    <div class="catatan-box">
                        <strong>Catatan Pengawas</strong>
                        <?= htmlspecialchars($row['catatan']) ?>
                    </div>
                    <?php endif; ?>

                    <div class="card-footer">
                        <span class="date">Diajukan: <?= $created_at ?></span>
                        <?php if (!empty($row['dokumen'])): ?>
                            <a href="../uploads/<?= htmlspecialchars($row['dokumen']) ?>" target="_blank" class="btn-detail">Lihat Dokumen</a>
                        <?php else: ?>
                            <span style="font-size:12px;color:#444">Tanpa Dokumen</span>
                        <?php endif; ?>
                    </div>

                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </main>
</div>

<script>
    const searchInput  = document.getElementById('searchInput');
    const filterStatus = document.getElementById('filterStatus');
    const cards        = document.querySelectorAll('.izin-card');
    const countLabel   = document.getElementById('countLabel');

    function applyFilter() {
        const q      = searchInput.value.toLowerCase();
        const status = filterStatus.value;
        let visible  = 0;

        cards.forEach(card => {
            const matchSearch = !q || card.dataset.search.includes(q);
            const matchStatus = !status || card.dataset.status === status;
            const show = matchSearch && matchStatus;
            card.style.display = show ? '' : 'none';
            if (show) visible++;
        });

        countLabel.textContent = visible + ' izin';
    }

    searchInput.addEventListener('input', applyFilter);
    filterStatus.addEventListener('change', applyFilter);
</script>

</body>
</html>