<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthenticationRequest;

class AuthenticationController extends Controller
{

    public function authenticate(AuthenticationRequest $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Invalid token'], 401);
        }

        return response()->json([
            'message' => 'Authenticated successfully',
            'user' => $user,
        ]);
    }
}
