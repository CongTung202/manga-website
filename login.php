<?php
require_once 'includes/db.php';

// Nếu đã đăng nhập rồi thì đá về trang chủ
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loginInput = trim($_POST['login_input']);
    $password = $_POST['password'];

    // 1. Tìm user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE (UserName = ? OR Email = ?) AND IsDeleted = 0");
    $stmt->execute([$loginInput, $loginInput]);
    $user = $stmt->fetch();

    // 2. Kiểm tra mật khẩu
    if ($user && $user['Password'] === $password) {
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['UserName'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['avatar'] = $user['Avatar'];

        if ($user['Role'] == 1) {
            header("Location: admin/index.php");
        } else {
            header("Location: index.php");
        }
        exit;
    } else {
        $error = "Thông tin đăng nhập không chính xác";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập - GTSCHUNDER</title>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/login.css?v=<?= time() ?>">
</head>
<body>

    <div class="login-wrapper">
        
        <h1 class="login-logo"><a href=""></a>GTSCHUNDER</h1>

        <div class="login-card">
            
            <div class="login-tabs">
                <div class="tab-item active">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </div>
                <a href="<?= BASE_URL ?>register" class="tab-item inactive">
                    <i class="fas fa-user-plus"></i> Đăng ký
                </a>
            </div>

            <div class="login-body">
                
                <?php if($error): ?>
                    <div class="alert-error">
                        <?= $error ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="text" name="login_input" class="form-input" required autocomplete="off" id="login_input" placeholder=" ">
                            <label for="login_input" class="floating-label">Gmail hoặc tên đăng nhập</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-wrapper">
                            <input type="password" name="password" class="form-input" required id="password" placeholder=" ">
                            <label for="password" class="floating-label">Mật khẩu</label>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="custom-checkbox">
                            <input type="checkbox">
                            <span class="checkmark">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            Lưu tài khoản
                        </label>
                    </div>

                    <button type="submit" class="btn-submit">Đăng nhập</button>
                </form>
            </div>
        </div>

        <div class="footer-text">
            <strong>GTSCHUNDER</strong> Copyright © <strong>GTSCHUNDER Corp.</strong> All Rights Reserved.
        </div>
    </div>

</body>
</html>