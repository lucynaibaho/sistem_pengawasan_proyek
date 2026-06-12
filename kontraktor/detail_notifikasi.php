<?php
session_start();
$koneksi = null;
require_once "../koneksi.php";

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    http_response_code(400);
    die("ID tidak valid.");
}

$id      = (int) $_GET['id'];
$user_id = (int) $_SESSION['id'];

// Ambil notifikasi dulu
$stmt = mysqli_prepare($koneksi, "SELECT * FROM notifikasi WHERE id = ? AND user_id = ?");
if (!$stmt) {
    die('Query notifikasi gagal: ' . mysqli_error($koneksi));
}
mysqli_stmt_bind_param($stmt, "ii", $id, $user_id);
mysqli_stmt_execute($stmt);
$notif = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$notif) {
    http_response_code(404);
    die("Notifikasi tidak ditemukan.");
}

// Mark as read
$stmtRead = mysqli_prepare($koneksi, "UPDATE notifikasi SET status = 'read' WHERE id = ?");
if ($stmtRead) {
    mysqli_stmt_bind_param($stmtRead, "i", $id);
    mysqli_stmt_execute($stmtRead);
    mysqli_stmt_close($stmtRead);
}

// Ambil detail form
$form = null;

if (!empty($notif['form_id'])) {
    $stmtForm = mysqli_prepare($koneksi, "
        SELECT f.*, u.username AS nama_kontraktor, f.catatan AS catatan_pengawas
        FROM form_izin_pekerjaan f
        LEFT JOIN users u ON f.kontraktor_id = u.id
        WHERE f.id = ?
    ");
    mysqli_stmt_bind_param($stmtForm, "i", $notif['form_id']);
    mysqli_stmt_execute($stmtForm);
    $form = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtForm));
} else {
    preg_match("/'([^']+)'/", $notif['pesan'], $matches);
    $nama_pekerjaan = $matches[1] ?? '';

    if ($nama_pekerjaan) {
        $stmtForm = mysqli_prepare($koneksi, "
            SELECT f.*, u.username AS nama_kontraktor, f.catatan AS catatan_pengawas
            FROM form_izin_pekerjaan f
            LEFT JOIN users u ON f.kontraktor_id = u.id
            WHERE f.kontraktor_id = ? AND f.jenis_pekerjaan = ?
            ORDER BY f.created_at DESC
            LIMIT 1
        ");
        mysqli_stmt_bind_param($stmtForm, "is", $user_id, $nama_pekerjaan);
        mysqli_stmt_execute($stmtForm);
        $form = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtForm));
    }
}

function e(mixed $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$statusClassMap = [
    'disetujui pengawas' => 'disetujui',
    'disetujui'          => 'disetujui',
    'ditolak'            => 'ditolak',
    'revisi'             => 'revisi',
    'menunggu review'    => 'menunggu',
    'menunggu'           => 'menunggu',
    'verifikasi'         => 'verifikasi',
];
$statusRaw   = strtolower(trim($form['status'] ?? ''));
$statusClass = $statusClassMap[$statusRaw] ?? 'unknown';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Detail Notifikasi | CV Cipta Manunggal Konsultan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #111111; color: #ffffff; }
.dashboard-container { display: flex; min-height: 100vh; }

.sidebar {
    width: 260px; background: #1a1a1a; padding: 30px 20px;
    border-right: 1px solid rgba(255,255,255,0.05);
    position: sticky; top: 0; height: 100vh; overflow-y: auto;
}
.sidebar-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 40px; }
.logo-arch { width: 38px; height: 38px; stroke: #ffc107; stroke-width: 4; fill: none; }
.sidebar h2 { font-size: 16px; }
.sidebar span { color: #ffc107; }
.sidebar nav { display: flex; flex-direction: column; gap: 8px; }
.sidebar nav a {
    text-decoration: none; color: #cccccc; padding: 10px 12px;
    border-radius: 6px; transition: 0.3s; font-size: 14px;
}
.sidebar nav a:hover, .sidebar nav a.active { background: #ffc107; color: #111; }
.sidebar nav a.logout { margin-top: 20px; background: #2a2a2a; }

.main-content { flex: 1; padding: 50px; overflow-y: auto; }

.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
.topbar h1 { font-size: 28px; font-weight: 700; }
.topbar p  { font-size: 14px; color: #888; margin-top: 4px; }
.role-badge {
    background: #ffc107; color: #111; padding: 8px 16px;
    border-radius: 20px; font-size: 12px; font-weight: 600;
}

.btn-back {
    display: inline-flex; align-items: center; gap: 8px;
    margin-bottom: 24px; text-decoration: none; color: #888;
    font-size: 13px; transition: color 0.2s;
}
.btn-back:hover { color: #ffc107; }
.btn-back svg { width: 16px; height: 16px; stroke: currentColor; fill: none; }

.detail-container {
    background: #1c1c1c; padding: 32px;
    border-radius: 12px; max-width: 800px;
}

.detail-header {
    display: flex; justify-content: space-between; align-items: flex-start;
    margin-bottom: 28px; padding-bottom: 20px;
    border-bottom: 1px solid rgba(255,255,255,0.08);
}
.detail-header h2 { font-size: 20px; font-weight: 700; }
.detail-header p  { font-size: 13px; color: #888; margin-top: 4px; }

.info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
.info-item { background: #111; padding: 14px 16px; border-radius: 8px; }
.info-item label {
    display: block; font-size: 11px; font-weight: 600; color: #888;
    text-transform: uppercase; letter-spacing: 0.6px; margin-bottom: 6px;
}
.info-item span { font-size: 14px; color: #fff; font-weight: 500; }

.status { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.disetujui  { background:#22c55e33; color:#22c55e; }
.ditolak    { background:#ef444433; color:#ef4444; }
.revisi     { background:#f59e0b33; color:#f59e0b; }
.menunggu   { background:#3b82f633; color:#3b82f6; }
.verifikasi { background:#8b5cf633; color:#8b5cf6; }
.unknown    { background:#6b728033; color:#6b7280; }

.box { padding: 18px; background: #111; border-left: 4px solid #ffc107; border-radius: 8px; margin-bottom: 16px; }
.box h3 { font-size: 13px; font-weight: 600; color: #ffc107; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px; }
.box p  { font-size: 14px; color: #ccc; line-height: 1.6; }

.detail-footer {
    margin-top: 24px; padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.08);
    display: flex; gap: 24px; font-size: 12px; color: #666;
}

.no-form {
    text-align: center; padding: 40px 20px; color: #555;
}
.no-form p { font-size: 14px; margin-top: 8px; }
</style>
</head>

<body>
<div class="dashboard-container">

    <aside class="sidebar">
        <div class="sidebar-brand">
            <svg viewBox="0 0 120 120" class="logo-arch">
                <rect x="10" y="10" width="100" height="100" stroke="#ffc107" stroke-width="3" fill="none"/>
                <path d="M35 80 V40 H60" stroke="#ffc107" stroke-width="4" fill="none"/>
                <path d="M60 40 L75 60 L90 40 V80" stroke="#ffc107" stroke-width="4" fill="none"/>
            </svg>
            <h2>CIPTA<span>MANUNGGAL</span></h2>
        </div>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="AjukanIzin.php">Ajukan Izin</a>
            <a href="LihatStatus.php" class="active">Status Izin</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Detail Notifikasi</h1>
                <p>Informasi lengkap izin pekerjaan Anda.</p>
            </div>
            <div class="role-badge">KONTRAKTOR</div>
        </header>

        <a href="javascript:history.back()" class="btn-back">
            <svg viewBox="0 0 24 24" stroke-width="2"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Kembali
        </a>

        <div class="detail-container">

            <?php if ($form): ?>

                <div class="detail-header">
                    <div>
                        <h2><?= e($form['jenis_pekerjaan']) ?></h2>
                        <p>Diajukan pada: <?= $form['created_at'] ? date('d M Y', strtotime($form['created_at'])) : '-' ?></p>
                    </div>
                    <span class="status <?= $statusClass ?>">
                        <?= e($form['status']) ?>
                    </span>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <label>Kontraktor</label>
                        <span><?= e($form['nama_kontraktor']) ?: '-' ?></span>
                    </div>
                    <div class="info-item">
                        <label>Lokasi Pekerjaan</label>
                        <span><?= e($form['lokasi']) ?: '-' ?></span>
                    </div>
                    <div class="info-item">
                        <label>Volume</label>
                        <span><?= e($form['volume']) ?: '-' ?></span>
                    </div>
                    <div class="info-item">
                        <label>Tanggal Verifikasi</label>
                        <span>
                            <?= $form['updated_at']
                                ? date('d M Y H:i', strtotime($form['updated_at']))
                                : 'Belum diverifikasi' ?>
                        </span>
                    </div>
                </div>

                <div class="box">
                    <h3>Pesan Notifikasi</h3>
                    <p><?= e($notif['pesan']) ?: 'Tidak ada pesan.' ?></p>
                </div>

                <div class="box">
                    <h3>Catatan Pengawas Lapangan</h3>
                    <p><?= e($form['catatan_pengawas']) ?: 'Tidak ada catatan dari pengawas.' ?></p>
                </div>

            <?php else: ?>

                <div class="box">
                    <h3>Pesan Notifikasi</h3>
                    <p><?= e($notif['pesan']) ?></p>
                </div>

                <div class="no-form">
                    <p>Detail form izin tidak tersedia untuk notifikasi ini.</p>
                </div>

            <?php endif; ?>

            <div class="detail-footer">
                <span>ID Notifikasi: #<?= e($notif['id']) ?></span>
                <span>Diterima: <?= $notif['created_at'] ? date('d M Y H:i', strtotime($notif['created_at'])) : '-' ?></span>
            </div>

        </div>
    </main>
</div>
</body>
</html>