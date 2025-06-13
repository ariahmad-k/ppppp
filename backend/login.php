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
    $username = $_POST['nama'];
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = mysqli_prepare($koneksi, "SELECT id_karyawan, nama, username, password, jabatan FROM karyawan WHERE username = ?");
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $data = mysqli_fetch_assoc($result);

        if ($data && password_verify($password, $data['password'])) {
            $_SESSION['user'] = [
                'id'       => $data['id_karyawan'],
                'nama'     => $data['nama'],
                'username' => $data['username'],
                'jabatan'  => $data['jabatan']
            ];

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
            $error = "Username atau password salah.";
        }
    } else {
        $error = "Semua field wajib diisi.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Kue Balok Mang Wiro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="text-center">
            <img class="login-logo" src="assets/img/logo-kuebalok.png" alt="Logo Kue Balok">
            <h1 class="login-title">Welcome Back!</h1>
            <p class="login-subtitle">Silakan login untuk melanjutkan</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
        </div>

        <form action="login.php" method="post">
            <div class="form-label-group">
                <input type="text" id="nama" name="nama" class="form-control" placeholder=" " required autofocus>
                <label for="nama">Username</label>
            </div>

            <div class="form-label-group">
                <input type="password" id="password" name="password" class="form-control" placeholder=" " required>
                <label for="password">Password</label>
            </div>

            <button class="login-btn" type="submit">Sign In</button>
        </form>

        <div class="login-footer text-center">
            &copy; <?= date('Y') ?> Kue Balok Mang Wiro. All rights reserved.
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>