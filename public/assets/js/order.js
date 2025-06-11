let cart = {};
let produkData = {};

// Ambil data harga dan stok di awal
window.addEventListener("DOMContentLoaded", () => {
  fetch("get_harga_produk.php")
    .then((res) => res.json())
    .then((data) => {
      produkData = data;
    });
});

function ubahJumlah(id, perubahan) {
  if (!cart[id]) cart[id] = 0;

  const stok = produkData["stok_" + id] ?? 0;

  if (perubahan === 1 && cart[id] >= stok) {
    alert("Stok tidak mencukupi!");
    return;
  }

  cart[id] += perubahan;
  if (cart[id] < 0) cart[id] = 0;

  document.getElementById("jumlah-" + id).textContent = cart[id];
  hitungTotal();
}

function hitungTotal() {
  let total = 0;
  for (const id in cart) {
    const harga = produkData[id] || 0;
    total += cart[id] * harga;
  }
  document.getElementById("totalHarga").textContent =
    "Rp. " + total.toLocaleString("id-ID");
}

function submitOrder() {
  const nama = document.getElementById("namaPemesan").value;
  if (!nama) {
    alert("Nama harus diisi!");
    return;
  }

  const formData = new FormData();
  formData.append("nama", nama);
  formData.append("cart", JSON.stringify(cart));

  fetch("submit_order.php", {
    method: "POST",
    body: formData,
  })
    .then((response) => response.text())
    .then((res) => {
      alert(res);
      window.location.reload();
    });
}
