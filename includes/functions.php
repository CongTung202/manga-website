<?php
// includes/functions.php
require_once 'cloudinary.php'; // Gọi cấu hình ở bước 3
use Cloudinary\Api\Upload\UploadApi;

function uploadImageToCloud($file, $folderName = 'manga_uploads') {
    try {
        // Kiểm tra lỗi file cơ bản
        if ($file['error'] !== 0) return null;

        // Upload lên Cloudinary
        $upload = (new UploadApi())->upload($file['tmp_name'], [
            'folder' => $folderName, // Thư mục trên Cloudinary
            'resource_type' => 'auto'
        ]);

        // Trả về đường dẫn ảnh (URL tuyệt đối: https://res.cloudinary...)
        return $upload['secure_url'];

    } catch (Exception $e) {
        // Ghi log lỗi nếu cần
        return null;
    }
}

// [QUAN TRỌNG] Hàm xử lý hiển thị ảnh
function getImageUrl($path) {
    // 1. Nếu không có ảnh -> Trả về ảnh placeholder
    if (empty($path)) {
        return 'https://via.placeholder.com/150?text=No+Image'; 
    }
    
    // 2. Nếu là ảnh Cloudinary (chứa http hoặc https) -> Trả về nguyên gốc
    if (strpos($path, 'http') === 0) {
        return $path;
    }
    
    // 3. Nếu là ảnh cũ trên Host (local) -> Nối thêm BASE_URL
    return BASE_URL . $path;
}
?>