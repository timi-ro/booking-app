<?php

namespace App\Drivers\Contracts;

interface QueueDriverInterface
{
    public function dispatchMediaProcessing(array $data): void;
}
