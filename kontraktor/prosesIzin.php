<?php
session_start();
require_once '../koneksi.php';

if (!isset($koneksi) || !$koneksi) {
    die('Koneksi database gagal: ' . mysqli_connect_error());
}

/* ================== CEK LOGIN ================== */
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'kontraktor') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_SESSION['id'])) {
    die("Session kontraktor tidak ditemukan. Silakan login ulang.");
}

$kontraktor_id = $_SESSION['id'];

/* ================== PROSES FORM ================== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    /* Ambil data dengan pengamanan */
    $jenis     = $_POST['jenis_pekerjaan'] ?? '';
    $volume    = $_POST['volume'] ?? '';
    $satuan    = $_POST['satuan'] ?? '';
    $material  = $_POST['material'] ?? '';
    $lokasi    = $_POST['lokasi'] ?? '';
    $metode    = $_POST['metode_kerja'] ?? '';
    $mulai     = $_POST['tanggal_mulai'] ?? '';
    $selesai   = $_POST['tanggal_selesai'] ?? '';
    $catatan   = $_POST['catatan'] ?? NULL;

    /* ================== VALIDASI TANGGAL ================== */
    if ($selesai < $mulai) {
        echo "<script>alert('Tanggal selesai tidak boleh sebelum tanggal mulai'); window.history.back();</script>";
        exit();
    }

    /* ================== UPLOAD DOKUMEN ================== */
    $nama_file = NULL;

    if (isset($_FILES['dokumen']) && $_FILES['dokumen']['error'] == 0) {

        $folder = "../uploads/";

        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $allowed = ['pdf','doc','docx','jpg','png'];
        $ext = strtolower(pathinfo($_FILES['dokumen']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed)) {
            die("Format file tidak diizinkan.");
        }

        $nama_file = time() . "_" . $_FILES['dokumen']['name'];
        move_uploaded_file($_FILES['dokumen']['tmp_name'], $folder . $nama_file);
    }

    $status = "Menunggu Review";

    /* ================== INSERT DATABASE (PREPARED STATEMENT) ================== */
    $stmt = mysqli_prepare($koneksi, "INSERT INTO form_izin_pekerjaan
        (kontraktor_id, jenis_pekerjaan, volume, satuan, material, lokasi, metode_kerja, tanggal_mulai, tanggal_selesai, dokumen, status, catatan)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die('Gagal menyiapkan query: ' . mysqli_error($koneksi));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "isssssssssss",
        $kontraktor_id,
        $jenis,
        $volume,
        $satuan,
        $material,
        $lokasi,
        $metode,
        $mulai,
        $selesai,
        $nama_file,
        $status,
        $catatan
    );

    if (mysqli_stmt_execute($stmt)) {
        echo "<script>alert('Izin berhasil dikirim ke pengawas'); window.location='dashboard.php';</script>";
    } else {
        echo "Error: " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
}
?>