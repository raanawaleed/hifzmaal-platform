<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedFamilyAccessException extends Exception
{
    protected $message = 'You do not have access to this family.';
    protected $code = 403;

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->message,
                'error' => 'unauthorized_access'
            ], $this->code);
        }

        return redirect()->route('dashboard')->withErrors(['error' => $this->message]);
    }
}
