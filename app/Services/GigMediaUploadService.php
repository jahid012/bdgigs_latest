<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class GigMediaUploadService
{
    public function store(UploadedFile $file, int $userId, ?string $type = null): array
    {
        $mediaType = $type ?: $this->typeFromMime($file->getMimeType() ?: '');
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
            'altText' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'originalName' => $file->getClientOriginalName(),
            'mimeType' => $file->getMimeType(),
            'size' => $file->getSize(),
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
