<?php

namespace App\Offering\Services;

use App\Shared\Drivers\Contracts\StorageDriverInterface;
use App\Offering\Exceptions\OfferingNotFoundException;
use App\Offering\Repositories\Contracts\OfferingRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class OfferingService
{
    public function __construct(
        protected OfferingRepositoryInterface $offeringRepository,
        protected StorageDriverInterface $storageDriver,
    ) {}

    public function createOffering(array $data): array
    {
        $userId = auth()->user()->id;
        $data['user_id'] = $userId;

        $offering = $this->offeringRepository->create($data);

        $this->clearOfferingsCache();

        return $offering;
    }

    public function listOfferings(int $userId, array $params): array
    {
        $page = $params['page'] ?? 1;
        $pageSize = $params['page_size'] ?? 15;

        return $this->offeringRepository->index($userId, $page, $pageSize);
    }

    public function browseOfferings(array $filters): array
    {
        $page = $filters['page'] ?? 1;
        $pageSize = $filters['page_size'] ?? 15;

        $cacheKey = $this->generateOfferingsCacheKey($filters, $page, $pageSize);

        return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($filters, $page, $pageSize) {
            return $this->offeringRepository->listAllWithFilters($filters, $page, $pageSize);
        });
    }

    /**
     * Generate a unique cache key for offerings list
     */
    protected function generateOfferingsCacheKey(array $filters, int $page, int $pageSize): string
    {
        ksort($filters);

        // Create a hash of the filters to keep key short
        $filterHash = md5(json_encode($filters));

        return "offerings:list:page_{$page}:size_{$pageSize}:filters_{$filterHash}";
    }

    public function getOfferingDetails(int $id): ?array
    {
        $offering = $this->offeringRepository->findWhere(['id' => $id, 'deleted_at' => null]);

        if (empty($offering)) {
            throw new OfferingNotFoundException();
        }

        return $this->offeringRepository->findByIdWithRelations($id);
    }

    public function updateOffering(int $id, array $data): void
    {
        $offering = $this->offeringRepository->findWhere(['id' => $id, 'deleted_at' => null]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        $this->offeringRepository->update($id, $data);

        $this->clearOfferingsCache();
    }

    public function deleteOffering(int $id): void
    {
        $offering = $this->offeringRepository->findWhere(['id' => $id]);

        if (empty($offering) || auth()->user()->id != $offering['user_id']) {
            throw new OfferingNotFoundException();
        }

        collect(['video', 'image'])
            ->map(fn ($field) => $offering[$field] ?? null)
            ->filter()
            ->each(fn ($path) => $this->storageDriver->deleteFile($path));

        $this->offeringRepository->delete($id);

        $this->clearOfferingsCache();
    }

    public function exist(int $id): bool
    {
        $offering = $this->offeringRepository->findWhere(['id' => $id]);

        return (bool) $offering;
    }

    protected function clearOfferingsCache(): void
    {
        $driver = config('cache.default');

        if ($driver === 'array') {
            Cache::flush();

            return;
        }

        if ($driver === 'redis') {
            $pattern = 'offerings:list:*';

            // Laravel's cache doesn't support wildcard deletion out of the box
            // So we use the Redis connection directly for pattern-based deletion
            $keys = Cache::getRedis()->keys(config('cache.prefix').':'.$pattern);

            if (! empty($keys)) {
                // Remove the Laravel cache prefix from keys
                $prefix = config('cache.prefix').':';
                $keysToDelete = array_map(function ($key) use ($prefix) {
                    return str_replace($prefix, '', $key);
                }, $keys);

                foreach ($keysToDelete as $key) {
                    Cache::forget($key);
                }
            }
        }
    }
}
