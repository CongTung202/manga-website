<?php
require_once 'includes/db.php';

// Nếu đã đăng nhập rồi thì đá về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loginInput = trim($_POST['login_input']); // Có thể là Username hoặc Email
    $password = $_POST['password'];

    // 1. Tìm user trong DB
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (UserName = ? OR Email = ?) AND IsDeleted = 0");
    $stmt->execute([$loginInput, $loginInput]);
    $user = $stmt->fetch();

    // 2. Kiểm tra mật khẩu (So sánh chuỗi thô)
    if ($user && $user['Password'] === $password) {
        // Đăng nhập thành công -> Lưu Session
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['UserName'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['avatar'] = $user['Avatar'];

        // 3. Chuyển hướng dựa trên Role
        if ($user['Role'] == 1) {
            header("Location: admin/index.php"); // Vào trang Admin
        } else {
            header("Location: index.php"); // Vào trang chủ
        }
        exit;
    } else {
        $error = "Sai tên đăng nhập hoặc mật khẩu!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - GTSCHUNDER</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css"> <style>
        /* CSS bổ sung cho thông báo lỗi PHP */
        .error-msg {
            color: #ff4d4d;
            font-size: 13px;
            text-align: center;
            margin-bottom: 15px;
            padding: 10px;
            background: rgba(255, 77, 77, 0.1);
            border: 1px solid #ff4d4d;
            border-radius: 4px;
        }
        /* Style cho link tab */
        a.tab-item { text-decoration: none; display: block; }
    </style>
</head>
<body>

    <div class="login-wrapper">
        
        <div class="lang-selector">
            <select>
                <option>Tiếng Việt</option>
                <option>English</option>
                <option>한국어</option>
            </select>
        </div>

        <h1 class="login-logo">GTSC<strong>HUNDER</strong></h1>

        <div class="login-card">
            
            <div readonly class="login-tabs">
                <div class="tab-item active">
                    <i class="fa-regular fa-id-card"></i> Đăng nhập
                </div>
                <a href="register.php" class="tab-item">
                    <i class="fa-solid fa-qrcode"></i> Đăng ký
                </a>
            </div>

            <div class="login-form-container">
                
                <?php if($error): ?>
                    <div class="error-msg">
                        <i class="fas fa-exclamation-circle me-1"></i> <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="input-group">
                        <input type="text" name="login_input" placeholder="Tên đăng nhập hoặc Email" required autocomplete="off">
                    </div>
                    <div class="input-group">
                        <input type="password" name="password" placeholder="Mật khẩu" required autocomplete="off">
                    </div>

                    <div class="login-options">
                        <label class="check-container">
                            <input type="checkbox" checked> 
                            Stay Signed in
                        </label>
                        <div class="ip-security">
                            IP Security <span class="toggle-switch active">ON</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-signin">Đăng Nhập</button>             
                </form>
            </div>
        </div>

        <footer class="login-footer">
            <strong>GTSCHUNDER</strong> Copyright © <strong><?= date('Y') ?></strong> All Rights Reserved.
        </footer>

    </div>

</body>
</html>