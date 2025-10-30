<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->input('role'); // get the role value from request

        $common = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            //TODO: make constant from all roles
            'role' => ['required', Rule::in(['admin', 'user', 'agency'])],
        ];

        if ($role === 'agency') {
            // extra validation for agencies
            $common['agency_name'] = ['required', 'string', 'max:255'];
            $common['agency_code'] = ['required', 'string', 'max:50'];
        }

        if ($role === 'user') {
            // extra validation for normal users
            $common['phone'] = ['required', 'string', 'min:10'];
        }

        return $common;
    }
}
