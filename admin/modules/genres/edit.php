<?php
require_once '../../../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Không tìm thấy ID");

// Lấy thông tin cũ
$stmt = $pdo->prepare("SELECT * FROM genres WHERE GenreID = ?");
$stmt->execute([$id]);
$genre = $stmt->fetch();

if (!$genre) die("Thể loại không tồn tại");

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error = "Tên không được để trống";
    } else {
        // Cập nhật
        $stmtUpdate = $pdo->prepare("UPDATE genres SET Name = ? WHERE GenreID = ?");
        if ($stmtUpdate->execute([$name, $id])) {
            header("Location: index.php");
            exit;
        } else {
            $error = "Lỗi khi cập nhật.";
        }
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Sửa Thể Loại</h3>
        <a href="index.php" class="btn btn-secondary px-4 py-2 rounded-pill">Quay lại</a>
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
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($genre['Name']) ?>" required>
                    </div>

                    <button type="submit" class="btn btn-warning w-100 py-2 rounded-pill">
                        <i class="fas fa-sync-alt me-2"></i>Cập nhật
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>