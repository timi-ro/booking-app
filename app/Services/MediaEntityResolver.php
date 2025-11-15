<?php

namespace App\Services;

use App\Constants\MediaEntities;
use App\Exceptions\Media\MediableNotFoundException;

class MediaEntityResolver
{
    public function __construct(
        protected OfferingService $offeringService,
    ) {}

    public function validateOrFail(string $entityType, int $entityId): void
    {
        if (!$this->resolve($entityType, $entityId)) {
            throw new MediableNotFoundException();
        }
    }

    private function resolve(string $entityType, int $entityId): bool
    {
        return match ($entityType) {
            MediaEntities::MEDIA_OFFERING => $this->offeringService->exist($entityId),
            default => throw new MediableNotFoundException("Unknown entity type: {$entityType}")
        };
    }
}
