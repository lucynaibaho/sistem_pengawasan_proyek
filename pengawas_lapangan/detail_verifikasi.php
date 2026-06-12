<?php
session_start();
$error = '';
$data = null;
$queryFoto = null;
$koneksi = null;

require_once "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "pengawas") {
    header("Location: ../login.php");
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    $error = 'ID tidak ditemukan.';
}

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

if (empty($error)) {
    $stmt = mysqli_prepare($koneksi, "SELECT f.*, u.username FROM form_izin_pekerjaan f LEFT JOIN users u ON f.kontraktor_id = u.id WHERE f.id = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);
    } else {
        $error = 'Query gagal: ' . mysqli_error($koneksi);
    }

    if (empty($error)) {
        $stmt = mysqli_prepare($koneksi, "SELECT * FROM laporan_harian WHERE id = ? ORDER BY tanggal DESC");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $queryFoto = mysqli_stmt_get_result($stmt);
            mysqli_stmt_close($stmt);
        } else {
            $queryFoto = null;
        }
    }

    if (empty($error) && !$data) {
        $error = 'Data tidak ditemukan.';
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error)) {

    $action = $_POST['action'] ?? '';
    $catatan = $_POST['catatan'] ?? '';
    $new_status_escaped = '';

    if ($action == 'approve') {
        $new_status = 'Disetujui Pengawas';
    } elseif ($action == 'reject') {
        $new_status = 'Ditolak';
    } elseif ($action == 'revisi') {
        $new_status = 'Revisi';
    } else {
        $new_status = null;
    }

    if ($new_status) {
        $stmt = mysqli_prepare($koneksi, "UPDATE form_izin_pekerjaan SET status = ?, catatan = ? WHERE id = ?");
        if ($stmt) {
            $new_status_escaped = $new_status;
            mysqli_stmt_bind_param($stmt, 'ssi', $new_status_escaped, $catatan, $id);
            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Gagal memperbarui status: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Gagal mempersiapkan update status.';
        }
    }

    if (empty($error) && $new_status) {
        $kontraktor_id = (int)($data['kontraktor_id'] ?? 0);
        $jenis = $data['jenis_pekerjaan'] ?? '';
        $pesan = "Status izin pekerjaan '$jenis' telah diperbarui menjadi '$new_status'.";

        $stmt = mysqli_prepare($koneksi, "INSERT INTO notifikasi (user_id, pesan, form_id) VALUES (?, ?, ?)");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'isi', $kontraktor_id, $pesan, $id);
            if (!mysqli_stmt_execute($stmt)) {
                $error = 'Gagal menyimpan notifikasi: ' . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $error = 'Gagal mempersiapkan notifikasi.';
        }
    }

    if (empty($error) && $new_status) {
        header("Location: verifikasi_lapangan.php");
        exit;
    }
}

// Export PDF
if (empty($error) && isset($_GET['export']) && $_GET['export'] == 'pdf') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Detail Izin - <?= $data['jenis_pekerjaan'] ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
            .title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
            .subtitle { font-size: 12px; color: #666; }
            table { width: 100%; margin-bottom: 20px; }
            td { padding: 10px; border-bottom: 1px solid #ddd; }
            td.label { font-weight: bold; width: 30%; }
            .section-title { font-weight: bold; font-size: 14px; margin-top: 15px; margin-bottom: 10px; background: #f5f5f5; padding: 8px; }
            .signature { margin-top: 40px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; }
            .sig-box { text-align: center; }
            .sig-line { border-top: 1px solid #000; margin-bottom: 5px; }
        </style>
    </head>
    <body>
        <div class="header">
            <div class="title">DETAIL IZIN PEKERJAAN</div>
            <div class="subtitle">CV CIPTA MANUNGGAL KONSULTAN</div>
        </div>

        <div class="section-title">INFORMASI IZIN</div>
        <table>
            <tr>
                <td class="label">Nomor Izin</td>
                <td><?= $data['id'] ?></td>
            </tr>
            <tr>
                <td class="label">Jenis Pekerjaan</td>
                <td><?= $data['jenis_pekerjaan'] ?></td>
            </tr>
            <tr>
                <td class="label">Volume</td>
                <td><?= $data['volume'] ?></td>
            </tr>
            <tr>
                <td class="label">Lokasi</td>
                <td><?= $data['lokasi'] ?></td>
            </tr>
            <tr>
                <td class="label">Material</td>
                <td><?= $data['material'] ?></td>
            </tr>
            <tr>
                <td class="label">Metode Kerja</td>
                <td><?= $data['metode_kerja'] ?></td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td><?= $data['status'] ?></td>
            </tr>
        </table>

        <div class="section-title">INFORMASI KONTRAKTOR</div>
        <table>
            <tr>
                <td class="label">Nama</td>
                <td><?= $data['username'] ?></td>
            </tr>
        </table>

        <div class="section-title">JADWAL KERJA</div>
        <table>
            <tr>
                <td class="label">Tanggal Mulai</td>
                <td><?= date('d M Y', strtotime($data['tanggal_mulai'])) ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Selesai</td>
                <td><?= date('d M Y', strtotime($data['tanggal_selesai'])) ?></td>
            </tr>
            <tr>
                <td class="label">Tanggal Pengajuan</td>
                <td><?= date('d M Y H:i', strtotime($data['created_at'])) ?></td>
            </tr>
        </table>

        <div class="section-title">CATATAN</div>
        <table>
            <tr>
                <td><?= $data['catatan'] ?? '-' ?></td>
            </tr>
        </table>

        <script>
            window.print();
        </script>
    </body>
    </html>
    <?php
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Izin Pekerjaan | CV Cipta Manunggal</title>
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

        /* ── TOPBAR ── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }

        .topbar h1 {
            font-size: 28px;
            font-weight: 700;
        }

        .topbar-actions {
            display: flex;
            gap: 12px;
        }

        .btn-back {
            background: #2a2a2a;
            color: #cccccc;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn-back:hover {
            background: #ffc107;
            color: #111;
        }

        .btn-pdf {
            background: transparent;
            color: #60a5fa;
            padding: 10px 16px;
            border: 1px solid #60a5fa;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
        }

        .btn-pdf:hover {
            background: rgba(96, 165, 250, 0.1);
        }

        /* ── DETAIL CARD ── */
        .detail-card {
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 32px;
            margin-bottom: 28px;
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
            width: 3px;
            height: 15px;
            background: #ffc107;
            border-radius: 2px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .detail-field {
            display: flex;
            flex-direction: column;
        }

        .detail-field label {
            font-size: 12px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .detail-field .value {
            font-size: 14px;
            color: #e0e0e0;
            line-height: 1.6;
        }

        .full-width {
            grid-column: span 2;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            width: fit-content;
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
        .status-revisi {
                background: rgba(168, 85, 247, 0.2);
                color: #a855f7;
            }
    
            /* ── LAPORAN HARIAN ── */
            .laporan-item {
                background: #0f0f0f;
                padding: 15px;
                border-radius: 8px;
                margin-bottom: 20px;
            }
    
            .laporan-item p {
                margin-bottom: 6px;
            }
    
            .laporan-item img {
                width: 220px;
                border-radius: 8px;
                margin-top: 10px;
            }
    
            /* ── ERROR MESSAGE ── */
            .error-message {
                background: rgba(255, 68, 68, 0.1);
                color: #ff4444;
                padding: 10px;
                border-radius: 6px;
                margin-bottom: 20px;
            }

        /* ── FORM ── */
        .form-section {
            margin-top: 12px;
        }

        textarea {
            width: 100%;
            height: 120px;
            background: #0f0f0f;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 12px 14px;
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            resize: vertical;
            transition: 0.3s;
        }

        textarea:focus {
            outline: none;
            border-color: #ffc107;
            box-shadow: 0 0 8px rgba(255, 193, 7, 0.2);
        }

        .button-group {
            display: flex;
            gap: 12px;
            margin-top: 24px;
        }

        .btn-action {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 13px;
            transition: 0.3s;
            font-family: 'Inter', sans-serif;
            flex: 1;
        }

        .btn-approve {
            background: #22c55e;
            color: #fff;
        }

        .btn-approve:hover {
            background: #16a34a;
            transform: translateY(-2px);
        }

        .btn-reject {
            background: #ef4444;
            color: #fff;
        }

        .btn-reject:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        .btn-revise {
            background: #f59e0b;
            color: #fff;
        }

        .btn-revise:hover {
            background: #d97706;
            transform: translateY(-2px);
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            .sidebar {
                width: 220px;
            }

            .main-content {
                padding: 30px;
            }

            .detail-grid {
                grid-template-columns: 1fr;
            }

            .full-width {
                grid-column: span 1;
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

            .topbar-actions {
                width: 100%;
            }

            .btn-back,
            .btn-pdf {
                flex: 1;
            }

            .button-group {
                flex-direction: column;
            }

            .detail-card {
                padding: 20px;
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
            <a href="verifikasi_lapangan.php" class="active">Verifikasi</a>
            <a href="laporan_verifikasi.php">Laporan Harian</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <h1>Detail Izin Pekerjaan</h1>
            <div class="topbar-actions">
                <a href="verifikasi_lapangan.php" class="btn-back">← Kembali</a>
                <?php if (empty($error)): ?>
                    <a href="?id=<?= $data['id'] ?>&export=pdf" class="btn-pdf" target="_blank">📥 Export PDF</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if (!empty($error)): ?>
            <div class="detail-card">
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            </div>
        <?php else: ?>
            <!-- DETAIL IZIN -->
            <div class="detail-card">
            <h2 class="section-title">Informasi Izin Pekerjaan</h2>
            
            <div class="detail-grid">
                <div class="detail-field">
                    <label>Nomor Izin</label>
                    <div class="value">#<?= $data['id'] ?></div>
                </div>

                <div class="detail-field">
                    <label>Status</label>
                    <div class="value">
                        <?php
                        $status_lower = strtolower($data['status']);
                        $status_class = '';
                        if(strpos($status_lower, 'menunggu') !== false) {
                            $status_class = 'status-menunggu';
                        } elseif(strpos($status_lower, 'disetujui') !== false) {
                            $status_class = 'status-disetujui';
                        } elseif(strpos($status_lower, 'ditolak') !== false) {
                            $status_class = 'status-ditolak';
                        } elseif(strpos($status_lower, 'revisi') !== false) {
                            $status_class = 'status-revisi';
                        }
                        ?>
                        <span class="status-badge <?= $status_class ?>"><?= $data['status'] ?></span>
                    </div>
                </div>

                <div class="detail-field">
                    <label>Jenis Pekerjaan</label>
                    <div class="value"><?= htmlspecialchars($data['jenis_pekerjaan']) ?></div>
                </div>

                <div class="detail-field">
                    <label>Lokasi</label>
                    <div class="value"><?= htmlspecialchars($data['lokasi']) ?></div>
                </div>

                <div class="detail-field">
                    <label>Volume</label>
                    <div class="value"><?= htmlspecialchars($data['volume']) ?></div>
                </div>

                <div class="detail-field">
                    <label>Material</label>
                    <div class="value"><?= htmlspecialchars($data['material']) ?></div>
                </div>

                <div class="detail-field">
                    <label>Metode Kerja</label>
                    <div class="value"><?= htmlspecialchars($data['metode_kerja']) ?></div>
                </div>

                <div class="detail-field">
                    <label>Tanggal Mulai</label>
                    <div class="value"><?= date('d M Y', strtotime($data['tanggal_mulai'])) ?></div>
                </div>

                <div class="detail-field">
                    <label>Tanggal Selesai</label>
                    <div class="value"><?= date('d M Y', strtotime($data['tanggal_selesai'])) ?></div>
                </div>

                <div class="detail-field">
                    <label>Tanggal Pengajuan</label>
                    <div class="value"><?= date('d M Y H:i', strtotime($data['created_at'])) ?></div>
                </div>

                <div class="detail-field full-width">
                    <label>Catatan Kontraktor</label>
                    <div class="value"><?= htmlspecialchars($data['catatan'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <a href="laporan_harian.php?id=<?= $data['id'] ?>" 
   style="background:#22c55e;padding:10px;color:white;text-decoration:none;">
   + Tambah Laporan
</a>

        <!-- KONTRAKTOR INFO -->
        <div class="detail-card">
            <h2 class="section-title">Informasi Kontraktor</h2>
            
            <div class="detail-grid">
                <div class="detail-field">
                    <label>Nama Kontraktor</label>
                    <div class="value"><?= htmlspecialchars($data['username'] ?? '-') ?></div>
                </div>
            </div>
        </div>

        <!-- VERIFIKASI FORM -->
        <div class="detail-card">
            <h2 class="section-title">Verifikasi Izin</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message" style="color: #ff4444; background: rgba(255,68,68,0.1); padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form-section">
                <div class="detail-field full-width">
                    <label>Catatan Verifikasi</label>
                    <textarea name="catatan" placeholder="Masukkan catatan verifikasi atau alasan penolakan..."></textarea>
                </div>

                <div class="button-group">
    <button type="submit" name="action" value="approve" class="btn-action btn-approve">
        ✓ Setujui
    </button>

    <button type="submit" name="action" value="revisi" class="btn-action btn-revise" onclick="return validateRevise()">
        ↺ Revisi
    </button>

    <button type="submit" name="action" value="reject" class="btn-action btn-reject">
        ✗ Tolak
    </button>
</div>
            </form>
        </div>

        <?php endif; ?>

        <?php if (empty($error)): ?>
        <div class="detail-card">
    <h2 class="section-title">Laporan Harian</h2>

<?php if ($queryFoto && mysqli_num_rows($queryFoto) > 0): ?>
        
        <?php while($f = mysqli_fetch_assoc($queryFoto)): ?>
            
            <div style="
                margin-bottom:20px;
                padding:15px;
                background:#111;
                border-radius:10px;
            ">
                <p><b>Tanggal:</b> <?= $f['tanggal'] ?></p>
                <p><b>Progress:</b> <?= $f['progres'] ?>%</p>
                <p><b>Cuaca:</b> <?= $f['cuaca'] ?></p>

                <img src="../upload/<?= $f['foto'] ?>" 
                     style="width:220px; border-radius:8px; margin-top:10px;">
            </div>

        <?php endwhile; ?>

    <?php else: ?>
        <p style="color:#888;">Belum ada laporan harian</p>
    <?php endif; ?>
</div>
        <?php endif; ?>
    </main>
</div>

<script>
function validateRevise() {
    const catatan = document.querySelector('textarea[name="catatan"]').value.trim();
    if (!catatan) {
        alert('Catatan teknis wajib diisi untuk permintaan revisi.');
        return false;
    }
    return true;
}
</script>

</body>
</html>