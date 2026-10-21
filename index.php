<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}

$tgl = date('Y-m-d');
$role = $_SESSION['role'] ?? '';
$id_user_login = $_SESSION['id_user'] ?? '';

$total_alat = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM alat"))['t'] ?? 0;
$total_kategori = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM kategori"))['t'] ?? 0;
$pinjam_hari_ini = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM peminjaman WHERE DATE(tgl_pinjam)='$tgl'"))['t'] ?? 0;
$user_count = ($role == 'admin') ? (mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as t FROM user"))['t'] ?? 0) : 0;

$q_tr = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_peminjam 
    FROM peminjaman p 
    LEFT JOIN user u ON p.id_user = u.id_user 
    WHERE DATE(p.tgl_pinjam) = '$tgl' ORDER BY p.id_peminjaman DESC");

// Query riwayat khusus untuk peminjam
$q_peminjaman_user = mysqli_query($koneksi, "SELECT * FROM peminjaman WHERE id_user = '$id_user_login' ORDER BY id_peminjaman DESC");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - Sistem Peminjaman Alat</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR DISESUAIKAN DENGAN TABEL FITUR SOAL -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white">
                <h5 class="text-center">PEMINJAMAN</h5><hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-white active bg-info rounded">Dashboard</a></li>
                    
                    <?php if ($role == 'admin'): ?>
                        <!-- FITUR ADMIN: CRUD User, Alat, Kategori, Peminjaman, Pengembalian, Log Aktivitas -->
                        <li class="nav-item mb-2"><a href="kategori.php" class="nav-link text-white">Kategori</a></li>
                        <li class="nav-item mb-2"><a href="alat.php" class="nav-link text-white">Data Barang</a></li>
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white">User</a></li>
                    <?php elseif ($role == 'petugas'): ?>
                        <!-- FITUR PETUGAS: Menyetujui Peminjaman, Memantau Pengembalian, Mencetak Laporan -->
                        <li class="nav-item mb-2"><a href="peminjaman.php?tab=riwayat" class="nav-link text-white">Persetujuan</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="laporan.php" class="nav-link text-white">Cetak Laporan</a></li>
                    <?php else: ?>
                        <!-- FITUR PEMINJAM: Melihat daftar alat, Mengajukan peminjaman, Mengembalikan alat -->
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Dashboard</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <!-- KONDISI 1: JIKA ADMIN -->
                <?php if ($role == 'admin'): ?>
                    <!-- STATISTIK ADMIN -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm border-start border-primary border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">TOTAL BARANG</small>
                                    <h3 class="fw-bold mt-1"><?= $total_alat; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm border-start border-success border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">TOTAL KATEGORI</small>
                                    <h3 class="fw-bold mt-1"><?= $total_kategori; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm border-start border-warning border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">PINJAM HARI INI</small>
                                    <h3 class="fw-bold mt-1"><?= $pinjam_hari_ini; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm border-start border-danger border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">TOTAL USER</small>
                                    <h3 class="fw-bold mt-1"><?= $user_count; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LAYOUT 2 KOLOM ADMIN (PEMINJAMAN & LOG AKTIVITAS) -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="m-0"><i class="bi bi-journal-text"></i> Peminjaman Hari Ini</h5>
                                </div>
                                <div class="card-body" style="max-height: 450px; overflow-y: auto;">
                                    <?php if (mysqli_num_rows($q_tr) > 0): $no = 1; while ($r = mysqli_fetch_assoc($q_tr)): $id_peminjaman = $r['id_peminjaman']; ?>
                                        <div class="card mb-3 border shadow-sm">
                                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2" style="font-size: 13px;">
                                                <div><strong>#<?= $no++; ?> - PJM: <?= $id_peminjaman; ?></strong></div>
                                                <div>
                                                    <?php 
                                                    $st = $r['status'];
                                                    $badge = 'bg-warning text-dark';
                                                    if ($st == 'Disetujui') $badge = 'bg-success';
                                                    elseif ($st == 'Ditolak') $badge = 'bg-danger';
                                                    elseif ($st == 'Selesai') $badge = 'bg-info text-dark';
                                                    ?>
                                                    <span class="badge <?= $badge; ?>"><?= $st; ?></span>
                                                </div>
                                            </div>
                                            <div class="card-body bg-white p-2">
                                                <p class="mb-1 small">Peminjam: <b><?= $r['nama_peminjam'] ?? 'Umum'; ?></b></p>
                                                <div class="table-responsive mb-1">
                                                    <table class="table table-sm table-bordered mb-1 align-middle" style="font-size: 12px;">
                                                        <thead class="table-light"><tr><th>Nama Alat</th><th>Jumlah</th></tr></thead>
                                                        <tbody>
                                                            <?php
                                                            $q_detail = mysqli_query($koneksi, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat = a.id_alat WHERE d.id_peminjaman = '$id_peminjaman'");
                                                            while ($d = mysqli_fetch_assoc($q_detail)) {
                                                            ?>
                                                                <tr><td><?= $d['nama_alat']; ?></td><td><?= $d['jumlah_pinjam']; ?> unit</td></tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <span class="text-muted" style="font-size: 11px;">Rencana Kembali: <b><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])); ?></b></span>
                                            </div>
                                        </div>
                                    <?php endwhile; else: ?>
                                        <div class="alert alert-secondary text-center small">Belum ada aktivitas peminjaman hari ini.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="m-0"><i class="bi bi-activity"></i> Log Aktivitas Sistem</h5>
                                </div>
                                <div class="card-body" style="max-height: 490px; overflow-y: auto;">
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover align-middle mb-0" style="font-size: 13px;">
                                            <thead class="table-dark">
                                                <tr><th width="30%">Waktu</th><th width="25%">User</th><th>Aktivitas</th></tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $q_log = mysqli_query($koneksi, "SELECT l.*, u.nama FROM log_aktivitas l LEFT JOIN user u ON l.id_user = u.id_user ORDER BY l.id_log DESC LIMIT 20");
                                                if ($q_log && mysqli_num_rows($q_log) > 0):
                                                    while ($log = mysqli_fetch_assoc($q_log)):
                                                ?>
                                                    <tr>
                                                        <td><small class="text-muted"><?= $log['waktu']; ?></small></td>
                                                        <td class="fw-bold"><?= $log['nama'] ?? 'Sistem'; ?></td>
                                                        <td><?= $log['aktivitas']; ?></td>
                                                    </tr>
                                                <?php endwhile; else: ?>
                                                    <tr><td colspan="3" class="text-center text-muted">Belum ada log aktivitas.</td></tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- KONDISI 2: JIKA PETUGAS -->
                <?php elseif ($role == 'petugas'): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm border-start border-primary border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">TOTAL BARANG</small>
                                    <h3 class="fw-bold mt-1"><?= $total_alat; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm border-start border-success border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">TOTAL KATEGORI</small>
                                    <h3 class="fw-bold mt-1"><?= $total_kategori; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm border-start border-warning border-4 py-2">
                                <div class="card-body">
                                    <small class="text-muted fw-bold">PINJAM HARI INI</small>
                                    <h3 class="fw-bold mt-1"><?= $pinjam_hari_ini; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-7">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="m-0"><i class="bi bi-journal-text"></i> Peminjaman Hari Ini</h5>
                                </div>
                                <div class="card-body" style="max-height: 450px; overflow-y: auto;">
                                    <?php if (mysqli_num_rows($q_tr) > 0): $no = 1; while ($r = mysqli_fetch_assoc($q_tr)): $id_peminjaman = $r['id_peminjaman']; ?>
                                        <div class="card mb-3 border shadow-sm">
                                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2" style="font-size: 13px;">
                                                <div><strong>#<?= $no++; ?> - PJM: <?= $id_peminjaman; ?></strong></div>
                                                <div>
                                                    <?php 
                                                    $st = $r['status'];
                                                    $badge = 'bg-warning text-dark';
                                                    if ($st == 'Disetujui') $badge = 'bg-success';
                                                    elseif ($st == 'Ditolak') $badge = 'bg-danger';
                                                    elseif ($st == 'Selesai') $badge = 'bg-info text-dark';
                                                    ?>
                                                    <span class="badge <?= $badge; ?>"><?= $st; ?></span>
                                                </div>
                                            </div>
                                            <div class="card-body bg-white p-2">
                                                <p class="mb-1 small">Peminjam: <b><?= $r['nama_peminjam'] ?? 'Umum'; ?></b></p>
                                                <div class="table-responsive mb-1">
                                                    <table class="table table-sm table-bordered mb-1 align-middle" style="font-size: 12px;">
                                                        <thead class="table-light"><tr><th>Nama Alat</th><th>Jumlah</th></tr></thead>
                                                        <tbody>
                                                            <?php
                                                            $q_detail = mysqli_query($koneksi, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat = a.id_alat WHERE d.id_peminjaman = '$id_peminjaman'");
                                                            while ($d = mysqli_fetch_assoc($q_detail)) {
                                                            ?>
                                                                <tr><td><?= $d['nama_alat']; ?></td><td><?= $d['jumlah_pinjam']; ?> unit</td></tr>
                                                            <?php } ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                <span class="text-muted" style="font-size: 11px;">Rencana Kembali: <b><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])); ?></b></span>
                                            </div>
                                        </div>
                                    <?php endwhile; else: ?>
                                        <div class="alert alert-secondary text-center small">Belum ada aktivitas peminjaman hari ini.</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5">
                            <div class="card shadow-sm border-0 bg-white">
                                <div class="card-header bg-dark text-white"><h5 class="m-0"><i class="bi bi-info-circle"></i> Informasi Tugas Petugas</h5></div>
                                <div class="card-body">
                                    <p class="text-muted">Selamat bekerja, Petugas! Berikut adalah panduan cepat tugasmu:</p>
                                    <ul class="small text-secondary ps-3 mb-3">
                                        <li class="mb-2">Gunakan menu <b>Persetujuan Pinjaman</b> untuk menyetujui atau menolak peminjaman.</li>
                                        <li class="mb-2">Gunakan menu <b>Pemantauan Kembali</b> untuk memantau pengembalian alat.</li>
                                        <li class="mb-2">Gunakan menu <b>Cetak Laporan</b> untuk mencetak rekapitulasi data.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                <!-- KONDISI 3: JIKA PEMINJAM -->
                <?php else: ?>
                    <div class="alert alert-info shadow-sm mb-4">
                        <h5><i class="bi bi-hand-thumbs-up"></i> Selamat Datang, <?= $_SESSION['nama']; ?>!</h5>
                        <p class="mb-0 small">Silakan gunakan menu di samping untuk melihat daftar alat, mengajukan peminjaman, atau mengembalikan alat.</p>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card shadow-sm mb-4">
                                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                                    <h5 class="m-0"><i class="bi bi-clock-history"></i> Riwayat Peminjaman Anda</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th width="5%">No</th>
                                                    <th>ID Pinjam</th>
                                                    <th>Tgl Pinjam</th>
                                                    <th>Rencana Kembali</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($q_peminjaman_user) > 0): $no = 1; while ($rp = mysqli_fetch_assoc($q_peminjaman_user)): ?>
                                                    <tr>
                                                        <td><?= $no++; ?></td>
                                                        <td><span class="badge bg-secondary">#PJM-<?= $rp['id_peminjaman']; ?></span></td>
                                                        <td><?= date('d/m/Y H:i', strtotime($rp['tgl_pinjam'])); ?></td>
                                                        <td><?= date('d/m/Y', strtotime($rp['tgl_kembali_rencana'])); ?></td>
                                                        <td>
                                                            <?php 
                                                            $st = $rp['status'];
                                                            $badge = 'bg-warning text-dark';
                                                            if ($st == 'Disetujui') $badge = 'bg-success';
                                                            elseif ($st == 'Ditolak') $badge = 'bg-danger';
                                                            elseif ($st == 'Selesai') $badge = 'bg-info text-dark';
                                                            ?>
                                                            <span class="badge <?= $badge; ?>"><?= $st; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center text-muted">Anda belum memiliki riwayat peminjaman.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>