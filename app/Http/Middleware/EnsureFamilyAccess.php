<?php

namespace App\Http\Middleware;

use App\Exceptions\UnauthorizedFamilyAccessException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFamilyAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $family = $request->route('family');

        if (!$family) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user || !$user->hasAccessToFamily($family)) {
            throw new UnauthorizedFamilyAccessException();
        }

        // Store family in request for easy access
        $request->merge(['current_family' => $family]);

        return $next($request);
    }
}