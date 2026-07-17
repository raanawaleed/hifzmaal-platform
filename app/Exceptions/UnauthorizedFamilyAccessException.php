<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedFamilyAccessException extends Exception
{
    protected $message = 'You do not have access to this family.';
    protected $code = 403;

    public function render($request)
    {
        // Every route this can be thrown from lives under /api/* — direct
        // browser navigation (e.g. clicking an export/report download link)
        // won't set Accept: application/json, so expectsJson() alone isn't
        // enough. There's no server-rendered 'dashboard' route in this
        // API+SPA app to redirect to, so that fallback would 500 instead
        // of gracefully failing.
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => $this->message,
                'error' => 'unauthorized_access'
            ], $this->code);
        }

        return redirect('/')->withErrors(['error' => $this->message]);
    }
}
