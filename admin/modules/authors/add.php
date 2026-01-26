<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $avatarUrl = null;

    if (empty($name)) {
        $error = "Vui lòng nhập tên tác giả.";
    } else {
        // Xử lý Upload ảnh (Nếu có chọn file)
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] == 0) {
            // Gọi hàm upload Cloudinary (đã có trong functions.php)
            // Tham số thứ 2 là 'authors' -> Folder trên Cloudinary
            $uploaded = uploadImageToCloud($_FILES['avatar'], 'authors');
            if ($uploaded) {
                $avatarUrl = $uploaded;
            } else {
                $error = "Lỗi khi upload ảnh lên Cloud.";
            }
        }

        if (!$error) {
            $stmt = $pdo->prepare("INSERT INTO authors (Name, Description, Avatar) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $description, $avatarUrl])) {
                echo "<script>alert('Thêm thành công!'); window.location.href='index.php';</script>";
                exit;
            } else {
                $error = "Lỗi hệ thống, vui lòng thử lại.";
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Thêm Tác Giả</h3>
        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Quay lại</a>
    </div>

    <div class="card-custom p-4" style="max-width: 800px; margin: 0 auto;">
        <?php if($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Tên tác giả <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" required placeholder="Nhập tên tác giả">
            </div>

            <div class="mb-3">
                <label class="form-label">Ảnh đại diện</label>
                <input type="file" name="avatar" class="form-control" accept="image/*">
                <div class="form-text text-muted">Hỗ trợ JPG, PNG, WEBP. Upload lên Cloudinary.</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Mô tả / Tiểu sử</label>
                <textarea name="description" class="form-control" rows="5" placeholder="Thông tin về tác giả..."></textarea>
            </div>

            <button type="submit" class="btn btn-success px-4">Lưu Tác Giả</button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>