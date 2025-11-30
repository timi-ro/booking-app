<?php

namespace App\Repositories\MySql\Media;

use App\Models\Media;
use App\Models\Offering;
use App\Repositories\Contracts\MediaRepositoryInterface;

class MediaEloquentRepository implements MediaRepositoryInterface
{
    public function create(array $data): array
    {
        return Media::create($data)->toArray();
    }

    public function update(int $id, array $data): void
    {
        Media::where(['id' => $id])->update($data);
    }

    public function findByUuid(string $uuid): ?array
    {
        return Media::where('uuid', $uuid)->first()?->toArray();
    }

    public function existsByMediableAndCollection(string $mediableType, int $mediableId, string $collection): bool
    {
        return Media::where('mediable_type', $mediableType)
            ->where('mediable_id', $mediableId)
            ->where('collection', $collection)
            ->exists();
    }

    public function delete(int $id): void
    {
        Media::destroy($id);
    }
}
