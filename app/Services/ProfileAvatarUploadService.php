<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProfileAvatarUploadService
{
    public function store(UploadedFile $file, int $userId): string
    {
        $directory = public_path("uploads/profile-images/{$userId}");

        File::ensureDirectoryExists($directory);

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'jpg';
        $filename = Str::uuid()->toString().'.'.$extension;

        $file->move($directory, $filename);

        return "/uploads/profile-images/{$userId}/{$filename}";
    }
}
