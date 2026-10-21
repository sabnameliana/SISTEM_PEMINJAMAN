<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}

$role = $_SESSION['role'] ?? '';

// BATASAN FITUR SESUAI SOAL: Kategori HANYA untuk Admin
if ($role != 'admin') {
    echo "<script>alert('Akses ditolak! Menu ini khusus untuk Admin.'); window.location.href='index.php';</script>";
    exit();
}

$sukses = $error = "";

// Proses Tambah Kategori
if (isset($_POST['tambah'])) {
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    if (!empty($nama_kategori)) {
        $query = mysqli_query($koneksi, "INSERT INTO kategori (nama_kategori) VALUES ('$nama_kategori')");
        if ($query) {
            $sukses = "Kategori berhasil ditambahkan!";
        } else {
            $error = "Gagal menyimpan kategori!";
        }
    } else {
        $error = "Nama kategori wajib diisi!";
    }
}

// Proses Edit Kategori
if (isset($_POST['edit'])) {
    $id = $_POST['id'];
    $nama_kategori = mysqli_real_escape_string($koneksi, $_POST['nama_kategori']);
    
    $query = mysqli_query($koneksi, "UPDATE kategori SET nama_kategori='$nama_kategori' WHERE id_kategori='$id'");
    if ($query) {
        $sukses = "Kategori berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui kategori!";
    }
}

// Proses Hapus Kategori
if (isset($_GET['hapus'])) {
    $id_kategori = $_GET['hapus'];
    mysqli_query($koneksi, "DELETE FROM kategori WHERE id_kategori = '$id_kategori'");
    header("location:kategori.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola Kategori - Peminjaman Alat</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR KHUSUS ADMIN SESUAI TABEL FITUR -->
            <div class="col-md-2 bg-dark min-vh-100 p-3 text-white">
                <h5 class="text-center">PEMINJAMAN</h5>
                <hr>
                <ul class="nav flex-column">
                    <li class="nav-item mb-2"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                    <li class="nav-item mb-2"><a href="kategori.php" class="nav-link text-white active bg-info rounded">Kategori</a></li>
                    <li class="nav-item mb-2"><a href="alat.php" class="nav-link text-white">Data Barang</a></li>
                    <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                    <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                    <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white">User</a></li>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Kelola Kategori</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin ingin logout?')">Logout</a>
                    </div>
                </div>

                <?php if ($sukses): ?><div class="alert alert-success alert-dismissible fade show"><?= $sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

                <button class="btn btn-primary text-white btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tambahModal">+ Tambah Kategori</button>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar Kategori</h5>

                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 450px;">
                            <table class="table table-striped align-middle m-0" id="tabelKategori">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th width="8%">No</th>
                                        <th width="20%">ID Kategori</th>
                                        <th>Nama Kategori</th>
                                        <th width="20%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $no = 1;
                                    $q = mysqli_query($koneksi, "SELECT * FROM kategori ORDER BY id_kategori ASC");
                                    if (mysqli_num_rows($q) > 0): while ($r = mysqli_fetch_assoc($q)): 
                                    ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td><span class="badge bg-secondary">#KTG-<?= $r['id_kategori']; ?></span></td>
                                                <td class="fw-bold"><?= $r['nama_kategori']; ?></td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editModal<?= $r['id_kategori']; ?>">Edit</button>
                                                    <a href="kategori.php?hapus=<?= $r['id_kategori']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus kategori ini?')">Hapus</a>
                                                </td>
                                            </tr>

                                            <!-- MODAL EDIT KATEGORI -->
                                            <div class="modal fade" id="editModal<?= $r['id_kategori']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header bg-warning text-white">
                                                                <h5 class="modal-title">Edit Kategori</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="id" value="<?= $r['id_kategori']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama Kategori</label>
                                                                    <input type="text" name="nama_kategori" class="form-control" value="<?= $r['nama_kategori']; ?>" required>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="submit" name="edit" class="btn btn-warning text-white">Simpan Perubahan</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                    <?php endwhile; else: ?>
                                        <tr><td colspan="4" class="text-center text-muted">Belum ada data kategori.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH KATEGORI -->
    <div class="modal fade" id="tambahModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah Kategori Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Kategori</label>
                            <input type="text" name="nama_kategori" class="form-control" placeholder="Masukan kategori baru" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="tambah" class="btn btn-success text-white">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>
</html>