<?php

namespace App\Drivers\Queue;

use App\Drivers\Contracts\QueueDriverInterface;
use App\Jobs\ProcessMediaUpload;

class LaravelQueueDriver implements QueueDriverInterface
{
    public function dispatchMediaProcessing(array $data): void
    {
        ProcessMediaUpload::dispatch(
            userId: $data['user_id'],
            entity: $data['entity'],
            entityId: $data['entity_id'],
            tempFilePath: $data['temp_file_path'],
            originalFileName: $data['original_file_name'],
            mimeType: $data['mime_type'],
            fileSize: $data['file_size'],
            mediableType: $data['mediable_type'],
            collection: $data['collection'],
        );
    }
}
