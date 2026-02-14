<?php

namespace App\Media\Constants;

class MediaEntities
{
    public const MEDIA_OFFERING = 'offering';

    public static function all(): array
    {
        return [
            self::MEDIA_OFFERING,
            // add more
        ];
    }

    public static function allEntitiesList(string $separator): string
    {
        return implode($separator, self::all());
    }
}
