<?php
// Mengatur header agar output dikenali sebagai JSON
header('Content-Type: application/json');
include '../backend/koneksi.php';

// Default response jika terjadi error
$response = ['stok' => 0, 'error' => true, 'message' => 'ID produk tidak disediakan.'];

if (isset($_GET['id'])) {
    $id_produk = $_GET['id'];

    // Ambil stok dari tabel produk menggunakan prepared statement
    $stmt = mysqli_prepare($koneksi, "SELECT stok FROM produk WHERE id_produk = ? AND status_produk = 'aktif'");
    mysqli_stmt_bind_param($stmt, "s", $id_produk);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        if ($data) {
            // Jika produk ditemukan, siapkan response sukses
            $response = [
                'stok' => (int)$data['stok'],
                'error' => false,
                'message' => 'Stok ditemukan.'
            ];
        } else {
            // Jika produk tidak ditemukan atau tidak aktif
            $response = ['stok' => 0, 'error' => true, 'message' => 'Produk tidak ditemukan atau tidak aktif.'];
        }
    } else {
        // Jika query gagal
        $response = ['stok' => 0, 'error' => true, 'message' => 'Query database gagal.'];
    }
}

// Mengubah array PHP menjadi format JSON dan menampilkannya
echo json_encode($response);
?>