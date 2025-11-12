<?php

namespace App\Policies;

use App\Models\Offering;
use App\Models\User;

class OfferingPolicy
{
    public function update(User $user, Offering $offering): bool
    {
        return $user->id === $offering->user_id;
    }

    public function delete(User $user, Offering $offering): bool
    {
        return $user->id === $offering->user_id;
    }
}
