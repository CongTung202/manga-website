<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Gọi file chứa uploadImageToCloud

// Lấy ID truyện từ URL (nếu có) để điền sẵn
$article_id = $_GET['article_id'] ?? '';
$article = null;

if ($article_id) {
    $stmt = $pdo->prepare("SELECT Title FROM articles WHERE ArticleID = ?");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
}

// Xử lý Form Submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $articleIdPost = $_POST['article_id'];
    $title = $_POST['title'];
    $index = $_POST['index']; 

    // 1. Thêm thông tin Chapter
    $sqlChap = "INSERT INTO chapters (ArticleID, Title, `Index`, CreatedAt) VALUES (?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sqlChap);
    
    if ($stmt->execute([$articleIdPost, $title, $index])) {
        $newChapterId = $pdo->lastInsertId();
        
        // 2. Xử lý Upload NHIỀU ảnh lên Cloudinary
        if (isset($_FILES['images'])) {
            $files = $_FILES['images'];
            $totalFiles = count($files['name']);

            $sqlImg = "INSERT INTO chapter_images (ChapterID, ImageURL, SortOrder) VALUES (?, ?, ?)";
            $stmtImg = $pdo->prepare($sqlImg);

            for ($i = 0; $i < $totalFiles; $i++) {
                if ($files['error'][$i] == 0) {
                    // Tái tạo mảng file đơn lẻ
                    $singleFile = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i]
                    ];

                    // Gọi hàm upload lên Cloudinary (thư mục 'chapters')
                    $uploadedPath = uploadImageToCloud($singleFile, 'chapters');

                    if ($uploadedPath) {
                        // Lưu link https://... vào DB
                        $stmtImg->execute([$newChapterId, $uploadedPath, $i]);
                    }
                }
            }
        }

        // Xong thì quay về trang chi tiết truyện
        header("Location: ../articles/view.php?id=" . $articleIdPost);
        exit;
    } else {
        echo "<script>alert('Lỗi tạo chapter!');</script>";
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Thêm Chapter Mới</h3>
        <?php if($article_id): ?>
            <a href="../articles/view.php?id=<?= $article_id ?>" class="btn btn-secondary px-4 py-2 rounded-pill">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        <?php else: ?>
            <a href="../articles/index.php" class="btn btn-secondary px-4 py-2 rounded-pill">
                <i class="fas fa-arrow-left me-2"></i>Quay lại
            </a>
        <?php endif; ?>
    </div>
    
    <div class="card-custom p-4">
        <form method="POST" enctype="multipart/form-data">
            
            <div class="mb-3">
                <label class="fw-bold mb-1">Truyện</label>
                <?php if($article): ?>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($article['Title']) ?>" disabled>
                    <input type="hidden" name="article_id" value="<?= $article_id ?>">
                <?php else: ?>
                    <select name="article_id" class="form-select" required>
                        <?php
                        $list = $pdo->query("SELECT ArticleID, Title FROM articles WHERE IsDeleted=0")->fetchAll();
                        foreach($list as $item) {
                            echo "<option value='{$item['ArticleID']}'>{$item['Title']}</option>";
                        }
                        ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1">Số thứ tự chương (Index)</label>
                    <input type="number" step="0.1" name="index" class="form-control" placeholder="VD: 1, 1.5, 10" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1">Tên chương (Tùy chọn)</label>
                    <input type="text" name="title" class="form-control" placeholder="VD: Sự khởi đầu...">
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold mb-1">Chọn ảnh nội dung truyện (Nhiều ảnh)</label>
                <input type="file" name="images[]" class="form-control" multiple required accept="image/*">
                <div class="form-text text-muted">
                    <i class="fas fa-info-circle me-1"></i>Giữ phím <strong>Ctrl</strong> (hoặc Shift) để chọn nhiều ảnh cùng lúc. Ảnh sẽ được upload lên Cloud và sắp xếp theo tên file.
                </div>
            </div>

            <hr>
            <button type="submit" class="btn btn-naver w-100 py-2 rounded-pill">
                <i class="fas fa-cloud-upload-alt me-2"></i> Upload & Lưu Chapter
            </button>
        </form>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>