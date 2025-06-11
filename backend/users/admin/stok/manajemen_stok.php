<?php
session_start();
include('../../../koneksi.php'); // Path dari users/admin/

// 1. OTENTIKASI & OTORISASI (Hanya Admin & Owner)
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['jabatan'], ['admin', 'owner'])) {
    header('Location: ../../login.php');
    exit;
}

// 2. LOGIKA PROSES UPDATE STOK DARI MODAL
// GANTI SELURUH BLOK INI DI FILE ANDA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stok'])) {
    $id_produk = $_POST['id_produk'];
    $stok_baru = (int)$_POST['jumlah_stok_baru'];
    $tanggal_hari_ini = date('Y-m-d');

    mysqli_begin_transaction($koneksi);

    try {
        // Query 1: Update stok terkini di tabel 'produk'
        $stmt1 = mysqli_prepare($koneksi, "UPDATE produk SET stok = ? WHERE id_produk = ?");
        mysqli_stmt_bind_param($stmt1, "is", $stok_baru, $id_produk);
        mysqli_stmt_execute($stmt1);

        // Query 2: Cek apakah sudah ada entri stok untuk produk ini hari ini
        // PERBAIKAN: Menggunakan nama kolom yang benar 'id_stok_harian'
        $stmt_check = mysqli_prepare($koneksi, "SELECT id_stok_harian FROM stok_harian WHERE id_produk = ? AND tanggal = ?");
        mysqli_stmt_bind_param($stmt_check, "ss", $id_produk, $tanggal_hari_ini);
        mysqli_stmt_execute($stmt_check);
        $result_check = mysqli_stmt_get_result($stmt_check);

        if (mysqli_num_rows($result_check) > 0) {
            // Jika sudah ada, UPDATE entri stok harian
            $stmt2 = mysqli_prepare($koneksi, "UPDATE stok_harian SET stok = ? WHERE id_produk = ? AND tanggal = ?");
            mysqli_stmt_bind_param($stmt2, "iss", $stok_baru, $id_produk, $tanggal_hari_ini);
        } else {
            // Jika belum ada, INSERT entri baru untuk stok harian
            $stmt2 = mysqli_prepare($koneksi, "INSERT INTO stok_harian (id_produk, stok, tanggal) VALUES (?, ?, ?)");
            mysqli_stmt_bind_param($stmt2, "sis", $id_produk, $stok_baru, $tanggal_hari_ini);
        }
        mysqli_stmt_execute($stmt2);

        // Jika semua query berhasil, commit perubahan
        mysqli_commit($koneksi);
        $_SESSION['notif'] = ['pesan' => 'Stok berhasil diperbarui.', 'tipe' => 'success'];

    } catch (Exception $e) {
        // Jika ada satu saja yang gagal, batalkan semua perubahan
        mysqli_rollback($koneksi);
        // PERBAIKAN: Menampilkan pesan error yang lebih detail
        $_SESSION['notif'] = ['pesan' => 'Gagal memperbarui stok. Error: ' . $e->getMessage(), 'tipe' => 'danger'];
    }

    header('Location: manajemen_stok.php');
    exit;
}


// 3. LOGIKA PENGAMBILAN DATA UNTUK DITAMPILKAN DI TABEL
// Menggunakan LEFT JOIN untuk memastikan semua produk tampil meskipun belum ada input stok hari ini
$sql = "SELECT p.id_produk, p.nama_produk, p.stok AS stok_terkini, sh.stok AS stok_harian_input
        FROM produk p
        LEFT JOIN stok_harian sh ON p.id_produk = sh.id_produk AND sh.tanggal = CURDATE()
        ORDER BY p.nama_produk";
$result = mysqli_query($koneksi, $sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <title>Manajemen Stok - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
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
                    <h1 class="mt-4">Manajemen Stok</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Stok</li>
                    </ol>
                    
                    <?php
                    if (isset($_SESSION['notif'])) {
                        $notif = $_SESSION['notif'];
                        echo '<div class="alert alert-' . htmlspecialchars($notif['tipe']) . ' alert-dismissible fade show" role="alert">' . htmlspecialchars($notif['pesan']) . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['notif']);
                    }
                    ?>
                    
                    <div class="alert alert-info">
                        Halaman ini digunakan untuk menginput stok total untuk hari ini. Stok terkini akan digunakan untuk semua transaksi.
                    </div>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-boxes me-1"></i>Daftar Stok Produk</div>
                        <div class="card-body">
                            <table id="datatablesSimple">
                                <thead>
                                    <tr>
                                        <th>ID Produk</th>
                                        <th>Nama Produk</th>
                                        <th>Stok Terkini (Untuk Transaksi)</th>
                                        <th>Stok Harian (Input Hari Ini)</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['id_produk']) ?></td>
                                        <td><?= htmlspecialchars($row['nama_produk']) ?></td>
                                        <td><strong><?= $row['stok_terkini'] ?? 0 ?></strong></td>
                                        <td><?= $row['stok_harian_input'] ?? '<span class="badge bg-secondary">Belum Diinput</span>' ?></td>
                                        <td>
                                            <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateStokModal" 
                                                data-id-produk="<?= htmlspecialchars($row['id_produk']) ?>" 
                                                data-nama-produk="<?= htmlspecialchars($row['nama_produk']) ?>"
                                                data-stok-terkini="<?= $row['stok_terkini'] ?? 0 ?>">
                                                <i class="fas fa-edit"></i> Update Stok
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
            </div>
    </div>

    <div class="modal fade" id="updateStokModal" tabindex="-1" aria-labelledby="updateStokModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateStokModalLabel">Update Stok untuk Hari Ini</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="manajemen_stok.php">
                    <div class="modal-body">
                        <h6 id="namaProdukModal">Nama Produk</h6>
                        <input type="hidden" name="id_produk" id="idProdukModal">
                        <div class="mb-3">
                            <label class="form-label">Stok Saat Ini</label>
                            <input type="text" class="form-control" id="stokTerkiniModal" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="jumlahStokBaru" class="form-label">Masukkan Jumlah Stok Baru Untuk Hari Ini</label>
                            <input type="number" class="form-control" name="jumlah_stok_baru" id="jumlahStokBaru" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_stok" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/umd/simple-datatables.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/datatables-simple-demo.js"></script>

    <script>
        // JavaScript untuk mengirim data ke modal saat tombol diklik
        const updateStokModal = document.getElementById('updateStokModal');
        updateStokModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const idProduk = button.getAttribute('data-id-produk');
            const namaProduk = button.getAttribute('data-nama-produk');
            const stokTerkini = button.getAttribute('data-stok-terkini');

            const modalTitle = updateStokModal.querySelector('#namaProdukModal');
            const modalIdProdukInput = updateStokModal.querySelector('#idProdukModal');
            const modalStokTerkiniInput = updateStokModal.querySelector('#stokTerkiniModal');
            
            modalTitle.textContent = 'Update Stok: ' + namaProduk;
            modalIdProdukInput.value = idProduk;
            modalStokTerkiniInput.value = stokTerkini;
        });
    </script>
</body>
</html>