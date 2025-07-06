<?php
// login.php - Halaman login untuk pegawai
require_once 'php_config.php';

// Jika sudah login, redirect ke index.php
if (isset($_SESSION['pegawai_id'])) {
    header('Location: index.php');
    exit;
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Email dan password harus diisi";
    } else {
        // Cari pegawai berdasarkan email
        $stmt = $conn->prepare("SELECT id_pegawai, nama_pegawai, email, password FROM pegawai WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $pegawai = $result->fetch_assoc();
            
            // Verifikasi password (asumsi password disimpan dengan password_hash())
            if (password_verify($password, $pegawai['password'])) {
                // Set session
                $_SESSION['pegawai_id'] = $pegawai['id_pegawai'];
                $_SESSION['nama_pegawai'] = $pegawai['nama_pegawai'];
                $_SESSION['email'] = $pegawai['email'];
                
                // Redirect ke halaman utama
                header('Location: index.php');
                exit;
            } else {
                $error = "Password salah";
            }
        } else {
            $error = "Email tidak ditemukan";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hotel Senang Hati</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background: rgba(255, 248, 240, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(139, 115, 85, 0.2);
            border: 1px solid rgba(205, 186, 160, 0.3);
        }
        
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
            color: #8b7355;
            font-family: 'Georgia', serif;
        }
        
        .login-form .form-group {
            margin-bottom: 20px;
        }
        
        .login-form input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(205, 186, 160, 0.4);
            border-radius: 12px;
            font-size: 16px;
            background: rgba(255, 248, 240, 0.8);
            color: #5d4e37;
        }
        
        .login-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d4a574, #c49969);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .login-btn:hover {
            background: linear-gradient(135deg, #c49969, #b8875a);
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            color: #5d4e37;
        }
        
        .register-link a {
            color: #8b7355;
            text-decoration: none;
            font-weight: 600;
        }
        
        .error-message {
            color: #daa574;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">Hotel Senang Hati</div>
        
        <?php if (isset($error)): ?>
            <div class="error-message"><?= $error ?></div>
        <?php endif; ?>
        
        <form class="login-form" method="POST">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        
        <div class="register-link">
            Belum punya akun? <a href="register.php">Daftar disini</a>
        </div>
    </div>
</body>
</html>