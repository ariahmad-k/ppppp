<?php
session_start();
include '../backend/koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_data'])) {
    $nama_pemesan = trim($_POST['nama_pemesan']);
    $no_telepon = trim($_POST['no_telepon']);
    $cart_data = json_decode($_POST['cart_data'], true);

    if (empty($nama_pemesan) || empty($no_telepon) || empty($cart_data) || json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['notif_cart'] = ['pesan' => 'Data tidak lengkap. Harap isi semua kolom.', 'tipe' => 'danger'];
        header('Location: keranjang.php');
        exit;
    }

    mysqli_begin_transaction($koneksi);

    // GANTI SELURUH BLOK try...catch ANDA DENGAN INI
    try {
        // 2. Validasi Harga & Stok di Sisi Server
        $total_harga_server = 0;
        $produk_valid = [];
        $stmt_produk = mysqli_prepare($koneksi, "SELECT id_produk, nama_produk, harga, stok, status_produk FROM produk WHERE id_produk = ? FOR UPDATE");
        
        foreach ($cart_data as $id => $item) {
            mysqli_stmt_bind_param($stmt_produk, "s", $id);
            mysqli_stmt_execute($stmt_produk);
            $produk_db = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_produk));

            if (!$produk_db || $produk_db['status_produk'] !== 'aktif') {
                throw new Exception("Produk '{$item['nama']}' tidak tersedia saat ini.");
            }
            if ($produk_db['stok'] < $item['jumlah']) {
                throw new Exception("Stok untuk produk '{$item['nama']}' tidak mencukupi (sisa: {$produk_db['stok']}).");
            }
            $total_harga_server += $produk_db['harga'] * $item['jumlah'];
            $produk_valid[$id] = $produk_db;
        }
        mysqli_stmt_close($stmt_produk);

        // 3. Buat entri baru di tabel `pesanan`
        $id_pesanan_baru = "ONLINE-" . time();
        $tgl_pesanan = date("Y-m-d H:i:s");
        $tipe_pesanan = 'online';
        $status_pesanan = 'menunggu_pembayaran';
        $metode_pembayaran = 'transfer';
        $jenis_pesanan_form = 'take_away'; // Default untuk online

        $stmt_pesanan = mysqli_prepare($koneksi, "INSERT INTO pesanan (id_pesanan, tipe_pesanan, jenis_pesanan, nama_pemesan, no_telepon, tgl_pesanan, total_harga, metode_pembayaran, status_pesanan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt_pesanan, "ssssssdss", $id_pesanan_baru, $tipe_pesanan, $jenis_pesanan_form, $nama_pemesan, $no_telepon, $tgl_pesanan, $total_harga_server, $metode_pembayaran, $status_pesanan);
        mysqli_stmt_execute($stmt_pesanan);

        // 4. Masukkan semua item ke tabel `detail_pesanan` DAN LANGSUNG KURANGI STOK
        $stmt_detail = mysqli_prepare($koneksi, "INSERT INTO detail_pesanan (id_pesanan, id_produk, jumlah, harga_saat_transaksi, sub_total) VALUES (?, ?, ?, ?, ?)");

        // Siapkan statement untuk update stok
        $stmt_update_stok = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok - ? WHERE id_produk = ?");

        foreach ($cart_data as $id => $item) {
            // Insert ke detail pesanan
            $harga_saat_ini = $produk_valid[$id]['harga'];
            $sub_total = $harga_saat_ini * $item['jumlah'];
            mysqli_stmt_bind_param($stmt_detail, "ssidd", $id_pesanan_baru, $id, $item['jumlah'], $harga_saat_ini, $sub_total);
            mysqli_stmt_execute($stmt_detail);

            // --- TAMBAHAN: Langsung kurangi stok di tabel produk ---
            mysqli_stmt_bind_param($stmt_update_stok, "is", $item['jumlah'], $id);
            mysqli_stmt_execute($stmt_update_stok);
        }

        // 5. Jika semua berhasil, simpan perubahan
        mysqli_commit($koneksi);

        // 6. Arahkan ke halaman konfirmasi
        header("Location: konfirmasi.php?id=" . $id_pesanan_baru);
        exit;
    } catch (Exception $e) {
        // Jika ada error (misal stok tidak cukup), batalkan semua query
        mysqli_rollback($koneksi);
        $_SESSION['notif_cart'] = ['pesan' => 'Gagal membuat pesanan: ' . $e->getMessage(), 'tipe' => 'danger'];
        header('Location: keranjang.php');
        exit;
    }
} else {
    header('Location: menu.php');
    exit;
}
?>