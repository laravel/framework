<?php

namespace Illuminate\Database\Eloquent;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class CouldNotGenerateSlugException extends RuntimeException
{
    /**
     * Create a new exception instance.
     */
    public function __construct(
        string $message,
        protected string $errorKey,
        protected string $errorMessage,
    ) {
        parent::__construct($message);
    }

    /**
     * Render the exception as an HTTP response.
     */
    public function render(Request $request): Response
    {
        $validator = Validator::make([], []);

        $validator->errors()->add($this->errorKey, $this->errorMessage);

        $exception = new ValidationException($validator);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->errorMessage,
                'errors' => $validator->errors()->messages(),
            ], $exception->status);
        }

        return redirect()
            ->back()
            ->withInput()
            ->withErrors($validator);
    }
}
