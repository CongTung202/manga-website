<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Chứa uploadImageToCloud

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; 
    $role = $_POST['role'];

    // 1. Kiểm tra tồn tại
    $check = $pdo->prepare("SELECT UserID FROM users WHERE (Email = ? OR UserName = ?) AND IsDeleted = 0");
    $check->execute([$email, $username]);
    
    if ($check->rowCount() > 0) {
        $error = "Email hoặc Tên đăng nhập đã tồn tại!";
    } else {
        // 2. Upload Avatar lên Cloudinary
        $avatarPath = null;
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            // Upload vào thư mục 'avatars' trên Cloudinary
            $avatarPath = uploadImageToCloud($_FILES['avatar'], 'avatars');
        }

        // 3. Insert User
        $sql = "INSERT INTO users (UserName, Email, Password, Role, Avatar, CreatedAt) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        
        if ($stmt->execute([$username, $email, $password, $role, $avatarPath])) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Có lỗi xảy ra, vui lòng thử lại.";
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Thêm Thành Viên Mới</h3>
        <a href="index.php" class="btn btn-secondary px-4 py-2 rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-custom p-4">
                
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold mb-1">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" required placeholder="VD: user123">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="fw-bold mb-1">Email</label>
                            <input type="email" name="email" class="form-control" required placeholder="example@gmail.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-1">Mật khẩu</label>
                        <input type="text" name="password" class="form-control" required placeholder="Nhập mật khẩu...">
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-1">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="0">Thành viên (Member)</option>
                            <option value="1">Quản trị viên (Admin)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-1">Avatar</label>
                        <input type="file" name="avatar" class="form-control" accept="image/*">
                        <div class="form-text text-muted">Hỗ trợ JPG, PNG. Ảnh sẽ được upload lên Cloud.</div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-naver w-100 py-2 rounded-pill">
                        <i class="fas fa-save me-2"></i>Lưu Thành Viên
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>