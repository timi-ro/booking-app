<?php

namespace App\Exceptions\OfferingDay;

use Exception;

class OfferingDayNotFoundException extends Exception
{
    protected $message = 'Offering day not found';
    protected $code = 404;
}
