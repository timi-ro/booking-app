<?php

namespace App\Drivers\Storage;

use App\Drivers\Contracts\StorageDriverInterface;
use Illuminate\Support\Facades\Storage;

class LaravelStorageDriver implements StorageDriverInterface
{

    public function putFile(string $path, mixed $content, string $disk = "local"): string
    {
        return Storage::disk($disk)->put($path, $content);
    }

    public function getFile()
    {
        // TODO: Implement getFile() method.
    }
}
