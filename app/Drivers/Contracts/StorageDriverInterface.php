<?php

namespace App\Drivers\Contracts;

interface StorageDriverInterface
{
    public function putFile(string $path, mixed $content): string;
    public function getFile();
}
