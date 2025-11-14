<?php

namespace App\Constants;

class MediaCollections
{
    // Offering collections
    public const OFFERING_IMAGE = 'offering_image';
    public const OFFERING_VIDEO = 'offering_video';

    public static function allOfferingCollections(): array
    {
        return [
            self::OFFERING_IMAGE,
            self::OFFERING_VIDEO
        ];
    }

    public static function allOfferingCollectionsList(string $separator): string
    {
        return implode($separator, self::allOfferingCollections());
    }
}
