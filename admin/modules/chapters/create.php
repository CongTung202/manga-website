<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Gọi file chứa uploadImageToCloud

// =========================================================
// 1. API: XỬ LÝ LẤY INDEX TIẾP THEO (AJAX GET)
// =========================================================
if (isset($_GET['ajax_get_index']) && isset($_GET['article_id'])) {
    header('Content-Type: application/json');
    $aid = $_GET['article_id'];
    $stmtIdx = $pdo->prepare("SELECT MAX(`Index`) FROM chapters WHERE ArticleID = ? AND IsDeleted = 0");
    $stmtIdx->execute([$aid]);
    $maxIdx = $stmtIdx->fetchColumn();
    
    // Nếu chưa có chap nào thì mặc định là 1, nếu có thì cộng 1
    echo json_encode(['next_index' => ($maxIdx !== null ? $maxIdx + 1 : 1)]);
    exit;
}

// =========================================================
// 2. API: XỬ LÝ UPLOAD BẰNG AJAX (POST)
// =========================================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    // BƯỚC 2.1: KHỞI TẠO CHAPTER TRƯỚC
    if ($_POST['ajax_action'] == 'create_chapter') {
        $articleIdPost = $_POST['article_id'];
        $title = trim($_POST['title']);
        $index = $_POST['index']; 

        $sqlChap = "INSERT INTO chapters (ArticleID, Title, `Index`, CreatedAt) VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sqlChap);
        
        if ($stmt->execute([$articleIdPost, $title, $index])) {
            echo json_encode(['success' => true, 'chapter_id' => $pdo->lastInsertId()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Lỗi lưu thông tin chương vào CSDL!']);
        }
        exit;
    }
    
    // BƯỚC 2.2: UPLOAD TỪNG ẢNH MỘT LÊN CLOUD
    if ($_POST['ajax_action'] == 'upload_image') {
        $chapterId = $_POST['chapter_id'];
        $sortOrder = $_POST['sort_order']; // Lấy số thứ tự ảnh
        
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file = $_FILES['image'];
            
            // Upload lên Cloudinary
            $uploadedPath = uploadImageToCloud($file, 'chapters');
            
            if ($uploadedPath) {
                // Lưu vào CSDL
                $stmtImg = $pdo->prepare("INSERT INTO chapter_images (ChapterID, ImageURL, SortOrder) VALUES (?, ?, ?)");
                if ($stmtImg->execute([$chapterId, $uploadedPath, $sortOrder])) {
                    echo json_encode(['success' => true]);
                    exit;
                }
            }
        }
        echo json_encode(['success' => false, 'message' => 'Upload ảnh thất bại']);
        exit;
    }
}

// =========================================================
// 3. GIAO DIỆN HIỂN THỊ
// =========================================================

// Lấy ID truyện từ URL để điền sẵn
$article_id = $_GET['article_id'] ?? '';
$article = null;
$nextIndex = 1;

if ($article_id) {
    // Lấy tên truyện
    $stmt = $pdo->prepare("SELECT Title FROM articles WHERE ArticleID = ?");
    $stmt->execute([$article_id]);
    $article = $stmt->fetch();
    
    // Tính sẵn Index tiếp theo
    $stmtIdx = $pdo->prepare("SELECT MAX(`Index`) FROM chapters WHERE ArticleID = ? AND IsDeleted = 0");
    $stmtIdx->execute([$article_id]);
    $maxIdx = $stmtIdx->fetchColumn();
    if ($maxIdx !== null) $nextIndex = $maxIdx + 1;
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-white">Thêm Chapter Mới</h3>
        <a href="<?= $article_id ? "../articles/view.php?id=$article_id" : "../articles/index.php" ?>" class="btn btn-secondary px-4 py-2 rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>
    
    <div class="card shadow-sm border-0 p-4" style="background-color: var(--bg-card); border-radius: 8px;">
        <form id="form-create">
            
            <div class="mb-3">
                <label class="fw-bold mb-1 text-white">Truyện</label>
                <?php if($article): ?>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($article['Title']) ?>" disabled>
                    <input type="hidden" name="article_id" id="article_id" value="<?= $article_id ?>">
                <?php else: ?>
                    <select name="article_id" id="article-select" class="form-select" required>
                        <option value="">-- Chọn truyện --</option>
                        <?php
                        $list = $pdo->query("SELECT ArticleID, Title FROM articles WHERE IsDeleted=0 ORDER BY Title ASC")->fetchAll();
                        foreach($list as $item) {
                            echo "<option value='{$item['ArticleID']}'>{$item['Title']}</option>";
                        }
                        ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1 text-white">Số thứ tự chương (Index)</label>
                    <input type="number" step="0.1" name="index" id="index-input" class="form-control fw-bold text-danger" value="<?= $nextIndex ?>" required>
                    <small class="text-muted"><i class="fas fa-magic"></i> Đã tự động điền chương tiếp theo</small>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1 text-white">Tên chương (Tùy chọn)</label>
                    <input type="text" name="title" class="form-control" placeholder="VD: Sự khởi đầu...">
                </div>
            </div>

            <div class="mb-3">
                <label class="fw-bold mb-1 text-white">Chọn ảnh nội dung truyện (Nhiều ảnh)</label>
                <input type="file" id="images" class="form-control" multiple required accept="image/*">
                <div class="form-text text-info mt-2">
                    <i class="fas fa-info-circle me-1"></i>Hệ thống sẽ tải lên <strong>lần lượt từng ảnh</strong> để chống lỗi Server Timeout. Bạn có thể chọn hàng trăm ảnh cùng lúc.
                </div>
            </div>

            <div id="progress-container" class="mt-4 mb-3" style="display: none; background-color: var(--bg-body); padding: 15px; border-radius: 6px; border: 1px solid var(--border-color);">
                <label class="fw-bold mb-2 text-warning" id="upload-status"><i class="fas fa-spinner fa-spin me-2"></i>Đang chuẩn bị...</label>
                <div class="progress" style="height: 25px; background-color: #333;">
                    <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success fw-bold" role="progressbar" style="width: 0%; font-size: 14px;">0%</div>
                </div>
            </div>

            <hr class="border-secondary">
            <button type="submit" id="btn-submit" class="btn btn-primary w-100 py-3 rounded text-uppercase fw-bold">
                <i class="fas fa-cloud-upload-alt me-2"></i> Bắt đầu Upload Chapter
            </button>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // --- 1. XỬ LÝ TỰ ĐỘNG ĐIỀN INDEX KHI CHỌN TRUYỆN Ở DROPDOWN ---
    const articleSelect = document.getElementById('article-select');
    const indexInput = document.getElementById('index-input');
    
    if (articleSelect) {
        articleSelect.addEventListener('change', async function() {
            const articleId = this.value;
            if (!articleId) {
                indexInput.value = ''; return;
            }
            try {
                const res = await fetch(`create.php?ajax_get_index=1&article_id=${articleId}`);
                const data = await res.json();
                indexInput.value = data.next_index;
            } catch(e) { console.error(e); }
        });
    }

    // --- 2. XỬ LÝ UPLOAD BẰNG AJAX (1 BY 1) ---
    document.getElementById('form-create').addEventListener('submit', async function(e) {
        e.preventDefault(); // Chặn hành vi submit load lại trang
        
        const files = document.getElementById('images').files;
        if (files.length === 0) {
            alert('Vui lòng chọn ít nhất 1 ảnh!'); return;
        }
        
        const submitBtn = document.getElementById('btn-submit');
        const progressContainer = document.getElementById('progress-container');
        const progressBar = document.getElementById('progress-bar');
        const statusText = document.getElementById('upload-status');
        
        // Khóa nút để tránh bấm 2 lần
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin me-2"></i> Đang xử lý...';
        progressContainer.style.display = 'block';
        
        try {
            // BƯỚC A: TẠO CHAPTER
            statusText.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Đang khởi tạo thông tin Chapter...';
            
            const formData = new FormData(this); // Lấy data từ form (title, index, article_id)
            formData.append('ajax_action', 'create_chapter');
            
            // Xử lý lấy article_id đúng cách (nếu có input hidden)
            if(!formData.get('article_id')) {
                 formData.append('article_id', document.getElementById('article_id').value);
            }
            
            const resChap = await fetch('create.php', { method: 'POST', body: formData });
            const dataChap = await resChap.json();
            
            if (!dataChap.success) throw new Error(dataChap.message);
            
            const chapterId = dataChap.chapter_id;
            
            // BƯỚC B: UPLOAD TỪNG ẢNH MỘT
            let successCount = 0;
            
            for (let i = 0; i < files.length; i++) {
                statusText.innerHTML = `<i class="fas fa-cloud-upload-alt me-2"></i>Đang tải lên ảnh <strong>${i + 1}</strong> / ${files.length} ...`;
                
                const imgData = new FormData();
                imgData.append('ajax_action', 'upload_image');
                imgData.append('chapter_id', chapterId);
                imgData.append('sort_order', i); // Lưu đúng thứ tự bạn đã chọn trên máy tính
                imgData.append('image', files[i]);
                
                try {
                    const imgRes = await fetch('create.php', { method: 'POST', body: imgData });
                    const imgJson = await imgRes.json();
                    if(imgJson.success) successCount++;
                } catch (errImg) {
                    console.error("Lỗi upload ảnh số " + (i+1), errImg);
                }
                
                // Cập nhật thanh tiến trình %
                let percent = Math.round(((i + 1) / files.length) * 100);
                progressBar.style.width = percent + '%';
                progressBar.innerHTML = percent + '%';
            }
            
            // BƯỚC C: HOÀN TẤT & CHUYỂN HƯỚNG
            if(successCount === files.length) {
                statusText.innerHTML = `<span class='text-success fw-bold'><i class='fas fa-check-circle me-1'></i>Hoàn tất tải lên ${successCount} ảnh! Đang chuyển hướng...</span>`;
            } else {
                statusText.innerHTML = `<span class='text-warning fw-bold'><i class='fas fa-exclamation-triangle me-1'></i>Tải xong, nhưng có ${files.length - successCount} ảnh bị lỗi. Đang chuyển hướng...</span>`;
            }
            
            setTimeout(() => {
                window.location.href = '../articles/view.php?id=' + formData.get('article_id');
            }, 1500);
            
        } catch (error) {
            alert("Có lỗi xảy ra: " + error.message);
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-cloud-upload-alt me-2"></i> Thử lại';
            statusText.innerHTML = "<span class='text-danger'>Thất bại!</span>";
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?>