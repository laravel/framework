<?php namespace Illuminate\Html;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Html\HtmlBuilder as Html;
use Illuminate\Support\Contracts\CsrfTokenProviderInterface;

class FormBuilder {

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
	 * An array of label names we've created.
	 *
	 * @var array
	 */
	protected $labels = array();

	/**
	 * The reserved form open attributes.
	 *
	 * @var array
	 */
	protected $reserved = array('method', 'url', 'route', 'action');

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
	 * Open up a new HTML form.
	 *
	 * @param  array   $options
	 * @return string
	 */
	public function open(array $options = array())
	{
		$method = strtoupper(array_get($options, 'method', 'post'));

		// We need to extract the proper method from the attributes. If the method is
		// something other than GET or POST we'll use POST since we will spoof the
		// actual method since forms don't support PUT or DELETE as native HTML.
		$attributes['method'] = $this->getMethod($method);

		$attributes['action'] = $this->getAction($options);

		$attributes['accept-charset'] = 'UTF-8';

		// If the method is PUT or DELETE, we will need to add a spoofer hidden field
		// that will instruct this Symfony request to pretend that the method is a
		// different method than it actually is, for convenience from the forms.
		$append = $this->getAppendage($method);

		$attributes = array_merge(
			$attributes, array_except($options, $this->reserved)
		);

		// Finally we're ready to create the final form HTML field. We will attribute
		// format the array of attributes. We will also add on the appendage which
		// is used to spoof the requests for PUT and DELETE requests to the app.
		$attributes = Html::attributes($attributes);

		return '<form'.$attributes.'>'.$append;
	}

	/**
	 * Generate a hidden field with the current CSRF token.
	 *
	 * @return string
	 */
	public function token()
	{
		return $this->hidden('csrf_token', $this->tokenProvider->getToken());
	}

	/**
	 * Create a form input field.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function input($type, $name, $value = null, $attributes = array())
	{
		$attributes['name'] = $name;

		$id = $this->getIdAttribute($name, $attributes);

		// We will get the appropriate value for the given field. We will look for the
		// value in the session for the value in the old input data then we'll look
		// in the model instance if one is set. Otherwise we will just use empty.
		$value = $this->getValueAttribute($name, $value);

		$merge = compact('type', 'value', 'id');

		$attributes = array_merge($attributes, $merge);

		return '<input'.Html::attributes($attributes).'>';
	}

	/**
	 * Create a text input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function text($name, $value = null, $attributes = array())
	{
		return $this->input('text', $name, $value, $attributes);
	}

	/**
	 * Create a password input field.
	 *
	 * @param  string  $name
	 * @param  array   $attributes
	 * @return string
	 */
	public function password($name, $attributes = array())
	{
		return $this->input('password', $name, '', $attributes);
	}

	/**
	 * Create a hidden input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function hidden($name, $value, $attributes = array())
	{
		return $this->input('hidden', $name, $value, $attributes);
	}

	/**
	 * Create a file input field.
	 *
	 * @param  string  $name
	 * @param  array   $attributes
	 * @return string
	 */
	public function file($name, $attributes = array())
	{
		return $this->input('file', $name, null, $attributes);
	}

	/**
	 * Create a textarea input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function textarea($name, $value = null, $attributes = array())
	{
		$attributes['name'] = $name;

		$attributes['id'] = $this->getIdAttribute($name, $attributes);

		// Next we will look for the rows and cols attributes, as each of these are put
		// on the textarea element definition. If they are not present, we will just
		// assume some sane default values for these attributes for the developer.
		if ( ! isset($attributes['rows'])) $attributes['rows'] = 10;

		if ( ! isset($attributes['cols'])) $attributes['cols'] = 50;

		$value = (string) $this->getValueAttribute($value);

		return '<textarea'.Html::attributes($attributes).'>'.e($value).'</textarea>';
	}

	/**
	 * Parse the form action method.
	 *
	 * @param  string  $method
	 * @return string
	 */
	protected function getMethod($method)
	{
		return $method != 'GET' ? 'POST' : $method;
	}

	/**
	 * Get the form action from the options.
	 *
	 * @param  array   $options
	 * @return string
	 */
	protected function getAction(array $options)
	{
		if (isset($options['url'])) return $options['url'];

		// We will also check for a "route" or "action" parameter on the array so that
		// developers can easily specify a route or controller action when creating
		// a form providing a convenient interface for creating the form actions.
		if (isset($options['route']))
		{
			return $this->url->route($options['route']);
		}
		elseif (isset($options['action']))
		{
			return $this->url->action($options['action']);
		}

		return $this->url->current();
	}

	/**
	 * Get the form appendage for the given method.
	 *
	 * @param  string  $method
	 * @return string
	 */
	protected function getAppendage($method)
	{
		if ($method == 'PUT' or $method == 'DELETE')
		{
			$append = $this->hidden('_method', $method);
		}

		return '';
	}

	/**
	 * Get the ID attribute for a field name.
	 *
	 * @param  string  $name
	 * @param  array   $attributes
	 * @return string
	 */
	protected function getIdAttribute($name, $attributes)
	{
		if (array_key_exists('id', $attributes))
		{
			return $attributes['id'];
		}

		if (in_array($name, $this->labels))
		{
			return $name;
		}
	}

	/**
	 * Get the value that should be assigned to the field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @return string
	 */
	protected function getValueAttribute($name, $value)
	{
		if ( ! is_null($value)) return $value;

		if ($this->session->hasOldInput($name))
		{
			return $this->session->getOldInput($name);
		}

		if (isset($this->model) and isset($this->model[$name]))
		{
			return $this->model[$name];
		}
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