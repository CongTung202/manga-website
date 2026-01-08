<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Gọi functions

$id = $_GET['id'] ?? null;
$articleId = $_GET['article_id'] ?? null;

if (!$id) die("Không tìm thấy Chapter ID");

// XỬ LÝ XÓA ẢNH LẺ
if (isset($_GET['action']) && $_GET['action'] == 'delete_img' && isset($_GET['img_id'])) {
    $imgId = $_GET['img_id'];
    $stmtDel = $pdo->prepare("DELETE FROM chapter_images WHERE ImageID = ?");
    $stmtDel->execute([$imgId]);
    header("Location: edit.php?id=$id&article_id=$articleId");
    exit;
}

// LẤY DỮ LIỆU CHAPTER
$stmt = $pdo->prepare("SELECT * FROM chapters WHERE ChapterID = ?");
$stmt->execute([$id]);
$chapter = $stmt->fetch();

// LẤY ẢNH CỦA CHAPTER
$stmtImg = $pdo->prepare("SELECT * FROM chapter_images WHERE ChapterID = ? ORDER BY SortOrder ASC");
$stmtImg->execute([$id]);
$images = $stmtImg->fetchAll();

// XỬ LÝ FORM CẬP NHẬT
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $index = $_POST['index'];
    
    // 1. Cập nhật thông tin cơ bản
    $sql = "UPDATE chapters SET Title=?, `Index`=? WHERE ChapterID=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$title, $index, $id]);

    // 2. Upload thêm ảnh (Cloudinary)
    if (isset($_FILES['images'])) {
        $files = $_FILES['images'];
        $totalFiles = count($files['name']);
        
        // Lấy thứ tự tiếp theo
        $nextOrder = count($images); 

        $sqlImg = "INSERT INTO chapter_images (ChapterID, ImageURL, SortOrder) VALUES (?, ?, ?)";
        $stmtImgAdd = $pdo->prepare($sqlImg);

        for ($i = 0; $i < $totalFiles; $i++) {
            if ($files['error'][$i] == 0) {
                $singleFile = [
                    'name' => $files['name'][$i],
                    'type' => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error' => $files['error'][$i],
                    'size' => $files['size'][$i]
                ];
                
                // Upload lên Cloudinary
                $uploadedPath = uploadImageToCloud($singleFile, 'chapters');
                
                if ($uploadedPath) {
                    $stmtImgAdd->execute([$id, $uploadedPath, $nextOrder + $i]);
                }
            }
        }
    }

    header("Location: ../articles/view.php?id=$articleId");
    exit;
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <h3 class="fw-bold mb-4">Chỉnh sửa Chapter</h3>
    
    <div class="card-custom p-4">
        <form method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1">Số thứ tự (Index)</label>
                    <input type="number" step="0.1" name="index" class="form-control" value="<?= $chapter['Index'] ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1">Tên chương</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($chapter['Title']) ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold mb-2">Ảnh hiện tại của chương:</label>
                <div class="d-flex flex-wrap gap-2 border border-secondary p-3 rounded" style="max-height: 400px; overflow-y: auto; background-color: var(--bg-body);">
                    <?php foreach($images as $img): ?>
                        <div class="position-relative" style="width: 120px;">
                            <img src="<?= getImageUrl($img['ImageURL']) ?>" class="w-100 rounded border border-secondary" loading="lazy">
                            
                            <a href="edit.php?id=<?= $id ?>&article_id=<?= $articleId ?>&action=delete_img&img_id=<?= $img['ImageID'] ?>" 
                               class="position-absolute top-0 end-0 badge bg-danger text-decoration-none shadow-sm"
                               style="margin: 2px;"
                               onclick="return confirm('Xóa ảnh này?')">
                               <i class="fas fa-times"></i>
                            </a>
                            <small class="d-block text-center text-muted mt-1" style="font-size: 10px;">Order: <?= $img['SortOrder'] ?></small>
                        </div>
                    <?php endforeach; ?>
                    <?php if(count($images) == 0) echo '<p class="text-muted small m-2">Chưa có ảnh nào.</p>'; ?>
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold mb-1 text-primary">Upload thêm ảnh (sẽ nối vào cuối):</label>
                <input type="file" name="images[]" class="form-control" multiple accept="image/*">
            </div>

            <hr>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-naver"><i class="fas fa-save me-2"></i>Cập nhật thay đổi</button>
                <a href="../articles/view.php?id=<?= $articleId ?>" class="btn btn-secondary">Quay lại</a>
            </div>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>