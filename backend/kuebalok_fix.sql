-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Waktu pembuatan: 08 Jun 2025 pada 08.58
-- Versi server: 8.0.30
-- Versi PHP: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `1`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id_detail` bigint NOT NULL,
  `id_pesanan` varchar(20) NOT NULL,
  `id_produk` varchar(20) NOT NULL,
  `jumlah` int NOT NULL,
  `harga_saat_transaksi` decimal(12,2) NOT NULL,
  `sub_total` decimal(12,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id_detail`, `id_pesanan`, `id_produk`, `jumlah`, `harga_saat_transaksi`, `sub_total`) VALUES
(1, 'PSK-20250608045539', 'KS001', 2, 5000.00, 10000.00),
(2, 'PSK-20250608045539', 'KB001', 2, 300.00, 600.00),
(3, 'PSK-20250608045550', 'KS001', 2, 5000.00, 10000.00),
(4, 'PSK-20250608045550', 'KB001', 2, 300.00, 600.00),
(5, 'PSK-20250608053131', 'KS001', 2, 5000.00, 10000.00),
(6, 'PSK-20250608053131', 'KB001', 2, 300.00, 600.00),
(7, 'PSK-20250608053214', 'KS001', 2, 5000.00, 10000.00),
(8, 'PSK-20250608053214', 'KB001', 2, 300.00, 600.00),
(9, 'ONLINE-1749367495', 'KB003', 2, 5000.00, 10000.00),
(10, 'ONLINE-1749368636', 'KB003', 2, 5000.00, 10000.00),
(11, 'ONLINE-1749371979', 'KS001', 1, 5000.00, 5000.00),
(12, 'ONLINE-1749371979', 'DK001', 1, 3000.00, 3000.00),
(13, 'ONLINE-1749372246', 'KS001', 1, 5000.00, 5000.00),
(14, 'ONLINE-1749372246', 'KS002', 1, 3000.00, 3000.00),
(15, 'ONLINE-1749372246', 'KB003', 1, 5000.00, 5000.00),
(16, 'PSK-20250608084512', 'KS002', 1, 3000.00, 3000.00),
(17, 'PSK-20250608084512', 'KB002', 1, 3000.00, 3000.00),
(18, 'PSK-20250608084516', 'KB003', 1, 5000.00, 5000.00),
(19, 'PSK-20250608084516', 'KB002', 1, 3000.00, 3000.00),
(20, 'PSK-20250608084526', 'KB003', 1, 5000.00, 5000.00),
(21, 'PSK-20250608084526', 'KB002', 1, 3000.00, 3000.00),
(22, 'PSK-20250608084547', 'KB001', 1, 300.00, 300.00),
(23, 'PSK-20250608084608', 'KB002', 1, 3000.00, 3000.00),
(24, 'ONLINE-1749372813', 'KB002', 1, 3000.00, 3000.00),
(25, 'ONLINE-1749372854', 'KB002', 1, 3000.00, 3000.00);

-- --------------------------------------------------------

--
-- Struktur dari tabel `feedback`
--

CREATE TABLE `feedback` (
  `id_feedback` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `pesan` text NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `karyawan`
--

CREATE TABLE `karyawan` (
  `id_karyawan` int NOT NULL,
  `nama` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `jabatan` enum('owner','admin','kasir') NOT NULL,
  `no_telepon` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `karyawan`
--

INSERT INTO `karyawan` (`id_karyawan`, `nama`, `username`, `password`, `jabatan`, `no_telepon`, `email`, `dibuat_pada`) VALUES
(1, 'wiam', 'wimz', '$2y$10$6UYYP70NDm7ckJLQaG.TeOrxGEtXdERmOCOlxE.oF6zwa34bfxJja', 'owner', '0932423', 'wim@gmail.com', '2025-06-06 17:00:00'),
(2, 'ridha', 'rida', '$2y$10$U3cC3.E0GtqcFIihsrKlb.KJzKmmkmpOKPZqxiksOc8J/W0FgIYhi', 'kasir', '2324', 'wedfsa@gmail.com', '2025-06-07 15:43:44'),
(3, 'dapongz', 'dz', '$2y$10$LVuH03iAfobVRlerlLI8w.D5PTSs5la4Fw8/lzKzM.SnoAaKO5442', 'admin', '1213132', 'asda@gmail.com', '2025-06-07 15:44:11');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pesanan`
--

CREATE TABLE `pesanan` (
  `id_pesanan` varchar(20) NOT NULL,
  `id_karyawan` int DEFAULT NULL,
  `tipe_pesanan` enum('kasir','online') NOT NULL,
  `jenis_pesanan` enum('dine_in','take_away') DEFAULT 'dine_in',
  `nama_pemesan` varchar(100) DEFAULT 'Walk-in',
  `no_telepon` varchar(25) DEFAULT NULL,
  `tgl_pesanan` datetime NOT NULL,
  `total_harga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `metode_pembayaran` enum('tunai','qris','debit','transfer') NOT NULL,
  `status_pesanan` enum('menunggu_pembayaran','menunggu_konfirmasi','pending','diproses','selesai','dibatalkan') NOT NULL,
  `bukti_pembayaran` varchar(255) DEFAULT NULL,
  `catatan` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `pesanan`
--

INSERT INTO `pesanan` (`id_pesanan`, `id_karyawan`, `tipe_pesanan`, `jenis_pesanan`, `nama_pemesan`, `no_telepon`, `tgl_pesanan`, `total_harga`, `metode_pembayaran`, `status_pesanan`, `bukti_pembayaran`, `catatan`) VALUES
('ONLINE-1749367495', NULL, 'online', 'take_away', 'weirjawijrwa', '1231', '2025-06-08 07:24:55', 10000.00, 'transfer', 'selesai', 'ONLINE-1749367495-1749367932.png', NULL),
('ONLINE-1749368636', NULL, 'online', 'take_away', 'asfsjsfnsjd', '021931829381094', '2025-06-08 07:43:56', 10000.00, 'transfer', 'selesai', 'ONLINE-1749368636-1749368649.png', NULL),
('ONLINE-1749371979', 2, 'online', 'take_away', 'asep', '876543211234', '2025-06-08 08:39:39', 8000.00, 'transfer', 'selesai', 'ONLINE-1749371979-1749371997.jpg', NULL),
('ONLINE-1749372246', 2, 'online', 'take_away', 'aku jawier', '098765432121', '2025-06-08 08:44:06', 13000.00, 'transfer', 'selesai', 'ONLINE-1749372246-1749372261.jpg', NULL),
('ONLINE-1749372813', 2, 'online', 'take_away', 'aadadad', '12345678', '2025-06-08 15:53:33', 3000.00, 'transfer', 'selesai', 'ONLINE-1749372813-1749372828.png', NULL),
('ONLINE-1749372854', 2, 'online', 'take_away', 'saadad', '1231444556786', '2025-06-08 15:54:14', 3000.00, 'transfer', 'selesai', 'ONLINE-1749372854-1749372868.png', NULL),
('PSK-20250608045539', 2, 'kasir', 'dine_in', 'sss', NULL, '2025-06-08 04:55:39', 10600.00, 'tunai', 'selesai', NULL, 'sdsd'),
('PSK-20250608045550', 2, 'kasir', 'dine_in', 'df', NULL, '2025-06-08 04:55:50', 10600.00, 'tunai', 'selesai', NULL, 'f'),
('PSK-20250608053131', 2, 'kasir', 'dine_in', 'df', NULL, '2025-06-08 05:31:31', 10600.00, 'tunai', 'selesai', NULL, 'f'),
('PSK-20250608053214', 2, 'kasir', 'dine_in', 'df', NULL, '2025-06-08 05:32:14', 10600.00, 'tunai', 'selesai', NULL, 'f'),
('PSK-20250608084512', 2, 'kasir', 'dine_in', 'Walk-in', NULL, '2025-06-08 08:45:12', 6000.00, 'tunai', 'selesai', NULL, ''),
('PSK-20250608084516', 2, 'kasir', 'dine_in', 'Walk-in', NULL, '2025-06-08 08:45:16', 8000.00, 'tunai', 'selesai', NULL, ''),
('PSK-20250608084526', 2, 'kasir', 'dine_in', 'Walk-in', NULL, '2025-06-08 08:45:26', 8000.00, 'tunai', 'selesai', NULL, ''),
('PSK-20250608084547', 2, 'kasir', 'dine_in', 'Walk-in', NULL, '2025-06-08 08:45:47', 300.00, 'tunai', 'selesai', NULL, ''),
('PSK-20250608084608', 2, 'kasir', 'dine_in', 'Walk-in', NULL, '2025-06-08 08:46:08', 3000.00, 'tunai', 'selesai', NULL, '');

-- --------------------------------------------------------

--
-- Struktur dari tabel `produk`
--

CREATE TABLE `produk` (
  `id_produk` varchar(20) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `kategori` enum('makanan','minuman') NOT NULL,
  `harga` decimal(12,2) NOT NULL DEFAULT '0.00',
  `stok` int NOT NULL DEFAULT '0',
  `poto_produk` varchar(255) DEFAULT 'default.jpg',
  `status_produk` enum('aktif','tidak aktif') NOT NULL DEFAULT 'aktif',
  `dibuat_pada` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `produk`
--

INSERT INTO `produk` (`id_produk`, `nama_produk`, `kategori`, `harga`, `stok`, `poto_produk`, `status_produk`, `dibuat_pada`) VALUES
('DK001', 'kopi', 'minuman', 3000.00, 5, 'kopi.jpeg', 'aktif', '2025-06-08 05:26:41'),
('KB001', 'kue balok original', 'makanan', 300.00, 1, 'kb-ori.jpg', 'aktif', '2025-06-08 04:53:29'),
('KB002', 'kue balok coklat', 'makanan', 3000.00, 11, 'kb-coklat.jpg', 'aktif', '2025-06-08 05:27:46'),
('KB003', 'kue balok macha', 'makanan', 5000.00, 8, 'kb-macha.jpg', 'aktif', '2025-06-08 05:28:36'),
('KS001', 'ketan susu coklat', 'makanan', 5000.00, 2, 'ks-coklat.jpg', 'aktif', '2025-06-08 04:53:55'),
('KS002', 'ketan susu keju coklat', 'makanan', 3000.00, 9, 'ks-kejucoklat.jpg', 'aktif', '2025-06-08 05:29:54');

-- --------------------------------------------------------

--
-- Struktur dari tabel `stok_harian`
--

CREATE TABLE `stok_harian` (
  `id_stok_harian` int NOT NULL,
  `id_produk` varchar(20) NOT NULL,
  `stok` int NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `stok_harian`
--

INSERT INTO `stok_harian` (`id_stok_harian`, `id_produk`, `stok`, `tanggal`) VALUES
(1, 'KS001', 10, '2025-06-08'),
(2, 'KB001', 10, '2025-06-08'),
(3, 'KS002', 10, '2025-06-08'),
(4, 'DK001', 5, '2025-06-08'),
(5, 'KB002', 15, '2025-06-08'),
(6, 'KB003', 10, '2025-06-08');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id_detail`),
  ADD KEY `id_pesanan` (`id_pesanan`),
  ADD KEY `id_produk` (`id_produk`);

--
-- Indeks untuk tabel `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id_feedback`);

--
-- Indeks untuk tabel `karyawan`
--
ALTER TABLE `karyawan`
  ADD PRIMARY KEY (`id_karyawan`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indeks untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id_pesanan`),
  ADD KEY `id_karyawan` (`id_karyawan`);

--
-- Indeks untuk tabel `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`id_produk`);

--
-- Indeks untuk tabel `stok_harian`
--
ALTER TABLE `stok_harian`
  ADD PRIMARY KEY (`id_stok_harian`),
  ADD UNIQUE KEY `produk_per_hari` (`id_produk`,`tanggal`),
  ADD KEY `id_produk_stok` (`id_produk`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id_detail` bigint NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id_feedback` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `karyawan`
--
ALTER TABLE `karyawan`
  MODIFY `id_karyawan` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `stok_harian`
--
ALTER TABLE `stok_harian`
  MODIFY `id_stok_harian` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `fk_detail_pesanan` FOREIGN KEY (`id_pesanan`) REFERENCES `pesanan` (`id_pesanan`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_detail_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE RESTRICT ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `fk_pesanan_karyawan` FOREIGN KEY (`id_karyawan`) REFERENCES `karyawan` (`id_karyawan`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `stok_harian`
--
ALTER TABLE `stok_harian`
  ADD CONSTRAINT `fk_stok_harian_produk` FOREIGN KEY (`id_produk`) REFERENCES `produk` (`id_produk`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
