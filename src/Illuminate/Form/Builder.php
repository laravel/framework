<?php namespace Illuminate\Form;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Contracts\CsrfTokenProviderInterface;

class Factory {

	/**
	 * The URL generator instance.
	 *
	 * @var Illuminate\Routing\UrlGenerator  $url
	 */
	protected $url;

	/**
	 * The CSRF token provider implementation.
	 *
	 * @var Illuminate\Support\Contracts\CsrfTokenProviderInterface
	 */
	protected $tokenProvider;

	/**
	 * Create a new form builder instance.
	 *
	 * @param  Illuminate\Routing\UrlGenerator  $url
	 * @return void
	 */
	public function __construct(UrlGenerator $url,
                                CsrfTokenProviderInterface $tokenProvider)
	{
		$this->url = $url;
		$this->tokenProvider = $tokenProvider;
	}

	/**
	 * Open a new form.
	 *
	 * @return string
	 */
	public function open()
	{
		//
	}

	/**
	 * Create a new model based form builder.
	 *
	 * @param  mixed    $model
	 * @param  array    $attributes
	 * @param  Closure  $callback
	 * @return void
	 */
	public function model($model, array $attributes, Closure $callback)
	{
		//
	}

}