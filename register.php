<?php
// register.php - Halaman pendaftaran pegawai baru
require_once 'php_config.php';

// Jika sudah login, redirect ke index.php
if (isset($_SESSION['pegawai_id'])) {
    header('Location: index.php');
    exit;
}

// Proses pendaftaran
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = sanitize($_POST['nama_pegawai'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $posisi = sanitize($_POST['posisi'] ?? 'Staff');
    $telepon = sanitize($_POST['no_telepon'] ?? '');

    // Validasi
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = "Nama harus diisi";
    }
    
    if (empty($email)) {
        $errors[] = "Email harus diisi";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid";
    } else {
        // Cek apakah email sudah terdaftar
        $stmt = $conn->prepare("SELECT id_pegawai FROM pegawai WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $errors[] = "Email sudah terdaftar";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password harus diisi";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password minimal 6 karakter";
    } elseif ($password !== $password_confirm) {
        $errors[] = "Konfirmasi password tidak cocok";
    }
    
    // Jika tidak ada error, proses pendaftaran
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $tanggal_masuk = date('Y-m-d');
        
        $stmt = $conn->prepare("INSERT INTO pegawai (nama_pegawai, posisi, no_telepon, email, tanggal_masuk) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('sssss', $nama, $posisi, $telepon, $email, $tanggal_masuk);
        
        if ($stmt->execute()) {
            $pegawai_id = $conn->insert_id;
            
            // Update password (karena kita ingin menyimpannya terpisah)
            $stmt = $conn->prepare("UPDATE pegawai SET password = ? WHERE id_pegawai = ?");
            $stmt->bind_param('si', $hashed_password, $pegawai_id);
            $stmt->execute();
            
            // Set session dan redirect
            $_SESSION['pegawai_id'] = $pegawai_id;
            $_SESSION['nama_pegawai'] = $nama;
            $_SESSION['email'] = $email;
            
            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Gagal mendaftar: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Hotel Senang Hati</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .register-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: rgba(255, 248, 240, 0.95);
            border-radius: 20px;
            box-shadow: 0 8px 30px rgba(139, 115, 85, 0.2);
            border: 1px solid rgba(205, 186, 160, 0.3);
        }
        
        .register-logo {
            text-align: center;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 700;
            color: #8b7355;
            font-family: 'Georgia', serif;
        }
        
        .register-form .form-group {
            margin-bottom: 20px;
        }
        
        .register-form input, 
        .register-form select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid rgba(205, 186, 160, 0.4);
            border-radius: 12px;
            font-size: 16px;
            background: rgba(255, 248, 240, 0.8);
            color: #5d4e37;
        }
        
        .register-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #d4a574, #c49969);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .register-btn:hover {
            background: linear-gradient(135deg, #c49969, #b8875a);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #5d4e37;
        }
        
        .login-link a {
            color: #8b7355;
            text-decoration: none;
            font-weight: 600;
        }
        
        .error-message {
            color: #daa574;
            margin-bottom: 20px;
            padding: 10px;
            background: rgba(255, 248, 240, 0.8);
            border-radius: 8px;
            border-left: 4px solid #daa574;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-logo">Hotel Senang Hati</div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach ($errors as $error): ?>
                    <div><?= $error ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <form class="register-form" method="POST">
            <div class="form-group">
                <input type="text" name="nama_pegawai" placeholder="Nama Lengkap" required>
            </div>
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <input type="password" name="password_confirm" placeholder="Konfirmasi Password" required>
            </div>
            <div class="form-group">
                <input type="tel" name="no_telepon" placeholder="Nomor Telepon">
            </div>
            <div class="form-group">
                <select name="posisi">
                    <option value="Staff">Staff</option>
                    <option value="Manager">Manager</option>
                    <option value="Admin">Admin</option>
                    <option value="Housekeeping">Housekeeping</option>
                    <option value="Resepsionis">Resepsionis</option>
                </select>
            </div>
            <button type="submit" class="register-btn">Daftar</button>
        </form>
        
        <div class="login-link">
            Sudah punya akun? <a href="login.php">Login disini</a>
        </div>
    </div>
</body>
</html>