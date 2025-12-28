<?php

namespace App\Drivers\Storage;

use App\Drivers\Contracts\StorageDriverInterface;
use Illuminate\Support\Facades\Storage;

class LaravelStorageDriver implements StorageDriverInterface
{

    public function putFile(string $path, mixed $content, string $disk = "local"): string
    {
        Storage::disk($disk)->put($path, $content);
        return $path;
    }

    public function getFile(string $path, string $disk = "local"): string
    {
        return Storage::disk($disk)->get($path);
    }

    public function deleteFile(string $path, string $disk = "local"): bool
    {
        if (Storage::disk($disk)->exists($path)) {
            return Storage::disk($disk)->delete($path);
        }

        return false;
    }

    public function getPath(string $path, string $disk = "local"): string
    {
        return Storage::disk($disk)->path($path);
    }
}
