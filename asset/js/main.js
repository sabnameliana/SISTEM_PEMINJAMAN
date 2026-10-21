// Function Konfirmasi Logout
function konfirmasiLogout(event) {
    const yakin = confirm("Apakah Anda yakin ingin keluar dari Sistem Kasir?");
    if (!yakin) {
        event.preventDefault(); // Batalkan navigasi jika user klik Cancel
    }
}

// Efek Interaktif & Konfirmasi Hapus Data
document.addEventListener("DOMContentLoaded", function () {
    console.log("Sistem Kasir Ready!");

    // Konfirmasi Otomatis untuk Tombol Hapus (jika ada class .btn-hapus)
    const btnHapus = document.querySelectorAll(".btn-hapus");
    btnHapus.forEach(btn => {
        btn.addEventListener("click", function (e) {
            const konfirmasi = confirm("Apakah Anda yakin ingin menghapus data ini?");
            if (!konfirmasi) {
                e.preventDefault();
            }
        });
    });
});