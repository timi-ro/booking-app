<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary Reservation Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for temporary reservations stored in Redis.
    |
    */

    'reservation' => [
        // Time-to-live for temporary reservations (in seconds)
        // After this time, the reservation expires and the slot becomes available
        'ttl' => env('RESERVATION_TTL', 600), // Default: 10 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Reference Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for booking reference generation.
    |
    */

    'reference' => [
        // Prefix for booking references
        'prefix' => env('BOOKING_REFERENCE_PREFIX', 'BOOK'),

        // Length of random string in booking reference
        'random_length' => env('BOOKING_REFERENCE_LENGTH', 6),
    ],

];
