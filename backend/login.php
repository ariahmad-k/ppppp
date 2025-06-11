<?php
session_start();
include 'koneksi.php';

$error = '';

// Jika sudah login, redirect ke halaman masing-masing
if (isset($_SESSION['user'])) {
    $jabatan = strtolower($_SESSION['user']['jabatan']);
    header("Location: users/{$jabatan}/index.php");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['nama']; // Menggunakan 'nama' sesuai form Anda
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        // 1. Gunakan PREPARED STATEMENT untuk mencegah SQL Injection
        $stmt = mysqli_prepare($koneksi, "SELECT id_karyawan, nama, username, password, jabatan FROM karyawan WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        // 2. Verifikasi password dengan password_verify()
        if ($data && password_verify($password, $data['password'])) {
            // Jika login berhasil
            
            // 3. Simpan data ke Sesi dengan struktur yang benar
            $_SESSION['user'] = [
                'id'       => $data['id_karyawan'],
                'nama'     => $data['nama'],
                'username' => $data['username'],
                'jabatan'  => $data['jabatan']
            ];

            // 4. Arahkan ke halaman berdasarkan jabatan
            $jabatan = strtolower($data['jabatan']);
            switch ($jabatan) {
                case 'owner':
                    header("Location: users/superadmin/index.php");
                    exit;
                case 'admin':
                    header("Location: users/admin/index.php");
                    exit;
                case 'kasir':
                    header("Location: users/kasir/index.php");
                    exit;
                default:
                    $error = "Jabatan tidak dikenali.";
            }
        } else {
            // Jika username tidak ditemukan atau password salah
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Semua field wajib diisi.";
    }
}
?>

<!doctype html>
<html lang="id">
<head>
    <title>Login - Kue Balok</title>
    <link href="assets/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/dist/css/floating-labels.css" rel="stylesheet">
</head>
<body>
    <form class="form-signin" action="login.php" method="post">
        <div class="text-center mb-4">
            <img class="mb-4" src="assets/img/logo-kuebalok.png" alt="" width="72" height="72">
            <h1 class="h3 mb-3 font-weight-normal">Form Login</h1>
            <p>Masukkan Username dan Password Anda</p>
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="form-label-group">
            <input type="text" class="form-control" name="nama" placeholder="Username" required autofocus>
            <label for="nama">Masukkan Username</label>
        </div>

        <div class="form-label-group">
            <input type="password" class="form-control" name="password" placeholder="Password" required>
            <label for="password">Masukkan Password</label>
        </div>

        <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
        <p class="mt-5 mb-3 text-muted text-center">&copy; Kue Balok Mang Wiro 2025</p>
    </form>
</body>
</html>