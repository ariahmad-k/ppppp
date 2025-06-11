<?php
$page_title = "Beranda - Kue Mang Wiro";

include '../backend/koneksi.php'; // Pastikan koneksi database sudah benar
include 'includes/header.php';



if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pesan'])) {
    // 1. Ambil data dari form
    $nama = $_POST['nama'];
    $email = $_POST['email'];
    $pesan = $_POST['pesan']; // Pastikan nama input di form sesuai

    // 2. Siapkan dan jalankan query yang aman
    $stmt = mysqli_prepare($koneksi, "INSERT INTO feedback (nama, email, pesan, tanggal) VALUES (?, ?, ?, current_timestamp())");
    mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $pesan);

    // 3. Beri notifikasi berdasarkan hasil eksekusi
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['notif_feedback'] = "Terima kasih! Pesan Anda telah kami terima.";
    } else {
        $_SESSION['notif_feedback'] = "Maaf, terjadi kesalahan saat mengirim pesan.";
    }

    // 4. Arahkan kembali ke bagian kontak
    header("Location: index.php#contact");
    exit;
}

// --- LOGIKA MENGAMBIL SEMUA PRODUK AKTIF ---
// --- LOGIKA MENGAMBIL SEMUA PRODUK AKTIF (Versi lebih aman) ---
$sql_produk = "SELECT id_produk, nama_produk, harga, poto_produk 
               FROM produk 
               WHERE status_produk = 'aktif'
               ORDER BY kategori, nama_produk";

$result_produk = mysqli_query($koneksi, $sql_produk);

// Tambahkan pengecekan ini
if (!$result_produk) {
    // Jika kueri gagal, tampilkan pesan error yang jelas dan hentikan skrip.
    // Ini lebih baik untuk debugging daripada halaman putih atau error yang tidak jelas.
    die("Error: Kueri untuk mengambil produk gagal dieksekusi. " . mysqli_error($koneksi));
}

?>

<section class="hero" id="home">
    <main class="content">
        <h1>Ngopi, Nga<span>Balok</span>, Ngawadul </h1>
        <p>Rasakan kelezatan kue balok lumer khas kami yang legendaris.</p>
        <a href="menu.php" class="cta">Pesan Sekarang</a>
    </main>
</section>
<section id="about" class="about">
    <h2><span>Tentang</span> Kami</h2>
    <div class="row">
        <div class="about-img">
            <img src="assets/img/logo kue balok.JPG" alt="Tentang Kami" />
        </div>
        <div class="content">
            <h3>Kenapa memilih Kue Balok kami?</h3>
            <p>Kami menyajikan kue balok dengan resep otentik yang diwariskan turun-temurun, menghasilkan tekstur lembut di dalam dan renyah di luar dengan lelehan cokelat premium yang melimpah.</p>
            <p>Setiap gigitan adalah pengalaman rasa yang tak terlupakan, dibuat dengan bahan-bahan berkualitas tinggi dan penuh cinta.</p>
        </div>
    </div>
</section>
<section id="menu" class="menu">
    <h2><span>Menu</span> Kami</h2>
    <p>Berikut adalah beberapa menu andalan kami. Lihat menu selengkapnya dan pesan sekarang!</p>
    <div class="row">
        <?php while ($row = mysqli_fetch_assoc($result_produk)) : ?>

        <div class = "menu-container">
            <div class="menu-card">

                <img src="../backend/assets/img/produk/<?= htmlspecialchars($row['poto_produk'] ?? 'default.jpg') ?>"
                    alt="<?= htmlspecialchars($row['nama_produk'] ?? 'Gambar Produk') ?>"
                    class="menu-card-img">

                <h3 class="menu-card-title">- <?= htmlspecialchars($row['nama_produk'] ?? 'Nama Produk') ?> -</h3>

                <p class="menu-card-price">Rp <?= number_format($row['harga'] ?? 0, 0, ',', '.') ?></p>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</section>
<section id = "faq" class ="faq">
    <h2><span>FAQ</span></h2>
    <p>Pertanyaan Yang Sering Di ajukan, 
        Untuk membantu beberapa pertanyaan umum Barudak Mang Wiro seputar Kue Balok Mang Wiro, berikut beberapa pertanyaan dan jawabannya:
    </p>
    <div class="faq-container">
    <div class="faq-card">
        <details>
            <summary>1. Bagaimana Cara Pesan Online?</summary>
            <p>Kamu bisa Klik di navigasi bar "Menu" atau bisa langsung di halaman utama
                scroll kebawah di bagian "Menu Kami".
            </p>
        </details>
    </div>
    <div class="faq-card">
        <details>
            <summary>2. Bagaimana cara pembayaran pesan online ?</summary>
            <p>Kamu bisa melakukan membayar dengan QRIS, Transfer Bank dan E-wallet.</p>
        </details>
    </div>
    <div class="faq-card">
        <details>
            <summary>3. Dimanakah lokasi Kue Balok Mang Wiro?</summary>
            <p>Outlet kami ada di jalan Otista Samping SMPN 3 Subang.lebih lengkapnya ada dibagian "Tentang Kami".</p>
        </details>
    </div>
    <div class="faq-card">
        <details>
            <summary>4. Tingkat kematang Kue Balok </summary>
            <p>tingkat Kematangan Kue Balok itu sendiri selera dari kami ada 2 pilih jika ingin lumer saat di makan kamu bisa Request setengah mateng dan 
                jika ingin sedikit padat saat di makan anda bisa Request mateng.</p>
        </details>
    </div>
</section>
<section id="contact" class="contact">
    <h2><span>Kontak</span> Kami</h2>
    <p>CQM8+R2W, Karanganyar, Kec. Subang, Kabupaten Subang, Jawa Barat 41211</p>
    <div class="row">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3963.6532678371236!2d107.76250947604129!3d-6.565374464182671!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e693b6ef7c6693f%3A0x7f77ee95ffd77873!2sKue%20Balok%20Mang%20Wiro!5e0!3m2!1sid!2sid!4v1747817984686!5m2!1sid!2sid"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
            class="map">
        </iframe>

        <form action="index.php#contact" method="POST">
            <?php
            if (isset($_SESSION['notif_feedback'])) {
                echo '<div class="form-notification">' . htmlspecialchars($_SESSION['notif_feedback']) . '</div>';
                unset($_SESSION['notif_feedback']);
            }
            ?>
            <div class="input-group">
                <i data-feather="user"></i>
                <input type="text" name="nama" placeholder="Nama Anda" required>
            </div>
            <div class="input-group">
                <i data-feather="mail"></i>
                <input type="email" name="email" placeholder="Email Anda" required>
            </div>
            <div class="input-group">
                <i data-feather="message-square"></i>
                <input type="text" name="pesan" placeholder="Pesan & Kesan Anda" required>
            </div>
            <button type="submit" name="kirim_pesan" class="btn">Kirim Pesan</button>
        </form>
    </div>
</section>
<?php
include 'includes/footer.php';
?>