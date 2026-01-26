<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    protected $message = 'Insufficient balance in account.';
    protected $code = 422;

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->message,
                'error' => 'insufficient_balance'
            ], $this->code);
        }

        return redirect()->back()->withErrors(['amount' => $this->message]);
    }
}
