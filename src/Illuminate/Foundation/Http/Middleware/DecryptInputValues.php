<?php

namespace Illuminate\Foundation\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class DecryptInputValues
{
    public function __construct(protected EncrypterContract $encrypter)
    {
    }

    public function handle($request, Closure $next, ...$inputKeys)
    {
        $request->merge(
            $request->collect($inputKeys ?: null)->map(function ($inputValue) {
                try {
                    $inputValue = $this->encrypter->decrypt($inputValue, false);
                } catch (DecryptException) {
                    $inputValue = null;
                }

                return $inputValue;
            })->all()
        );

        return $next($request);
    }
}
