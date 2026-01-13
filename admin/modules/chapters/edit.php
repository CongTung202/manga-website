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

// XỬ LÝ CẬP NHẬT VỊ TRÍ ẢNH (AJAX)
if (isset($_GET['action']) && $_GET['action'] == 'update_sort' && isset($_POST['image_id']) && isset($_POST['sort_order'])) {
    header('Content-Type: application/json');
    $imgId = $_POST['image_id'];
    $sortOrder = $_POST['sort_order'];
    
    $stmt = $pdo->prepare("UPDATE chapter_images SET SortOrder = ? WHERE ImageID = ?");
    if ($stmt->execute([$sortOrder, $imgId])) {
        echo json_encode(['success' => true, 'message' => 'Cập nhật vị trí thành công']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật vị trí']);
    }
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
                <label class="fw-bold mb-2">Ảnh hiện tại của chương (Thay đổi vị trí):</label>
                <div class="table-responsive border border-secondary rounded" style="max-height: 500px; overflow-y: auto; background-color: var(--bg-body);">
                    <table class="table table-hover m-0">
                        <thead class="sticky-top" style="background-color: var(--bg-secondary);">
                            <tr>
                                <th style="width: 80px;">Ảnh</th>
                                <th style="width: 120px;">Vị trí (Order)</th>
                                <th style="width: 100px;">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($images as $img): ?>
                                <tr>
                                    <td>
                                        <img src="<?= getImageUrl($img['ImageURL']) ?>" style="width: 60px; height: 80px; object-fit: cover;" class="rounded border">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control sort-input" 
                                               data-img-id="<?= $img['ImageID'] ?>" 
                                               value="<?= $img['SortOrder'] ?>" 
                                               min="0" step="1">
                                        <small class="text-muted d-block mt-1">ID: <?= $img['ImageID'] ?></small>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-primary update-order-btn" 
                                                data-img-id="<?= $img['ImageID'] ?>">
                                            <i class="fas fa-sync-alt"></i> Cập nhật
                                        </button>
                                        <a href="edit.php?id=<?= $id ?>&article_id=<?= $articleId ?>&action=delete_img&img_id=<?= $img['ImageID'] ?>" 
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Xóa ảnh này?')">
                                           <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if(count($images) == 0): ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Chưa có ảnh nào.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <small class="d-block text-muted mt-2">
                    <i class="fas fa-info-circle"></i> Nhập số thứ tự mong muốn rồi nhấn "Cập nhật" để thay đổi vị trí ảnh
                </small>
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý nút cập nhật vị trí
    const updateBtns = document.querySelectorAll('.update-order-btn');
    
    updateBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const imgId = this.getAttribute('data-img-id');
            const input = document.querySelector(`input.sort-input[data-img-id="${imgId}"]`);
            const sortOrder = input.value;
            
            // Xác nhận trước khi cập nhật
            if (!confirm(`Bạn muốn đặt ảnh này ở vị trí ${sortOrder}?`)) {
                return;
            }
            
            // Gửi AJAX request
            fetch(`edit.php?id=<?= $id ?>&article_id=<?= $articleId ?>&action=update_sort`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `image_id=${imgId}&sort_order=${sortOrder}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✓ Cập nhật vị trí thành công!');
                    // Làm mới trang sau 1 giây
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('✗ ' + data.message);
                }
            })
            .catch(error => {
                console.error('Lỗi:', error);
                alert('✗ Có lỗi xảy ra. Vui lòng thử lại.');
            });
        });
    });
    
    // Cho phép nhấn Enter để cập nhật
    const inputs = document.querySelectorAll('.sort-input');
    inputs.forEach(input => {
        input.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const btn = this.closest('tr').querySelector('.update-order-btn');
                btn.click();
            }
        });
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>