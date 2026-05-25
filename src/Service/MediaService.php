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
}
