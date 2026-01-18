<?php
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    
    // [ĐÃ SỬA] Đổi key nhận dữ liệu thành register_password để khớp với form HTML
    $password = $_POST['register_password']; 
    $confirm_password = $_POST['confirm_password'];

    // 1. Validate cơ bản
    if ($password !== $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp!";
    } else {
        // 2. Kiểm tra User đã tồn tại chưa
        $stmt = $pdo->prepare("SELECT UserID FROM users WHERE UserName = ? OR Email = ?");
        $stmt->execute([$username, $email]);
        
        if ($stmt->rowCount() > 0) {
            $error = "Tên đăng nhập hoặc Email đã được sử dụng.";
        } else {
            // [THAY ĐỔI Ở ĐÂY] Đặt ảnh mặc định
            $defaultAvatar = 'default/defaultavatar.png'; 

            // 3. Thêm vào DB
            $sql = "INSERT INTO users (UserName, Email, Password, Role, Avatar) VALUES (?, ?, ?, 0, ?)";
            $stmtInsert = $pdo->prepare($sql);
            
            if ($stmtInsert->execute([$username, $email, $password, $defaultAvatar])) {
                $success = "Đăng ký thành công! Đang chuyển hướng...";
                echo "<script>setTimeout(function(){ window.location.href = 'login.php'; }, 2000);</script>";
            } else {
                $error = "Có lỗi xảy ra, vui lòng thử lại.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - GTSCHUNDER</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css?v=<?= time() ?>">
</head>
<body>

    <div class="login-wrapper">
        
        <h1 class="login-logo">GTSC<strong>HUNDER</strong></h1>

        <div class="login-card">
            
            <div class="login-tabs">
                <a href="login.php" class="tab-item inactive">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </a>
                <div class="tab-item active">
                    <i class="fas fa-user-plus"></i> Đăng ký
                </div>
            </div>

            <div class="login-body">
                
                <?php if($error): ?>
                    <div class="alert-error">
                        <i class="fas fa-exclamation-triangle me-2"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert-success" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; padding: 10px; border: 1px solid #2ecc71; border-radius: 4px; margin-bottom: 20px; font-size: 13px; text-align: center;">
                        <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                    </div>
                <?php else: ?>

                <form method="POST">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="text" name="username" class="form-input" required autocomplete="off" id="username" placeholder=" ">
                            <label for="username" class="floating-label">Tên đăng nhập</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="email" name="email" class="form-input" required autocomplete="off" id="email" placeholder=" ">
                            <label for="email" class="floating-label">Địa chỉ Email</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="password" name="register_password" class="form-input" required id="register_password" placeholder=" ">
                            <label for="register_password" class="floating-label">Mật khẩu</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="password" name="confirm_password" class="form-input" required id="confirm_password" placeholder=" ">
                            <label for="confirm_password" class="floating-label">Xác nhận mật khẩu</label>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">Đăng ký tài khoản</button>
                    
                    <div class="login-footer-link" style="text-align: center; margin-top: 15px; font-size: 13px; color: var(--text-muted);">
                        Đã có tài khoản? <a href="login.php" style="color: var(--primary-theme); font-weight: bold;">Đăng nhập ngay</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-text">
            <strong>GTSCHUNDER</strong> Copyright © <strong>GTSCHUNDER Corp.</strong> All Rights Reserved.
        </div>
    </div>

</body>
</html>