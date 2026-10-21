<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$id_user_login = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 1;

date_default_timezone_set('Asia/Jakarta');

// 1. PROSES VERIFIKASI OLEH PETUGAS / ADMIN (DENGAN KEAMANAN BACKEND ANTI-KECURANGAN)
if (isset($_POST['proses_kembali'])) {
    $id_peminjaman       = $_POST['id_peminjaman'];
    $tgl_kembali         = date('Y-m-d');
    $denda               = intval($_POST['denda'] ?? 0);
    $status_pengembalian = $_POST['status_pengembalian'] ?? 'Selesai';

    // Cek tanggal asli di database untuk mencegah manipulasi form
    $q_pjm = mysqli_query($koneksi, "SELECT tgl_kembali_rencana FROM peminjaman WHERE id_peminjaman = '$id_peminjaman'");
    $data_pjm = mysqli_fetch_assoc($q_pjm);
    $tgl_rencana_ts = strtotime($data_pjm['tgl_kembali_rencana']);
    $tgl_sekarang_ts = strtotime($tgl_kembali);

    if ($tgl_sekarang_ts > $tgl_rencana_ts) {
        $selisih = floor(($tgl_sekarang_ts - $tgl_rencana_ts) / (60 * 60 * 24));
        $denda_min = $selisih * 5000;

        // Jika sebenarnya telat, paksa status tidak boleh 'Selesai'
        if ($status_pengembalian == 'Selesai') {
            $status_pengembalian = 'Terlambat';
        }
        // Pastikan denda tidak kurang dari denda keterlambatan minimal
        if ($denda < $denda_min && $status_pengembalian != 'Hilang') {
            $denda = $denda_min; 
        }
    }

    // Masukkan data ke tabel pengembalian
    $q_pengembalian = "INSERT INTO pengembalian (id_peminjaman, tgl_kembali, denda, status) VALUES ('$id_peminjaman', '$tgl_kembali', '$denda', '$status_pengembalian')";

    if (mysqli_query($koneksi, $q_pengembalian)) {
        // Ubah status peminjaman menjadi Selesai
        mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Selesai' WHERE id_peminjaman = '$id_peminjaman'");

        // Stok alat dikembalikan jika statusnya BUKAN 'Hilang'
        if ($status_pengembalian != 'Hilang') {
            $q_det = mysqli_query($koneksi, "SELECT * FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
            while ($d = mysqli_fetch_assoc($q_det)) {
                $id_alat = $d['id_alat'];
                $jumlah = $d['jumlah_pinjam'];
                mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jumlah WHERE id_alat = '$id_alat'");
            }
        }

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Memverifikasi pengembalian buku ID #PJM-$id_peminjaman (Status: $status_pengembalian, Denda: Rp $denda)')");

        echo "<script>alert('Pengajuan pengembalian berhasil diverifikasi!'); window.location.href='pengembalian.php';</script>";
    } else {
        echo "<script>alert('Gagal memproses pengembalian: " . mysqli_error($koneksi) . "');</script>";
    }
}

// 2. PROSES PENGAJUAN OLEH PEMINJAM (Hanya ubah status jadi Menunggu Verifikasi)
if (isset($_POST['ajukan_kembali'])) {
    $id_peminjaman = $_POST['id_peminjaman'];

    mysqli_query($koneksi, "UPDATE peminjaman SET status = 'Menunggu Verifikasi' WHERE id_peminjaman = '$id_peminjaman'");
    mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Mengajukan pengembalian buku untuk peminjaman ID #PJM-$id_peminjaman')");

    echo "<script>alert('Pengajuan pengembalian berhasil dikirim. Silakan serahkan buku fisik ke petugas.'); window.location.href='pengembalian.php';</script>";
}

// Query data berdasarkan Role
if ($role == 'admin' || $role == 'petugas') {
    // Petugas/Admin melihat data yang 'Disetujui' atau 'Menunggu Verifikasi'
    $q_aktif = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_peminjam FROM peminjaman p JOIN user u ON p.id_user = u.id_user WHERE p.status IN ('Disetujui', 'Menunggu Verifikasi') ORDER BY p.tgl_kembali_rencana ASC");
    $q_riwayat = mysqli_query($koneksi, "SELECT pg.*, p.tgl_kembali_rencana, u.nama AS nama_peminjam FROM pengembalian pg JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman JOIN user u ON p.id_user = u.id_user ORDER BY pg.id_pengembalian DESC");
} else {
    // Peminjam melihat miliknya yang 'Disetujui' ATAU 'Menunggu Verifikasi' agar tidak langsung hilang dari tabel aktif
    $q_aktif = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_peminjam FROM peminjaman p JOIN user u ON p.id_user = u.id_user WHERE p.id_user = '$id_user_login' AND p.status IN ('Disetujui', 'Menunggu Verifikasi') ORDER BY p.tgl_kembali_rencana ASC");
    $q_riwayat = mysqli_query($koneksi, "SELECT pg.*, p.tgl_kembali_rencana, u.nama AS nama_peminjam FROM pengembalian pg JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman JOIN user u ON p.id_user = u.id_user WHERE p.id_user = '$id_user_login' ORDER BY pg.id_pengembalian DESC");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Pengembalian Buku - Sistem Peminjaman</title>
    <link href="asset/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white">
                <h5 class="text-center">PEMINJAMAN</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-white">Dashboard</a></li>

                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="kategori.php" class="nav-link text-white">Kategori</a></li>
                        <li class="nav-item mb-2"><a href="alat.php" class="nav-link text-white">Data Barang</a></li>
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white active bg-info rounded">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white">User</a></li>
                    <?php elseif ($role == 'petugas'): ?>
                        <li class="nav-item mb-2"><a href="peminjaman.php?tab=riwayat" class="nav-link text-white">Persetujuan</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white active bg-info rounded">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="laporan.php" class="nav-link text-white">Cetak Laporan</a></li>
                    <?php else: ?>
                        <li class="nav-item mb-2"><a href="peminjaman.php?tab=form" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white active bg-info rounded">Pengembalian</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Daftar Pengembalian</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <div class="row">
                    <!-- KOLOM KIRI: SEDANG DIPINJAM / MENUNGGU VERIFIKASI -->
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2">
                                <h6 class="m-0 fw-bold"><i class="bi bi-box-arrow-in-down-left"></i> Sedang Dipinjam / Diajukan</h6>
                                <input type="text" id="searchAktif" class="form-control form-control-sm w-50" placeholder="Cari..." onkeyup="filterTabel('searchAktif', 'tabelAktif')">
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                                    <table class="table table-bordered table-hover align-middle small mb-0" id="tabelAktif">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th>Pinjam</th>
                                                <th>Peminjam</th>
                                                <th>Tempo</th>
                                                <th>Barang</th>
                                                <th class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($q_aktif) > 0): while ($r = mysqli_fetch_assoc($q_aktif)): $id_pinjam = $r['id_peminjaman']; ?>
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-secondary">#PJM-<?= $id_pinjam; ?></span>
                                                            <?php if ($r['status'] == 'Menunggu Verifikasi'): ?>
                                                                
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="fw-bold"><?= $r['nama_peminjam']; ?></td>
                                                        <td>
                                                            <?php
                                                            $tgl_rencana = $r['tgl_kembali_rencana'];
                                                            if (date('Y-m-d') > $tgl_rencana) {
                                                                echo "<span class='text-danger fw-bold'>" . date('d/m/y', strtotime($tgl_rencana)) . "</span>";
                                                            } else {
                                                                echo "<span class='text-success'>" . date('d/m/y', strtotime($tgl_rencana)) . "</span>";
                                                            }
                                                            ?>
                                                        </td>
                                                        <td>
                                                            <ul class="mb-0 ps-3">
                                                                <?php
                                                                $q_det = mysqli_query($koneksi, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat = a.id_alat WHERE d.id_peminjaman = '$id_pinjam'");
                                                                while ($d = mysqli_fetch_assoc($q_det)) {
                                                                    echo "<li>{$d['nama_alat']} ({$d['jumlah_pinjam']})</li>";
                                                                }
                                                                ?>
                                                            </ul>
                                                        </td>
                                                        <td class="text-center">
                                                            <?php if ($role == 'peminjam'): ?>
                                                                <!-- JIKA PEMINJAM: HANYA BISA MENGAJUKAN -->
                                                                <?php if ($r['status'] == 'Disetujui'): ?>
                                                                    <form method="POST" onsubmit="return confirm('Ajukan pengembalian buku ini ke petugas?')">
                                                                        <input type="hidden" name="id_peminjaman" value="<?= $id_pinjam; ?>">
                                                                        <button type="submit" name="ajukan_kembali" class="btn btn-sm btn-warning fw-bold py-0 px-2">
                                                                            Ajukan
                                                                        </button>
                                                                    </form>
                                                                <?php else: ?>
                                                                    <span class="badge bg-info text-dark">Menunggu</span>
                                                                <?php endif; ?>

                                                            <?php else: ?>
                                                                <!-- JIKA PETUGAS / ADMIN -->
                                                                <?php if ($r['status'] == 'Menunggu Verifikasi'): ?>
                                                                    <button class="btn btn-sm btn-primary fw-bold py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKembali<?= $id_pinjam; ?>">
                                                                        Verifikasi
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-success fw-bold py-0 px-2" data-bs-toggle="modal" data-bs-target="#modalKembali<?= $id_pinjam; ?>">
                                                                        Kembalikan
                                                                    </button>
                                                                <?php endif; ?>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>

                                                    <!-- MODAL PENGEMBALIAN KHUSUS PETUGAS / ADMIN -->
                                                    <?php if ($role == 'admin' || $role == 'petugas'): ?>
                                                        <div class="modal fade" id="modalKembali<?= $id_pinjam; ?>" tabindex="-1">
                                                            <div class="modal-dialog">
                                                                <form method="POST" action="">
                                                                    <div class="modal-content">
                                                                        <div class="modal-header bg-primary text-white">
                                                                            <h5 class="modal-title">Pengembalian Buku</h5>
                                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                                        </div>
                                                                        <div class="modal-body text-start">
                                                                            <input type="hidden" name="id_peminjaman" value="<?= $id_pinjam; ?>">
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold">Peminjam</label>
                                                                                <input type="text" class="form-control" value="<?= $r['nama_peminjam']; ?>" readonly>
                                                                            </div>
                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold">Rencana Tanggal Kembali</label>
                                                                                <input type="text" class="form-control" value="<?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])); ?>" readonly>
                                                                            </div>

                                                                            <?php
                                                                            $tarif_denda_per_hari = 5000;
                                                                            $tgl_rencana_ts = strtotime($r['tgl_kembali_rencana']);
                                                                            $tgl_sekarang_ts = strtotime(date('Y-m-d'));
                                                                            $selisih_hari = 0;
                                                                            $denda_terlambat = 0;
                                                                            $status_otomatis = 'Selesai';

                                                                            if ($tgl_sekarang_ts > $tgl_rencana_ts) {
                                                                                $selisih_hari = floor(($tgl_sekarang_ts - $tgl_rencana_ts) / (60 * 60 * 24));
                                                                                $denda_terlambat = $selisih_hari * $tarif_denda_per_hari;
                                                                                $status_otomatis = 'Terlambat';
                                                                            }
                                                                            ?>

                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold">Status Pengembalian & Kondisi Buku</label>
                                                                                <select name="status_pengembalian" id="statusPilih<?= $id_pinjam; ?>" class="form-select fw-bold" onchange="hitungTotalDenda<?= $id_pinjam; ?>()" required>
                                                                                    <?php if ($selisih_hari > 0): ?>
                                                                                        <!-- KEAMANAN FRONTEND: Jika telat, opsi 'Selesai' disembunyikan agar tidak bisa dicurangi -->
                                                                                        <option value="Terlambat" data-denda="0" selected>Terlambat (<?= $selisih_hari; ?> Hari Telat)</option>
                                                                                        <option value="Rusak" data-denda="20000">Rusak</option>
                                                                                        <option value="Hilang" data-denda="50000">Hilang</option>
                                                                                    <?php else: ?>
                                                                                        <!-- Jika tepat waktu -->
                                                                                        <option value="Selesai" data-denda="0" selected>Selesai</option>
                                                                                        <option value="Rusak" data-denda="20000">Rusak</option>
                                                                                        <option value="Hilang" data-denda="50000">Hilang</option>
                                                                                    <?php endif; ?>
                                                                                </select>
                                                                            </div>

                                                                            <div class="mb-3">
                                                                                <label class="form-label fw-bold">Total Denda (Rp)</label>
                                                                                <input type="number" name="denda" id="inputDenda<?= $id_pinjam; ?>" class="form-control fw-bold border-primary" value="<?= $denda_terlambat; ?>" min="0" required>
                                                                            </div>

                                                                        </div>
                                                                        <div class="modal-footer">
                                                                            <button type="submit" name="proses_kembali" class="btn btn-success fw-bold">Konfirmasi Selesai</button>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>

                                                        <script>
                                                            function hitungTotalDenda<?= $id_pinjam; ?>() {
                                                                let select = document.getElementById('statusPilih<?= $id_pinjam; ?>');
                                                                let selectedOption = select.options[select.selectedIndex];
                                                                let dendaKondisi = parseInt(selectedOption.getAttribute('data-denda')) || 0;
                                                                let dendaTelat = <?= $denda_terlambat; ?>;
                                                                
                                                                let total = dendaTelat + dendaKondisi;
                                                                document.getElementById('inputDenda<?= $id_pinjam; ?>').value = total;
                                                            }
                                                        </script>
                                                    <?php endif; ?>

                                                <?php endwhile;
                                            else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Tidak ada alat dipinjam.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KOLOM KANAN: RIWAYAT PENGEMBALIAN SELESAI -->
                    <div class="col-md-6">
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center py-2">
                                <h6 class="m-0 fw-bold"><i class="bi bi-clock-history"></i> Riwayat Selesai</h6>
                                <input type="text" id="searchRiwayat" class="form-control form-control-sm w-50" placeholder="Cari..." onkeyup="filterTabel('searchRiwayat', 'tabelRiwayat')">
                            </div>
                            <div class="card-body p-2">
                                <div class="table-responsive" style="max-height: 420px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped align-middle small mb-0" id="tabelRiwayat">
                                        <thead class="table-dark sticky-top">
                                            <tr>
                                                <th>ID</th>
                                                <th>Peminjam</th>
                                                <th>Tgl Kembali</th>
                                                <th>Denda</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($q_riwayat) > 0): while ($rw = mysqli_fetch_assoc($q_riwayat)): ?>
                                                    <tr>
                                                        <td><span class="badge bg-dark">#PENG-<?= $rw['id_pengembalian']; ?></span></td>
                                                        <td class="fw-bold"><?= $rw['nama_peminjam']; ?></td>
                                                        <td><?= date('d/m/y', strtotime($rw['tgl_kembali'])); ?></td>
                                                        <td>Rp <?= number_format($rw['denda'], 0, ',', '.'); ?></td>
                                                        <td>
                                                            <?php
                                                            $st_kembali = $rw['status'];
                                                            $badge_st = 'bg-success';
                                                            if ($st_kembali == 'Terlambat') $badge_st = 'bg-warning text-dark';
                                                            elseif ($st_kembali == 'Rusak') $badge_st = 'bg-warning text-dark';
                                                            elseif ($st_kembali == 'Hilang') $badge_st = 'bg-danger';
                                                            ?>
                                                            <span class="badge <?= $badge_st; ?>"><?= $st_kembali; ?></span>
                                                        </td>
                                                    </tr>
                                                <?php endwhile;
                                            else: ?>
                                                <tr>
                                                    <td colspan="5" class="text-center text-muted">Belum ada riwayat.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        function filterTabel(inputId, tableId) {
            let input = document.getElementById(inputId);
            let filter = input.value.toLowerCase();
            let table = document.getElementById(tableId);
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let tdArr = tr[i].getElementsByTagName('td');
                let found = false;
                for (let j = 0; j < tdArr.length; j++) {
                    let td = tdArr[j];
                    if (td) {
                        let textValue = td.textContent || td.innerText;
                        if (textValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }
                tr[i].style.display = found ? "" : "none";
            }
        }
    </script>
    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>