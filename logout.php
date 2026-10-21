<?php
session_start();
include "config/koneksi.php";

if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    $id_user_login = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? null;
    $nama_user = $_SESSION['nama'] ?? 'Pengguna';

    if ($id_user_login) {
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Pengguna $nama_user berhasil logout dari sistem')");
    }
}

session_unset();
session_destroy();
header("location: login.php");
exit();
?>