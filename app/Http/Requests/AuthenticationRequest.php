<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthenticationRequest extends FormRequest
{
    public function authorize() {
        return true;
    }

    public function rules()
    {
        // No body validation — token is in header
        return [];
    }

    public function token(): ?string
    {
        return $this->bearerToken();
    }
}
