<?php
session_start();
require_once "../koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (!isset($_SESSION['id']) || $_SESSION['role'] != 'kontraktor') {
    header("Location: ../login.php");
    exit;
}

$kontraktor_id = $_SESSION['id'];

// FILTER
$filter_status = $_GET['status'] ?? '';
$keyword       = $_GET['q'] ?? '';

$conditions = ['kontraktor_id=?'];
$params = [$kontraktor_id];
$types = "i";

if ($filter_status != '') {
    $conditions[] = 'status=?';
    $params[] = $filter_status;
    $types .= "s";
}

if ($keyword != '') {
    $conditions[] = '(jenis_pekerjaan LIKE ? OR lokasi LIKE ?)';
    $like = "%$keyword%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$where = implode(" AND ", $conditions);

$sql = "SELECT * FROM form_izin_pekerjaan WHERE $where ORDER BY id DESC";
$stmt = mysqli_prepare($koneksi, $sql);
if (!$stmt) {
    die('Query gagal: ' . mysqli_error($koneksi));
}
$bindParams = [$types];
foreach ($params as $key => $value) {
    $bindParams[] = &$params[$key];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
mysqli_stmt_close($stmt);

function e($str){ return htmlspecialchars($str); }

function getStatusClass($status) {
    if (strpos($status, 'Disetujui') !== false || strpos($status, 'Approved') !== false) {
        return 'status-approved';
    } elseif (strpos($status, 'Ditolak') !== false || strpos($status, 'Reject') !== false) {
        return 'status-rejected';
    } elseif (strpos($status, 'Review') !== false) {
        return 'status-review';
    } else {
        return 'status-pending';
    }
}

function getStatusText($status) {
    if (strpos($status, 'Disetujui') !== false || strpos($status, 'Approved') !== false) {
        return 'Disetujui';
    } elseif (strpos($status, 'Ditolak') !== false || strpos($status, 'Reject') !== false) {
        return 'Ditolak';
    } elseif (strpos($status, 'Review') !== false) {
        return 'Review';
    } else {
        return $status;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Izin | CV Cipta Manunggal Konsultan</title>
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

        /* ── FILTER CARD ── */
        .filter-card {
            background: #1c1c1c;
            padding: 24px;
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

        .filter-form input,
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

        .filter-form input:focus,
        .filter-form select:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 8px rgba(255, 193, 7, 0.2);
        }

        .filter-form input::placeholder {
            color: #666;
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

        .btn-export {
            background: transparent;
            color: #ffc107;
            padding: 10px 20px;
            border-radius: 8px;
            border: 1px solid #ffc107;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            text-decoration: none;
            display: inline-block;
        }

        .btn-export:hover {
            background: rgba(255, 193, 7, 0.1);
            transform: translateY(-2px);
        }

        /* ── TABLE CARD ── */
        .table-card {
            background: #1c1c1c;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            overflow: hidden;
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #0f0f0f;
            padding: 16px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        td {
            padding: 16px;
            border-bottom: 1px solid rgba(255,255,255,0.03);
            font-size: 13px;
        }

        tr:hover {
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

        .status-approved { 
            background: rgba(34, 197, 94, 0.2); 
            color: #22c55e; 
        }
        .status-pending  { 
            background: rgba(255, 193, 7, 0.2); 
            color: #ffc107; 
        }
        .status-review   { 
            background: rgba(59, 130, 246, 0.2); 
            color: #60a5fa; 
        }
        .status-rejected { 
            background: rgba(239, 68, 68, 0.2); 
            color: #ef4444; 
        }

        /* ── BUTTONS ── */
        .btn-detail {
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
        }

        .btn-detail:hover {
            background: rgba(96, 165, 250, 0.1);
        }

        /* ── MODAL ── */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: #1c1c1c;
            padding: 32px;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            border: 1px solid rgba(255,255,255,0.05);
            position: relative;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateY(30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            padding-bottom: 16px;
        }

        .modal-header h3 {
            font-size: 18px;
            font-weight: 600;
        }

        .close {
            background: transparent;
            border: none;
            color: #888;
            font-size: 24px;
            cursor: pointer;
            transition: 0.3s;
        }

        .close:hover {
            color: #fff;
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .modal-item {
            padding: 12px 0;
        }

        .modal-item-label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }

        .modal-item-value {
            font-size: 14px;
            font-weight: 500;
            color: #e0e0e0;
            word-break: break-word;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }

        .empty-state p {
            font-size: 16px;
            margin-bottom: 12px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                padding: 30px;
            }

            .filter-form {
                gap: 8px;
            }

            .filter-form input,
            .filter-form select {
                flex: 1;
                min-width: 140px;
            }

            .modal-body {
                grid-template-columns: 1fr;
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

            .main-content {
                padding: 20px;
            }

            .topbar {
                flex-direction: column;
                gap: 16px;
                align-items: flex-start;
            }

            .filter-form {
                flex-direction: column;
            }

            .filter-form input,
            .filter-form select {
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
            <a href="./dashboard.php">Dashboard</a>
            <a href="./AjukanIzin.php">Ajukan Izin</a>
            <a href="./LihatStatus.php">Status Izin</a>
            <a href="./riwayat.php" class="active">Riwayat</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Riwayat Izin</h1>
                <p>Daftar lengkap pengajuan izin pekerjaan Anda</p>
            </div>
            <div class="role-badge">KONTRAKTOR</div>
        </header>

        <!-- FILTER -->
        <div class="filter-card">
            <form method="GET" class="filter-form">
                <input type="text" name="q" placeholder="Cari jenis pekerjaan atau lokasi..." value="<?=e($keyword)?>">
                <select name="status">
                    <option value="">- Semua Status -</option>
                    <option value="Menunggu Review" <?= $filter_status === 'Menunggu Review' ? 'selected' : '' ?>>Menunggu Review</option>
                    <option value="Disetujui" <?= $filter_status === 'Disetujui' ? 'selected' : '' ?>>Disetujui</option>
                    <option value="Ditolak" <?= $filter_status === 'Ditolak' ? 'selected' : '' ?>>Ditolak</option>
                </select>
                <button type="submit" class="btn-filter">Filter</button>
                <a href="export_pdf.php" class="btn-export">Export PDF</a>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <?php if (!empty($data)): ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Jenis Pekerjaan</th>
                            <th>Lokasi</th>
                            <th>Tanggal Mulai</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($data as $i=>$row): ?>
                        <tr>
                            <td><?= $i+1 ?></td>
                            <td><?= e($row['jenis_pekerjaan']) ?></td>
                            <td><?= e($row['lokasi']) ?></td>
                            <td><?= date('d M Y', strtotime($row['tanggal_mulai'])) ?></td>
                            <td>
                                <span class="status-badge <?= getStatusClass($row['status']) ?>">
                                    <?= getStatusText($row['status']) ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn-detail" onclick="showDetail(<?= htmlspecialchars(json_encode($row)) ?>)">
                                    Detail
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <p>Belum ada data pengajuan izin</p>
                <p style="font-size: 12px; color: #555;">Mulai dengan <a href="./AjukanIzin.php" style="color: #ffc107; text-decoration: none;">mengajukan izin baru</a></p>
            </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- MODAL -->
<div id="modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Detail Izin Pekerjaan</h3>
            <button type="button" class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="modal-item">
                <div class="modal-item-label">Jenis Pekerjaan</div>
                <div class="modal-item-value" id="d_jenis">—</div>
            </div>
            <div class="modal-item">
                <div class="modal-item-label">Volume</div>
                <div class="modal-item-value" id="d_volume">—</div>
            </div>
            <div class="modal-item">
                <div class="modal-item-label">Lokasi</div>
                <div class="modal-item-value" id="d_lokasi">—</div>
            </div>
            <div class="modal-item">
                <div class="modal-item-label">Metode Kerja</div>
                <div class="modal-item-value" id="d_metode">—</div>
            </div>
            <div class="modal-item">
                <div class="modal-item-label">Status</div>
                <div class="modal-item-value" id="d_status">—</div>
            </div>
            <div class="modal-item">
                <div class="modal-item-label">Catatan</div>
                <div class="modal-item-value" id="d_catatan">—</div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(data) {
    document.getElementById('modal').classList.add('show');
    document.getElementById('d_jenis').innerText = data.jenis_pekerjaan || '—';
    document.getElementById('d_volume').innerText = data.volume || '—';
    document.getElementById('d_lokasi').innerText = data.lokasi || '—';
    document.getElementById('d_metode').innerText = data.metode_kerja || '—';
    document.getElementById('d_status').innerText = data.status || '—';
    document.getElementById('d_catatan').innerText = data.catatan || '-';
}

function closeModal() {
    document.getElementById('modal').classList.remove('show');
}

// Close modal when clicking outside
document.getElementById('modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

</body>
</html>