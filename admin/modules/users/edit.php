<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Không tìm thấy ID");

// Lấy thông tin User
$stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) die("Thành viên không tồn tại.");

// Xử lý Form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $role = $_POST['role'];
    $newPass = $_POST['password']; 
    
    // Xử lý Avatar (Cloudinary)
    $avatarPath = $user['Avatar']; // Mặc định giữ cũ
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
        $uploaded = uploadImageToCloud($_FILES['avatar'], 'avatars');
        if ($uploaded) $avatarPath = $uploaded;
    }

    // Xử lý Cập nhật (Có đổi pass hay không)
    if (!empty($newPass)) {
        $sql = "UPDATE users SET Email=?, Role=?, Avatar=?, Password=? WHERE UserID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $role, $avatarPath, $newPass, $id]);
    } else {
        $sql = "UPDATE users SET Email=?, Role=?, Avatar=? WHERE UserID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $role, $avatarPath, $id]);
    }

    header("Location: index.php");
    exit;
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Sửa Thành Viên: <?= htmlspecialchars($user['UserName']) ?></h3>
        <a href="index.php" class="btn btn-secondary px-4 py-2 rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card-custom p-4">
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="fw-bold mb-1">Tên đăng nhập</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($user['UserName']) ?>" disabled readonly style="background-color: var(--bg-hover);">
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-1">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['Email']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-1">Mật khẩu mới</label>
                        <input type="text" name="password" class="form-control" placeholder="Để trống nếu không muốn đổi mật khẩu">
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-1">Vai trò</label>
                        <select name="role" class="form-select">
                            <option value="0" <?= $user['Role'] == 0 ? 'selected' : '' ?>>Thành viên (Member)</option>
                            <option value="1" <?= $user['Role'] == 1 ? 'selected' : '' ?>>Quản trị viên (Admin)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="fw-bold mb-2">Avatar hiện tại</label>
                        <div class="d-flex align-items-center mb-2">
                            <?php 
                                $currentAvatar = !empty($user['Avatar']) ? getImageUrl($user['Avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['UserName']);
                            ?>
                            <img src="<?= $currentAvatar ?>" width="60" height="60" class="rounded-circle border border-secondary me-3" style="object-fit: cover;">
                            
                            <div class="flex-grow-1">
                                <input type="file" name="avatar" class="form-control" accept="image/*">
                                <div class="form-text text-muted">Chọn ảnh mới để thay thế (Cloudinary).</div>
                            </div>
                        </div>
                    </div>

                    <hr>
                    <button type="submit" class="btn btn-naver w-100 py-2 rounded-pill">
                        <i class="fas fa-sync-alt me-2"></i>Cập nhật thông tin
                    </button>
                </form>

            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>