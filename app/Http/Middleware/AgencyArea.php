<?php

namespace App\Http\Middleware;

use App\Constants\UserRoles;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
class AgencyArea
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(auth()->user()->role != UserRoles::AGENCY) {
            // TODO: use response template
            throw new \Exception("You are not allowed to access this page");
        }

        return $next($request);
    }
}
