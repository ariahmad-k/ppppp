// =======================================================
// ==      SCRIPT.JS VERSI FINAL & TERSTRUKTUR      ==
// =======================================================

// --- FUNGSI-FUNGSI UNTUK KERANJANG BELANJA ---

async function addToCart(product) {
    // Ambil keranjang dari localStorage
    let cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    
    // Jumlah produk ini di keranjang saat ini
    const currentQuantityInCart = cart[product.id] ? cart[product.id].jumlah : 0;

    try {
        // 1. Kirim permintaan ke API cek_stok.php
        const response = await fetch(`cek_stok.php?id=${product.id}`);
        const data = await response.json();

        // 2. Cek apakah ada error dari server atau stok tidak ditemukan
        if (data.error) {
            alert(data.message);
            return;
        }

        const serverStock = data.stok;

        // 3. Validasi: Bandingkan stok server dengan jumlah yang ingin ditambahkan
        if (serverStock > currentQuantityInCart) {
            // Jika stok masih tersedia, tambahkan ke keranjang
            if (cart[product.id]) {
                cart[product.id].jumlah++;
            } else {
                cart[product.id] = {
                    nama: product.nama,
                    harga: product.harga,
                    jumlah: 1
                };
            }
            localStorage.setItem('kueBalokCart', JSON.stringify(cart));
            alert(`'${product.nama}' berhasil ditambahkan ke keranjang!`);
            updateCartIcon();
        } else {
            // Jika stok habis
            alert(`Maaf, stok untuk '${product.nama}' tidak mencukupi (sisa: ${serverStock}).`);
        }

    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memeriksa stok.');
    }
}

function updateCartIcon() {
    const cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    let totalItems = 0;
    for (const id in cart) {
        totalItems += cart[id].jumlah;
    }

    const cartCountElement = document.querySelector('.cart-item-count');
    if (cartCountElement) {
        cartCountElement.textContent = totalItems;
        cartCountElement.style.display = totalItems > 0 ? 'inline-block' : 'none';
    }
}

function renderCartPage() {
    const cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    const cartContainer = document.getElementById('cart-items-container');
    const totalPriceElement = document.getElementById('cart-total-price');
    const cartDataInput = document.getElementById('cart-data-input');
    const checkoutButton = document.querySelector('#checkout-form button[type="submit"]');
    let totalPrice = 0;

    cartContainer.innerHTML = ''; // Kosongkan container dulu

    if (Object.keys(cart).length === 0) {
        cartContainer.innerHTML = '<tr><td colspan="5" style="text-align:center; padding: 2rem;">Keranjang Anda masih kosong.</td></tr>';
        totalPriceElement.textContent = 'Total: Rp 0';
        if(checkoutButton) checkoutButton.disabled = true; // Nonaktifkan tombol jika keranjang kosong
        return;
    }

    if(checkoutButton) checkoutButton.disabled = false; // Aktifkan jika ada isinya

    for (const id in cart) {
        const item = cart[id];
        const subtotal = item.harga * item.jumlah;
        totalPrice += subtotal;

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.nama}</td>
            <td>Rp ${item.harga.toLocaleString('id-ID')}</td>
            <td>
                <div class="quantity-control">
                    <button onclick="updateCartQuantity('${id}', -1)">-</button>
                    <input type="text" value="${item.jumlah}" readonly>
                    <button onclick="updateCartQuantity('${id}', 1)">+</button>
                </div>
            </td>
            <td class="text-end">Rp ${subtotal.toLocaleString('id-ID')}</td>
            <td class="text-center"><a href="#" class="remove-btn" onclick="removeFromCart('${id}')">Hapus</a></td>
        `;
        cartContainer.appendChild(row);
    }

    totalPriceElement.textContent = `Total: Rp ${totalPrice.toLocaleString('id-ID')}`;
    if(cartDataInput) cartDataInput.value = JSON.stringify(cart);
}

function updateCartQuantity(productId, change) {
    let cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    if (cart[productId]) {
        cart[productId].jumlah += change;
        if (cart[productId].jumlah <= 0) {
            delete cart[productId];
        }
        localStorage.setItem('kueBalokCart', JSON.stringify(cart));
        renderCartPage();
        updateCartIcon();
    }
}

function removeFromCart(productId) {
    let cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};
    if (cart[productId] && confirm(`Anda yakin ingin menghapus '${cart[productId].nama}' dari keranjang?`)) {
        delete cart[productId];
        localStorage.setItem('kueBalokCart', JSON.stringify(cart));
        renderCartPage();
        updateCartIcon();
    }
}

// --- FUNGSI-FUNGSI UNTUK INISIALISASI HALAMAN ---

function initHomePageAndMenuPage() {
    const addToCartButtons = document.querySelectorAll('.add-to-cart-btn button');
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const product = {
                id: this.dataset.id,
                nama: this.dataset.nama,
                harga: parseInt(this.dataset.harga)
            };
            addToCart(product);
        });
    });
}

// --- BLOK UTAMA: INISIALISASI SAAT HALAMAN DIMUAT ---

document.addEventListener('DOMContentLoaded', function () {
    
    // 1. Inisialisasi UI Umum (Navbar, Search)
    const navbarNav = document.querySelector('.navbar-nav');
    const hamburgerMenu = document.querySelector('#hamburger-menu');
    if (hamburgerMenu) {
        hamburgerMenu.onclick = (e) => {
            navbarNav.classList.toggle('active');
            e.preventDefault();
        };
    }

    const searchForm = document.querySelector('.search-form');
    const searchBox = document.querySelector('#search-box');
    const searchButton = document.querySelector('#search-button');
    if(searchButton) {
        searchButton.onclick = (e) => {
            searchForm.classList.toggle('active');
            searchBox.focus();
            e.preventDefault();
        };
    }
     // Logika untuk form checkout di halaman keranjang.php
    const checkoutForm = document.getElementById('checkout-form');
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            const cart = JSON.parse(localStorage.getItem('kueBalokCart')) || {};

            // Validasi terakhir sebelum kirim, pastikan keranjang tidak kosong
            if (Object.keys(cart).length === 0) {
                alert('Keranjang Anda kosong! Silakan pilih menu terlebih dahulu.');
                e.preventDefault(); // Mencegah form dikirim
                return;
            }

            // PENTING: Pastikan input tersembunyi berisi data keranjang terbaru
            document.getElementById('cart-data-input').value = JSON.stringify(cart);

            // KUNCI UTAMA: Hapus keranjang dari localStorage SETELAH data disiapkan untuk dikirim
            localStorage.removeItem('kueBalokCart');

            // Setelah ini, form akan melanjutkan proses submit ke proses_pesanan.php
            // dan keranjang di browser sudah kosong.
        });
    }

    document.addEventListener('click', function (e) {
        if (hamburgerMenu && !hamburgerMenu.contains(e.target) && !navbarNav.contains(e.target)) {
            navbarNav.classList.remove('active');
        }
        if (searchButton && !searchButton.contains(e.target) && !searchForm.contains(e.target)) {
            searchForm.classList.remove('active');
        }
    });

    // 2. Inisialisasi Keranjang Belanja (berjalan di semua halaman)
    updateCartIcon();

    // 3. Inisialisasi KHUSUS untuk Halaman Menu atau Halaman Utama
    if (document.querySelector('.menu-card')) {
        initHomePageAndMenuPage();
    }
    
    // 4. Inisialisasi KHUSUS untuk Halaman Keranjang
    if (document.querySelector('.cart-page')) {
        renderCartPage();
    }

    // 5. Inisialisasi Ikon Feather
    feather.replace();
});