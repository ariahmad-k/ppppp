<?php
// Memastikan sesi dimulai dengan aman di setiap halaman
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../backend/koneksi.php'; // Pastikan koneksi database sudah benar

?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>Kue Balok Mang Wiro</title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,300;0,400;0,700;1,700&display=swap" rel="stylesheet" />

    <script src="https://unpkg.com/feather-icons"></script>


    <link rel="stylesheet" href="assets/css/style.css" />
    <link rel="icon" type="image/png" href="assets/img/logo-kuebalok.png">
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="navbar-logo">
            <img src="assets/img/logo kecil 5.png" alt="LOGO KUE BALOK MANG WIRO" />
        </a>
        <div class="navbar-nav">
            <a href="index.php#home">Beranda</a>
            <a href="index.php#about">Tentang Kami</a>
            <a href="menu.php">Menu</a>
            <a href="lacak.php">Lacak Pesanan</a>
            <a href="index.php#faq">FAQ</a>
            <a href="kontak.php">Kontak</a>
        </div>

        <div class="navbar-extra">
            <a href="keranjang.php" id="shopping-cart-button">
                <i data-feather="shopping-cart"></i>
                <span class="cart-item-count" style="display:none; background-color:red; color:white; border-radius:50%; padding: 0.1rem 0.5rem; font-size: 0.8rem; position:absolute; top:0; right:0;">0</span>
            </a>
            <a href="#" id="hamburger-menu"><i data-feather="menu"></i></a>
        </div>

        <div class="search-form">
            <input type="search" id="search-box" placeholder="Cari menu...">
            <label for="search-box"><i data-feather="search"></i></label>
        </div>
    </nav>

