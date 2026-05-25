<?php

class MediaModel {
    private $uploadsDir;
    private $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    private $maxSize = 5242880; // 5MB

    public function __construct() {
        $this->uploadsDir = __DIR__ . '/../../assets/uploads/';
        if (!is_dir($this->uploadsDir)) mkdir($this->uploadsDir, 0755, true);
    }

    public function upload($file, $prefix = '') {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed');
        }

        if ($file['size'] > $this->maxSize) {
            throw new Exception('File too large');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($this->allowedTypes[$mime])) {
            throw new Exception('Invalid file type');
        }

        $ext = $this->allowedTypes[$mime];
        $name = ($prefix ? $prefix . '_' : '') . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $path = $this->uploadsDir . $name;

        if (!move_uploaded_file($file['tmp_name'], $path)) {
            throw new Exception('Upload failed');
        }

        return ['path' => '../assets/uploads/' . $name];
    }

    public function delete($filePath) {
        $filePath = str_replace(['..', '\\'], ['', '/'], $filePath);
        $fullPath = __DIR__ . '/../../' . ltrim($filePath, '/');

        if (!file_exists($fullPath) || strpos(realpath($fullPath), realpath($this->uploadsDir)) !== 0) {
            throw new Exception('File not found');
        }

        unlink($fullPath);
    }
}
