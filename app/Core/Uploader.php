<?php
namespace App\Core;

use RuntimeException;

/**
 * Upload ảnh an toàn.
 *   $path = Uploader::image('image', 'products');   // null nếu không chọn file; ném RuntimeException nếu file sai
 *   -> lưu 'uploads/products/<tên-ngẫu-nhiên>.jpg'; hiển thị bằng asset($path)
 *   Uploader::delete($oldPath);
 *
 * Không tin tên file/đuôi/Content-Type do client gửi: MIME được đọc từ nội dung file (finfo)
 * và đuôi file được suy ra từ MIME.
 */
class Uploader
{
    private const TYPES = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    public static function image(string $field, string $folder = 'products', int $maxBytes = 2 * 1024 * 1024): ?string
    {
        $file = $_FILES[$field] ?? null;
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (is_array($file['error'])) {
            throw new RuntimeException('Dữ liệu tải lên không hợp lệ.');
        }
        if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
            throw new RuntimeException(self::sizeMessage($maxBytes));
        }
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('Tải ảnh lên thất bại, vui lòng thử lại.');
        }
        if ($file['size'] > $maxBytes) {
            throw new RuntimeException(self::sizeMessage($maxBytes));
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset(self::TYPES[$mime]) || @getimagesize($file['tmp_name']) === false) {
            throw new RuntimeException('Chỉ chấp nhận ảnh JPG, PNG hoặc WEBP.');
        }

        if (!preg_match('/^\w+$/', $folder)) {
            throw new RuntimeException('Thư mục lưu ảnh không hợp lệ.');
        }
        $dir = self::root() . '/' . $folder;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException('Không tạo được thư mục lưu ảnh.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . self::TYPES[$mime];
        if (!move_uploaded_file($file['tmp_name'], "$dir/$name")) {
            throw new RuntimeException('Không lưu được ảnh, vui lòng thử lại.');
        }
        return "uploads/$folder/$name";
    }

    /** Xóa file đã upload; bỏ qua nếu đường dẫn rỗng hoặc nằm ngoài thư mục uploads. */
    public static function delete(?string $path): void
    {
        if (!$path || !str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        $file = dirname(self::root()) . '/' . $path;
        if (is_file($file)) {
            @unlink($file);
        }
    }

    private static function root(): string
    {
        return BASE_DIR . '/public/assets/uploads';
    }

    private static function sizeMessage(int $maxBytes): string
    {
        return 'Ảnh quá lớn (tối đa ' . round($maxBytes / 1048576, 1) . ' MB).';
    }
}
