<?php namespace Illuminate\Foundation\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Validation\Validator;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Validation\ValidatesWhenResolvedTrait;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;

class FormRequest extends Request implements ValidatesWhenResolved {

	use ValidatesWhenResolvedTrait;

	/**
	 * The container instance.
	 *
	 * @var  Container  $container
	 */
	protected $container;

	/**
	 * The redirector instance.
	 *
	 * @var Redirector
	 */
	protected $redirector;

	/**
	 * The route instance the request is dispatched to.
	 *
	 * @var \Illuminate\Routing\Route
	 */
	public $route;

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
	 * The input keys that should not be flashed on redirect.
	 *
	 * @var array
	 */
	protected $dontFlash = ['password', 'password_confirmation'];

	/**
	 * Get the validator instance for the request.
	 *
	 * @return \Illuminate\Validation\Validator
	 */
	protected function getValidatorInstance()
	{
		$factory = $this->container->make('Illuminate\Validation\Factory');

		if (method_exists($this, 'validator'))
		{
			return $this->container->call([$this, 'validator'], compact('factory'));
		}
		else
		{
			return $factory->make(
				$this->formatInput(), $this->container->call([$this, 'rules']), $this->messages()
			);
		}
	}

	/**
	 * Get the input that should be fed to the validator.
	 *
	 * @return array
	 */
	protected function formatInput()
	{
		return $this->all();
	}

	/**
	 * Handle a failed validation attempt.
	 *
	 * @param  \Illuminate\Validation\Validator  $validator
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
		if (method_exists($this, 'authorize'))
		{
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
		if ($this->ajax())
		{
			return new JsonResponse($errors, 422);
		}
		else
		{
			return $this->redirector->to($this->getRedirectUrl())
                                            ->withInput($this->except($this->dontFlash))
                                            ->withErrors($errors);
		}
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
	 * @param  \Illuminate\Validation\Validator  $validator
	 * @return array
	 */
	protected function formatErrors(Validator $validator)
	{
		return $validator->errors()->getMessages();
	}

	/**
	 * Get the URL to redirect to on a validation error.
	 *
	 * @return string
	 */
	protected function getRedirectUrl()
	{
		$url = $this->redirector->getUrlGenerator();

		if ($this->redirect)
		{
			return $url->to($this->redirect);
		}
		elseif ($this->redirectRoute)
		{
			return $url->route($this->redirectRoute);
		}
		elseif ($this->redirectAction)
		{
			return $url->action($this->redirectAction);
		}
		else
		{
			return $url->previous();
		}
	}

	/**
	 * Set the route handling the request.
	 *
	 * @param  \Illuminate\Routing\Route  $route
	 * @return \Illuminate\Foundation\Http\FormRequest
	 */
	public function setRoute(Route $route)
	{
		$this->route = $route;

		return $this;
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
	 * @param  Container  $container
	 * @return $this
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
	}

	/**
	 * Get an input element from the request.
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->input($key);
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

}
