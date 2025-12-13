<?php

namespace App\Exceptions\OfferingTimeSlot;

use Exception;

class OfferingTimeSlotNotFoundException extends Exception
{
    protected $message = 'Time slot not found';
    protected $code = 404;
}
