<?php

namespace App\Shared\Drivers\Contracts;

interface QueueDriverInterface
{
    public function dispatchMediaProcessing(array $data): void;
}
