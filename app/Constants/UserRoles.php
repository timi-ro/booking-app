<?php

namespace App\Constants;

class UserRoles
{
    public const CUSTOMER = 'customer';

    public const AGENCY = 'agency';

    public const ADMIN = 'admin';

    public static function all(): array
    {
        return [
            self::CUSTOMER,
            self::AGENCY,
        ];
    }

    public static function concatWithSeparator(string $separator): string
    {
        return implode($separator, self::all());
    }
}
