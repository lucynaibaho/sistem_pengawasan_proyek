<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != "kontraktor") {
    header("Location: ../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Ajukan Izin | CV Cipta Manunggal Konsultan</title>
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

        .topbar h1 { font-size: 28px; font-weight: 700; }
        .topbar p  { font-size: 14px; color: #888; margin-top: 4px; }

        .role-badge {
            background: #ffc107;
            color: #111;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        /* ── FORM CARD ── */
        .form-section {
            margin-bottom: 40px;
        }

        .form-card {
            background: #1c1c1c;
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 40px;
            max-width: 700px;
        }

        .form-card form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .field label {
            font-size: 13px;
            font-weight: 600;
            color: #e0e0e0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .field input,
        .field select,
        .field textarea {
            background: #252525;
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            padding: 12px 14px;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: 0.3s;
        }

        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            border-color: #ffc107;
            background: #2a2a2a;
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
        }

        .field textarea {
            resize: vertical;
            min-height: 100px;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-card button {
            background: #ffc107;
            color: #111;
            border: none;
            padding: 14px 24px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
            margin-top: 10px;
        }

        .form-card button:hover {
            background: #ffb300;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 193, 7, 0.3);
        }

        .form-card button:active {
            transform: translateY(0);
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                padding: 30px 20px;
            }
            .grid-2 {
                grid-template-columns: 1fr;
            }
            .form-card {
                padding: 24px;
                max-width: 100%;
            }
            .topbar {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
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
            <a href="./AjukanIzin.php" class="active">Ajukan Izin</a>
            <a href="./LihatStatus.php">Status Izin</a>
            <a href="#">Riwayat</a>
            <a href="../logout.php" class="logout">Logout</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">
            <div>
                <h1>Ajukan Izin Pekerjaan</h1>
                <p>Kirimkan permohonan izin pekerjaan untuk proyek konstruksi Anda</p>
            </div>
            <div class="role-badge">KONTRAKTOR</div>
        </header>

        <section class="form-section">
            <div class="form-card">

                <form method="POST" action="prosesIzin.php" enctype="multipart/form-data">

            <div class="field">
                <label>Jenis Pekerjaan</label>
                <select name="jenis_pekerjaan" required>
                    <option value="">Pilih jenis pekerjaan</option>
                    <option>Pekerjaan Persiapan</option>
                    <option>Pekerjaan Struktur</option>
                    <option>Pekerjaan Finishing</option>
                    <option>Pekerjaan Landscape</option>
                </select>
            </div>

            <div class="grid-2">
                <div>
                    <label>Volume</label>
                    <input type="number" name="volume" required>
                </div>
                <div>
                    <label>Satuan</label>
                    <select name="satuan" required>
                        <option value="">Pilih satuan</option>
                        <option>m²</option>
                        <option>m³</option>
                        <option>unit</option>
                        <option>kg</option>
                        <option>meter</option>
                    </select>
                </div>
            </div>

            <div class="field">
                <label>Material</label>
                <select name="material" required>
                    <option>Beton Ready Mix K-225</option>
                    <option>Besi Beton</option>
                    <option>Bata Ringan</option>
                    <option>Keramik</option>
                    <option>Cat Interior</option>
                </select>
            </div>

            <div class="field">
                <label>Lokasi Pekerjaan</label>
                <select name="lokasi" required>
                    <option>Lantai 1</option>
                    <option>Lantai 2</option>
                    <option>Area Parkir</option>
                    <option>Area Landscape</option>
                </select>
            </div>

            <div class="field">
                <label>Metode Kerja</label>
                <select name="metode_kerja" required>
                    <option>Manual</option>
                    <option>Semi Mekanis</option>
                    <option>Mekanis</option>
                    <option>Precast</option>
                </select>
            </div>

            <div class="grid-2">
                <div>
                    <label>Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" required>
                </div>
                <div>
                    <label>Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" required>
                </div>
            </div>

            <div class="field">
                <label>Upload Dokumen (Opsional)</label>
                <input type="file" name="dokumen" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
            </div>

            <div class="field">
                <label>Catatan Tambahan</label>
            <textarea 
            name="catatan" 
            rows="4" 
            placeholder="Tuliskan keterangan tambahan terkait pekerjaan (opsional)...">
            </textarea>
            </div>

            <button type="submit" name="submit">Ajukan Izin</button>

                </form>

            </div>
        </section>

    </main>
</div>

</body>
</html>