<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            Log::warning('Unauthorized admin access attempt', [
                'user_id' => $user ? $user->id : null,
                'email' => $user ? $user->email : null,
                'route' => $request->path(),
            ]);

            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
