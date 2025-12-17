<?php

namespace App\Http\Middleware;

use App\Domain\Gym\Gym;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGymOwner
{
    /**
     * Handle an incoming request.
     *
     * Expects a 'gym' or 'gymId' parameter in the route.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Get gym ID from route parameter
        $gymId = $request->route('gym') ?? $request->route('gymId');

        if (!$gymId) {
            return response()->json([
                'message' => 'Gym ID not provided.',
            ], 400);
        }

        $gym = Gym::find($gymId);

        if (!$gym) {
            return response()->json([
                'message' => 'Gym not found.',
            ], 404);
        }

        if ($gym->owner_id !== $request->user()->id) {
            return response()->json([
                'message' => 'You must be the gym owner to access this resource.',
            ], 403);
        }

        // Attach gym to request for use in controller
        $request->merge(['gymModel' => $gym]);

        return $next($request);
    }
}
