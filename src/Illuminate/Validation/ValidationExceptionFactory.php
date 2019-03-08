<?php


namespace Illuminate\Validation;


use Illuminate\Contracts\Validation\ExceptionFactory;

class ValidationExceptionFactory implements ExceptionFactory
{
    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator $validator
     * @param  \Symfony\Component\HttpFoundation\Response $response
     * @param  string $errorBag
     * @param string $message The Exception message to throw.
     * @param int $code
     * @param \Throwable $previous
     * @return \Illuminate\Validation\ValidationException exception to throw
     */
    function make($validator, $response = null, $errorBag = 'default', $message = 'The given data was invalid.', $code = 0, $previous = null)
    {
        return new ValidationException($validator, $response, $errorBag, $message, $code, $previous);
    }

    /**
     * Create a new validation exception from a plain array of messages.
     *
     * @param  array $messages
     * @return  \Illuminate\Validation\ValidationException
     */
    function withMessages(array $messages)
    {
        return ValidationException::withMessages($messages);
    }
}