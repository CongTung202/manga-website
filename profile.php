<?php
// profile.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];
$message = '';

// --- XỬ LÝ UPLOAD AVATAR ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['avatar_file'])) {
    // Gọi hàm upload Cloudinary
    $avatarUrl = uploadImageToCloud($_FILES['avatar_file'], 'avatars');

    if ($avatarUrl) {
        // Cập nhật Database với link mới
        $stmt = $pdo->prepare("UPDATE users SET Avatar = ? WHERE UserID = ?");
        $stmt->execute([$avatarUrl, $userId]);
        
        // Cập nhật Session
        $_SESSION['avatar'] = $avatarUrl;
        $message = "Đã cập nhật ảnh đại diện lên Cloud!";
    } else {
        $message = "Lỗi khi upload ảnh.";
    }
}

// Xử lý XÓA bình luận
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $commentId = $_GET['id'];
    $stmtCheck = $pdo->prepare("SELECT CommentID FROM comments WHERE CommentID = ? AND UserID = ?");
    $stmtCheck->execute([$commentId, $userId]);
    
    if ($stmtCheck->rowCount() > 0) {
        $pdo->prepare("UPDATE comments SET IsDeleted = 1 WHERE CommentID = ?")->execute([$commentId]);
        $message = "Đã xóa bình luận.";
    } else {
        $message = "Bạn không có quyền xóa.";
    }
}

// Lấy thông tin User
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Lấy lịch sử bình luận
$sqlCmt = "SELECT c.*, a.Title AS ArticleTitle, a.ArticleID, a.Slug 
           FROM comments c 
           JOIN articles a ON c.ArticleID = a.ArticleID 
           WHERE c.UserID = ? AND c.IsDeleted = 0 
           ORDER BY c.CreatedAt DESC";
$stmtCmt = $pdo->prepare($sqlCmt);
$stmtCmt->execute([$userId]);
$myComments = $stmtCmt->fetchAll();

$pageTitle = "Hồ sơ cá nhân";
require_once 'includes/header.php';
?>

<style>
    /* CSS Riêng cho trang Profile (Dark Mode) */
    .profile-container { display: flex; gap: 30px; margin-top: 30px; align-items: flex-start; }
    
    /* Box Thông tin User */
    .profile-box { 
        flex: 1; 
        max-width: 320px;
        background: var(--bg-element); 
        padding: 30px 20px; 
        border-radius: 4px; 
        text-align: center; 
        border: 1px solid var(--border-color); 
        position: sticky; top: 20px;
    }
    
    .avatar-wrapper { position: relative; display: inline-block; margin-bottom: 15px; }
    .avatar-img { 
        width: 120px; height: 120px; 
        border-radius: 50%; 
        object-fit: cover; 
        border: 3px solid var(--bg-body); 
        box-shadow: 0 0 0 1px var(--border-color);
    }
    
    .btn-upload-cam {
        position: absolute; bottom: 5px; right: 5px;
        width: 32px; height: 32px; border-radius: 50%;
        background: var(--bg-body); border: 1px solid var(--border-color);
        color: var(--text-main); display: flex; align-items: center; justify-content: center;
        cursor: pointer; transition: 0.2s;
    }
    .btn-upload-cam:hover { background: var(--primary-theme); color: #fff; border-color: var(--primary-theme); }

    .user-name { color: var(--text-main); font-weight: bold; font-size: 20px; margin-bottom: 5px; }
    .user-email { color: var(--text-muted); font-size: 13px; }
    .user-role { 
        display: inline-block; margin-top: 10px; padding: 3px 10px; 
        background: var(--primary-theme); color: #fff; border-radius: 12px; font-size: 11px; font-weight: bold;
    }

    /* Box Hoạt động */
    .activity-box { 
        flex: 2; 
        background: var(--bg-element); 
        border-radius: 4px; 
        border: 1px solid var(--border-color); 
        overflow: hidden;
    }
    .activity-header { 
        padding: 15px 20px; 
        border-bottom: 1px solid var(--border-color); 
        font-weight: bold; color: var(--text-main); 
        background: rgba(0,0,0,0.1);
    }
    
    .activity-list { list-style: none; padding: 0; margin: 0; }
    .activity-item { 
        padding: 15px 20px; 
        border-bottom: 1px solid var(--border-color); 
        display: flex; justify-content: space-between; 
        transition: 0.2s;
    }
    .activity-item:last-child { border-bottom: none; }
    .activity-item:hover { background: var(--bg-hover); }

    .act-link { color: var(--primary-theme); font-weight: bold; font-size: 14px; text-decoration: none; }
    .act-link:hover { text-decoration: underline; }
    
    .act-content { color: #ccc; font-size: 13px; margin-top: 5px; line-height: 1.4; }
    .act-time { color: var(--text-muted); font-size: 11px; margin-top: 5px; }

    .btn-del-cmt { color: #ff4d4d; font-size: 12px; opacity: 0.7; }
    .btn-del-cmt:hover { opacity: 1; text-decoration: underline; }

    /* Modal Dark Mode Overrides */
    .modal-content { background-color: var(--bg-element); color: var(--text-main); border: 1px solid var(--border-color); }
    .modal-header { border-bottom-color: var(--border-color); }
    .modal-footer { border-top-color: var(--border-color); }
    .btn-close { filter: invert(1) grayscale(100%) brightness(200%); } /* Làm nút đóng màu trắng */
    .form-control { background-color: var(--bg-body); border-color: var(--border-color); color: var(--text-main); }
    .form-control:focus { background-color: var(--bg-body); color: var(--text-main); border-color: var(--primary-theme); box-shadow: none; }
    .form-text { color: var(--text-muted); }
    
    /* Responsive */
    @media (max-width: 768px) {
        .profile-container { flex-direction: column; }
        .profile-box { max-width: 100%; width: 100%; position: static; }
    }
</style>

<div class="main-container">
    <div class="profile-container">
        
        <div class="profile-box">
    <div class="avatar-wrapper">
        <?php 
            // Logic hiển thị ảnh (giữ nguyên)
            $userAvatar = !empty($user['Avatar']) ? BASE_URL . $user['Avatar'] : 'https://ui-avatars.com/api/?name=' . urlencode($user['UserName']) . '&background=random&color=fff&size=200';
        ?>
        <img src="<?= getImageUrl($_SESSION['avatar']) ?>" class="avatar-img">
        
        <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display: none;">
            <input type="file" name="avatar_file" id="avatarInput" accept="image/*">
        </form>

        <button class="btn-upload-cam" type="button" onclick="document.getElementById('avatarInput').click();">
            <i class="fas fa-camera"></i>
        </button>
    </div>

    <h4 class="user-name"><?= htmlspecialchars($user['UserName']) ?></h4>
    <p class="user-email"><?= htmlspecialchars($user['Email']) ?></p>
    
    <span class="user-role"><?= $user['Role'] == 1 ? 'ADMIN' : 'MEMBER' ?></span>
    
    <?php if($message): ?>
        <div class="alert alert-info small py-2 mt-3 mb-0" style="background: rgba(80, 104, 145, 0.2); border: 1px solid var(--primary-theme); color: var(--text-main);">
            <?= $message ?>
        </div>
    <?php endif; ?>
    </div>

        <div class="activity-box">
            <div class="activity-header">
                Hoạt động gần đây
            </div>
            
            <div class="activity-list">
                <?php if (count($myComments) > 0): ?>
                    <?php foreach ($myComments as $cmt): ?>
                        <div class="activity-item">
                            <div style="flex: 1; padding-right: 15px;">
                                <div style="margin-bottom: 4px;">
                                    <span style="font-size: 12px; color: var(--text-muted);">Bình luận tại: </span>
                                    <a href="detail.php?id=<?= $cmt['ArticleID'] ?>" class="act-link">
                                        <?= htmlspecialchars($cmt['ArticleTitle']) ?>
                                    </a>
                                </div>
                                <div class="act-content"><?= nl2br(htmlspecialchars($cmt['Content'])) ?></div>
                                <div class="act-time"><i class="far fa-clock me-1"></i> <?= date('d/m/Y H:i', strtotime($cmt['CreatedAt'])) ?></div>
                            </div>
                            
                            <div>
                                <a href="profile.php?action=delete&id=<?= $cmt['CommentID'] ?>" class="btn-del-cmt" onclick="return confirm('Bạn chắc chắn muốn xóa bình luận này?')">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="padding: 40px; text-align: center; color: var(--text-muted);">
                        <i class="far fa-comment-dots" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                        Chưa có bình luận nào.
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
<script>
    // Lắng nghe sự kiện khi người dùng chọn file
    document.getElementById('avatarInput').addEventListener('change', function() {
        if (this.files && this.files[0]) {
            // Tự động submit form khi đã có file
            document.getElementById('avatarForm').submit();
        }
    });
</script>
<?php require_once 'includes/footer.php'; ?>