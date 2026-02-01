<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isGuru()) {
        header("Location: dashboard_guru.php");
    } else {
        header("Location: dashboard_siswa.php");
    }
    exit();
}

$error = '';
$success = '';

// Process registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = clean_input($_POST['full_name']);
    $email = clean_input($_POST['email']);
    $role = 'siswa'; // Default role is student
    
    // Validation
    if (empty($username) || empty($password) || empty($full_name) || empty($email)) {
        $error = 'Semua field harus diisi!';
    } elseif (strlen($username) < 4) {
        $error = 'Username minimal 4 karakter!';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm_password) {
        $error = 'Password dan konfirmasi password tidak cocok!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid!';
    } else {
        // Check if username already exists
        $check_query = "SELECT * FROM users WHERE username = '$username'";
        $check_result = mysqli_query($conn, $check_query);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error = 'Username sudah digunakan!';
        } else {
            // Check if email already exists
            $check_email = "SELECT * FROM users WHERE email = '$email'";
            $check_email_result = mysqli_query($conn, $check_email);
            
            if (mysqli_num_rows($check_email_result) > 0) {
                $error = 'Email sudah terdaftar!';
            } else {
                // Insert new user
                $hashed_password = MD5($password); // In production, use password_hash()
                $insert_query = "INSERT INTO users (username, password, full_name, email, role) 
                                VALUES ('$username', '$hashed_password', '$full_name', '$email', '$role')";
                
                if (mysqli_query($conn, $insert_query)) {
                    $success = 'Registrasi berhasil! Silakan login.';
                    // Auto redirect to login after 2 seconds
                    header("refresh:2;url=index.php");
                } else {
                    $error = 'Terjadi kesalahan saat registrasi. Silakan coba lagi.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - LMS Jaringan Komputer</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 1000px;
            width: 50%;
            display: flex;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .register-left {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 60px 40px;
            color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-left h1 {
            font-size: 32px;
            margin-bottom: 20px;
        }
        
        .register-left p {
            font-size: 16px;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 30px;
        }
        
        .register-left .features {
            list-style: none;
        }
        
        .register-left .features li {
            margin-bottom: 15px;
            padding-left: 30px;
            position: relative;
        }
        
        .register-left .features li:before {
            content: '✓';
            position: absolute;
            left: 0;
            font-weight: bold;
            font-size: 20px;
        }
        
        .network-icon {
            font-size: 80px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .register-right {
            flex: 1;
            padding: 60px 40px;
        }
        
        .register-right h2 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .register-right .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
        }
        
        .strength-weak {
            background: #f44336;
            width: 33%;
        }
        
        .strength-medium {
            background: #ff9800;
            width: 66%;
        }
        
        .strength-strong {
            background: #4caf50;
            width: 100%;
        }
        
        @media (max-width: 768px) {
            .register-container {
                flex-direction: column;
            }
            
            .register-left {
                padding: 40px 30px;
            }
            
            .register-right {
                padding: 40px 30px;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
       
        
        <div class="register-right">
            <h2>Buat Akun Baru</h2>
            <p class="subtitle">Isi form di bawah untuk mendaftar</p>
            
            <?php if ($error): ?>
                <div class="error-message">❌ <?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-message">✓ <?php echo $success; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm">
                <div class="form-group">
                    <label for="full_name">Nama Lengkap</label>
                    <input type="text" id="full_name" name="full_name" required 
                           value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                           placeholder="Masukkan nama lengkap Anda">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           placeholder="contoh@email.com">
                </div>
                
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Minimal 4 karakter"
                           minlength="4">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Minimal 6 karakter"
                           minlength="6"
                           onkeyup="checkPasswordStrength()">
                    <div class="password-strength">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <small id="strengthText" style="color: #666;"></small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Ulangi password"
                           minlength="6">
                </div>
                
                <button type="submit" class="btn-register">Daftar Sekarang</button>
            </form>
            
            <div class="login-link">
                Sudah punya akun? <a href="index.php">Login di sini</a>
            </div>
        </div>
    </div>
    
    <script>
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            strengthBar.className = 'password-strength-bar';
            
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
                strengthText.textContent = 'Lemah';
                strengthText.style.color = '#f44336';
            } else if (strength <= 4) {
                strengthBar.classList.add('strength-medium');
                strengthText.textContent = 'Sedang';
                strengthText.style.color = '#ff9800';
            } else {
                strengthBar.classList.add('strength-strong');
                strengthText.textContent = 'Kuat';
                strengthText.style.color = '#4caf50';
            }
        }
        
        // Validate password match before submit
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Password dan konfirmasi password tidak cocok!');
                return false;
            }
        });
    </script>
</body>
</html>