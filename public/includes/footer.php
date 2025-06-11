<footer>
    <div class="socials">
        <a href="https://www.instagram.com/kuebalokmangwiro/" target="_blank">
            <i data-feather="instagram"></i>
        </a>
    </div>

    <div class="links">
        <a href="index.php#home">Home</a>
        <a href="index.php#about">Tentang Kami</a>
        <a href="menu.php">Menu</a>
        <a href="lacak.php">Lacak Pesanan</a>
        <a href="index.php#faq">FAQ</a>
        <a href="index.php#contact">Kontak</a>
    </div>

    <div class="credit">
        <p>Created by <a href="">Kue Mang Wiro</a>. | &copy; 2025.</p>
    </div>
</footer>
<script>
    // Letakkan kode ini di dalam tag <script> di halaman menu atau footer.

    document.addEventListener('DOMContentLoaded', function() {

        // 1. Ambil semua tombol 'Tambah ke Keranjang'
        const addToCartButtons = document.querySelectorAll('.add-to-cart-btn button');

        // 2. Beri event listener untuk setiap tombol
        addToCartButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault(); // Mencegah aksi default tombol

                const productId = this.dataset.id;
                const productName = this.dataset.nama;
                const productPrice = parseFloat(this.dataset.harga);

                // Panggil fungsi untuk mengecek stok sebelum menambahkan
                checkStockAndAddToCart(productId, productName, productPrice);
            });
        });

        // 3. Fungsi utama untuk cek stok dan menambah ke keranjang
        async function checkStockAndAddToCart(id, nama, harga) {

            // Ambil keranjang dari localStorage, atau buat baru jika belum ada
            let cart = JSON.parse(localStorage.getItem('shopping_cart')) || {};

            // Cek berapa jumlah item ini yang sudah ada di keranjang
            const currentQtyInCart = cart[id] ? cart[id].jumlah : 0;

            try {
                // 4. Panggil API cek_stok.php menggunakan Fetch
                const response = await fetch(`cek_stok.php?id=${id}`);
                const data = await response.json();

                // 5. Logika Pengecekan
                if (data.error) {
                    // Jika produk tidak ditemukan di database
                    alert(`Error: Produk ${nama} tidak tersedia.`);
                    return;
                }

                if (data.stok > currentQtyInCart) {
                    // ---- STOK TERSEDIA ----
                    // Jika item sudah ada, tambah jumlahnya. Jika belum, buat item baru.
                    if (cart[id]) {
                        cart[id].jumlah++;
                    } else {
                        cart[id] = {
                            nama: nama,
                            harga: harga,
                            jumlah: 1
                        };
                    }

                    // Simpan kembali keranjang yang sudah diupdate ke localStorage
                    localStorage.setItem('shopping_cart', JSON.stringify(cart));

                    // Beri notifikasi ke pelanggan (bisa diganti dengan notifikasi yang lebih cantik)
                    alert(`${nama} berhasil ditambahkan ke keranjang!`);

                    // (Opsional) Update tampilan ikon keranjang
                    updateCartIcon();

                } else {
                    // ---- STOK HABIS ----
                    alert(`Maaf, stok untuk ${nama} sudah habis atau tidak mencukupi.`);
                }

            } catch (error) {
                console.error('Gagal menghubungi server untuk cek stok:', error);
                alert('Terjadi kesalahan. Tidak dapat memverifikasi stok saat ini.');
            }
        }

        // (Opsional) Fungsi untuk mengupdate angka di ikon keranjang
        function updateCartIcon() {
            let cart = JSON.parse(localStorage.getItem('shopping_cart')) || {};
            let totalItems = 0;
            for (let id in cart) {
                totalItems += cart[id].jumlah;
            }

            // Asumsi Anda punya elemen dengan id 'cart-item-count' di dekat ikon keranjang
            const cartIcon = document.querySelector('#cart-item-count');
            if (cartIcon) {
                cartIcon.textContent = totalItems;
            }
        }

        // Panggil saat halaman pertama kali dimuat
        updateCartIcon();
    });


    feather.replace();
</script>

<script src="assets/js/script.js"></script>
</body>

</html>