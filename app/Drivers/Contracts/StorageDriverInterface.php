<?php

namespace App\Drivers\Contracts;

interface StorageDriverInterface
{
    public function putFile(string $path, mixed $content, string $disk = "local"): string;
    public function getFile(string $path, string $disk = "local"): string;
    public function deleteFile(string $path, string $disk = "local"): bool;
}
