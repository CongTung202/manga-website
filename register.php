<?php
require_once 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
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
            // 3. Thêm vào DB
            $sql = "INSERT INTO users (UserName, Email, Password, Role) VALUES (?, ?, ?, 0)";
            $stmtInsert = $pdo->prepare($sql);
            
            if ($stmtInsert->execute([$username, $email, $password])) {
                $success = "Đăng ký thành công!";
            } else {
                $error = "Có lỗi xảy ra, vui lòng thử lại.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký - GTSCHUNDER</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css">
    
    <style>
        .error-msg {
            color: #ff4d4d; font-size: 13px; text-align: center; margin-bottom: 15px;
            padding: 10px; background: rgba(255, 77, 77, 0.1); border: 1px solid #ff4d4d; border-radius: 4px;
        }
        .success-msg {
            color: #00c300; font-size: 13px; text-align: center; margin-bottom: 15px;
            padding: 10px; background: rgba(0, 195, 0, 0.1); border: 1px solid #00c300; border-radius: 4px;
        }
        a.tab-item { text-decoration: none; display: block; }
        
        /* Ghi đè style bo góc cho form đăng ký vì có nhiều input hơn */
        .reg-group input { border-radius: 0; }
        .reg-group:first-child input { border-radius: 4px 4px 0 0; }
        .reg-group:last-child input { border-radius: 0 0 4px 4px; }
        /* Reset border-top cho các input ở giữa để tránh bị double border */
        .reg-group:not(:first-child) input { border-top: none; }
        .reg-group:not(:first-child) input:focus { border-top: 1px solid var(--primary-theme); margin-top: -1px; }

    </style>
</head>
<body>

    <div class="login-wrapper">
        
        <div class="lang-selector">
            <select>
                <option>Tiếng Việt</option>
                <option>English</option>
            </select>
        </div>

        <h1 class="login-logo">GTSC<strong>HUNDER</strong></h1>

        <div class="login-card">
            
            <div class="login-tabs">
                <a href="login.php" class="tab-item">
                    <i class="fa-regular fa-id-card"></i> Đăng nhập
                </a>
                <label readonly class="tab-item active">
                    <i class="fa-solid fa-qrcode"></i> Đăng ký
                </label>
            </div>

            <div class="login-form-container">
                
                <?php if($error): ?>
                    <div class="error-msg"><?= $error ?></div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="success-msg">
                        <?= $success ?> <br>
                        <a href="login.php" style="color: inherit; font-weight: bold; text-decoration: underline;">Đăng nhập ngay</a>
                    </div>
                <?php else: ?>

                <form method="POST">
                    <div class="input-group reg-group">
                        <input type="text" name="username" placeholder="Tên đăng nhập" required autocomplete="off">
                    </div>
                    <div class="input-group reg-group">
                        <input type="email" name="email" placeholder="Địa chỉ Email" required autocomplete="off">
                    </div>
                    <div class="input-group reg-group">
                        <input type="password" name="password" placeholder="Mật khẩu" required autocomplete="off">
                    </div>
                    <div class="input-group reg-group">
                        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu" required autocomplete="off">
                    </div>

                    <div style="margin-top: 20px;"></div>

                    <button type="submit" class="btn-signin">Đăng Ký</button>

                    <div class="login-links">
                        <span>Đã có tài khoản?</span>
                        <a href="login.php" style="color: var(--primary-theme); font-weight: bold;">Đăng nhập ngay</a>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>

        <footer class="login-footer">
            <strong>GTSCHUNDER</strong> Copyright © <strong><?= date('Y') ?></strong> All Rights Reserved.
        </footer>

    </div>

</body>
</html>