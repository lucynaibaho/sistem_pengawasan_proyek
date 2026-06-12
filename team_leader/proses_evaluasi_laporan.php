<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "teamleader") {
    header("Location: ../login.php");
    exit;
}

require_once "../koneksi.php";
if (!isset($koneksi) || !$koneksi) {
    die("Koneksi gagal: file koneksi tidak ditemukan atau koneksi database bermasalah.");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: evaluasi_laporan_bulanan.php");
    exit;
}

$laporan_id = isset($_POST['laporan_id']) ? intval($_POST['laporan_id']) : 0;
$action = $_POST['action'] ?? '';
$catatan = trim($_POST['catatan'] ?? '');

if ($laporan_id <= 0) {
    $_SESSION['error'] = "Laporan tidak valid.";
    header("Location: evaluasi_laporan_bulanan.php");
    exit;
}

$report_query = mysqli_query($koneksi, "SELECT judul, periode FROM laporan_bulanan WHERE id = $laporan_id LIMIT 1");
$report = mysqli_fetch_assoc($report_query);
if (!$report) {
    $_SESSION['error'] = "Laporan tidak ditemukan.";
    header("Location: evaluasi_laporan_bulanan.php");
    exit;
}

if ($action === 'approve') {
    $stmt = mysqli_prepare($koneksi, "UPDATE laporan_bulanan SET status = 'Disetujui', catatan_eval = ?, updated_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $catatan, $laporan_id);
    mysqli_stmt_execute($stmt);
    $_SESSION['message'] = "Laporan bulanan '{$report['judul']}' telah disetujui.";
    header("Location: evaluasi_laporan_bulanan.php");
    exit;
}

if ($action === 'revision') {
    if ($catatan === '') {
        $_SESSION['error'] = "Catatan revisi harus diisi untuk meminta revisi.";
        header("Location: evaluasi_laporan_bulanan.php");
        exit;
    }

    $stmt = mysqli_prepare($koneksi, "UPDATE laporan_bulanan SET status = 'Diminta Revisi', catatan_eval = ?, updated_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'si', $catatan, $laporan_id);
    mysqli_stmt_execute($stmt);

    $message = "Team Leader meminta revisi laporan bulanan '{$report['judul']}' ({$report['periode']}). Catatan: {$catatan}";
    $koordinator_query = mysqli_query($koneksi, "SELECT id FROM users WHERE role = 'koordinator'");
    while ($koordinator = mysqli_fetch_assoc($koordinator_query)) {
        $stmt_notif = mysqli_prepare($koneksi, "INSERT INTO notifikasi (user_id, form_id, pesan, status, created_at) VALUES (?, ?, ?, 'unread', NOW())");
        mysqli_stmt_bind_param($stmt_notif, 'iis', $koordinator['id'], $laporan_id, $message);
        mysqli_stmt_execute($stmt_notif);
    }

    $_SESSION['message'] = "Permintaan revisi telah dikirim ke Koordinator Pengawas.";
    header("Location: evaluasi_laporan_bulanan.php");
    exit;
}

$_SESSION['error'] = "Aksi tidak valid.";
header("Location: evaluasi_laporan_bulanan.php");
exit;
