<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user || (!empty($roles) && !in_array($user->role, $roles, true))) {
            return response()->json([
                'data' => null,
                'errors' => ['message' => 'Forbidden'],
            ], 403);
        }

        return $next($request);
    }
}
