<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}

$role = $_SESSION['role'] ?? '';

// Hak akses Petugas & Admin
if ($role != 'petugas' && $role != 'admin') {
    echo "<script>alert('Akses ditolak! Menu laporan khusus untuk Petugas dan Admin.'); window.location.href='index.php';</script>";
    exit();
}

// Filter Tanggal
$tgl_mulai   = $_GET['tgl_mulai'] ?? date('Y-m-01');
$tgl_selesai = $_GET['tgl_selesai'] ?? date('Y-m-d');

// Query Data Laporan Pengembalian & Denda
$q_laporan = mysqli_query($koneksi, "
    SELECT pg.*, p.tgl_pinjam, p.tgl_kembali_rencana, u.nama AS nama_peminjam 
    FROM pengembalian pg 
    JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman 
    JOIN user u ON p.id_user = u.id_user 
    WHERE DATE(pg.tgl_kembali) BETWEEN '$tgl_mulai' AND '$tgl_selesai' 
    ORDER BY pg.id_pengembalian DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pengembalian & Denda</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white d-print-none">
                <h5 class="text-center">PEMINJAMAN</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="kategori.php" class="nav-link text-white">Kategori</a></li>
                        <li class="nav-item mb-2"><a href="alat.php" class="nav-link text-white">Data Barang</a></li>
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white">User</a></li>
                        <li class="nav-item mb-2"><a href="laporan.php" class="nav-link text-white active bg-info rounded">Cetak Laporan</a></li>
                    <?php else: ?>
                        <li class="nav-item mb-2"><a href="peminjaman.php?tab=riwayat" class="nav-link text-white">Persetujuan</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="laporan.php" class="nav-link text-white active bg-info rounded">Cetak Laporan</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">

                <!-- KOP / JUDUL KHUSUS CETAK -->
                <div class="text-center mb-4 d-none d-print-block">
                    <h3 class="fw-bold m-0">LAPORAN PEMINJAMAN & PENGEMBALIAN BUKU</h3>
                    <p class="text-muted m-0">Periode Tanggal: <?= date('d/m/Y', strtotime($tgl_mulai)); ?> s.d <?= date('d/m/Y', strtotime($tgl_selesai)); ?></p>
                    <hr>
                </div>

                <!-- HEADER MONITOR -->
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2 d-print-none">
                    <h4>Laporan Pengembalian & Denda</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <!-- FILTER TANGGAL -->
                <div class="card shadow-sm mb-4 d-print-none">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Dari Tanggal</label>
                                <input type="date" name="tgl_mulai" class="form-control" value="<?= htmlspecialchars($tgl_mulai); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small">Sampai Tanggal</label>
                                <input type="date" name="tgl_selesai" class="form-control" value="<?= htmlspecialchars($tgl_selesai); ?>" required>
                            </div>
                            <div class="col-md-4 d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill"><i class="bi bi-filter"></i> Tampilkan</button>
                                <button type="button" onclick="cetakLaporan()" class="btn btn-success flex-fill"><i class="bi bi-printer"></i> Cetak</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- TABEL LAPORAN -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div id="areaTabel" class="table-responsive" style="max-height: 450px;">
                            <table class="table table-bordered table-striped align-middle small m-0">
                                <thead class="table-dark text-center sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Transaksi</th>
                                        <th>Tanggal Kembali</th>
                                        <th>Peminjam</th>
                                        <th>Rincian Buku & Status Denda</th>
                                        <th class="d-print-none">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $total_denda_periode = 0;
                                    if ($q_laporan && mysqli_num_rows($q_laporan) > 0):
                                        while ($lap = mysqli_fetch_assoc($q_laporan)):
                                            $id_pengembalian = $lap['id_pengembalian'];
                                            $id_peminjaman = $lap['id_peminjaman'];
                                            $denda_nominal = $lap['denda'] ?? 0;
                                            $total_denda_periode += $denda_nominal;

                                            // Ambil rincian alat/buku yang dipinjam pada transaksi ini
                                            $q_det = mysqli_query($koneksi, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat = a.id_alat WHERE d.id_peminjaman = '$id_peminjaman'");
                                    ?>
                                            <tr>
                                                <td class="text-center"><?= $no++; ?></td>
                                                <td class="text-center"><span class="badge bg-secondary">#PENG-<?= $id_pengembalian; ?></span></td>
                                                <td class="text-center"><?= date('d/m/Y', strtotime($lap['tgl_kembali'])); ?></td>
                                                <td class="fw-bold"><?= htmlspecialchars($lap['nama_peminjam']); ?></td>
                                                <td>
                                                    <ul class="ps-3 m-0">
                                                        <?php while ($d = mysqli_fetch_assoc($q_det)): ?>
                                                            <li class="mb-1">
                                                                <b><?= htmlspecialchars($d['nama_alat']); ?></b> 
                                                                <span class="text-muted" style="font-size: 11px;">(<?= $d['jumlah_pinjam']; ?> unit)</span>
                                                            </li>
                                                        <?php endwhile; ?>
                                                    </ul>

                                                    <hr class="my-1">

                                                    <div style="font-size: 11px;" class="px-2 d-flex justify-content-between align-items-center">
                                                        <span>Status Kondisi: <span class="badge bg-info text-dark"><?= htmlspecialchars($lap['status']); ?></span></span>
                                                        <span class="fw-bold text-danger">Denda: Rp <?= number_format($denda_nominal, 0, ',', '.'); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center d-print-none">
                                                    <a href="struk_peminjaman.php?id=<?= $id_peminjaman; ?>" target="_blank" class="btn btn-success btn-sm px-2 py-1" title="Cetak Struk Peminjaman">
                                                        🖨️
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php
                                        endwhile;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">Tidak ada data transaksi pengembalian pada rentang tanggal tersebut.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- TOTAL PENDAPATAN DENDA KESELURUHAN -->
                        <?php if (mysqli_num_rows($q_laporan) > 0): ?>
                            <div class="alert alert-dark mt-3 d-flex justify-content-between align-items-center mb-0">
                                <h5 class="m-0 fw-bold">TOTAL PENERIMAAN DENDA PERIODE INI:</h5>
                                <h4 class="m-0 fw-bold text-warning">Rp <?= number_format($total_denda_periode, 0, ',', '.'); ?></h4>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function cetakLaporan() {
            const area = document.getElementById('areaTabel');
            area.style.maxHeight = 'none';
            area.style.overflow = 'visible';

            window.print();

            area.style.maxHeight = '450px';
            area.style.overflow = 'auto';
        }
    </script>
</body>
</html>