<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedAccessException extends Exception
{
    protected $message = 'Unauthorized access to this resource';
    protected $code = 403;
}
