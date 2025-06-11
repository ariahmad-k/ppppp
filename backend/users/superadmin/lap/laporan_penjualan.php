<?php
session_start();
include '../../../koneksi.php';

// 1. OTENTIKASI & OTORISASI
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'owner') {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA FILTER
// Defaultnya menampilkan laporan pemasukan untuk bulan ini
$jenis_laporan = $_GET['jenis_laporan'] ?? 'pemasukan';
$tanggal_mulai = $_GET['tanggal_mulai'] ?? date('Y-m-01');
$tanggal_selesai = $_GET['tanggal_selesai'] ?? date('Y-m-d');

// Judul Halaman dinamis
$judul_halaman = "Laporan Pemasukan";

// 3. STRUKTUR KONDISIONAL UNTUK SETIAP JENIS LAPORAN
if ($jenis_laporan === 'pemasukan') {
    $judul_halaman = "Laporan Pemasukan";
    // Query untuk kartu ringkasan
    $stmt_pendapatan = mysqli_prepare($koneksi, "SELECT SUM(total_harga) AS total FROM pesanan WHERE DATE(tgl_pesanan) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt_pendapatan, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_pendapatan);
    $total_pendapatan = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_pendapatan))['total'] ?? 0;

    $stmt_transaksi = mysqli_prepare($koneksi, "SELECT COUNT(id_pesanan) AS jumlah FROM pesanan WHERE DATE(tgl_pesanan) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt_transaksi, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_transaksi);
    $jumlah_transaksi = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_transaksi))['jumlah'] ?? 0;

    $stmt_item = mysqli_prepare($koneksi, "SELECT SUM(dp.jumlah) AS total FROM detail_pesanan dp JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ?");
    mysqli_stmt_bind_param($stmt_item, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_item);
    $total_item_terjual = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_item))['total'] ?? 0;

    // Query untuk tabel rincian
    $sql_rincian = "SELECT pk.id_pesanan, pk.tgl_pesanan, pk.total_harga, k.nama AS nama_kasir FROM pesanan pk JOIN karyawan k ON pk.id_karyawan = k.id_karyawan WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ? ORDER BY pk.tgl_pesanan DESC";
    $stmt_rincian = mysqli_prepare($koneksi, $sql_rincian);
    mysqli_stmt_bind_param($stmt_rincian, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_rincian);
    $result_rincian = mysqli_stmt_get_result($stmt_rincian);
    $daftar_transaksi = [];
    while ($row = mysqli_fetch_assoc($result_rincian)) {
        $daftar_transaksi[] = $row;
    }
} elseif ($jenis_laporan === 'produk') {
    $judul_halaman = "Laporan Analisis Produk";
    $base_sql = "SELECT p.nama_produk, SUM(dp.jumlah) AS total_terjual FROM detail_pesanan dp JOIN produk p ON dp.id_produk = p.id_produk JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan WHERE DATE(pk.tgl_pesanan) BETWEEN ? AND ? GROUP BY p.id_produk, p.nama_produk";

    $stmt_terlaris = mysqli_prepare($koneksi, $base_sql . " ORDER BY total_terjual DESC LIMIT 10");
    mysqli_stmt_bind_param($stmt_terlaris, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_terlaris);
    $produk_terlaris = mysqli_fetch_all(mysqli_stmt_get_result($stmt_terlaris), MYSQLI_ASSOC);

    $stmt_kurang_laku = mysqli_prepare($koneksi, $base_sql . " ORDER BY total_terjual ASC LIMIT 10");
    mysqli_stmt_bind_param($stmt_kurang_laku, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_kurang_laku);
    $produk_kurang_laku = mysqli_fetch_all(mysqli_stmt_get_result($stmt_kurang_laku), MYSQLI_ASSOC);
} elseif ($jenis_laporan === 'jam_sibuk') {
    $judul_halaman = "Analisis Jam Sibuk";
    $sql_jam = "SELECT HOUR(tgl_pesanan) as jam, COUNT(id_pesanan) as jumlah_transaksi FROM pesanan WHERE DATE(tgl_pesanan) BETWEEN ? AND ? GROUP BY jam ORDER BY jam ASC";
    $stmt_jam = mysqli_prepare($koneksi, $sql_jam);
    mysqli_stmt_bind_param($stmt_jam, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_jam);
    $data_jam_sibuk_raw = mysqli_fetch_all(mysqli_stmt_get_result($stmt_jam), MYSQLI_ASSOC);

    // Siapkan data untuk Chart.js
    $labels_jam = [];
    $data_transaksi_per_jam = [];
    $all_hours = range(0, 23); // Buat array jam dari 0-23
    $sales_by_hour = array_column($data_jam_sibuk_raw, 'jumlah_transaksi', 'jam'); // map jam ke penjualan

    foreach ($all_hours as $hour) {
        $labels_jam[] = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        $data_transaksi_per_jam[] = $sales_by_hour[$hour] ?? 0;
    }
} elseif ($jenis_laporan === 'kategori_pembayaran') {
    $judul_halaman = "Analisis Tipe Produk & Pembayaran";

    // --- PERUBAHAN: Query untuk Tipe Produk (bukan lagi Kategori) ---
    $sql_tipe_produk = "SELECT 
                            CASE 
                                WHEN LEFT(p.id_produk, 2) = 'KB' THEN 'Kue Balok'
                                WHEN LEFT(p.id_produk, 2) = 'KS' THEN 'Ketan Susu'
                                WHEN LEFT(p.id_produk, 2) = 'OT' THEN 'Makanan Lain'
                                WHEN LEFT(p.id_produk, 2) = 'DK' THEN 'Minuman'
                                ELSE 'Lainnya' 
                            END AS tipe_produk,
                            SUM(dp.sub_total) as total_pendapatan 
                        FROM detail_pesanan dp 
                        JOIN produk p ON dp.id_produk = p.id_produk 
                        JOIN pesanan pk ON dp.id_pesanan = pk.id_pesanan 
                        WHERE pk.status_pesanan = 'selesai' AND DATE(pk.tgl_pesanan) BETWEEN ? AND ? 
                        GROUP BY tipe_produk
                        ORDER BY total_pendapatan DESC";

    $stmt_tipe_produk = mysqli_prepare($koneksi, $sql_tipe_produk);
    mysqli_stmt_bind_param($stmt_tipe_produk, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_tipe_produk);
    $data_tipe_produk = mysqli_fetch_all(mysqli_stmt_get_result($stmt_tipe_produk), MYSQLI_ASSOC);

    // Query Metode Pembayaran (tetap sama)
    $sql_pembayaran = "SELECT metode_pembayaran, COUNT(id_pesanan) as jumlah_penggunaan FROM pesanan WHERE status_pesanan = 'selesai' AND DATE(tgl_pesanan) BETWEEN ? AND ? GROUP BY metode_pembayaran";
    $stmt_pembayaran = mysqli_prepare($koneksi, $sql_pembayaran);
    mysqli_stmt_bind_param($stmt_pembayaran, "ss", $tanggal_mulai, $tanggal_selesai);
    mysqli_stmt_execute($stmt_pembayaran);
    $data_pembayaran = mysqli_fetch_all(mysqli_stmt_get_result($stmt_pembayaran), MYSQLI_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <title><?php echo htmlspecialchars($judul_halaman); ?> - Owner</title>
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">

</head>

<body class="sb-nav-fixed">
    <?php include '../inc/navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include '../inc/sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4"><?php echo htmlspecialchars($judul_halaman); ?></h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Laporan</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-filter me-1"></i>Filter Laporan</div>
                        <div class="card-body">

                            <form method="GET" action="laporan_penjualan.php" id="filterForm">
                                <div class="d-flex gap-2 mb-3">
                                    <button type="button" id="btnHariIni" class="btn btn-outline-secondary btn-sm">Hari Ini</button>
                                    <button type="button" id="btnMingguIni" class="btn btn-outline-secondary btn-sm">Minggu Ini</button>
                                    <button type="button" id="btnBulanIni" class="btn btn-outline-secondary btn-sm">Bulan Ini</button>
                                </div>
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-3">
                                        <label for="jenis_laporan" class="form-label">Jenis Laporan:</label>
                                        <select class="form-select" name="jenis_laporan" id="jenis_laporan">
                                            <option value="pemasukan" <?= $jenis_laporan == 'pemasukan' ? 'selected' : '' ?>>Pemasukan</option>
                                            <option value="produk" <?= $jenis_laporan == 'produk' ? 'selected' : '' ?>>Produk</option>
                                            <option value="jam_sibuk" <?= $jenis_laporan == 'jam_sibuk' ? 'selected' : '' ?>>Jam Sibuk</option>
                                            <option value="kategori_pembayaran" <?= $jenis_laporan == 'kategori_pembayaran' ? 'selected' : '' ?>>Kategori & Pembayaran</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="tanggal_mulai" class="form-label">Tanggal Mulai:</label>
                                        <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" value="<?php echo htmlspecialchars($tanggal_mulai); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="tanggal_selesai" class="form-label">Tanggal Selesai:</label>
                                        <input type="date" class="form-control" id="tanggal_selesai" name="tanggal_selesai" value="<?php echo htmlspecialchars($tanggal_selesai); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <h4 class="mt-4">Hasil untuk Periode <?php echo date('d M Y', strtotime($tanggal_mulai)) . ' - ' . date('d M Y', strtotime($tanggal_selesai)); ?></h4>

                    <?php if ($jenis_laporan === 'pemasukan'): ?>
                        <div class="row">
                            <div class="col-xl-4 col-md-6">
                                <div class="card bg-success text-white mb-4">
                                    <div class="card-body">
                                        <div class="fs-5">Total Pendapatan</div>
                                        <div class="fs-3 fw-bold">Rp <?php echo number_format($total_pendapatan, 0, ',', '.'); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card bg-primary text-white mb-4">
                                    <div class="card-body">
                                        <div class="fs-5">Jumlah Transaksi</div>
                                        <div class="fs-3 fw-bold"><?php echo $jumlah_transaksi; ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-xl-4 col-md-6">
                                <div class="card bg-warning text-dark mb-4">
                                    <div class="card-body">
                                        <div class="fs-5">Total Item Terjual</div>
                                        <div class="fs-3 fw-bold"><?php echo $total_item_terjual; ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card mb-4">
                            <div class="card-header"><i class="fas fa-table me-1"></i>Rincian Transaksi</div>
                            <div class="card-body">
                                <table id="datatablesSimple">
                                    <thead>
                                        <tr>
                                            <th>Tanggal</th>
                                            <th>Waktu</th>
                                            <th>No. Pesanan</th>
                                            <th>Total</th>
                                            <th>Kasir</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($daftar_transaksi as $transaksi): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y', strtotime($transaksi['tgl_pesanan'])); ?></td>
                                                <td><?php echo date('H:i:s', strtotime($transaksi['tgl_pesanan'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaksi['id_pesanan']); ?></td>
                                                <td>Rp <?php echo number_format($transaksi['total_harga'], 0, ',', '.'); ?></td>
                                                <td><?php echo htmlspecialchars($transaksi['nama_kasir']); ?></td>
                                                <td>
                                                    <a href="../kasir/detail_pesanan.php?id=<?php echo $transaksi['id_pesanan']; ?>" class="btn btn-sm btn-info" target="_blank">Detail</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    <?php elseif ($jenis_laporan === 'produk'): ?>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-success text-white"><i class="fas fa-star me-1"></i>10 Produk Terlaris</div>
                                    <div class="card-body">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nama Produk</th>
                                                    <th class="text-end">Jumlah Terjual</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($produk_terlaris)): ?>
                                                    <?php foreach ($produk_terlaris as $index => $produk): ?>
                                                        <tr>
                                                            <th><?= $index + 1 ?></th>
                                                            <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                                                            <td class="text-end"><strong><?= htmlspecialchars($produk['total_terjual']) ?></strong></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Tidak ada data.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-danger text-white"><i class="fas fa-thumbs-down me-1"></i>10 Produk Kurang Diminati</div>
                                    <div class="card-body">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Nama Produk</th>
                                                    <th class="text-end">Jumlah Terjual</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (!empty($produk_kurang_laku)): ?>
                                                    <?php foreach ($produk_kurang_laku as $index => $produk): ?>
                                                        <tr>
                                                            <th><?= $index + 1 ?></th>
                                                            <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                                                            <td class="text-end"><strong><?= htmlspecialchars($produk['total_terjual']) ?></strong></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="3" class="text-center">Tidak ada data.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <?php elseif ($jenis_laporan === 'jam_sibuk'): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-primary text-white"><i class="fas fa-clock me-1"></i>Grafik Jumlah Transaksi per Jam</div>
                            <div class="card-body"><canvas id="jamSibukChart"></canvas></div>
                        </div>

                    <?php elseif ($jenis_laporan === 'kategori_pembayaran'): ?>
                        <div class="row">
                            <div class="col-lg-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-info text-white"><i class="fas fa-tags me-1"></i>Pendapatan per Tipe Produk</div>
                                    <div class="card-body"><canvas id="tipeProdukChart" height="200"></canvas></div>
                                </div>
                            </div>
                            <div class="col-lg-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-warning text-dark"><i class="fas fa-credit-card me-1"></i>Popularitas Metode Pembayaran</div>
                                    <div class="card-body"><canvas id="pembayaranChart" height="200"></canvas></div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/datatables-simple-demo.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Logika untuk menampilkan Chart hanya jika data yang sesuai ada
            <?php if ($jenis_laporan === 'jam_sibuk' && isset($labels_jam)): ?>
                new Chart(document.getElementById('jamSibukChart'), {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($labels_jam); ?>,
                        datasets: [{
                            label: 'Jumlah Transaksi',
                            data: <?php echo json_encode($data_transaksi_per_jam); ?>,
                            backgroundColor: 'rgba(2,117,216,0.8)',
                            borderColor: 'rgba(2,117,216,1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>

            <?php if ($jenis_laporan === 'kategori_pembayaran' && isset($data_tipe_produk)): ?>
                new Chart(document.getElementById('tipeProdukChart'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($data_tipe_produk, 'tipe_produk')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($data_tipe_produk, 'total_pendapatan')); ?>,
                            backgroundColor: ['#0275d8', '#5cb85c', '#f0ad4e', '#d9534f', '#343a40'],
                        }]
                    }
                });
            <?php endif; ?>

            <?php if ($jenis_laporan === 'kategori_pembayaran' && isset($data_kategori)): ?>
                new Chart(document.getElementById('kategoriChart'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($data_kategori, 'kategori')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($data_kategori, 'total_pendapatan')); ?>,
                            backgroundColor: ['#0275d8', '#5cb85c', '#f0ad4e', '#d9534f'],
                        }]
                    }
                });
            <?php endif; ?>

            <?php if ($jenis_laporan === 'kategori_pembayaran' && isset($data_pembayaran)): ?>
                new Chart(document.getElementById('pembayaranChart'), {
                    type: 'pie',
                    data: {
                        labels: <?php echo json_encode(array_column($data_pembayaran, 'metode_pembayaran')); ?>,
                        datasets: [{
                            data: <?php echo json_encode(array_column($data_pembayaran, 'jumlah_penggunaan')); ?>,
                            backgroundColor: ['#f0ad4e', '#5bc0de', '#5cb85c', '#d9534f'],
                        }]
                    }
                });

                // === TAMBAHKAN LOGIKA FILTER TANGGAL INI ===
                const form = document.getElementById('filterForm');
                const tglMulai = document.getElementById('tanggal_mulai');
                const tglSelesai = document.getElementById('tanggal_selesai');

                // Fungsi untuk format tanggal YYYY-MM-DD
                const formatDate = (date) => date.toISOString().split('T')[0];

                const today = new Date();

                document.getElementById('btnHariIni').addEventListener('click', () => {
                    tglMulai.value = formatDate(today);
                    tglSelesai.value = formatDate(today);
                    form.submit(); // Langsung tampilkan laporan
                });

                document.getElementById('btnMingguIni').addEventListener('click', () => {
                    const dayOfWeek = today.getDay(); // 0=Minggu, 1=Senin, ...
                    const startOfWeek = new Date(today);
                    // Set ke hari Senin minggu ini
                    const diff = today.getDate() - dayOfWeek + (dayOfWeek === 0 ? -6 : 1);
                    startOfWeek.setDate(diff);

                    tglMulai.value = formatDate(startOfWeek);
                    tglSelesai.value = formatDate(today);
                    form.submit();
                });

                document.getElementById('btnBulanIni').addEventListener('click', () => {
                    const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                    tglMulai.value = formatDate(startOfMonth);
                    tglSelesai.value = formatDate(today);
                    form.submit();
                });
            <?php endif; ?>
        });
    </script>
</body>

</html>