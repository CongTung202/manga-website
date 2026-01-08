<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Lỗi ID");

// 1. Lấy thông tin truyện
$stmt = $pdo->prepare("SELECT * FROM articles WHERE ArticleID = ?");
$stmt->execute([$id]); 
$article = $stmt->fetch();
if (!$article) die("Truyện không tồn tại.");

// 2. Lấy Thể loại hiện tại (String để fill vào input)
$stmtG = $pdo->prepare("SELECT g.Name FROM genres g JOIN articles_genres ag ON g.GenreID = ag.GenreID WHERE ag.ArticleID = ?");
$stmtG->execute([$id]);
$currentGenres = $stmtG->fetchAll(PDO::FETCH_COLUMN);
$genresString = implode(',', $currentGenres);

// 3. Lấy Tác giả hiện tại (String để fill vào input)
$stmtA = $pdo->prepare("SELECT a.Name FROM authors a JOIN articles_authors aa ON a.AuthorID = aa.AuthorID WHERE aa.ArticleID = ?");
$stmtA->execute([$id]);
$currentAuthors = $stmtA->fetchAll(PDO::FETCH_COLUMN);
$authorsString = implode(',', $currentAuthors);

// 4. Lấy Whitelist cho Tagify
$allGenres = $pdo->query("SELECT Name FROM genres ORDER BY Name ASC")->fetchAll(PDO::FETCH_COLUMN);
$jsonGenres = json_encode($allGenres);

$allAuthors = $pdo->query("SELECT Name FROM authors ORDER BY Name ASC")->fetchAll(PDO::FETCH_COLUMN);
$jsonAuthors = json_encode($allAuthors);

// --- XỬ LÝ SUBMIT FORM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $status = $_POST['status'];
    $catId = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
    
    // Upload ảnh mới (nếu có)
    if (isset($_FILES['cover']) && $_FILES['cover']['error'] == 0) {
        $uploadedUrl = uploadImageToCloud($_FILES['cover'], 'covers');
        if ($uploadedUrl) {
            // Cập nhật cả ảnh
            $sql = "UPDATE articles SET Title=?, Description=?, Status=?, CategoryID=?, CoverImage=?, UpdatedAt=NOW() WHERE ArticleID=?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$title, $description, $status, $catId, $uploadedUrl, $id]);
        }
    } else {
        // Giữ nguyên ảnh cũ
        $sql = "UPDATE articles SET Title=?, Description=?, Status=?, CategoryID=?, UpdatedAt=NOW() WHERE ArticleID=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $description, $status, $catId, $id]);
    }

    // --- XỬ LÝ TAGS (Genre & Author) ---
    function updateTags($pdo, $jsonInput, $table, $idCol, $linkTable, $articleId) {
        // Xóa liên kết cũ
        $pdo->prepare("DELETE FROM $linkTable WHERE ArticleID = ?")->execute([$articleId]);
        
        if (empty($jsonInput)) return;
        $tags = json_decode($jsonInput, true);
        if (!is_array($tags)) return; // Fallback nếu input text thường

        foreach ($tags as $tag) {
            $name = trim($tag['value']);
            if (empty($name)) continue;
            
            // Check hoặc Tạo mới
            $stmtCheck = $pdo->prepare("SELECT $idCol FROM $table WHERE Name = ?");
            $stmtCheck->execute([$name]);
            $exists = $stmtCheck->fetch();

            if ($exists) {
                $tagId = $exists[$idCol];
            } else {
                $pdo->prepare("INSERT INTO $table (Name) VALUES (?)")->execute([$name]);
                $tagId = $pdo->lastInsertId();
            }
            // Link
            try {
                $pdo->prepare("INSERT INTO $linkTable (ArticleID, $idCol) VALUES (?, ?)")->execute([$articleId, $tagId]);
            } catch(PDOException $e) {}
        }
    }

    updateTags($pdo, $_POST['genres_input'], 'genres', 'GenreID', 'articles_genres', $id);
    updateTags($pdo, $_POST['authors_input'], 'authors', 'AuthorID', 'articles_authors', $id);

    header("Location: view.php?id=$id");
    exit;
}

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="container-fluid p-4">
    <h3 class="mb-4">Sửa Truyện: <?= htmlspecialchars($article['Title']) ?></h3>
    
    <form method="POST" enctype="multipart/form-data" class="card-custom p-4">
        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="fw-bold">Tên truyện</label>
                    <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($article['Title']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Tác giả</label>
                    <input name='authors_input' class='form-control' value='<?= htmlspecialchars($authorsString) ?>'>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Thể loại</label>
                    <input name='genres_input' class='form-control' value='<?= htmlspecialchars($genresString) ?>'>
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Mô tả</label>
                    <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($article['Description']) ?></textarea>
                </div>
            </div>

            <div class="col-md-4">
                <div class="mb-3 text-center">
                    <label class="fw-bold d-block mb-2">Ảnh bìa hiện tại</label>
                    <img src="<?= getImageUrl($article['CoverImage']) ?>" class="img-fluid rounded border border-secondary" style="max-height: 200px;">
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold">Thay ảnh bìa (Nếu cần)</label>
                    <input type="file" name="cover" class="form-control">
                </div>

                <div class="mb-3">
                    <label class="fw-bold">Trạng thái</label>
                    <select name="status" class="form-select">
                        <option value="1" <?= $article['Status']==1?'selected':'' ?>>Đang tiến hành</option>
                        <option value="2" <?= $article['Status']==2?'selected':'' ?>>Hoàn thành</option>
                        <option value="0" <?= $article['Status']==0?'selected':'' ?>>Tạm ngưng</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold">Nguồn gốc / Chất liệu</label>
                    <?php $cats = $pdo->query("SELECT * FROM categories")->fetchAll(); ?>
                    <select name="category_id" class="form-select">
                        <option value="">-- Chọn phân loại --</option>
                        <?php foreach($cats as $c): ?>
                            <option value="<?= $c['CategoryID'] ?>" <?= ($article['CategoryID'] == $c['CategoryID']) ? 'selected' : '' ?>>
                                <?= $c['Name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <hr>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-naver w-100"><i class="fas fa-save me-2"></i>Lưu thay đổi</button>
                    <a href="view.php?id=<?= $id ?>" class="btn btn-secondary">Hủy</a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Khởi tạo Tagify
    new Tagify(document.querySelector('input[name=genres_input]'), {
        whitelist: <?= $jsonGenres ?>,
        maxTags: 10,
        dropdown: { maxItems: 20, classname: "tags-look", enabled: 0, closeOnSelect: false }
    });

    new Tagify(document.querySelector('input[name=authors_input]'), {
        whitelist: <?= $jsonAuthors ?>,
        maxTags: 5,
        dropdown: { maxItems: 20, classname: "tags-look", enabled: 0, closeOnSelect: false }
    });
</script>

<?php require_once '../../includes/footer.php'; ?>