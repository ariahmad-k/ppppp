<?php
session_start();
include '../../koneksi.php';

// 1. OTENTIKASI & OTORISASI KASIR
if (!isset($_SESSION['user']) || $_SESSION['user']['jabatan'] !== 'kasir') {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA PEMROSESAN FORM (POST REQUEST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = true;

    // --- Aksi: Validasi Pesanan Online ---
    if (isset($_POST['validasi_pesanan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $id_kasir_yang_validasi = $_SESSION['user']['id'];

        mysqli_begin_transaction($koneksi);
        try {
            $sql_beban = "SELECT SUM(dp.jumlah) AS total_item_aktif FROM detail_pesanan dp JOIN pesanan p ON dp.id_pesanan = p.id_pesanan WHERE p.status_pesanan IN ('pending', 'diproses') AND (dp.id_produk LIKE 'KB%' OR dp.id_produk LIKE 'KS%')";
            $result_beban = mysqli_query($koneksi, $sql_beban);
            $beban_dapur = mysqli_fetch_assoc($result_beban)['total_item_aktif'] ?? 0;

            $status_baru = ($beban_dapur < 20) ? 'diproses' : 'pending';

            $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = ?, id_karyawan = ? WHERE id_pesanan = ? AND status_pesanan = 'menunggu_konfirmasi'");
            mysqli_stmt_bind_param($stmt_update, "sis", $status_baru, $id_kasir_yang_validasi, $id_pesanan);
            mysqli_stmt_execute($stmt_update);

            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Pesanan berhasil divalidasi dan masuk antrean.', 'tipe' => 'success'];
        } catch (Exception $e) {
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal memvalidasi pesanan. Error: ' . $e->getMessage(), 'tipe' => 'danger'];
        }
    }

    // --- TAMBAHAN: Aksi Batalkan Pesanan (Fake Order) ---
    // Di dalam file pesanan_masuk.php

    // --- GANTI BLOK INI DENGAN VERSI BARU ---
    if (isset($_POST['batalkan_pesanan'])) {
        $id_pesanan = $_POST['id_pesanan'];

        // Mulai transaksi untuk memastikan semua proses berjalan atau tidak sama sekali
        mysqli_begin_transaction($koneksi);

        try {
            // 1. Ambil semua item di pesanan ini
            $sql_items = "SELECT id_produk, jumlah FROM detail_pesanan WHERE id_pesanan = ?";
            $stmt_items = mysqli_prepare($koneksi, $sql_items);
            mysqli_stmt_bind_param($stmt_items, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

            // 2. Kembalikan stok untuk setiap item
            $stmt_update_stok = mysqli_prepare($koneksi, "UPDATE produk SET stok = stok + ? WHERE id_produk = ?");
            while ($item = mysqli_fetch_assoc($result_items)) {
                mysqli_stmt_bind_param($stmt_update_stok, "is", $item['jumlah'], $item['id_produk']);
                mysqli_stmt_execute($stmt_update_stok);
            }

            // 3. Ubah status pesanan menjadi 'dibatalkan'
            $stmt_batal = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'dibatalkan' WHERE id_pesanan = ? AND status_pesanan = 'menunggu_konfirmasi'");
            mysqli_stmt_bind_param($stmt_batal, "s", $id_pesanan);
            mysqli_stmt_execute($stmt_batal);

            // 4. Jika semua berhasil, simpan perubahan
            mysqli_commit($koneksi);
            $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah dibatalkan dan stok berhasil dikembalikan.", 'tipe' => 'warning'];
        } catch (Exception $e) {
            // Jika ada error, batalkan semua perubahan
            mysqli_rollback($koneksi);
            $_SESSION['notif'] = ['pesan' => 'Gagal membatalkan pesanan. Terjadi error sistem.', 'tipe' => 'danger'];
        }
    }
    // --- PERUBAHAN: Aksi Tandai Siap Diambil (dari Dapur) ---
    if (isset($_POST['siap_diambil'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $stmt_siap = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'siap_diambil' WHERE id_pesanan = ?");
        mysqli_stmt_bind_param($stmt_siap, "s", $id_pesanan);
        if (mysqli_stmt_execute($stmt_siap)) {
            $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah ditandai Siap Diambil.", 'tipe' => 'info'];
        } else {
            $_SESSION['notif'] = ['pesan' => 'Gagal mengubah status pesanan.', 'tipe' => 'danger'];
        }
    }

    // --- TAMBAHAN: Aksi Konfirmasi Pengambilan (oleh Pelanggan) ---
    if (isset($_POST['konfirmasi_pengambilan'])) {
        $id_pesanan = $_POST['id_pesanan'];
        $stmt_selesai = mysqli_prepare($koneksi, "UPDATE pesanan SET status_pesanan = 'selesai' WHERE id_pesanan = ?");
        mysqli_stmt_bind_param($stmt_selesai, "s", $id_pesanan);
        if (mysqli_stmt_execute($stmt_selesai)) {
            $_SESSION['notif'] = ['pesan' => "Pesanan #$id_pesanan telah selesai (diambil pelanggan).", 'tipe' => 'success'];
        } else {
            $_SESSION['notif'] = ['pesan' => 'Gagal menyelesaikan pesanan.', 'tipe' => 'danger'];
        }
    }

    if ($redirect) {
        header('Location: pesanan_masuk.php');
        exit;
    }
}

// 3. LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN
// a. Ambil pesanan online yang butuh konfirmasi
$sql_pesanan_baru = "SELECT * FROM pesanan 
                     WHERE status_pesanan IN ('menunggu_pembayaran', 'menunggu_konfirmasi') 
                     ORDER BY tgl_pesanan ASC";
$pesanan_online = mysqli_fetch_all(mysqli_query($koneksi, $sql_pesanan_baru), MYSQLI_ASSOC);

// b. Ambil pesanan di antrean dapur
$sql_antrean = "SELECT * FROM pesanan WHERE status_pesanan IN ('pending', 'diproses') ORDER BY FIELD(status_pesanan, 'diproses', 'pending'), tgl_pesanan ASC";
$result_antrean = mysqli_query($koneksi, $sql_antrean);
$antrean_pesanan = mysqli_fetch_all($result_antrean, MYSQLI_ASSOC);

// c. TAMBAHAN: Ambil pesanan yang siap diambil pelanggan
$sql_siap = "SELECT * FROM pesanan WHERE status_pesanan = 'siap_diambil' ORDER BY tgl_pesanan ASC";
$result_siap = mysqli_query($koneksi, $sql_siap);
$pesanan_siap_diambil = mysqli_fetch_all($result_siap, MYSQLI_ASSOC);


// --- TAMBAHAN: Logika Cerdas untuk Ambil Semua Detail Item Sekaligus ---
$detail_items = [];
$all_pesanan_ids = array_merge(
    array_column($pesanan_online, 'id_pesanan'),
    array_column($antrean_pesanan, 'id_pesanan'),
    array_column($pesanan_siap_diambil, 'id_pesanan')
);

if (!empty($all_pesanan_ids)) {
    $ids_string = "'" . implode("','", $all_pesanan_ids) . "'";
    $sql_details = "SELECT dp.id_pesanan, dp.jumlah, p.nama_produk 
                    FROM detail_pesanan dp 
                    JOIN produk p ON dp.id_produk = p.id_produk 
                    WHERE dp.id_pesanan IN ($ids_string)";

    $result_details = mysqli_query($koneksi, $sql_details);
    while ($row = mysqli_fetch_assoc($result_details)) {
        $detail_items[$row['id_pesanan']][] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <title>Pesanan Masuk & Antrean - Kasir</title>
    <link href="../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
    <link rel="icon" type="image/png" href="../../assets/img/logo-kuebalok.png">
    <style>
        /* === TAMBAHKAN CSS INI === */
        .catatan-pesanan {
            background-color: #fff3cd;
            /* Warna kuning muda seperti sticky note */
            border-left: 4px solid #ffeeba;
            padding: 10px;
            border-radius: 4px;
            margin-top: 1rem;
            /* Beri jarak dari elemen di atasnya */
            font-size: 1.3rem;
            /* Sedikit lebih kecil agar tidak terlalu mendominasi */
        }

        .catatan-pesanan strong {
            display: block;
            margin-bottom: 5px;
            color: #856404;
            /* Warna teks yang lebih gelap */
        }

        .catatan-pesanan p {
            color: #555;
        }

        /* ========================= */
    </style>
</head>

<body class="sb-nav-fixed">
    <?php include 'inc/navbar.php'; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include 'inc/sidebar.php'; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Manajemen Pesanan</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Pesanan Masuk</li>
                    </ol>

                    <?php
                    if (isset($_SESSION['notif'])) {
                        $notif = $_SESSION['notif'];
                        echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($notif['pesan']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['notif']);
                    }
                    ?>

                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white"><i class="fas fa-inbox me-1"></i>Pesanan Online Masuk (<?= count($pesanan_online) ?>)</div>
                                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                                    <?php if (empty($pesanan_online)): ?>
                                        <p class="text-center text-muted">Tidak ada pesanan online yang masuk.</p>
                                        <?php else: foreach ($pesanan_online as $pesanan): ?>

                                            <div class="card mb-3" id="pesanan-<?= htmlspecialchars($pesanan['id_pesanan']) ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between">
                                                        <h5 class="card-title"><?= htmlspecialchars($pesanan['nama_pemesan']) ?></h5>
                                                        <span id="status-label-<?= htmlspecialchars($pesanan['id_pesanan']) ?>" class="badge bg-<?= ($pesanan['status_pesanan'] == 'menunggu_pembayaran') ? 'secondary' : 'info' ?>">
                                                            <?= str_replace('_', ' ', $pesanan['status_pesanan']) ?>
                                                        </span>
                                                    </div>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['id_pesanan']) ?></h6>
                                                    <?php
                                                    if ($pesanan['jenis_pesanan'] == 'take_away') {
                                                        $pesanan['jenis_pesanan'] = 'PESANAN TAKE AWAY';
                                                    } elseif ($pesanan['jenis_pesanan'] == 'dine_in') {
                                                        $pesanan['jenis_pesanan'] = 'PESANAN DINE IN';
                                                    }
                                                    ?>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['jenis_pesanan']) ?></h6>

                                                    <ul class="list-unstyled mb-2 small">
                                                        <?php if (isset($detail_items[$pesanan['id_pesanan']])): ?>
                                                            <?php foreach ($detail_items[$pesanan['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x
                                                                    <?= htmlspecialchars($item['nama_produk']) ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </ul>
                                                    <?php if (!empty($pesanan['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note"></i> Catatan:</strong>
                                                            <p><em><?= nl2br(htmlspecialchars($pesanan['catatan'])) ?></em></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <p class="card-text mt-2"><strong>Total:</strong> Rp <?= number_format($pesanan['total_harga']) ?><br>
                                                        <strong>Waktu:</strong>
                                                        <?= date('H:i', strtotime($pesanan['tgl_pesanan'])) ?>
                                                    </p>

                                                    <div class="action-buttons" id="actions-<?= htmlspecialchars($pesanan['id_pesanan']) ?>">
                                                        <?php if ($pesanan['status_pesanan'] == 'menunggu_konfirmasi'): ?>
                                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#buktiBayarModal" data-bukti-bayar="<?= htmlspecialchars($pesanan['bukti_pembayaran']) ?>">Bukti</button>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Validasi pesanan ini?');"><input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>"><button type="submit" name="validasi_pesanan" class="btn btn-success btn-sm">Validasi</button></form>
                                                            <form method="POST" class="d-inline" onsubmit="return confirm('Anda yakin ingin MEMBATALKAN pesanan ini?');"><input type="hidden" name="id_pesanan" value="<?= $pesanan['id_pesanan'] ?>"><button type="submit" name="batalkan_pesanan" class="btn btn-danger btn-sm">Batalkan</button></form>
                                                        <?php else: // Menunggu Pembayaran 
                                                        ?>
                                                            <small class="text-muted">Menunggu pelanggan mengupload bukti pembayaran.</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                    <?php endforeach;
                                    endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-warning"><i class="fas fa-blender-phone me-1"></i>Antrean
                                    Pesanan Dapur</div>
                                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                                    <?php if (!empty($antrean_pesanan)): ?>
                                        <?php foreach ($antrean_pesanan as $antrean): ?>
                                            <?php $is_pending = $antrean['status_pesanan'] === 'pending'; ?>
                                            <div class="card mb-3 border-<?= $is_pending ? 'danger' : 'primary' ?>">
                                                <div class="card-header d-flex justify-content-between">
                                                    <strong><?= htmlspecialchars($antrean['nama_pemesan']) ?></strong>
                                                    <span
                                                        class="badge bg-<?= $is_pending ? 'danger' : 'primary' ?>"><?= ucfirst($antrean['status_pesanan']) ?></span>
                                                </div>
                                                <div class="card-body">

                                                    <h6 class="card-subtitle mb-2 text-muted">
                                                        <?= htmlspecialchars($pesanan['id_pesanan']) ?>
                                                    </h6>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['jenis_pesanan']) ?></h6>

                                                    <ul class="list-unstyled mb-2 small">
                                                        <?php if (isset($detail_items[$antrean['id_pesanan']])): ?>
                                                            <?php foreach ($detail_items[$antrean['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x
                                                                    <?= htmlspecialchars($item['nama_produk']) ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </ul>
                                                    <p class="small mb-2">Pesanan dari:
                                                        <strong><?= ucfirst($antrean['tipe_pesanan']) ?></strong>
                                                    </p>

                                                    <?php if (!empty($antrean['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note"></i> Catatan:</strong>
                                                            <p><em><?= nl2br(htmlspecialchars($antrean['catatan'])) ?></em></p>
                                                        </div>
                                                    <?php endif; ?>

                                                    <form method="POST" class="d-inline"
                                                        onsubmit="return confirm('Tandai pesanan ini sudah SIAP DIAMBIL?');">
                                                        <input type="hidden" name="id_pesanan"
                                                            value="<?= $antrean['id_pesanan'] ?>">
                                                        <button type="submit" name="siap_diambil"
                                                            class="btn btn-success btn-sm w-100">Tandai Siap Diambil</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-center text-muted">Antrean dapur kosong.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="card mb-4">
                                <div class="card-header bg-success text-white"><i
                                        class="fas fa-check-circle me-1"></i>Pesanan Siap Diambil</div>
                                <div class="card-body" style="max-height: 70vh; overflow-y: auto;">
                                    <?php if (!empty($pesanan_siap_diambil)): ?>
                                        <?php foreach ($pesanan_siap_diambil as $siap): ?>
                                            <div class="card mb-3 border-success">
                                                <div class="card-header d-flex justify-content-between">
                                                    <strong><?= htmlspecialchars($siap['nama_pemesan']) ?></strong>
                                                    <span class="badge bg-success">Siap Diambil</span>
                                                </div>
                                                <div class="card-body">
                                                    <h6 class="card-subtitle mb-2 text-muted">
                                                        <?= htmlspecialchars($pesanan['id_pesanan']) ?>
                                                    </h6>
                                                    <h6 class="card-subtitle mb-2 text-muted"><?= htmlspecialchars($pesanan['jenis_pesanan']) ?></h6>
                                                    <ul class="list-unstyled mb-2 small">
                                                        <?php if (isset($detail_items[$siap['id_pesanan']])): ?>
                                                            <?php foreach ($detail_items[$siap['id_pesanan']] as $item): ?>
                                                                <li><?= htmlspecialchars($item['jumlah']) ?>x
                                                                    <?= htmlspecialchars($item['nama_produk']) ?>
                                                                </li>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </ul>
                                                    <p class="small mb-2">Pesanan dari:
                                                        <strong><?= ucfirst($siap['tipe_pesanan']) ?></strong>
                                                    </p>
                                                    <?php if (!empty($siap['catatan'])): ?>
                                                        <div class="catatan-pesanan">
                                                            <strong><i class="fas fa-sticky-note"></i> Catatan:</strong>
                                                            <p><em><?= nl2br(htmlspecialchars($siap['catatan'])) ?></em></p>
                                                        </div>
                                                    <?php endif; ?>
                                                    <form method="POST" class="d-inline"
                                                        onsubmit="return confirm('Konfirmasi pesanan ini sudah diambil pelanggan?');">
                                                        <input type="hidden" name="id_pesanan"
                                                            value="<?= $siap['id_pesanan'] ?>">
                                                        <button type="submit" name="konfirmasi_pengambilan"
                                                            class="btn btn-primary btn-sm w-100">Konfirmasi Pengambilan</button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="text-center text-muted">Tidak ada pesanan yang siap diambil.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <div class="modal fade" id="buktiBayarModal" tabindex="-1" aria-labelledby="buktiBayarModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="buktiBayarModalLabel">Bukti Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="gambarBuktiBayar" src="" class="img-fluid" alt="Bukti Pembayaran">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../js/scripts.js"></script>
    <script>
        const buktiBayarModal = document.getElementById('buktiBayarModal');
        buktiBayarModal.addEventListener('show.bs.modal', event => {
            const button = event.relatedTarget;
            const namaFileBukti = button.getAttribute('data-bukti-bayar');
            const gambarModal = buktiBayarModal.querySelector('#gambarBuktiBayar');
            // PASTIKAN PATH INI SESUAI DENGAN LOKASI UPLOAD ANDA
            gambarModal.src = '../../assets/img/bukti_bayar/' + namaFileBukti;


            const pesananDiHalaman = document.querySelectorAll('[id^="pesanan-"]');
            let idsToWatch = Array.from(pesananDiHalaman).map(card => card.id.replace('pesanan-', ''));

            async function checkOrderStatus() {
                if (idsToWatch.length === 0) {
                    clearInterval(statusInterval);
                    return;
                }
                try {
                    const response = await fetch('../api/api_cek_status_pesanan.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            ids: idsToWatch
                        })
                    });
                    const statuses = await response.json();

                    for (const id in statuses) {
                        const newStatus = statuses[id];
                        const cardElement = document.getElementById('pesanan-' + id);
                        if (!cardElement) continue;

                        const currentStatusLabel = document.getElementById('status-label-' + id);
                        const currentStatusText = currentStatusLabel.textContent.trim().replace(' ', '_');

                        if (newStatus !== currentStatusText) {
                            // Jika status berubah dari 'menunggu_pembayaran' ke 'menunggu_konfirmasi'
                            if (newStatus === 'menunggu_konfirmasi') {
                                updateCardToValidatable(cardElement, id);
                            } else { // Jika status berubah menjadi lainnya (misal: dibatalkan)
                                disableCard(cardElement, newStatus);
                                idsToWatch = idsToWatch.filter(watchId => watchId !== id);
                            }
                        }
                    }
                } catch (error) {
                    console.error("Gagal memeriksa status:", error);
                }
            }

            function updateCardToValidatable(card, id) {
                // Update label status
                const label = document.getElementById('status-label-' + id);
                label.textContent = 'menunggu konfirmasi';
                label.className = 'badge bg-info'; // Ganti warna jadi biru

                // Ganti isi tombol aksi dengan tombol validasi lengkap
                // NOTE: Anda perlu mengambil `bukti_pembayaran` dari API atau memuat ulang bagian ini
                // Cara termudah adalah memuat ulang halaman untuk mendapat data bukti bayar yang baru
                alert(`Pesanan #${id} telah dikonfirmasi oleh pelanggan dan siap divalidasi. Halaman akan dimuat ulang.`);
                window.location.reload();
            }

            function disableCard(card, newStatus) {
                card.style.opacity = '0.6';
                card.style.pointerEvents = 'none';
                const statusLabel = document.getElementById('status-label-' + card.id.replace('pesanan-', ''));
                statusLabel.className = 'badge bg-danger';
                statusLabel.textContent = newStatus;
            }

            if (idsToWatch.length > 0) {
                const statusInterval = setInterval(checkOrderStatus, 10000); // Cek setiap 10 detik
            }
        });
    </script>
</body>

</html>