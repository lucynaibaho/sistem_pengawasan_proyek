<?php
session_start();
include "koneksi.php";

$username = $_POST['username'];
$password = $_POST['password'];

$query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username'");
$data = mysqli_fetch_assoc($query);

if ($data) {
    if ($password == $data['password']) {

        $_SESSION['id'] = $data['id'];
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];

        // Redirect sesuai role
        if ($data['role'] == "kontraktor") {
            header("Location: kontraktor/dashboard.php");
            exit;
        } elseif ($data['role'] == "pengawas") {
            header("Location: pengawas_lapangan/pengawas_lapangan.php");
            exit;
        } elseif ($data['role'] == "koordinator") {
            header("Location: koordinator_pengawas/koordinator_pengawas.php");
            exit;
        } elseif ($data['role'] == "teamleader") {
            header("Location: team_leader/teamleader.php");
            exit;
        } else {
            $_SESSION['error'] = "Role tidak valid, periksa kembali username dan password!";
            header("Location: login.php");
            exit;
        }

    } else {
        $_SESSION['error'] = "Password salah!";
        header("Location: login.php");
        exit;
    }
} else {
    $_SESSION['error'] = "Username tidak ditemukan!";
    header("Location: login.php");
    exit;
}
?>