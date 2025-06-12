<?php
session_start();
include '../backend/koneksi.php';

// 1. VALIDASI INPUT ID PESANAN DARI URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id_pesanan = $_GET['id'];

// 2. LOGIKA PROSES UPLOAD BUKTI PEMBAYARAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['bukti_pembayaran'])) {
    $file = $_FILES['bukti_pembayaran'];

    // Validasi file upload (Error, Tipe, Ukuran)
    if ($file['error'] === 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
        if (in_array($file['type'], $allowed_types) && $file['size'] < 2097152) { // Maks 2MB

            // Buat nama file yang unik untuk menghindari tumpang tindih
            $nama_file_baru = $id_pesanan . '-' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            // Path untuk menyimpan file. Perhatikan ../ untuk naik satu level dari /public
            $lokasi_upload = '../backend/assets/img/bukti_bayar/' . $nama_file_baru;

            if (move_uploaded_file($file['tmp_name'], $lokasi_upload)) {
                // Jika upload berhasil, update database
                $stmt_update = mysqli_prepare($koneksi, "UPDATE pesanan SET bukti_pembayaran = ?, status_pesanan = 'menunggu_konfirmasi' WHERE id_pesanan = ? AND status_pesanan = 'menunggu_pembayaran'");
                mysqli_stmt_bind_param($stmt_update, "ss", $nama_file_baru, $id_pesanan);

                if (mysqli_stmt_execute($stmt_update)) {
                    $_SESSION['notif_konfirmasi'] = ['pesan' => 'Terima kasih! Bukti pembayaran berhasil diupload dan akan segera kami periksa.', 'tipe' => 'success'];
                } else {
                    $_SESSION['notif_konfirmasi'] = ['pesan' => 'Gagal menyimpan data bukti pembayaran.', 'tipe' => 'danger'];
                }
            } else {
                $_SESSION['notif_konfirmasi'] = ['pesan' => 'Gagal memindahkan file yang diupload.', 'tipe' => 'danger'];
            }
        } else {
            $_SESSION['notif_konfirmasi'] = ['pesan' => 'File tidak valid. Pastikan formatnya JPG/PNG dan ukuran di bawah 2MB.', 'tipe' => 'warning'];
        }
    } else {
        $_SESSION['notif_konfirmasi'] = ['pesan' => 'Terjadi error saat mengupload file.', 'tipe' => 'danger'];
    }

    // Arahkan kembali ke halaman yang sama untuk menampilkan notifikasi
    header("Location: konfirmasi.php?id=" . $id_pesanan);
    exit;
}

// 3. AMBIL DATA PESANAN UNTUK DITAMPILKAN
$stmt_get = mysqli_prepare($koneksi, "SELECT * FROM pesanan WHERE id_pesanan = ?");
mysqli_stmt_bind_param($stmt_get, "s", $id_pesanan);
mysqli_stmt_execute($stmt_get);
$result_get = mysqli_stmt_get_result($stmt_get);
$pesanan = mysqli_fetch_assoc($result_get);

if (!$pesanan) {
    // Jika ID pesanan tidak valid atau tidak ditemukan
    header('Location: index.php');
    exit;
}

$page_title = "Konfirmasi Pesanan";
include 'includes/header.php';

$sql_metode = "SELECT * FROM metode_pembayaran WHERE status = 'aktif'";
$result_metode = mysqli_query($koneksi, $sql_metode);


?>

<section class="page-section" style="padding: 8rem 7% 4rem;">
    <div class="container">
        <h2 style="text-align: center; font-size: 2.6rem; margin-bottom: 2rem;">Konfirmasi <span>Pemesanan</span></h2>

        <?php
        if (isset($_SESSION['notif_konfirmasi'])) {
            $notif = $_SESSION['notif_konfirmasi'];
            $alert_class = ($notif['tipe'] === 'success') ? 'form-success' : 'form-danger'; // Ganti class untuk styling
            echo '<div class="' . $alert_class . '" style="padding: 1rem; margin-bottom: 1rem; border: 1px solid; text-align: center; border-radius: 5px;">' . htmlspecialchars($notif['pesan']) . '</div>';
            unset($_SESSION['notif_konfirmasi']);
        }
        ?>

        <div class="card" style="border: 1px solid #ccc; border-radius: 5px; padding: 2rem; max-width: 700px; margin: auto;">

            <?php if ($pesanan['status_pesanan'] == 'menunggu_pembayaran'): ?>
                <div class="text-center">
                    <h3 style="color: var(--primary);">Pesanan Anda Berhasil Dibuat!</h3>
                    <p>Silakan simpan Nomor Pesanan Anda untuk melakukan pelacakan.</p>

                    <h2 class="order-id-box"><?= htmlspecialchars($pesanan['id_pesanan']) ?></h2>

                    <hr style="margin: 2rem 0;">

                    <h4>Instruksi Pembayaran</h4>
                    <h5>Pilih Metode Pembayaran:</h5>
                    <?php while ($metode = mysqli_fetch_assoc($result_metode)): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="metode_pembayaran" id="metode_<?= $metode['id_metode'] ?>" value="<?= $metode['nama_metode'] ?>"
                                data-atas-nama="<?= $metode['atas_nama'] ?>"
                                data-nomor="<?= $metode['nomor_tujuan'] ?>"
                                data-gambar="<?= $metode['gambar_path'] ?>">
                            <label class="form-check-label" for="metode_<?= $metode['id_metode'] ?>">
                                <?= $metode['nama_metode'] ?>
                            </label>
                        </div>
                    <?php endwhile; ?>

                    <div id="detail-pembayaran" style="display:none; margin-top: 1rem; padding: 1rem; background-color: #f8f9fa;">
                        <p>Silakan lakukan pembayaran ke:</p>
                        <strong id="info-atas-nama"></strong><br>
                        <strong id="info-nomor"></strong>
                        <img src="" id="info-gambar" style="max-width: 200px; display:none;">
                    </div>

                    <div class="upload-wrapper">
                        <h5>Upload Bukti Pembayaran Anda</h5>
                        <form action="" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="id_pesanan" value="<?= htmlspecialchars($pesanan['id_pesanan']) ?>">
                            <div class="input-group">
                                <input type="file" name="bukti_pembayaran" class="form-control" required>
                            </div>
                            <button type="submit" class="btn">Kirim Bukti Pembayaran</button>
                        </form>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center">
                    <h3 style="color: green;">Terima Kasih!</h3>
                    <p>Status pesanan Anda <strong>#<?= htmlspecialchars($pesanan['id_pesanan']) ?></strong> saat ini adalah:</p>
                    <h4 style="background: #eee; padding: 1rem; border-radius: 5px; display: inline-block; text-transform: capitalize;">
                        <?php echo str_replace('_', ' ', htmlspecialchars($pesanan['status_pesanan'])); ?>
                    </h4>
                    <p class="mt-4">Anda bisa memeriksa kembali status pesanan Anda secara berkala.</p>
                    <a href="lacak.php" class="btn">Lacak Pesanan Lain</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
    document.querySelectorAll('input[name="metode_pembayaran"]').forEach(radio => {
        radio.addEventListener('change', function() {
            document.getElementById('detail-pembayaran').style.display = 'block';
            document.getElementById('info-atas-nama').textContent = "A/N: " + this.dataset.atasNama;
            document.getElementById('info-nomor').textContent = "Nomor: " + this.dataset.nomor;

            const imgEl = document.getElementById('info-gambar');
            if (this.dataset.gambar) {
                // Ganti path ini jika perlu
                imgEl.src = '../backend/assets/img/metode_bayar/' + this.dataset.gambar;
                imgEl.style.display = 'block';
            } else {
                imgEl.style.display = 'none';
            }
        });
    });
</script>
<?php
include 'includes/footer.php';
?>