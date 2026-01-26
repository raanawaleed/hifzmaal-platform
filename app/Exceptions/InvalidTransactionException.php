<?php

namespace App\Exceptions;

use Exception;

class InvalidTransactionException extends Exception
{
    protected $message = 'Invalid transaction data.';
    protected $code = 422;

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->message,
                'error' => 'invalid_transaction'
            ], $this->code);
        }

        return redirect()->back()->withErrors(['transaction' => $this->message]);
    }
}
