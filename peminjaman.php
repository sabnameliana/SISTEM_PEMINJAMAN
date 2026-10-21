<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php");
    exit();
}

$role = $_SESSION['role'] ?? '';
$id_user_login = $_SESSION['id_user'] ?? $_SESSION['user_id'] ?? 1;

// Tentukan tab aktif 
if ($role == 'petugas') {
    $tab = 'riwayat';
} elseif ($role == 'admin') {
    $tab = 'form';
} else {
    $tab = $_GET['tab'] ?? 'form';
}

// PROSES CHECKOUT PEMINJAMAN
if (isset($_POST['checkout'])) {
    if ($role == 'petugas') {
        echo "<script>alert('Akses ditolak! Petugas tidak memiliki hak untuk melakukan peminjaman.'); window.location.href='peminjaman.php?tab=riwayat';</script>";
        exit();
    }

    $tgl_kembali_rencana = $_POST['tgl_kembali_rencana'];
    $user_input          = $_POST['user_input'] ?? '';

    $id_user = $id_user_login;
    if ($role == 'admin' && !empty($user_input)) {
        $id_pilih = trim(explode(' - ', $user_input)[0]);
        $cek_user = mysqli_query($koneksi, "SELECT id_user FROM user WHERE id_user = '$id_pilih'");
        if (mysqli_num_rows($cek_user) > 0) {
            $id_user = $id_pilih;
        }
    }

    $tgl_pinjam = date('Y-m-d H:i:s');
    $p_ids      = $_POST['alat_id'] ?? [];
    $qtys       = $_POST['jumlah_pinjam'] ?? [];

    $ada_barang = false;
    for ($i = 0; $i < count($qtys); $i++) {
        if (intval($qtys[$i]) > 0) {
            $ada_barang = true;
            break;
        }
    }

    if (!$ada_barang) {
        echo "<script>alert('Peminjaman gagal! Belum ada barang yang dipilih.'); window.history.back();</script>";
        exit();
    }

    $q_pinjam = "INSERT INTO peminjaman (id_user, tgl_pinjam, tgl_kembali_rencana, status) VALUES ('$id_user', '$tgl_pinjam', '$tgl_kembali_rencana', 'Pending')";

    if (mysqli_query($koneksi, $q_pinjam)) {
        $id_peminjaman = mysqli_insert_id($koneksi);

        for ($i = 0; $i < count($p_ids); $i++) {
            $qty = intval($qtys[$i]);
            if ($qty > 0) {
                mysqli_query($koneksi, "INSERT INTO detail_peminjaman (id_peminjaman, id_alat, jumlah_pinjam) VALUES ('$id_peminjaman', '{$p_ids[$i]}', '$qty')");
                mysqli_query($koneksi, "UPDATE alat SET stok = stok - $qty WHERE id_alat = '{$p_ids[$i]}'");
            }
        }

        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Mengajukan peminjaman alat baru (ID #PJM-$id_peminjaman)')");

        echo "<script>alert('Pengajuan Peminjaman Berhasil Dikirim!'); window.location.href='peminjaman.php?tab=riwayat';</script>";
    } else {
        echo "<script>alert('Gagal: " . mysqli_error($koneksi) . "');</script>";
    }
}

// PROSES UPDATE STATUS (SETUJU / TOLAK) KHUSUS PETUGAS
if (isset($_GET['aksi']) && isset($_GET['id'])) {
    if ($role == 'petugas') {
        $id_peminjaman = $_GET['id'];
        $aksi = $_GET['aksi'];

        if ($aksi == 'setuju') {
            $status_baru = 'Disetujui';
        } elseif ($aksi == 'tolak') {
            $status_baru = 'Ditolak';

            $q_det = mysqli_query($koneksi, "SELECT * FROM detail_peminjaman WHERE id_peminjaman = '$id_peminjaman'");
            while ($d = mysqli_fetch_assoc($q_det)) {
                $id_alat = $d['id_alat'];
                $jml = $d['jumlah_pinjam'];
                mysqli_query($koneksi, "UPDATE alat SET stok = stok + $jml WHERE id_alat = '$id_alat'");
            }
        }

        mysqli_query($koneksi, "UPDATE peminjaman SET status='$status_baru' WHERE id_peminjaman='$id_peminjaman'");
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Mengubah status peminjaman #PJM-$id_peminjaman menjadi$status_baru')");

        header("location:peminjaman.php?tab=riwayat");
        exit();
    }
}

// Ambil data riwayat peminjaman
if ($role == 'admin' || $role == 'petugas') {
    $q_peminjaman = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_peminjam FROM peminjaman p JOIN user u ON p.id_user = u.id_user ORDER BY p.id_peminjaman DESC");
} else {
    $q_peminjaman = mysqli_query($koneksi, "SELECT p.*, u.nama AS nama_peminjam FROM peminjaman p JOIN user u ON p.id_user = u.id_user WHERE p.id_user = '$id_user_login' ORDER BY p.id_peminjaman DESC");
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Peminjaman Alat - Sistem Peminjaman</title>
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
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white active bg-info rounded">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white">User</a></li>
                    <?php elseif ($role == 'petugas'): ?>
                        <li class="nav-item mb-2"><a href="peminjaman.php?tab=riwayat" class="nav-link text-white active bg-info rounded">Persetujuan</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="laporan.php" class="nav-link text-white">Cetak Laporan</a></li>
                    <?php else: ?>
                        <li class="nav-item mb-2"><a href="peminjaman.php?tab=form" class="nav-link text-white active bg-info rounded">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Daftar Peminjaman</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <!-- NAVIGASI TAB KHUSUS PEMINJAM -->
                <?php if ($role == 'peminjam'): ?>
                    <div class="d-flex align-items-center gap-2 mb-4 p-2 bg-white shadow-sm rounded border">
                        <a href="peminjaman.php?tab=form" class="btn <?= ($tab == 'form') ? 'btn-primary fw-bold shadow-sm' : 'btn-light text-secondary'; ?> px-4 py-2">
                            <i class="bi bi-plus-circle me-1"></i> Form Pengajuan Peminjaman
                        </a>
                        <a href="peminjaman.php?tab=riwayat" class="btn <?= ($tab == 'riwayat') ? 'btn-primary fw-bold shadow-sm' : 'btn-light text-secondary'; ?> px-4 py-2">
                            <i class="bi bi-clock-history me-1"></i> Daftar Status Peminjaman
                        </a>
                    </div>
                <?php endif; ?>

                <!-- TAB 1: FORM PEMINJAMAN -->
                <?php if ($tab == 'form' && $role != 'petugas'): ?>
                    <form method="POST" id="formPeminjaman" autocomplete="off">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-dark text-white p-3">
                                        <h5 class="fw-bold m-0"><i class="bi bi-box-arrow-up-right"></i> Form Input Peminjaman</h5>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label fw-bold">Rencana Tanggal Kembali</label>
                                                <input type="date" name="tgl_kembali_rencana" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')); ?>" required>
                                            </div>
                                            <?php if ($role == 'admin'): ?>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label fw-bold">Pilih Peminjam (User)</label>
                                                    <input type="text" id="user_input" name="user_input" list="daftar_user" class="form-control" placeholder="Ketik Nama / ID User" required>
                                                    <datalist id="daftar_user">
                                                        <?php
                                                        $usr = mysqli_query($koneksi, "SELECT * FROM user ORDER BY nama ASC");
                                                        while ($u = mysqli_fetch_array($usr)) {
                                                            echo "<option value='{$u['id_user']} - {$u['nama']} ({$u['username']})'></option>";
                                                        }
                                                        ?>
                                                    </datalist>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <hr>

                                        <div class="input-group mb-3">
                                            <span class="input-group-text bg-dark text-white"><i class="bi bi-search"></i> Cari</span>
                                            <input type="text" id="inputPencarian" class="form-control" placeholder="Cari nama alat..." onkeyup="filterAlat()">
                                        </div>
                                        <h5 class="fw-bold mb-3">Pilih Inventaris Alat:</h5>

                                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 300px;">
                                            <div class="table-responsive m-0">
                                                <table class="table table-bordered table-hover align-middle m-0" id="tabelAlat">
                                                    <thead class="table-dark">
                                                        <tr>
                                                            <th>Nama Barang</th>
                                                            <th>Kondisi</th>
                                                            <th>Stok Tersedia</th>
                                                            <th width="25%">Jumlah</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php
                                                        $alat = mysqli_query($koneksi, "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON a.id_kategori = k.id_kategori WHERE a.stok > 0");
                                                        if (mysqli_num_rows($alat) > 0) {
                                                            while ($al = mysqli_fetch_array($alat)) {
                                                                echo "<tr>
                                                                    <td><b>{$al['nama_alat']}</b> <br><small class='text-muted'>Kat: {$al['nama_kategori']}</small></td>
                                                                    <td><span class='badge bg-success'>{$al['kondisi']}</span></td>
                                                                    <td><span class='badge bg-primary'>{$al['stok']} unit</span></td>
                                                                    <td>
                                                                        <input type='hidden' name='alat_id[]' value='{$al['id_alat']}'>
                                                                        <input type='hidden' class='nama-item' value='{$al['nama_alat']}'>
                                                                        <input type='number' name='jumlah_pinjam[]' class='form-control qty-item' min='0' max='{$al['stok']}' value='0' oninput='hitungTotal()'>
                                                                    </td>
                                                                </tr>";
                                                            }
                                                        } else {
                                                            echo "<tr><td colspan='4' class='text-center text-danger'>Stok alat kosong!</td></tr>";
                                                        }
                                                        ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-dark text-white p-3">
                                        <h5 class="fw-bold m-0"><i class="bi bi-clipboard-check"></i> Ringkasan Peminjaman</h5>
                                    </div>
                                    <div class="card-body p-3 bg-light">
                                        <div class="bg-dark text-primary p-3 text-end rounded mb-3 font-monospace">
                                            <small class="text-light">TOTAL JENIS BARANG</small>
                                            <h2 class="m-0 fw-bold" id="display_total_item">0 Item</h2>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">Rincian Barang Dipinjam:</label>
                                            <div class="table-responsive border rounded bg-white p-2" style="max-height: 200px; overflow-y: auto;">
                                                <table class="table table-sm table-borderless m-0 small">
                                                    <thead class="border-bottom">
                                                        <tr>
                                                            <th>Barang</th>
                                                            <th class="text-end">Qty</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="ringkasanRincianBody">
                                                        <tr>
                                                            <td colspan="2" class="text-center text-muted">Belum ada rincian.</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label">Total Unit Dipinjam</label>
                                            <input type="text" id="total_unit_text" class="form-control fw-bold" value="0 Unit" readonly>
                                        </div>
                                        <button type="submit" name="checkout" class="btn btn-primary text-white btn-lg w-100 fw-bold shadow-sm">
                                            <i class="bi bi-send-check"></i> AJUKAN PEMINJAMAN
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- TAB 2: DAFTAR & PERSETUJUAN STATUS -->
                <?php if ($tab == 'riwayat'): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-dark text-white">
                            <h5 class="m-0"><i class="bi bi-clock-history"></i> Status & Persetujuan Peminjaman</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped align-middle">
                                        <thead class="table-dark text-center">
                                            <tr>
                                                <th width="5%">No</th>
                                                <th>ID Pinjam</th>
                                                <th>Peminjam</th>
                                                <th>Tgl Pinjam</th>
                                                <th>Rencana Kembali</th>
                                                <th>Detail Barang</th>
                                                <th>Status</th>
                                                <th width="15%">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($q_peminjaman) > 0): $no = 1;
                                                while ($r = mysqli_fetch_assoc($q_peminjaman)): $id_pinjam = $r['id_peminjaman']; ?>
                                                    <tr>
                                                        <td class="text-center"><?= $no++; ?></td>
                                                        <td class="text-center"><span class="badge bg-secondary">#PJM-<?= $id_pinjam; ?></span></td>
                                                        <td class="fw-bold"><?= htmlspecialchars($r['nama_peminjam']); ?></td>
                                                        <td class="text-center"><?= date('d/m/Y H:i', strtotime($r['tgl_pinjam'])); ?></td>
                                                        <td class="text-center"><?= date('d/m/Y', strtotime($r['tgl_kembali_rencana'])); ?></td>
                                                        <td>
                                                            <ul class="mb-0 ps-3 small">
                                                                <?php
                                                                $q_detail = mysqli_query($koneksi, "SELECT d.*, a.nama_alat FROM detail_peminjaman d JOIN alat a ON d.id_alat = a.id_alat WHERE d.id_peminjaman = '$id_pinjam'");
                                                                while ($d = mysqli_fetch_assoc($q_detail)) {
                                                                    echo "<li>{$d['nama_alat']} ({$d['jumlah_pinjam']} unit)</li>";
                                                                }
                                                                ?>
                                                            </ul>
                                                        </td>
                                                        <td class="text-center align-middle">
                                                            <?php
                                                            $st = $r['status'];
                                                            $badge = 'bg-warning text-dark';
                                                            if ($st == 'Disetujui') $badge = 'bg-success';
                                                            elseif ($st == 'Ditolak') $badge = 'bg-danger';
                                                            elseif ($st == 'Selesai') $badge = 'bg-info text-dark';
                                                            ?>
                                                            <span class="badge <?= $badge; ?> px-2 py-1"><?= $st; ?></span>
                                                        </td>

                                                        <!-- KOLOM AKSI (Tombol Struk & Tombol Persetujuan Petugas) -->
                                                        <td class="text-center align-middle">
                                                            <div class="d-flex justify-content-center align-items-center gap-1">
                                                                <!-- Tombol Cetak Struk muncul jika sudah Disetujui / Selesai -->
                                                                <?php if ($st == 'Disetujui' || $st == 'Selesai'): ?>
                                                                    <a href="struk_peminjaman.php?id=<?= $id_pinjam; ?>" target="_blank" class="btn btn-sm btn-success px-2 py-1" title="Cetak Struk Peminjaman" style="font-size: 11px;">
                                                                        <i class="bi bi-printer"></i> Struk
                                                                    </a>
                                                                <?php endif; ?>

                                                                <!-- Tombol Setuju / Tolak khusus Petugas saat status Pending -->
                                                                <?php if ($role == 'petugas' && $st == 'Pending'): ?>
                                                                    <a href="peminjaman.php?tab=riwayat&aksi=setuju&id=<?= $id_pinjam; ?>" class="btn btn-sm btn-success px-2 py-1" onclick="return confirm('Setujui peminjaman ini?')" style="font-size: 11px;">Setuju</a>
                                                                    <a href="peminjaman.php?tab=riwayat&aksi=tolak&id=<?= $id_pinjam; ?>" class="btn btn-sm btn-danger px-2 py-1" onclick="return confirm('Tolak peminjaman ini? Stok akan dikembalikan.')" style="font-size: 11px;">Tolak</a>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile;
                                            else: ?>
                                                <tr>
                                                    <td colspan="8" class="text-center text-muted">Belum ada data peminjaman.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <script>
                        function filterAlat() {
                            let filter = document.getElementById('inputPencarian').value.toLowerCase();
                            let tr = document.getElementById('tabelAlat').getElementsByTagName('tr');
                            for (let i = 1; i < tr.length; i++) {
                                let td = tr[i].getElementsByTagName('td')[0];
                                if (td) {
                                    let text = td.textContent || td.innerText;
                                    tr[i].style.display = text.toLowerCase().indexOf(filter) > -1 ? "" : "none";
                                }
                            }
                        }

                        function hitungTotal() {
                            let qtys = document.querySelectorAll('.qty-item');
                            let namas = document.querySelectorAll('.nama-item');

                            let totalJenisItem = 0;
                            let totalUnit = 0;
                            let rincianHtml = "";
                            let adaItem = false;

                            for (let i = 0; i < qtys.length; i++) {
                                let qtyVal = parseInt(qtys[i].value) || 0;
                                if (qtyVal > 0) {
                                    adaItem = true;
                                    totalJenisItem++;
                                    totalUnit += qtyVal;

                                    let namaVal = namas[i].value;

                                    rincianHtml += `<tr>
                                    <td>${namaVal}</td>
                                    <td class="text-end fw-bold"><b>${qtyVal}</b> u</td>
                                </tr>`;
                                }
                            }

                            let rincianBody = document.getElementById('ringkasanRincianBody');

                            if (adaItem) {
                                rincianBody.innerHTML = rincianHtml;
                            } else {
                                rincianBody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">Belum ada rincian.</td></tr>`;
                            }

                            document.getElementById('display_total_item').innerText = totalJenisItem + " Item";
                            document.getElementById('total_unit_text').value = totalUnit + " Unit";
                        }
                    </script>
                    </div>
            </div>
        </div>
</body>

</html>