<?php
session_start();
include "config/koneksi.php";

if (!isset($_SESSION['status']) || $_SESSION['status'] != "login") {
    header("location:login.php?pesan=belum_login");
    exit();
}

$role = $_SESSION['role'] ?? '';
$id_user_login = $_SESSION['id_user'] ?? '';

// BATASAN FITUR: CRUD User Khusus Admin
if ($role != 'admin') {
    echo "<script>alert('Akses ditolak! Menu kelola user hanya untuk Admin.'); window.location.href='index.php';</script>";
    exit();
}

$sukses = $error = "";

// 1. PROSES TAMBAH USER
if (isset($_POST['tambah'])) {
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']);
    $role_user= mysqli_real_escape_string($koneksi, $_POST['role']);

    // Cek username ganda
    $cek_user = mysqli_query($koneksi, "SELECT * FROM user WHERE username = '$username'");
    if (mysqli_num_rows($cek_user) > 0) {
        $error = "Username sudah digunakan! Pilih username lain.";
    } else {
        $query = mysqli_query($koneksi, "INSERT INTO user (nama, username, password, role) VALUES ('$nama', '$username', '$password', '$role_user')");
        if ($query) {
            mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Menambahkan user baru: $nama ($role_user)')");
            $sukses = "User baru berhasil ditambahkan!";
        } else {
            $error = "Gagal menambah user!";
        }
    }
}

// 2. PROSES EDIT USER
if (isset($_POST['edit'])) {
    $id_user  = $_POST['id_user'];
    $nama     = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $role_user= mysqli_real_escape_string($koneksi, $_POST['role']);
    $pass_baru= $_POST['password'];

    if (!empty($pass_baru)) {
        $password_hashed = md5($pass_baru);
        $query = mysqli_query($koneksi, "UPDATE user SET nama='$nama', username='$username', password='$password_hashed', role='$role_user' WHERE id_user='$id_user'");
    } else {
        $query = mysqli_query($koneksi, "UPDATE user SET nama='$nama', username='$username', role='$role_user' WHERE id_user='$id_user'");
    }

    if ($query) {
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Memperbarui data user: $nama')");
        $sukses = "Data user berhasil diperbarui!";
    } else {
        $error = "Gagal memperbarui user!";
    }
}

// 3. PROSES HAPUS USER
if (isset($_GET['hapus'])) {
    $id_user_hapus = $_GET['hapus'];
    if ($id_user_hapus == $id_user_login) {
        echo "<script>alert('Anda tidak dapat menghapus akun Anda sendiri yang sedang login!'); window.location.href='user.php';</script>";
        exit();
    }

    $q_usr = mysqli_query($koneksi, "SELECT nama FROM user WHERE id_user = '$id_user_hapus'");
    $d_usr = mysqli_fetch_assoc($q_usr);
    $nama_dihapus = $d_usr['nama'] ?? 'ID #'.$id_user_hapus;

    $hapus = mysqli_query($koneksi, "DELETE FROM user WHERE id_user = '$id_user_hapus'");
    if ($hapus) {
        mysqli_query($koneksi, "INSERT INTO log_aktivitas (id_user, aktivitas) VALUES ('$id_user_login', 'Menghapus user: $nama_dihapus')");
    }
    header("location:user.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Kelola User - Sistem Peminjaman</title>
    <link rel="stylesheet" href="asset/css/bootstrap.min.css">
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
                    <li class="nav-item mb-2"><a href="kategori.php" class="nav-link text-white">Kategori</a></li>
                    <li class="nav-item mb-2"><a href="alat.php" class="nav-link text-white">Data Barang</a></li>
                    <li class="nav-item mb-2"><a href="peminjaman.php" class="nav-link text-white">Peminjaman</a></li>
                    <li class="nav-item mb-2"><a href="pengembalian.php" class="nav-link text-white">Pengembalian</a></li>
                    <li class="nav-item mb-2"><a href="user.php" class="nav-link text-white active bg-info rounded">User</a></li>
                </ul>
            </div>

            <!-- KONTEN UTAMA -->
            <div class="col-md-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-2">
                    <h4>Kelola User</h4>
                    <div>
                        <span>Halo, <b><?= $_SESSION['nama']; ?></b> <span class="badge bg-secondary ms-1 text-uppercase" style="font-size: 10px;"><?= $role; ?></span></span>
                        <a href="logout.php" class="btn btn-outline-danger btn-sm ms-3" onclick="return confirm('Yakin logout?')">Logout</a>
                    </div>
                </div>

                <?php if ($sukses): ?><div class="alert alert-success alert-dismissible fade show"><?= $sukses; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
                <?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show"><?= $error; ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

                <button class="btn btn-primary btn-sm mb-3" data-bs-toggle="modal" data-bs-target="#tambahUserModal">+ Tambah User</button>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="mb-3">Daftar User Sistem</h5>
                        <div class="overflow-auto border rounded p-2 bg-white" style="max-height: 450px;">
                            <table class="table table-striped align-middle m-0">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th width="5%">No</th>
                                        <th>Nama Lengkap</th>
                                        <th>Username</th>
                                        <th>Role / Hak Akses</th>
                                        <th width="15%" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $no = 1;
                                    $q_user = mysqli_query($koneksi, "SELECT * FROM user ORDER BY id_user ASC");
                                    if (mysqli_num_rows($q_user) > 0):
                                        while ($u = mysqli_fetch_assoc($q_user)):
                                    ?>
                                            <tr>
                                                <td><?= $no++; ?></td>
                                                <td class="fw-bold"><?= $u['nama']; ?></td>
                                                <td><code><?= $u['username']; ?></code></td>
                                                <td>
                                                    <?php
                                                    $r_badge = 'bg-primary';
                                                    if ($u['role'] == 'admin') $r_badge = 'bg-danger';
                                                    elseif ($u['role'] == 'petugas') $r_badge = 'bg-warning text-dark';
                                                    ?>
                                                    <span class="badge <?= $r_badge; ?> text-uppercase"><?= $u['role']; ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <button class="btn btn-sm btn-warning text-white" data-bs-toggle="modal" data-bs-target="#editUserModal<?= $u['id_user']; ?>">Edit</button>
                                                    <?php if ($u['id_user'] != $id_user_login): ?>
                                                        <a href="user.php?hapus=<?= $u['id_user']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin ingin menghapus user ini?')">Hapus</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- MODAL EDIT USER -->
                                            <div class="modal fade" id="editUserModal<?= $u['id_user']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header bg-warning text-white">
                                                                <h5 class="modal-title">Edit Data User</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body text-start">
                                                                <input type="hidden" name="id_user" value="<?= $u['id_user']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Nama Lengkap</label>
                                                                    <input type="text" name="nama" class="form-control" value="<?= $u['nama']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Username</label>
                                                                    <input type="text" name="username" class="form-control" value="<?= $u['username']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Password Baru <small class="text-muted">(Kosongkan jika tidak diubah)</small></label>
                                                                    <input type="password" name="password" class="form-control" placeholder="Isi password baru jika mau ubah">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Role Akses</label>
                                                                    <select name="role" class="form-select" required>
                                                                        <option value="admin" <?= ($u['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                                                                        <option value="petugas" <?= ($u['role'] == 'petugas') ? 'selected' : ''; ?>>Petugas</option>
                                                                        <option value="peminjam" <?= ($u['role'] == 'peminjam') ? 'selected' : ''; ?>>Peminjam</option>
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

                                    <?php endwhile; else: ?>
                                        <tr><td colspan="5" class="text-center text-muted">Belum ada user.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH USER -->
    <div class="modal fade" id="tambahUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Tambah User Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" placeholder="Masukan nama lengkap" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" placeholder="Masukan username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" placeholder="Masukan password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role Akses</label>
                            <select name="role" class="form-select" required>
                                <option value="peminjam">Peminjam</option>
                                <option value="petugas">Petugas</option>
                                <option value="admin">Admin</option>
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

    <script src="asset/js/bootstrap.bundle.min.js"></script>
</body>

</html>