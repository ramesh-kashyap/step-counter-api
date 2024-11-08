<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAppKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if the app key exists in the request header or query parameters
        if ($request->header('App-Key') !== 'StepUp') {
            // If the app key is not valid, redirect to the login route
            return response()->json(['message' => 'Unauthorized. Invalid App Key.'], 401);
        }

        // If the app key is valid, proceed to the next middleware or controller
        return $next($request);
    }
}
