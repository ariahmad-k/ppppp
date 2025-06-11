<?php
session_start();
include '../../koneksi.php'; // Path disesuaikan dari users/kasir/

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

// 2. VALIDASI INPUT ID PESANAN
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Jika tidak ada ID di URL, kembali ke halaman riwayat
    header('Location: riwayat_pesanan.php');
    exit;
}
$id_pesanan = $_GET['id'];

// 3. AMBIL DATA HEADER PESANAN
$sql_header = "SELECT 
                    pk.id_pesanan, 
                    pk.nama_pemesan, 
                    pk.tgl_pesanan, 
                    pk.total_harga, 
                    pk.metode_pembayaran,
                    pk.jenis_pesanan,
                    pk.catatan,
                    k.nama AS nama_kasir
                FROM 
                    pesanan pk
                JOIN 
                    karyawan k ON pk.id_karyawan = k.id_karyawan
                WHERE 
                    pk.id_pesanan = ?";
$stmt_header = mysqli_prepare($koneksi, $sql_header);
mysqli_stmt_bind_param($stmt_header, "s", $id_pesanan);
mysqli_stmt_execute($stmt_header);
$result_header = mysqli_stmt_get_result($stmt_header);
$pesanan = mysqli_fetch_assoc($result_header);

// Jika pesanan dengan ID tersebut tidak ditemukan, tampilkan pesan error
if (!$pesanan) {
    die("Error: Pesanan tidak ditemukan.");
}

// 4. AMBIL DATA ITEM-ITEM PESANAN
$sql_detail = "SELECT
                    dp.jumlah,
                    dp.harga_saat_transaksi AS harga_saat_transaksi,
                    dp.sub_total,
                    p.nama_produk
                FROM
                    detail_pesanan dp
                JOIN
                    produk p ON dp.id_produk = p.id_produk
                WHERE
                    dp.id_pesanan = ?";
$stmt_detail = mysqli_prepare($koneksi, $sql_detail);
mysqli_stmt_bind_param($stmt_detail, "s", $id_pesanan);
mysqli_stmt_execute($stmt_detail);
$result_detail = mysqli_stmt_get_result($stmt_detail);
$detail_items = [];
while ($row = mysqli_fetch_assoc($result_detail)) {
    $detail_items[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Detail Pesanan - Kasir</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <style>
        /* CSS untuk tampilan cetak */
        @media print {
            body.printing * {
                visibility: hidden;
            }

            body.printing #invoice-area,
            body.printing #invoice-area * {
                visibility: visible;
            }

            body.printing #invoice-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'inc/navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php
            include 'inc/sidebar.php';
            ?>
                <div class="sb-sidenav-footer">
                    <div class="small">Kasir :</div>
                    <?php
                    echo isset($_SESSION['user']['name']) ? $_SESSION['user']['name'] : 'Unknown';
                    ?>
                </div>
            </nav>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Detail Pesanan</h1>
                    <ol class="breadcrumb mb-4 no-print">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="pesanan_data_riwayat.php">Riwayat Pesanan</a></li>
                        <li class="breadcrumb-item active">Detail</li>
                    </ol>

                    <div id="invoice-area">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-receipt me-1"></i>
                                    <strong>No. Pesanan: <?php echo htmlspecialchars($pesanan['id_pesanan']); ?></strong>
                                </div>
                                <div>
                                    <small>Tanggal: <?php echo date('d F Y, H:i', strtotime($pesanan['tgl_pesanan'])); ?></small>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h5>Detail Transaksi:</h5>
                                        <ul class="list-unstyled">
                                            <li><strong>Nama Pemesan:</strong> <?php echo htmlspecialchars($pesanan['nama_pemesan']); ?></li>
                                            <li><strong>Kasir:</strong> <?php echo htmlspecialchars($pesanan['nama_kasir']); ?></li>
                                            <li><strong>Jenis:</strong> <?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $pesanan['jenis_pesanan']))); ?></li>
                                            <li><strong>Metode Bayar:</strong> <?php echo htmlspecialchars(ucfirst($pesanan['metode_pembayaran'])); ?></li>
                                        </ul>
                                    </div>
                                    <?php if (!empty($pesanan['catatan'])): ?>
                                        <div class="col-md-6">
                                            <h5>Catatan:</h5>
                                            <p><?php echo nl2br(htmlspecialchars($pesanan['catatan'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h5 class="mt-4">Rincian Item:</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nama Produk</th>
                                                <th class="text-center">Jumlah</th>
                                                <th class="text-end">Harga Satuan</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($detail_items as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['nama_produk']); ?></td>
                                                    <td class="text-center"><?php echo $item['jumlah']; ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($item['harga_saat_transaksi'], 0, ',', '.'); ?></td>
                                                    <td class="text-end">Rp <?php echo number_format($item['sub_total'], 0, ',', '.'); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-light">
                                                <th colspan="3" class="text-end"><strong>Total Keseluruhan</strong></th>
                                                <th class="text-end"><strong>Rp <?php echo number_format($pesanan['total_harga'], 0, ',', '.'); ?></strong></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-center no-print">
                        <a href="pesanan_data_riwayat.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Kembali ke Riwayat
                        </a>
                        <button onclick="printInvoice()" class="btn btn-primary">
                            <i class="fas fa-print"></i> Cetak Struk
                        </button>
                    </div>

                </div>
            </main>
            <footer class="py-4 bg-light mt-auto no-print">
                <div class="container-fluid px-4">
                    <div class="d-flex align-items-center justify-content-between small">
                        <div class="text-muted">Copyright &copy; KueBalok 2025</div>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../js/scripts.js"></script>
    <script>
        function printInvoice() {
            // Menambahkan class 'printing' ke body untuk memicu CSS @media print
            document.body.classList.add('printing');
            window.print();
            // Menghapus class setelah dialog print ditutup
            document.body.classList.remove('printing');
        }
    </script>
</body>

</html>