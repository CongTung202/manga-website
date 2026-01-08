<?php
require_once '../../../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description'] ?? ''); // Nếu bạn có cột Description

    if (empty($name)) {
        $error = "Tên thể loại không được để trống!";
    } else {
        // Kiểm tra trùng tên
        $stmtCheck = $pdo->prepare("SELECT GenreID FROM genres WHERE Name = ?");
        $stmtCheck->execute([$name]);
        if ($stmtCheck->rowCount() > 0) {
            $error = "Thể loại này đã tồn tại!";
        } else {
            // Thêm mới
            // Nếu bảng genres của bạn chưa có cột Description, hãy bỏ biến $description đi
            $stmt = $pdo->prepare("INSERT INTO genres (Name) VALUES (?)"); 
            if ($stmt->execute([$name])) {
                header("Location: index.php");
                exit;
            } else {
                $error = "Lỗi hệ thống, vui lòng thử lại.";
            }
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Thêm Thể Loại</h3>
        <a href="index.php" class="btn btn-secondary px-4 py-2 rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card-custom p-4">
                <?php if($error): ?>
                    <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="fw-bold mb-2">Tên thể loại</label>
                        <input type="text" name="name" class="form-control" placeholder="VD: Action, Romance..." required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold mb-2">Mô tả (Tùy chọn)</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Mô tả ngắn về thể loại..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-naver w-100 py-2 rounded-pill">
                        <i class="fas fa-save me-2"></i>Lưu thể loại
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>