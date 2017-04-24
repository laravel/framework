<?php

namespace Illuminate\Foundation\Http;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

class FormRequest extends Request implements ValidatesWhenResolved
{
    use ValidatesWhenResolvedTrait;

    /**
     * The container instance.
     *
     * @var \Illuminate\Container\Container
     */
    protected $container;

    /**
     * The redirector instance.
     *
     * @var \Illuminate\Routing\Redirector
     */
    protected $redirector;

    /**
     * The URI to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirect;

    /**
     * The route to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectRoute;

    /**
     * The controller action to redirect to if validation fails.
     *
     * @var string
     */
    protected $redirectAction;

    /**
     * Adds an optional hash which will be appended to the auto-generated
     * redirect URL.
     *
     * This is useful when a form is not on top of the page to which the user is
     * being redirected, so that the browser will automatically scroll down to
     * the correct location in the page and the user can see the form and its
     * error messages.
     *
     * Do not prefix this value with the actual hash symbol "#", e.g. if you
     * want to redirect the user to "http://example.com/foo#bar", just set this
     * value to "bar", and handle the actual URL with one of the other redirect
     * attributes.
     *
     * @var string|null
     */
    protected $redirectHash;

    /**
     * The key to be used for the view error bag.
     *
     * @var string
     */
    protected $errorBag = 'default';

    /**
     * The input keys that should not be flashed on redirect.
     *
     * @var array
     */
    protected $dontFlash = ['password', 'password_confirmation'];

    /**
     * Get the validator instance for the request.
     *
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getValidatorInstance()
    {
        $factory = $this->container->make(ValidationFactory::class);

        if (method_exists($this, 'validator')) {
            return $this->container->call([$this, 'validator'], compact('factory'));
        }

        return $factory->make(
            $this->all(), $this->container->call([$this, 'rules']), $this->messages(), $this->attributes()
        );
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return mixed
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException($this->response(
            $this->formatErrors($validator)
        ));
    }

    /**
     * Determine if the request passes the authorization check.
     *
     * @return bool
     */
    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->container->call([$this, 'authorize']);
        }

        return false;
    }

    /**
     * Handle a failed authorization attempt.
     *
     * @return mixed
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException($this->forbiddenResponse());
    }

    /**
     * Get the proper failed validation response for the request.
     *
     * @param  array  $errors
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function response(array $errors)
    {
        if ($this->ajax() || $this->wantsJson()) {
            return new JsonResponse($errors, 422);
        }

        return $this->redirector->to($this->getRedirectUrl())
                                        ->withInput($this->except($this->dontFlash))
                                        ->withErrors($errors, $this->errorBag);
    }

    /**
     * Get the response for a forbidden operation.
     *
     * @return \Illuminate\Http\Response
     */
    public function forbiddenResponse()
    {
        return new Response('Forbidden', 403);
    }

    /**
     * Format the errors from the given Validator instance.
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @return array
     */
    protected function formatErrors(Validator $validator)
    {
        return $validator->getMessageBag()->toArray();
    }

    /**
     * Get the URL to redirect to on a validation error.
     *
     * @return string
     */
    protected function getRedirectUrl()
    {
        $url = $this->redirector->getUrlGenerator();

        if ($this->redirect) {
            $redirectUrl = $url->to($this->redirect);
        } elseif ($this->redirectRoute) {
            $redirectUrl = $url->route($this->redirectRoute);
        } elseif ($this->redirectAction) {
            $redirectUrl = $url->action($this->redirectAction);
        } else {
            $redirectUrl = $url->previous();
        }

        /*
         * Append the hash only if it's defined and if it isn't already included
         * in the generated URL, for example if the user has included it via the
         * `$redirectTo` attribute.
         */

        if ($this->redirectHash && strpos($redirectUrl, '#') === false) {
            $redirectUrl .= "#{$this->redirectHash}";
        }

        return $redirectUrl;
    }

    /**
     * Set the Redirector instance.
     *
     * @param  \Illuminate\Routing\Redirector  $redirector
     * @return \Illuminate\Foundation\Http\FormRequest
     */
    public function setRedirector(Redirector $redirector)
    {
        $this->redirector = $redirector;

        return $this;
    }

    /**
     * Set the container implementation.
     *
     * @param  \Illuminate\Container\Container  $container
     * @return $this
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Set custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Set custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Returns the configured redirect hash.
     *
     * Useful to keep things DRY in case of, for example, a controller using
     * this request needs to redirect to the same hash in the case of successful
     * form submission.
     *
     * @return string|null
     */
    public function getRedirectHash()
    {
        return $this->redirectHash;
    }
}
