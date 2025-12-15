<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentSuccessEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $reservationId,
        public string $paymentId,
        public ?string $customerNotes = null,
    ) {
    }
}
