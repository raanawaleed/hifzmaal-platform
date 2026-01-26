<?php

namespace App\Exceptions;

use Exception;

class BudgetExceededException extends Exception
{
    protected $message = 'This transaction will exceed the budget limit.';
    protected $code = 422;

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->message,
                'error' => 'budget_exceeded',
                'warning' => true
            ], $this->code);
        }

        return redirect()->back()->withErrors(['amount' => $this->message]);
    }
}
