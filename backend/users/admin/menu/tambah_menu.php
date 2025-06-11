<?php
session_start();
include('../../../koneksi.php');

// --- FUNGSI UNTUK MEMBUAT ID PRODUK OTOMATIS ---
function generateNextId($prefix, $koneksi) {
    // Query untuk mencari ID terbesar dengan prefix tertentu
    $query = "SELECT MAX(id_produk) AS last_id FROM produk WHERE id_produk LIKE ?";
    $stmt = mysqli_prepare($koneksi, $query);
    $search_param = $prefix . '%';
    mysqli_stmt_bind_param($stmt, "s", $search_param);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    $last_id = $row['last_id'];
    
    if ($last_id) {
        // Jika ada ID sebelumnya, ambil nomornya, tambah 1
        $number = (int) substr($last_id, strlen($prefix));
        $number++;
    } else {
        // Jika belum ada, mulai dari 1
        $number = 1;
    }
    
    // Format nomor dengan 3 digit (e.g., 001, 012)
    $new_id = $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    return $new_id;
}

// --- LOGIKA UNTUK PRE-CALCULATE NEXT ID UNTUK JAVASCRIPT ---
$next_ids = [
    'KB' => generateNextId('KB', $koneksi), // Kue Balok
    'KS' => generateNextId('KS', $koneksi), // Ketan Susu
    'OT' => generateNextId('OT', $koneksi), // Makanan Lain
    'DK' => generateNextId('DK', $koneksi)  // Minuman
];


// --- LOGIKA PROSES FORM SAAT DISUBMIT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit'])) {
    $id_produk = $_POST['id_produk'];
    $nama = $_POST['nama_produk'];
    $harga = $_POST['harga'];
    $kategori = $_POST['kategori'];

    // Proses upload gambar
    $gambar = $_FILES['gambar']['name'];
    $tmp = $_FILES['gambar']['tmp_name'];
    // Perbaikan path: tambahkan slash "/" setelah 'produk'
    $path = "../../../assets/img/produk/" . $gambar;

    // Pindahkan file gambar terlebih dahulu
    if (move_uploaded_file($tmp, $path)) {
        // Gunakan Prepared Statement untuk keamanan
        $query = "INSERT INTO produk (id_produk, nama_produk, harga, kategori, poto_produk) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($koneksi, $query);
        mysqli_stmt_bind_param($stmt, "ssdss", $id_produk, $nama, $harga, $kategori, $gambar);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['notif'] = ['pesan' => 'Menu baru berhasil ditambahkan.', 'tipe' => 'success'];
        } else {
            $_SESSION['notif'] = ['pesan' => 'Gagal menambahkan menu. Error: ' . mysqli_error($koneksi), 'tipe' => 'danger'];
        }
        header("Location: data_menu.php");
        exit;

    } else {
        $_SESSION['notif'] = ['pesan' => 'Gagal mengupload gambar.', 'tipe' => 'danger'];
        header("Location: tambah_menu.php"); // Kembali ke form tambah jika gambar gagal
        exit;
    }
}

// Ambil data produk untuk ditampilkan di tabel
$result = mysqli_query($koneksi, "SELECT * FROM produk ORDER BY kategori, nama_produk");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Tambah Menu - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
    <link href="../../../css/styles.css" rel="stylesheet" />
    <link rel="icon" type="image/png" href="../../../assets/img/logo-kuebalok.png">
    <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
</head>
<body class="sb-nav-fixed">
    <?php include "../inc/navbar.php"; ?>
    <div id="layoutSidenav">
        <div id="layoutSidenav_nav">
            <?php include "../inc/sidebar.php"; ?>
        </div>
        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    <h1 class="mt-4">Tambah Menu Baru</h1>
                    <ol class="breadcrumb mb-4">
                        <li class="breadcrumb-item"><a href="../index.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="data_menu.php">Data Menu</a></li>
                        <li class="breadcrumb-item active">Tambah Menu</li>
                    </ol>

                    <div class="card mb-4">
                        <div class="card-header"><i class="fas fa-plus-circle me-1"></i>Formulir Tambah Menu</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="row gx-3">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="jenis_menu" class="form-label">Jenis Menu</label>
                                            <select class="form-select" id="jenis_menu" name="jenis_menu" required>
                                                <option value="" selected disabled>-- Pilih Jenis Menu --</option>
                                                <option value="KB">Kue Balok</option>
                                                <option value="KS">Ketan Susu</option>
                                                <option value="OT">Makanan Lain</option>
                                                <option value="DK">Minuman</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="id_produk" class="form-label">ID Menu (Otomatis)</label>
                                            <input type="text" class="form-control" name="id_produk" id="id_produk" readonly required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="nama_produk" class="form-label">Nama Menu</label>
                                            <input type="text" class="form-control" name="nama_produk" id="nama_produk" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="harga" class="form-label">Harga</label>
                                            <input type="number" class="form-control" name="harga" id="harga" min="0" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="kategori" class="form-label">Kategori</label>
                                            <select class="form-select" name="kategori" id="kategori" required>
                                                <option value="makanan">Makanan</option>
                                                <option value="minuman">Minuman</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="gambar" class="form-label">Upload Gambar</label>
                                            <input class="form-control" type="file" name="gambar" id="gambar" required>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="submit" class="btn btn-primary">
                                    <i class="fas fa-plus"></i> Tambah Menu
                                </button>
                                <a href="data_menu.php" class="btn btn-secondary">Batal</a>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
            <footer class="py-4 bg-light mt-auto">
                </footer>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="../../../js/scripts.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Data ID berikutnya yang sudah dihitung oleh PHP
            const nextIds = <?php echo json_encode($next_ids); ?>;

            const jenisMenuSelect = document.getElementById('jenis_menu');
            const idProdukInput = document.getElementById('id_produk');
            const kategoriSelect = document.getElementById('kategori');

            jenisMenuSelect.addEventListener('change', function() {
                const selectedPrefix = this.value;
                if (selectedPrefix) {
                    idProdukInput.value = nextIds[selectedPrefix];
                    // Otomatis set kategori jika jenisnya minuman
                    if (selectedPrefix === 'DK') {
                        kategoriSelect.value = 'minuman';
                    } else {
                        kategoriSelect.value = 'makanan';
                    }
                } else {
                    idProdukInput.value = '';
                }
            });
        });
    </script>
</body>
</html>