<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "pengawas") {
    header("Location: ../login.php");
    exit;
}

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Periksa file koneksi.php dan pastikan MySQL berjalan.");
}

/* ================= AMBIL DATA ================= */
// Ambil semua izin pekerjaan
$izin_query = mysqli_query($koneksi, "SELECT * FROM form_izin_pekerjaan ORDER BY tanggal_mulai DESC");
if (!$izin_query) {
    die("Query izin pekerjaan gagal: " . mysqli_error($koneksi));
}
$izin_list = [];
while ($i = mysqli_fetch_assoc($izin_query)) {
    $izin_list[] = $i;
}

// Ambil semua laporan harian
$laporan_query = mysqli_query($koneksi, "SELECT * FROM laporan_harian ORDER BY tanggal DESC");
if (!$laporan_query) {
    die("Query laporan harian gagal: " . mysqli_error($koneksi));
}
$laporan_list = [];
while ($l = mysqli_fetch_assoc($laporan_query)) {
    $laporan_list[] = $l;
}

// Ambil foto untuk setiap laporan
$fotos_query = mysqli_query($koneksi, "SELECT * FROM dokumentasi_lapangan");
if (!$fotos_query) {
    die("Query dokumentasi lapangan gagal: " . mysqli_error($koneksi));
}
$foto_map = [];
while ($f = mysqli_fetch_assoc($fotos_query)) {
    if (!isset($foto_map[$f['laporan_id']])) {
        $foto_map[$f['laporan_id']] = [];
    }
    $foto_map[$f['laporan_id']][] = $f;
}

/* ================= INTEGRASI DATA ================= */
// Fungsi untuk mencari izin yang aktif pada tanggal laporan
function getIzinAktifUntukLaporan($laporan_tanggal, $izin_list) {
    $aktif = [];
    foreach ($izin_list as $izin) {
        $tanggal_mulai = strtotime($izin['tanggal_mulai']);
        $tanggal_selesai = strtotime($izin['tanggal_selesai']);
        $laporan_ts = strtotime($laporan_tanggal);
        
        if ($laporan_ts >= $tanggal_mulai && $laporan_ts <= $tanggal_selesai) {
            $aktif[] = $izin;
        }
    }
    return $aktif;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Terintegrasi | CV Cipta Manunggal Konsultan</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; background: #111111; color: #ffffff; }
.dashboard-container { display: flex; min-height: 100vh; }

/* ── SIDEBAR ── */
.sidebar {
    width: 260px; background: #1a1a1a; padding: 30px 20px;
    border-right: 1px solid rgba(255,255,255,0.05);
    position: sticky; top: 0; height: 100vh; overflow-y: auto;
}
.sidebar-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 40px; }
.logo-arch { width: 38px; height: 38px; stroke: #ffc107; stroke-width: 4; fill: none; }
.sidebar h2 { font-size: 16px; }
.sidebar span { color: #ffc107; }
.sidebar nav { display: flex; flex-direction: column; gap: 15px; }
.sidebar nav a {
    text-decoration: none; color: #cccccc; padding: 10px;
    border-radius: 6px; transition: 0.3s; font-size: 14px;
}
.sidebar nav a:hover, .sidebar nav a.active { background: #ffc107; color: #111; }
.logout { margin-top: 30px; background: #2a2a2a; }

/* ── MAIN ── */
.main-content { flex: 1; padding: 50px; overflow-y: auto; }

/* ── TOPBAR ── */
.topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
.topbar h1 { font-size: 28px; font-weight: 700; }
.topbar p { font-size: 14px; color: #888; margin-top: 4px; }
.role-badge { background: #ffc107; color: #111; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 600; }

/* ── CARD ── */
.card {
    background: #1c1c1c; border: 1px solid rgba(255,255,255,0.05);
    border-radius: 12px; padding: 32px; margin-bottom: 28px;
}

.section-title {
    font-size: 15px; font-weight: 600; margin-bottom: 20px;
    display: flex; align-items: center; gap: 10px;
    padding-bottom: 12px; border-bottom: 1px solid rgba(255,255,255,0.05);
}
.section-title::before {
    content: ''; display: inline-block; width: 3px; height: 15px;
    background: #ffc107; border-radius: 2px;
}

/* ── INFO GRID ── */
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 16px; }
.info-item { background: #111; padding: 12px 16px; border-radius: 8px; }
.info-item label { display: block; font-size: 11px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px; }
.info-item span { font-size: 14px; color: #fff; font-weight: 500; }

/* ── BADGE ── */
.badge {
    display: inline-block; padding: 6px 12px; border-radius: 20px;
    font-size: 11px; font-weight: 600; text-transform: uppercase;
}
.badge-disetujui { background: rgba(34, 197, 94, 0.15); color: #22c55e; border: 1px solid rgba(34, 197, 94, 0.3); }
.badge-ditolak { background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3); }
.badge-menunggu { background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); }

/* ── PROGRESS ── */
.progress { margin-top: 12px; height: 4px; background: #333; border-radius: 2px; }
.progress-fill { height: 100%; background: #ffc107; border-radius: 2px; }

/* ── TIMELINE ── */
.timeline-item { display: flex; gap: 16px; margin-bottom: 24px; }
.timeline-dot {
    width: 12px; height: 12px; background: #ffc107;
    border-radius: 50%; margin-top: 4px; flex-shrink: 0;
}
.timeline-content { flex: 1; }
.timeline-date { font-size: 13px; color: #ffc107; font-weight: 600; margin-bottom: 8px; }

/* ── GALLERY ── */
.gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 12px; margin-top: 16px; }
.gallery-item { position: relative; border-radius: 8px; overflow: hidden; background: #0f0f0f; aspect-ratio: 1; }
.gallery-item img { width: 100%; height: 100%; object-fit: cover; cursor: pointer; transition: 0.3s; display: block; }
.gallery-item img:hover { transform: scale(1.05); }

/* ── IZIN BADGE ── */
.izin-item {
    background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);
    border-radius: 8px; padding: 12px; margin-bottom: 8px; font-size: 13px;
}
.izin-item strong { color: #6366f1; }

/* ── FILTER ── */
.filter-bar { display: flex; gap: 12px; margin-bottom: 24px; flex-wrap: wrap; }
.filter-btn {
    background: #2a2a2a; color: #ccc; border: 1px solid rgba(255,255,255,0.1);
    padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 12px;
    transition: 0.3s; font-family: 'Inter', sans-serif;
}
.filter-btn:hover, .filter-btn.active { background: #ffc107; color: #111; border-color: #ffc107; }

/* ── STATS ── */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 16px; margin-bottom: 28px; }
.stat-card { background: #1c1c1c; border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; padding: 20px; text-align: center; }
.stat-number { font-size: 24px; font-weight: 700; color: #ffc107; margin-bottom: 4px; }
.stat-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; }

@media (max-width: 768px) {
    .dashboard-container { flex-direction: column; }
    .sidebar { width: 100%; height: auto; position: relative; border-right: none; border-bottom: 1px solid rgba(255,255,255,0.05); padding: 20px; }
    .sidebar nav { flex-direction: row; gap: 8px; flex-wrap: wrap; }
    .sidebar nav a { flex: 1; min-width: 70px; text-align: center; }
    .main-content { padding: 20px; }
    .topbar { flex-direction: column; gap: 16px; align-items: flex-start; }
    .card { padding: 20px; }
    .info-grid { grid-template-columns: 1fr; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
}
</style>
</head>

<body>
<div class="dashboard-container">

    <!-- SIDEBAR -->
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
            <a href="pengawas_lapangan.php">Dashboard</a>
            <a href="verifikasi_lapangan.php">Verifikasi</a>
            <a href="laporan_harian.php">Laporan Harian</a>
            <a href="laporan_verifikasi.php" class="active">Terintegrasi</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div>
                <h1>📊 Laporan Terintegrasi</h1>
                <p>Integrasi data izin pekerjaan dan laporan harian dengan dokumentasi.</p>
            </div>
            <div class="role-badge">PENGAWAS</div>
        </header>

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= count($izin_list) ?></div>
                <div class="stat-label">Izin Pekerjaan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($laporan_list) ?></div>
                <div class="stat-label">Laporan Harian</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($foto_map) ?></div>
                <div class="stat-label">Laporan Terdokumentasi</div>
            </div>
        </div>

        <!-- FILTER -->
        <div class="filter-bar">
            <button class="filter-btn active" onclick="filterStatus('all')">Semua</button>
            <button class="filter-btn" onclick="filterStatus('disetujui')">✓ Disetujui</button>
            <button class="filter-btn" onclick="filterStatus('menunggu')">⏳ Menunggu</button>
            <button class="filter-btn" onclick="filterStatus('ditolak')">✗ Ditolak</button>
        </div>

        <!-- TIMELINE LAPORAN HARIAN + IZIN TERKAIT -->
        <div id="timelineContainer">
            <?php if (empty($laporan_list)): ?>
            <div class="card">
                <p style="text-align: center; color: #666;">Tidak ada laporan harian.</p>
            </div>
            <?php endif; ?>

            <?php foreach ($laporan_list as $laporan): 
                $izin_aktif = getIzinAktifUntukLaporan($laporan['tanggal'], $izin_list);
                $foto = $foto_map[$laporan['id']] ?? [];
                $foto_count = count($foto);
            ?>
            <div class="timeline-item" data-status="semua">

                <div class="timeline-dot"></div>

                <div class="timeline-content">
                    <div class="timeline-date">
                        📅 <?= date('l, d M Y', strtotime($laporan['tanggal'])) ?>
                    </div>

                    <div class="card">
                        <h3 style="margin-bottom: 16px;">Laporan Harian - Progress <?= $laporan['progres'] ?>%</h3>

                        <!-- INFO LAPORAN -->
                        <div class="info-grid">
                            <div class="info-item">
                                <label>Cuaca</label>
                                <span><?= htmlspecialchars($laporan['cuaca']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Tenaga Kerja</label>
                                <span><?= htmlspecialchars($laporan['tenaga_kerja']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Logistik</label>
                                <span><?= htmlspecialchars($laporan['logistik']) ?></span>
                            </div>
                            <div class="info-item">
                                <label>Peralatan</label>
                                <span><?= htmlspecialchars($laporan['alat']) ?></span>
                            </div>
                        </div>

                        <!-- PROGRESS BAR -->
                        <div style="margin-top: 16px;">
                            <label style="font-size: 11px; color: #888;">Progress Pekerjaan</label>
                            <div class="progress">
                                <div class="progress-fill" style="width: <?= $laporan['progres'] ?>%"></div>
                            </div>
                        </div>

                        <!-- CATATAN & KENDALA -->
                        <?php if ($laporan['kendala']): ?>
                        <div style="margin-top: 12px; padding: 12px; background: rgba(239, 68, 68, 0.1); border-radius: 8px; border-left: 3px solid #ef4444;">
                            <span style="font-size: 12px;">⚠️ <strong>Kendala:</strong> <?= htmlspecialchars($laporan['kendala']) ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if ($laporan['catatan']): ?>
                        <div style="margin-top: 12px;">
                            <span style="font-size: 12px; color: #aaa;">📝 <strong>Catatan:</strong> <?= htmlspecialchars($laporan['catatan']) ?></span>
                        </div>
                        <?php endif; ?>

                        <!-- IZIN PEKERJAAN AKTIF -->
                        <?php if (!empty($izin_aktif)): ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <h4 style="font-size: 13px; margin-bottom: 12px; color: #ffc107;">🧾 Izin Pekerjaan Aktif (<?= count($izin_aktif) ?>)</h4>
                            <?php foreach ($izin_aktif as $izin): ?>
                            <div class="izin-item">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <strong><?= htmlspecialchars($izin['jenis_pekerjaan']) ?></strong>
                                    <span class="badge badge-<?= strtolower($izin['status']) ?>">
                                        <?= ucfirst($izin['status']) ?>
                                    </span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                                    <div><span style="color: #888;">Lokasi:</span> <?= htmlspecialchars($izin['lokasi']) ?></div>
                                    <div><span style="color: #888;">Volume:</span> <?= $izin['volume'] . ' ' . $izin['satuan'] ?></div>
                                    <div><span style="color: #888;">Material:</span> <?= htmlspecialchars($izin['material']) ?></div>
                                    <div><span style="color: #888;">Metode:</span> <?= htmlspecialchars($izin['metode_kerja']) ?></div>
                                </div>
                                <?php if ($izin['catatan']): ?>
                                <div style="margin-top: 8px; font-size: 11px; color: #aaa;">
                                    📋 <?= htmlspecialchars($izin['catatan']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05); text-align: center; color: #666; font-size: 12px;">
                            ℹ️ Tidak ada izin pekerjaan yang aktif pada tanggal ini
                        </div>
                        <?php endif; ?>

                        <!-- GALLERY DOKUMENTASI -->
                        <?php if (!empty($foto)): ?>
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid rgba(255,255,255,0.05);">
                            <h4 style="font-size: 13px; margin-bottom: 12px; color: #ffc107;">📸 Dokumentasi (<?= $foto_count ?> foto)</h4>
                            <div class="gallery">
                                <?php foreach ($foto as $f): ?>
                                <div class="gallery-item">
                                    <img src="uploads/<?= htmlspecialchars($f['file_path']) ?>" 
                                         onclick="window.open(this.src)" alt="Dokumentasi">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- AKSI -->
                        <div style="margin-top: 20px; display: flex; gap: 8px;">
                            <a href="dokumentasi_laporan.php?id=<?= $laporan['id'] ?>" 
                               style="display: inline-block; background: #3b82f6; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px; transition: 0.3s;">
                                📸 Kelola Dokumentasi
                            </a>
                            <a href="laporan_harian.php" 
                               style="display: inline-block; background: #6b7280; color: #fff; padding: 8px 16px; border-radius: 6px; text-decoration: none; font-size: 12px; transition: 0.3s;">
                                📝 Edit Laporan
                            </a>
                        </div>

                    </div>

                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </main>

</div>

<script>
function filterStatus(status) {
    const items = document.querySelectorAll('.timeline-item');
    const buttons = document.querySelectorAll('.filter-btn');
    
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    items.forEach(item => {
        if (status === 'all') {
            item.style.display = 'flex';
        } else {
            item.style.display = item.dataset.status === status ? 'flex' : 'none';
        }
    });
}
</script>

</body>
</html>