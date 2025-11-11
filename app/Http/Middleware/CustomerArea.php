<?php

namespace App\Http\Middleware;

use App\Constants\UserRoles;
use App\Exceptions\User\AuthenticationException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class CustomerArea
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->user()->role != UserRoles::CUSTOMER) {
            throw new AuthenticationException();
        }

        return $next($request);
    }
}
