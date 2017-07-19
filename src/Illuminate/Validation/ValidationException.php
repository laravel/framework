<?php

namespace Illuminate\Validation;

use Exception;

class ValidationException extends Exception
{
    /**
     * The validator instance.
     *
     * @var \Illuminate\Contracts\Validation\Validator
     */
    public $validator;

    /**
     * The recommended response to send to the client.
     *
     * @var \Symfony\Component\HttpFoundation\Response|null
     */
    public $response;

    /**
     * The name of the error bag.
     *
     * @var string
     */
    public $errorBag;

    /**
     * The URI to redirect to.
     *
     * @var string
     */
    public $redirectUrl;

    /**
     * Create a new exception instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $errorBag
     * @param  string  $redirectUrl
     * @return void
     */
    public function __construct($validator, $response = null, $errorBag = 'default', $redirectUrl = null)
    {
        parent::__construct('The given data failed to pass validation.');

        $this->response = $response;
        $this->errorBag = $errorBag;
        $this->validator = $validator;
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Get the underlying response instance.
     *
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }
}
