<?php

namespace Illuminate\Foundation\Validation;

use Illuminate\Http\Request;

trait ValidatesRequests
{
    /**
     * Validate the given data with the given rules.
     *
     * @param  \Illuminate\Http\Request|array  $request
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate($request, array $rules, array $messages = [], array $customAttributes = [])
    {
        $data = $request instanceof Request ? $request->all() : $request;

        validator($data, $rules, $messages, $customAttributes)->validate();
    }
}
