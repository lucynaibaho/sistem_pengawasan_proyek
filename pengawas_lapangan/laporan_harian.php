<?php
session_start();
include "../koneksi.php";

/* ================= CEK ROLE ================= */
if (!isset($_SESSION['role']) || $_SESSION['role'] != "pengawas") {
    header("Location: ../login.php");
    exit;
}

if (!isset($koneksi) || !$koneksi) {
    die("Koneksi database gagal. Periksa file koneksi.php dan jalankan MySQL.");
}

/* ================= SIMPAN DATA ================= */
if(isset($_POST['submit'])){

    $tanggal = $_POST['tanggal'];
    $progres = $_POST['progres'];
    $cuaca = $_POST['cuaca'];
    $tenaga = $_POST['tenaga'];
    $logistik = $_POST['logistik'];
    $alat = $_POST['alat'];
    $kendala = $_POST['kendala'];
    $catatan = $_POST['catatan'];

    // VALIDASI
    if(
        empty($tanggal) || empty($progres) || empty($cuaca) ||
        empty($tenaga) || empty($logistik) || empty($alat)
    ){
        $error = "Semua field utama wajib diisi!";
    } else {

        $stmt = mysqli_prepare($koneksi, "
        INSERT INTO laporan_harian 
        (id, tanggal, progres, cuaca, tenaga_kerja, logistik, alat, kendala, catatan)
        VALUES (NULL,?,?,?,?,?,?,?,?)
        ");

        if (!$stmt) {
            $error = "Gagal menyiapkan query: " . mysqli_error($koneksi);
        } else {
            mysqli_stmt_bind_param($stmt,"ssssssss",
                $tanggal,$progres,$cuaca,$tenaga,$logistik,$alat,$kendala,$catatan
            );

            if (!mysqli_stmt_execute($stmt)) {
                $error = "Gagal menyimpan laporan: " . mysqli_stmt_error($stmt);
            }

            // 🔥 AMBIL ID TERAKHIR
            $last_id = mysqli_insert_id($koneksi);
        }

        if (!isset($last_id)) {
            $last_id = 0;
        }

        if ($last_id) {
            // 🔥 REDIRECT KE HALAMAN DOKUMENTASI
            header("Location: dokumentasi_laporan.php?id=".$last_id);
            exit;
        } else {
            $error = "Gagal menyimpan laporan!";
        }
    }
}

/* ================= AMBIL DATA ================= */
$data = mysqli_query($koneksi,"
SELECT * FROM laporan_harian
ORDER BY tanggal DESC
");
if (!$data) {
    die("Query laporan harian gagal: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Laporan Harian | CV Cipta Manunggal Konsultan</title>
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

/* ── CARD ── */
.card {
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

/* ── FORM ── */
.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

.form-field {
    display: flex;
    flex-direction: column;
}

.form-field label {
    font-size: 12px;
    color: #888;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 6px;
}

.form-field input,
.form-field textarea,
.form-field select {
    background: #0f0f0f;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    padding: 12px 14px;
    color: #fff;
    font-family: 'Inter', sans-serif;
    font-size: 13px;
    transition: 0.3s;
}

.form-field input:focus,
.form-field textarea:focus,
.form-field select:focus {
    outline: none;
    border-color: #ffc107;
    box-shadow: 0 0 8px rgba(255, 193, 7, 0.2);
}

.form-field textarea {
    min-height: 80px;
    resize: vertical;
}

.full-width {
    grid-column: span 2;
}

.button-group {
    display: flex;
    gap: 12px;
    margin-top: 24px;
}

.btn-submit {
    background: #22c55e;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: 0.3s;
    font-family: 'Inter', sans-serif;
}

.btn-submit:hover {
    background: #16a34a;
    transform: translateY(-2px);
}

/* ── TABLE ── */
.table-container {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid rgba(255,255,255,0.05);
}

th {
    color: #888;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    color: #e0e0e0;
}

/* ── ALERT ── */
.alert {
    padding: 12px 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.alert-success {
    background: rgba(34, 197, 94, 0.1);
    color: #22c55e;
}

/* ── RESPONSIVE ── */
@media (max-width: 1024px) {
    .sidebar { width: 220px; }
    .main-content { padding: 30px; }
    .form-grid { grid-template-columns: 1fr; }
    .full-width { grid-column: span 1; }
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
        gap: 10px;
        flex-wrap: wrap;
    }
    .sidebar nav a {
        flex: 1;
        min-width: 80px;
        text-align: center;
    }
    .main-content { padding: 20px; }
    .topbar {
        flex-direction: column;
        gap: 16px;
        align-items: flex-start;
    }
    .card { padding: 20px; }
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
            <a href="laporan_verifikasi.php" class="active">Laporan Harian</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN -->
    <main class="main-content">

        <!-- TOPBAR -->
        <header class="topbar">
            <div>
                <h1>Laporan Harian</h1>
                <p>Kelola laporan harian pekerjaan konstruksi.</p>
            </div>
            <div class="role-badge">PENGAWAS</div>
        </header>

        <!-- FORM CARD -->
        <div class="card">
            <h2 class="section-title">Tambah Laporan Harian</h2>

            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-grid">
                    <div class="form-field">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" required>
                    </div>

                    <div class="form-field">
                        <label>Progress (%)</label>
                        <input type="number" name="progres" min="0" max="100" required>
                    </div>

                    <div class="form-field">
                        <label>Cuaca</label>
                        <select name="cuaca" required>
                            <option value="">Pilih Cuaca</option>
                            <option value="Cerah">Cerah</option>
                            <option value="Berawan">Berawan</option>
                            <option value="Hujan">Hujan</option>
                            <option value="Badai">Badai</option>
                        </select>
                    </div>

                    <div class="form-field">
                        <label>Tenaga Kerja</label>
                        <input type="text" name="tenaga" placeholder="Jumlah dan jenis tenaga kerja" required>
                    </div>

                    <div class="form-field">
                        <label>Logistik</label>
                        <input type="text" name="logistik" placeholder="Ketersediaan logistik" required>
                    </div>

                    <div class="form-field">
                        <label>Alat</label>
                        <input type="text" name="alat" placeholder="Ketersediaan alat" required>
                    </div>

                    <div class="form-field full-width">
                        <label>Kendala</label>
                        <textarea name="kendala" placeholder="Jelaskan kendala yang dihadapi"></textarea>
                    </div>

                    <div class="form-field full-width">
                        <label>Catatan</label>
                        <textarea name="catatan" placeholder="Catatan tambahan"></textarea>
                    </div>
                </div>

                <div class="button-group">
                    <button type="submit" name="submit" class="btn-submit">Simpan Laporan</button>
                </div>
            </form>
        </div>

        <!-- TABLE CARD -->
        <div class="card">
            <h2 class="section-title">Riwayat Laporan Harian</h2>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Progress</th>
                            <th>Cuaca</th>
                            <th>Tenaga Kerja</th>
                            <th>Logistik</th>
                            <th>Alat</th>
                            <th>Kendala</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = mysqli_fetch_assoc($data)): ?>
                            <tr>
                                <td><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                <td><?= $row['progres'] ?>%</td>
                                <td><?= htmlspecialchars($row['cuaca']) ?></td>
                                <td><?= htmlspecialchars($row['tenaga_kerja']) ?></td>
                                <td><?= htmlspecialchars($row['logistik']) ?></td>
                                <td><?= htmlspecialchars($row['alat']) ?></td>
                                <td><?= htmlspecialchars($row['kendala'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['catatan'] ?: '-') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>
</body>
</html>