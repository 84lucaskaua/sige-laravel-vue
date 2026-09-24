<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasProfile
{
    public function handle(Request $request, Closure $next, string ...$profiles): Response
    {
        $user = $request->user();

        if (!$user || !in_array($user->perfil, $profiles, true)) {
            return response()->json([
                'message' => 'Você não tem permissão para realizar esta ação.',
            ], 403);
        }

        return $next($request);
    }
}