<?php
session_start();
include "config/koneksi.php";

$id_peminjaman = $_GET['id'] ?? exit("ID Peminjaman tidak valid.");

$sql = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_peminjam FROM peminjaman p 
    JOIN user u ON p.id_user = u.id_user WHERE p.id_peminjaman = '$id_peminjaman'");
$t = mysqli_fetch_array($sql) or exit("Data peminjaman tidak ditemukan.");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Struk Peminjaman #PJM-<?= $id_peminjaman; ?></title>
    <link href="asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>

<body class="bg-light font-monospace" onload="window.print()">

    <div class="bg-white border p-3 mx-auto mt-3 shadow-sm" style="max-width: 300px;">

        <!-- HEADER -->
        <div class="text-center mb-2">
            <h6 class="fw-bold m-0">PERPUSTAKAAN</h6>
            <small class="text-muted d-block" style="font-size: 10px;">Bukti Pengajuan Peminjaman</small>
        </div>

        <!-- INFO TRANSAKSI -->
        <div class="border-top border-bottom py-1 my-2" style="font-size: 11px;">
            <div>No. Pinjam : #PJM-<?= $id_peminjaman; ?></div>
            <div>Tgl Pinjam : <?= date('d/m/Y H:i', strtotime($t['tgl_pinjam'])); ?></div>
            <div>Tempo &nbsp;&nbsp;&nbsp;: <?= date('d/m/Y', strtotime($t['tgl_kembali_rencana'])); ?></div>
            <div>Peminjam &nbsp;: <?= htmlspecialchars($t['nama_peminjam']); ?></div>
            <div>Status &nbsp;&nbsp;&nbsp;: <span class="fw-bold text-primary"><?= $t['status']; ?></span></div>
        </div>

        <!-- ITEM BARANG -->
        <div class="border-bottom pb-1 mb-2" style="font-size: 11px;">
            <?php
            $sql_det = mysqli_query($koneksi, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat = a.id_alat WHERE d.id_peminjaman = '$id_peminjaman'");
            while ($d = mysqli_fetch_array($sql_det)) {
            ?>
                <div class="fw-bold"><?= $d['nama_alat']; ?></div>
                <div class="d-flex justify-content-between text-secondary">
                    <span>Jumlah pinjam:</span>
                    <span><?= $d['jumlah_pinjam']; ?> unit</span>
                </div>
            <?php } ?>
        </div>

        <!-- FOOTER & TOMBOL -->
        <div class="pt-2 text-center" style="font-size: 10px;">
            <p class="m-0 fw-bold">Simpan Struk Ini Sebagai Bukti Sah!</p>
            <span class="text-muted d-block mb-2">Serahkan ke petugas bersama fisik barang.</span>

            <div class="d-print-none pt-1">
                <button onclick="window.print()" class="btn btn-primary btn-sm py-0"><i class="bi bi-printer"></i> Cetak</button>
                <button onclick="window.location.href='peminjaman.php?tab=riwayat'" class="btn btn-secondary btn-sm py-0"><i class="bi bi-arrow-left"></i> Kembali</button>
            </div>
        </div>

    </div>

</body>

</html>