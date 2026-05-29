<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaService
{
    private string $targetDirectory;

    // Le paramètre sera injecté via config/services.yaml (ex: '%kernel.project_dir%/public/assets/uploads')
    public function __construct(string $targetDirectory)
    {
        $this->targetDirectory = $targetDirectory;
    }

    public function upload(UploadedFile $file, string $prefix = ''): array
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        // Générer un nom unique sécurisé
        $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '', $originalFilename);
        $fileName = ($prefix ? $prefix . '_' : '') . time() . '_' . uniqid() . '.' . $file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            throw new \Exception('Upload failed: ' . $e->getMessage());
        }

        return ['path' => 'assets/uploads/' . $fileName];
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }

    /**
     * Deletes an uploaded file. Guards against path traversal: only files that
     * actually resolve inside the uploads directory may be removed.
     */
    public function delete(string $relativePath): void
    {
        $clean = str_replace(['..', '\\'], ['', '/'], $relativePath);
        // Accept paths like "assets/uploads/foo.jpg" or just "foo.jpg".
        $fileName = basename($clean);
        $fullPath = rtrim($this->targetDirectory, '/') . '/' . $fileName;

        $real = realpath($fullPath);
        $base = realpath($this->targetDirectory);

        if ($real === false || $base === false || !str_starts_with($real, $base)) {
            throw new \Exception('File not found');
        }

        unlink($real);
    }
}
