<?php
require_once 'includes/db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) { 
    header("Location: login.php"); 
    exit; 
}

$userId = $_SESSION['user_id'];

// Lấy danh sách truyện đã lưu
$sql = "SELECT a.* FROM bookmarks b 
        JOIN articles a ON b.ArticleID = a.ArticleID 
        WHERE b.UserID = ? 
        ORDER BY b.CreatedAt DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([$userId]);
$savedMangas = $stmt->fetchAll();

$pageTitle = "Tủ truyện";
require_once 'includes/header.php';
?>

<div class="main-container">
    
    <main class="content">
        <section class="section">
            <div class="section__header">
                <h3>Truyện đang theo dõi <span style="font-weight: normal; font-size: 14px; color: var(--text-muted);">(<?= count($savedMangas) ?>)</span></h3>
            </div>

            <?php if (count($savedMangas) > 0): ?>
                <div class="card-list">
                    <?php foreach($savedMangas as $art): ?>
                    <article class="card" onclick="window.location.href='<?= BASE_URL ?>truyen/<?= $art['ArticleID'] ?>'">
                        <div class="card__thumb">
                            <?php if($art['CoverImage']): ?>
                                <img src="<?= getImageUrl($art['CoverImage']) ?>" alt="Thumb">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#333; display:flex; align-items:center; justify-content:center; color:#777; font-size:10px;">NO IMG</div>
                            <?php endif; ?>
                            
                            <a href="<?= BASE_URL ?>includes/action_bookmark.php?id=<?= $art['ArticleID'] ?>" 
                               onclick="event.stopPropagation(); return confirm('Bạn muốn bỏ theo dõi truyện này?')"
                               style="position:absolute; top:5px; right:5px; background:rgba(0,0,0,0.6); color:#fff; width:28px; height:28px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:0.2s; border:1px solid rgba(255,255,255,0.3);"
                               onmouseover="this.style.backgroundColor='#ff4d4d'; this.style.borderColor='#ff4d4d';"
                               onmouseout="this.style.backgroundColor='rgba(0,0,0,0.6)'; this.style.borderColor='rgba(255,255,255,0.3)';">
                               <i class="fas fa-times" style="font-size: 14px;"></i>
                            </a>
                        </div>
                        <h4 class="card__title"><?= htmlspecialchars($art['Title']) ?></h4>
                        <p class="card__author">
                            <i class="fas fa-eye me-1"></i> <?= number_format($art['ViewCount']) ?>
                        </p>
                    </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; color: var(--text-muted); background: var(--bg-element); border-radius: 8px; border: 1px solid var(--border-color);">
                    <i class="far fa-folder-open" style="font-size: 40px; margin-bottom: 15px; opacity: 0.5;"></i>
                    <p class="mb-3">Bạn chưa theo dõi truyện nào.</p>
                    <a href="<?= BASE_URL ?>" style="color: var(--primary-theme); font-weight: bold; text-decoration: none;">
                        <i class="fas fa-search me-1"></i> Khám phá truyện ngay
                    </a>
                </div>
            <?php endif; ?>
        </section>
    </main>

    <aside class="sidebar">
        <?php include 'includes/right_sidebar.php'; ?>
    </aside>

</div> <?php require_once 'includes/footer.php'; ?>