<?php
// includes/comment_section.php

// 1. Kiểm tra biến đầu vào bắt buộc (ArticleID)
if (!isset($id)) return;

if (!isset($currentUrl)) {
    $currentUrl = "../detail.php?id=$id#comments";
}

// 2. Xác định ngữ cảnh: Đang ở trang Chapter (read.php) hay Trang chủ truyện (detail.php)
// Biến $chapterId được lấy từ file cha (read.php). Nếu ở detail.php thì nó sẽ null.
$filterChapterId = isset($chapterId) ? $chapterId : null;

// --- HÀM HỖ TRỢ HIỂN THỊ AVATAR ---
if (!function_exists('getAvatarLink')) {
    function getAvatarLink($path, $username) {
        if (!empty($path)) {
            if (strpos($path, 'http') === 0) return $path;
            return BASE_URL . $path;
        }
        return 'https://ui-avatars.com/api/?name=' . urlencode($username) . '&background=random&color=fff&size=128';
    }
}
?>

<style>
    /* CSS Comment Section */
    .comment-box { margin-top: 20px; background-color: var(--bg-element); padding: 20px; border-radius: 4px; border: 1px solid var(--border-color); }
    .comment-header { font-weight: bold; font-size: 18px; color: var(--text-main); margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; }
    .cmt-form-wrapper { background-color: var(--bg-body); padding: 15px; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 30px; }
    .cmt-input { width: 100%; background: transparent; border: none; resize: none; color: var(--text-main); font-size: 14px; outline: none; min-height: 60px; }
    .cmt-submit-row { display: flex; justify-content: space-between; align-items: center; margin-top: 10px; border-top: 1px solid var(--border-color); padding-top: 10px; }
    .btn-cmt-post { background-color: var(--primary-theme); color: #fff; border: none; padding: 6px 20px; font-size: 13px; font-weight: bold; border-radius: 3px; }
    
    .cmt-list { list-style: none; padding: 0; margin: 0; }
    .cmt-item { display: flex; padding: 15px 0; border-bottom: 1px solid var(--border-color); }
    .cmt-avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 1px solid var(--border-color); margin-right: 15px; flex-shrink: 0; }
    .cmt-body { width: 100%; }
    .cmt-meta { margin-bottom: 5px; }
    .cmt-username { font-weight: bold; color: var(--text-main); margin-right: 8px; font-size: 14px; }
    .cmt-time { color: var(--text-muted); font-size: 11px; }
    .cmt-content { font-size: 14px; color: #ccc; line-height: 1.5; }
    
    .badge-admin { background: #ff4d4d; color: #fff; font-size: 9px; padding: 1px 4px; border-radius: 2px; margin-left: 5px; vertical-align: middle; }
    .badge-chap { background: #333; color: #aaa; border: 1px solid #555; font-size: 10px; padding: 1px 5px; border-radius: 3px; margin-left: 8px; }
    .current-user-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; margin-right: 8px; }
</style>

<div class="comment-box" id="comments">
    
    <?php if (isset($_SESSION['user_id'])): ?>
        <div class="cmt-form-wrapper">
            <form action="includes/post_comment.php" method="POST">
                <input type="hidden" name="article_id" value="<?= $id ?>">
                <input type="hidden" name="redirect_url" value="<?= $currentUrl ?>">
                
                <?php if ($filterChapterId): ?>
                    <input type="hidden" name="chapter_id" value="<?= $filterChapterId ?>">
                <?php endif; ?>
                
                <textarea name="content" class="cmt-input" placeholder="<?= $filterChapterId ? 'Bình luận về Chapter này...' : 'Bình luận về bộ truyện này...' ?>" required></textarea>
                
                <div class="cmt-submit-row">
                    <div class="d-flex align-items-center">
                        <?php 
                            $myAvatarPath = $_SESSION['avatar'] ?? '';
                            $myAvatarUrl = getAvatarLink($myAvatarPath, $_SESSION['username']);
                        ?>
                        <img src="<?= $myAvatarUrl ?>" class="current-user-avatar">
                        <span style="color: var(--text-main); font-weight: bold; font-size: 13px;"><?= htmlspecialchars($_SESSION['username']) ?></span>
                    </div>
                    <button type="submit" class="btn-cmt-post">Đăng</button>
                </div>
            </form>
        </div>
    <?php else: ?>
        <div style="background-color: var(--bg-body); padding: 20px; text-align: center; border-radius: 4px; border: 1px solid var(--border-color); margin-bottom: 30px;">
            <span style="color: var(--text-muted);">Vui lòng</span> 
            <a href="login.php" style="color: var(--primary-theme); font-weight: bold;">Đăng nhập</a> 
            <span style="color: var(--text-muted);">để tham gia bình luận.</span>
        </div>
    <?php endif; ?>

    <?php
    $comments = [];
    if ($filterChapterId) {
        // [TRƯỜNG HỢP 1] Đang ở read.php -> Chỉ lấy bình luận của Chapter này
        $sqlCmt = "SELECT c.*, u.UserName, u.Avatar, u.Role 
                   FROM comments c 
                   JOIN users u ON c.UserID = u.UserID 
                   WHERE c.ChapterID = ? AND c.IsDeleted = 0 
                   ORDER BY c.CreatedAt DESC";
        $stmtCmt = $pdo->prepare($sqlCmt);
        $stmtCmt->execute([$filterChapterId]);
        $comments = $stmtCmt->fetchAll();
        $cmtTitle = "Bình luận Chapter";
    } else {
        // [TRƯỜNG HỢP 2] Đang ở detail.php -> Lấy TẤT CẢ bình luận của truyện (JOIN thêm bảng chapters để lấy tên chap nếu có)
        $sqlCmt = "SELECT c.*, u.UserName, u.Avatar, u.Role, ch.Index as ChapterIndex 
                   FROM comments c 
                   JOIN users u ON c.UserID = u.UserID 
                   LEFT JOIN chapters ch ON c.ChapterID = ch.ChapterID 
                   WHERE c.ArticleID = ? AND c.IsDeleted = 0 
                   ORDER BY c.CreatedAt DESC";
        $stmtCmt = $pdo->prepare($sqlCmt);
        $stmtCmt->execute([$id]);
        $comments = $stmtCmt->fetchAll();
        $cmtTitle = "Bình luận truyện";
    }
    ?>

    <div class="comment-header">
        <?= $cmtTitle ?> <span style="font-size: 14px; color: var(--text-muted); font-weight: normal;">(<?= count($comments) ?>)</span>
    </div>

    <ul class="cmt-list">
        <?php if (count($comments) > 0): ?>
            <?php foreach ($comments as $cmt): ?>
                <?php 
                    $cmtUserAvatar = getAvatarLink($cmt['Avatar'], $cmt['UserName']);
                ?>
                <li class="cmt-item">
                    <img src="<?= $cmtUserAvatar ?>" class="cmt-avatar" alt="Avatar">
                    
                    <div class="cmt-body">
                        <div class="cmt-meta">
                            <span class="cmt-username">
                                <?= htmlspecialchars($cmt['UserName']) ?>
                                <?php if($cmt['Role'] == 1): ?><span class="badge-admin">ADMIN</span><?php endif; ?>
                                
                                <?php if (!$filterChapterId && !empty($cmt['ChapterIndex'])): ?>
                                    <span class="badge-chap" onclick="window.location.href='read.php?id=<?= $id ?>&chap=<?= $cmt['ChapterID'] ?>#comments'" style="cursor:pointer;">
                                        Chap <?= $cmt['ChapterIndex'] ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                            <span class="cmt-time"><?= date('d/m/Y H:i', strtotime($cmt['CreatedAt'])) ?></span>
                        </div>
                        <div class="cmt-content">
                            <?= nl2br(htmlspecialchars($cmt['Content'])) ?>
                        </div>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php else: ?>
            <li style="text-align: center; padding: 30px; color: var(--text-muted);">
                Chưa có bình luận nào. Hãy là người đầu tiên!
            </li>
        <?php endif; ?>
    </ul>
</div>