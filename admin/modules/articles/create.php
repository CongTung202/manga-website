<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Chứa uploadImageToCloud và getImageUrl

// 1. Lấy dữ liệu cho Tagify (Thể loại & Tác giả)
$allGenres = $pdo->query("SELECT Name FROM genres ORDER BY Name ASC")->fetchAll(PDO::FETCH_COLUMN);
$jsonGenres = json_encode($allGenres);

$allAuthors = $pdo->query("SELECT Name FROM authors ORDER BY Name ASC")->fetchAll(PDO::FETCH_COLUMN);
$jsonAuthors = json_encode($allAuthors);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $description = $_POST['description'];
    $status = $_POST['status'];
    $catId = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))); 

    // --- XỬ LÝ ẢNH BÌA VỚI CLOUDINARY ---
    $coverImage = null;
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] == 0) {
        $coverImage = uploadImageToCloud($_FILES['cover'], 'covers');
    }

    // Insert Truyện
    $sql = "INSERT INTO articles (Title, Slug, Description, CoverImage, Status, CategoryID, CreatedAt, UpdatedAt) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute([$title, $slug, $description, $coverImage, $status, $catId])) {
        $newArticleId = $pdo->lastInsertId();

        // --- HÀM XỬ LÝ TAGS (Dùng chung cho Genre và Author) ---
        function processTags($pdo, $jsonInput, $table, $idCol, $linkTable, $articleId) {
            if (empty($jsonInput)) return;
            $tags = json_decode($jsonInput, true);
            if (!is_array($tags)) return;

            foreach ($tags as $tag) {
                $name = trim($tag['value']);
                if (empty($name)) continue;

                // 1. Kiểm tra tồn tại
                $stmtCheck = $pdo->prepare("SELECT $idCol FROM $table WHERE Name = ?");
                $stmtCheck->execute([$name]);
                $exists = $stmtCheck->fetch();

                if ($exists) {
                    $tagId = $exists[$idCol];
                } else {
                    // 2. Chưa có -> Thêm mới
                    $stmtNew = $pdo->prepare("INSERT INTO $table (Name) VALUES (?)");
                    $stmtNew->execute([$name]);
                    $tagId = $pdo->lastInsertId();
                }

                // 3. Link vào truyện
                try {
                    $pdo->prepare("INSERT INTO $linkTable (ArticleID, $idCol) VALUES (?, ?)")->execute([$articleId, $tagId]);
                } catch(PDOException $e) {}
            }
        }

        // Xử lý Thể loại
        processTags($pdo, $_POST['genres_input'], 'genres', 'GenreID', 'articles_genres', $newArticleId);
        
        // Xử lý Tác giả
        processTags($pdo, $_POST['authors_input'], 'authors', 'AuthorID', 'articles_authors', $newArticleId);
        
        header("Location: view.php?id=" . $newArticleId);
        exit;
    }
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Thêm Truyện Mới</h3>
        <a href="index.php" class="btn btn-secondary px-4 py-2 rounded-pill">
            <i class="fas fa-arrow-left me-2"></i>Quay lại
        </a>
    </div>

    <form method="POST" enctype="multipart/form-data" class="card-custom p-4">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="fw-bold mb-1">Tên truyện</label>
                    <input type="text" name="title" class="form-control" required placeholder="Nhập tên truyện...">
                </div>

                <div class="mb-3">
                    <label class="fw-bold mb-1">Tác giả</label>
                    <input name='authors_input' class='form-control' placeholder='Nhập tên tác giả...'>
                    <small class="text-muted">Nhập tên tác giả và nhấn Enter. Nếu chưa có, hệ thống sẽ tự tạo mới.</small>
                </div>

                <div class="mb-3">
                    <label class="fw-bold mb-1">Thể loại</label>
                    <input name='genres_input' class='form-control' placeholder='Gõ thể loại rồi bấm Enter...'>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold mb-1">Mô tả / Sơ lược</label>
                    <textarea name="description" class="form-control" rows="6"></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3">
                    <label class="fw-bold mb-1">Nguồn gốc / Chất liệu</label>
                    <?php $cats = $pdo->query("SELECT * FROM categories")->fetchAll(); ?>
                    <select name="category_id" class="form-select">
                        <option value="">-- Chọn phân loại --</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?= $c['CategoryID'] ?>"><?= $c['Name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="fw-bold mb-1">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="1">Đang tiến hành</option>
                        <option value="2">Hoàn thành</option>
                        <option value="0">Tạm ngưng</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="fw-bold mb-1">Ảnh bìa</label>
                    <input type="file" name="cover" class="form-control">
                </div>
                
                <hr>
                <button type="submit" class="btn btn-naver w-100 py-2 rounded-pill">
                    <i class="fas fa-save me-2"></i>Lưu truyện
                </button>
            </div>
        </div>
    </form>
</div>

<script>
    // Cấu hình Tagify cho Thể loại
    var inputGenre = document.querySelector('input[name=genres_input]');
    new Tagify(inputGenre, {
        whitelist: <?= $jsonGenres ?>,
        maxTags: 10,
        dropdown: { maxItems: 20, classname: "tags-look", enabled: 0, closeOnSelect: false }
    });

    // Cấu hình Tagify cho Tác giả
    var inputAuthor = document.querySelector('input[name=authors_input]');
    new Tagify(inputAuthor, {
        whitelist: <?= $jsonAuthors ?>,
        maxTags: 5,
        dropdown: { maxItems: 20, classname: "tags-look", enabled: 0, closeOnSelect: false }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>