<?php
function catat_log($koneksi, $id_user, $aktivitas) {
    $id_user = mysqli_real_escape_string($koneksi, $id_user);
    $aktivitas = mysqli_real_escape_string($koneksi, $aktivitas);
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user', '$aktivitas')");
}
?>