<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}

$sukses = $error = "";
$role = $_SESSION['role'] ?? '';
$id_user_login = $_SESSION['id_user'] ?? ''; 

// HAK AKSES SESUAI TABEL SOAL: Petugas tidak memiliki fitur alat
if ($role == 'petugas') {
    echo "<script>alert('Akses ditolak! Petugas tidak mengelola data alat.'); window.location.href='index.php';</script>";
    exit();
}

// Proses Tambah Alat (Hanya Admin)
if (isset($_POST['tambah']) && $role == 'admin') {
    $nama_alat = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);

    if (!empty($nama_alat) && !empty($id_kategori) && $stok !== "") {
        $query = mysqli_query($koneksi, "INSERT INTO alat (nama_alat, id_kategori, stok, kondisi) VALUES ('$nama_alat', '$id_kategori', '$stok', '$kondisi')");
        if ($query) {
            // --- CATAT LOG TAMBAH ALAT ---
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Menambahkan data barang baru: $nama_alat')");
            
            $sukses = "Data alat berhasil ditambahkan!";
        } else {
            $error = "Gagal menyimpan data alat!";
        }
    } else {
        $error = "Semua kolom wajib diisi!";
    }
}

// Proses Edit Alat (Hanya Admin)
if (isset($_POST['edit']) && $role == 'admin') {
    $id = $_POST['id'];
    $nama_alat = mysqli_real_escape_string($koneksi, $_POST['nama_alat']);
    $id_kategori = mysqli_real_escape_string($koneksi, $_POST['id_kategori']);
    $stok = mysqli_real_escape_string($koneksi, $_POST['stok']);
    $kondisi = mysqli_real_escape_string($koneksi, $_POST['kondisi']);

    $query = mysqli_query($koneksi, "UPDATE alat SET nama_alat='$nama_alat', id_kategori='$id_kategori', stok='$stok', kondisi='$kondisi' WHERE id_alat='$id'");
    if ($query) {
        // --- CATAT LOG EDIT ALAT ---
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Memperbarui data barang: $nama_alat')");

        $sukses = "Data alat berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui data!";
    }
}

// Proses Hapus Alat (Hanya Admin)
if (isset($_GET['hapus']) && $role == 'admin') {
    $id_alat = $_GET['hapus'];
    
    // Ambil nama alat terlebih dahulu sebelum dihapus agar tercatat di log
    $q_cek = mysqli_query($koneksi, "SELECT nama_alat FROM alat WHERE id_alat = '$id_alat'");
    $d_cek = mysqli_fetch_assoc($q_cek);
    $nama_alat_dihapus = $d_cek['nama_alat'] ?? 'Barang ID #'.$id_alat;

    $hapus_query = mysqli_query($koneksi, "DELETE FROM alat WHERE id_alat = '$id_alat'");
    if ($hapus_query) {
        // --- CATAT LOG HAPUS ALAT ---
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Menghapus data barang: $nama_alat_dihapus')");
    }

    header("location:alat.php");
    exit();
}

// Ambil data kategori untuk pilihan dropdown di form
$data_kategori = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY nama_kategori ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Data Alat - Sistem Peminjaman Alat</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR DISESUAIKAN DENGAN TABEL SOAL -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white">
                <h5 class="text-center">PEMINJAMAN</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                    
                    <?php if ($role == 'admin'): ?>
                        <li class="nav-item mb-2"><a href="kategori.php" class="nav-link text-white">Kategori</a></li>
                        <li class="nav-item mb-2"><a href="alat.php" class="nav-link text-white active bg-info rounded">Data Barang</a></li>
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                        <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white">User</a></li>
                    <?php else: ?>
                        <!-- MENU KHUSUS PEMINJAM -->
                        <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                        <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
           <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Kelola Barang</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <?php if ($sukses): ?><div class="alert alert-success alert-dismissible fade show"><?= $sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

                <!-- TOMBOL TAMBAH (HANYA MUNCUL UNTUK ADMIN) -->
                <?php if ($role == 'admin'): ?>
                    <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tambahModal">+ Tambah Barang</button>
                <?php endif; ?>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar Inventaris</h5>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <input type="text" id="cariAlat" class="form-control" placeholder="Cari nama alat / kategori..." onkeyup="filterAlat()">
                            </div>
                        </div>

                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 450px;">
                            <table class="table table-striped align-middle m-0" id="tabelAlat">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>No</th>
                                        <th>ID Alat</th>
                                        <th>Nama Alat</th>
                                        <th>Kategori</th>
                                        <th>Stok</th>
                                        <th>Kondisi</th>
                                        <?php if ($role == 'admin'): ?>
                                            <th class="text-center">Aksi</th>
                                        <?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $q = mysqli_query($koneksi, "SELECT a.*, k.nama_kategori FROM alat a JOIN kategori k ON a.id_kategori = k.id_kategori ORDER BY a.id_alat ASC");
                                    if (mysqli_num_rows($q) > 0): while ($r = mysqli_fetch_assoc($q)): 
                                    ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><span class="badge bg-secondary">#ID<?= $r['id_alat']; ?></span></td>
                                                <td class="fw-bold"><?= $r['nama_alat']; ?></td>
                                                <td><span class="badge bg-info text-white"><?= $r['nama_kategori']; ?></span></td>
                                                <td>
                                                    <?php if ($r['stok'] > 0): ?>
                                                        <span class="badge bg-primary"><?= $r['stok']; ?> unit</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger">Habis</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $kondisi = $r['kondisi'];
                                                    $badge_kondisi = 'bg-success';
                                                    if ($kondisi == 'Rusak Ringan') $badge_kondisi = 'bg-warning';
                                                    elseif ($kondisi == 'Rusak Berat') $badge_kondisi = 'bg-danger';
                                                    ?>
                                                    <span class="badge <?= $badge_kondisi; ?>"><?= $kondisi; ?></span>
                                                </td>
                                                
                                                <?php if ($role == 'admin'): ?>
                                                    <td class="text-center">
                                                        <button class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id_alat']; ?>">Edit</button>
                                                        <a href="alat.php?hapus=<?= $r['id_alat']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus alat ini?')">Hapus</a>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>

                                            <!-- MODAL EDIT ALAT (Hanya dirender jika admin) -->
                                            <?php if ($role == 'admin'): ?>
                                                <div class="modal fade" id="editModal<?= $r['id_alat']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST">
                                                                <div class="modal-header bg-warning text-white">
                                                                    <h5 class="modal-title">Edit Data Alat</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body text-start">
                                                                    <input type="hidden" name="id" value="<?= $r['id_alat']; ?>">
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Nama Alat</label>
                                                                        <input type="text" name="nama_alat" class="form-control" value="<?= $r['nama_alat']; ?>" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Kategori</label>
                                                                        <select name="id_kategori" class="form-select" required>
                                                                            <?php 
                                                                            mysqli_data_seek($data_kategori, 0);
                                                                            while ($kat = mysqli_fetch_assoc($data_kategori)): 
                                                                                $selected = ($kat['id_kategori'] == $r['id_kategori']) ? 'selected' : '';
                                                                            ?>
                                                                                <option value="<?= $kat['id_kategori']; ?>" <?= $selected; ?>><?= $kat['nama_kategori']; ?></option>
                                                                            <?php endwhile; ?>
                                                                        </select>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Stok</label>
                                                                        <input type="number" name="stok" class="form-control" value="<?= $r['stok']; ?>" min="0" required>
                                                                    </div>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Kondisi</label>
                                                                        <select name="kondisi" class="form-select" required>
                                                                            <option value="Baik" <?= ($r['kondisi'] == 'Baik') ? 'selected' : ''; ?>>Baik</option>
                                                                            <option value="Rusak Ringan" <?= ($r['kondisi'] == 'Rusak Ringan') ? 'selected' : ''; ?>>Rusak Ringan</option>
                                                                            <option value="Rusak Berat" <?= ($r['kondisi'] == 'Rusak Berat') ? 'selected' : ''; ?>>Rusak Berat</option>
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="submit" name="edit" class="btn btn-warning text-white">Simpan Perubahan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                    <?php endwhile; else: ?>
                                        <tr>
                                            <td colspan="<?= ($role == 'admin') ? '7' : '6'; ?>" class="text-center text-muted">Belum ada data alat.</td>
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

    <!-- MODAL TAMBAH ALAT (Hanya dirender jika admin) -->
    <?php if ($role == 'admin'): ?>
        <div class="modal fade" id="tambahModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Tambah Barang Baru</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Nama Barang</label>
                                <input type="text" name="nama_alat" class="form-control" placeholder="Masukan nama barang" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kategori</label>
                                <select name="id_kategori" class="form-select" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php 
                                    mysqli_data_seek($data_kategori, 0);
                                    while ($kat = mysqli_fetch_assoc($data_kategori)): 
                                    ?>
                                        <option value="<?= $kat['id_kategori']; ?>"><?= $kat['nama_kategori']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Stok</label>
                                <input type="number" name="stok" class="form-control" placeholder="Masukan jumlah stok" min="0" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kondisi Awal</label>
                                <select name="kondisi" class="form-select" required>
                                    <option value="Baik">Baik</option>
                                    <option value="Rusak Ringan">Rusak Ringan</option>
                                    <option value="Rusak Berat">Rusak Berat</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="tambah" class="btn btn-success">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script src="asset/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterAlat() {
            let input = document.getElementById('cariAlat');
            let filter = input.value.toLowerCase();
            let table = document.getElementById('tabelAlat');
            let tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                let tdNama = tr[i].getElementsByTagName('td')[2];
                let tdKat = tr[i].getElementsByTagName('td')[3];
                if (tdNama || tdKat) {
                    let textNama = tdNama ? tdNama.textContent || tdNama.innerText : "";
                    let textKat = tdKat ? tdKat.textContent || tdKat.innerText : "";
                    if (textNama.toLowerCase().indexOf(filter) > -1 || textKat.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>

</html>