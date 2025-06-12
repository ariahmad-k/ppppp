<?php
session_start();
include '../../../koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 2. Ambil parameter filter dari URL
$jenis_laporan = $_GET['jenis_laporan'] ?? 'pemasukan';
$periode = $_GET['periode'] ?? 'bulanan';
$nilai = $_GET['nilai'] ?? '';

// 3. Logika penentuan tanggal (SAMA PERSIS dengan di halaman laporan)
$tanggal_mulai = '';
$tanggal_selesai = '';

switch ($periode) {
    case 'harian':
        if (empty($nilai)) { $nilai = date('Y-m-d'); }
        $tanggal_mulai = $nilai;
        $tanggal_selesai = $nilai;
        break;
    case 'mingguan':
        if (empty($nilai)) { $nilai = date('Y-\WW'); }
        [$tahun, $minggu] = explode('-W', $nilai);
        $dto = new DateTime();
        $dto->setISODate((int)$tahun, (int)$minggu);
        $tanggal_mulai = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $tanggal_selesai = $dto->format('Y-m-d');
        break;
    default: // bulanan
        if (empty($nilai)) { $nilai = date('Y-m'); }
        $tanggal_mulai = $nilai . '-01';
        $tanggal_selesai = date('Y-m-t', strtotime($tanggal_mulai));
        break;
}

// 4. Set Header HTTP untuk download file CSV
$filename = "laporan_{$jenis_laporan}_{$tanggal_mulai}_sd_{$tanggal_selesai}.csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// 5. Buka output stream PHP untuk menulis file
$output = fopen('php://output', 'w');

// 6. Logika untuk setiap jenis laporan
if ($jenis_laporan === 'pemasukan') {
    // Tulis header kolom untuk file CSV
    fputcsv($output, ['No. Pesanan', 'Tanggal', 'Waktu', 'Nama Pemesan', 'Kasir', 'Tipe Pesanan', 'Total Harga']);

    // Ambil data dari database
    $sql = "SELECT pk.id_pesanan, pk.tgl_pesanan, pk.nama_pemesan, pk.tipe_pesanan, pk.total_harga, k.nama AS nama_kasir 
            FROM pesanan pk 
            LEFT JOIN karyawan k ON pk.id_karyawan = k.id_karyawan 
            WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ? 
            ORDER BY pk.tgl_pesanan DESC";
    
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Tulis setiap baris data ke file CSV
    while ($row = mysqli_fetch_assoc($result)) {
        $rowData = [
            $row['id_pesanan'],
            date('d-m-Y', strtotime($row['tgl_pesanan'])),
            date('H:i:s', strtotime($row['tgl_pesanan'])),
            $row['nama_pemesan'],
            $row['nama_kasir'] ?? 'Online',
            ucfirst($row['tipe_pesanan']),
            $row['total_harga']
        ];
        fputcsv($output, $rowData);
    }

} elseif ($jenis_laporan === 'produk') {
    // Tulis header kolom
    fputcsv($output, ['Nama Produk', 'Total Terjual']);

    // Ambil data produk
    $sql = "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual 
            FROM detail_pesanan dp 
            JOIN produk p ON dp.id_produk = p.id_produk 
            JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan 
            WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ? 
            GROUP BY p.id_produk, p.nama_produk 
            ORDER BY total_terjual DESC";
            
    $stmt = mysqli_prepare($koneksi, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Tulis data ke CSV
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, [$row['nama_produk'], $row['total_terjual']]);
    }
    
} else {
    // Default jika jenis laporan lain belum diimplementasikan untuk ekspor
    fputcsv($output, ['Ekspor untuk jenis laporan ini belum tersedia.']);
}

// Tutup output stream dan hentikan skrip
fclose($output);
exit;
?>