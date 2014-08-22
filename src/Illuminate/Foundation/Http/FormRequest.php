<?php namespace Illuminate\Foundation\Http;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Container\Container;
use Illuminate\Validation\Validator;
use Illuminate\Http\Exception\HttpResponseException;
use Illuminate\Validation\Factory as ValidationFactory;

class FormRequest extends Request {

	/**
	 * The container instance.
	 *
	 * @var  \Illuminate\Container\Container  $container
	 */
	protected $container;

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
	 * Validate the form request according to its rules.
	 *
	 * @param  \Illuminate\Validation\Factory  $factory
	 * @return void
	 */
	public function validate(ValidationFactory $factory)
	{
		$instance = $factory->make(
			$this->input(), $this->container->call([$this, 'rules'])
		);

		if ($instance->fails())
		{
			throw new HttpResponseException($this->response(
				$this->formatErrors($instance)
			));
		}
		elseif ($this->failsAuthorization())
		{
			throw new HttpResponseException($this->forbiddenResponse());
		}

		$this->runFinalValidationChecks();
	}

	/**
	 * Deteremine if the request fails the authorization check.
	 *
	 * @return bool
	 */
	protected function failsAuthorization()
	{
		if (method_exists($this, 'authorize'))
		{
			return ! $this->container->call([$this, 'authorize']);
		}

		return true;
	}

	/**
	 * Post validation method. Run any final validation checks.
	 *
	 * @return void
	 */
	protected function runFinalValidationChecks()
	{
		if (method_exists($this, 'validated'))
		{
			$this->container->call([$this, 'validated']);
		}
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
		return $validator->errors()->all();
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
	 * Set the container instance used to resolve dependencies.
	 *
	 * @param  \Illuminate\Container\Container  $container
	 * @return \Illuminate\Foundation\Http\FormRequest
	 */
	public function setContainer(Container $container)
	{
		$this->container = $container;

		return $this;
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
	 * Get an input element from the request.
	 *
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->input($key);
	}

}