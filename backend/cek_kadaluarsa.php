<?php
// File ini bisa di-include di halaman yang sering diakses, seperti header.php
// agar pengecekan berjalan secara berkala saat ada aktivitas pengguna.

// Pastikan koneksi sudah ada jika file ini di-include terpisah
if (!isset($koneksi)) {
    include __DIR__ . '/koneksi.php';
}

// 1. Cari semua pesanan yang statusnya 'menunggu_pembayaran' dan sudah lewat 10 menit
$sql_cari = "SELECT id_pesanan FROM pesanan WHERE status_pesanan = 'menunggu_pembayaran' AND tgl_pesanan < NOW() - INTERVAL 10 MINUTE";
$result_cari = mysqli_query($koneksi, $sql_cari);

if ($result_cari && mysqli_num_rows($result_cari) > 0) {
    
    // Siapkan statement untuk update stok agar bisa dipakai berulang kali
    $stmt_update_stok = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
    
    // Siapkan statement untuk update status pesanan
    $stmt_batal = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ?");

    // 2. Loop setiap pesanan yang kadaluarsa
    while ($pesanan_kadaluarsa = mysqli_fetch_assoc($result_cari)) {
        $id = $pesanan_kadaluarsa['id_pesanan'];

        // 3. Mulai transaksi untuk setiap pesanan agar aman
        mysqli_begin_transaction($koneksi);
        try {
            // Ambil detail item dari pesanan yang akan dibatalkan
            $sql_items = "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "s", $id);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            // 4. Kembalikan stok untuk setiap item
            while($item = mysqli_fetch_assoc($result_items)) {
                mysqli_stmt_bind_param($stmt_update_stok, "is", $item['jumlah'], $item['id_produk']);
                mysqli_stmt_execute($stmt_update_stok);
            }
            
            // 5. Ubah status pesanan menjadi 'dibatalkan'
            mysqli_stmt_bind_param($stmt_batal, "s", $id);
            mysqli_stmt_execute($stmt_batal);

            // 6. Jika semua berhasil, simpan perubahan
            mysqli_commit($koneksi);

        } catch (Exception $e) {
            // Jika ada satu saja error, batalkan semua perubahan untuk pesanan ini
            mysqli_rollback($koneksi);
            // Anda bisa menambahkan log error di sini jika perlu
        }
    }
}
?>