<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GigMediaUploadService
{
    public function store(UploadedFile $file, int $userId, ?string $type = null): array
    {
        $mimeType = $file->getMimeType() ?: '';
        $size = $file->getSize();
        $originalName = $file->getClientOriginalName();
        $mediaType = $type ?: $this->typeFromMime($mimeType);
        $directory = public_path("uploads/gig-media/{$userId}");

        File::ensureDirectoryExists($directory);

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin';
        $filename = Str::uuid()->toString().'.'.$extension;
        $file->move($directory, $filename);

        $url = "/uploads/gig-media/{$userId}/{$filename}";

        return [
            'type' => $mediaType,
            'url' => $url,
            'thumbnailUrl' => $mediaType === 'image' ? $url : null,
            'altText' => pathinfo($originalName, PATHINFO_FILENAME),
            'originalName' => $originalName,
            'mimeType' => $mimeType,
            'size' => $size,
        ];
    }

    private function typeFromMime(string $mime): string
    {
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }

        if ($mime === 'application/pdf') {
            return 'document';
        }

        return 'image';
    }
}
