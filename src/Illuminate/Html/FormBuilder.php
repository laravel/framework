<?php namespace Illuminate\Html;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Html\HtmlBuilder as Html;
use Illuminate\Session\Store as Session;

class FormBuilder {

	/**
	 * The URL generator instance.
	 *
	 * @var Illuminate\Routing\UrlGenerator  $url
	 */
	protected $url;

	/**
	 * The CSRF token used by the form builder.
	 *
	 * @var string
	 */
	protected $csrfToken;

	/**
	 * The session store implementation.
	 *
	 * @var Illuminate\Support\Contracts\SessionStoreInterface
	 */
	protected $session;

	/**
	 * The current model instance for the form.
	 *
	 * @var mixed
	 */
	protected $model;

	/**
	 * An array of label names we've created.
	 *
	 * @var array
	 */
	protected $labels = array();

	/**
	 * The registered form builder macros.
	 *
	 * @var array
	 */
	protected $macros = array();

	/**
	 * The reserved form open attributes.
	 *
	 * @var array
	 */
	protected $reserved = array('method', 'url', 'route', 'action', 'files');

	/**
	 * Create a new form builder instance.
	 *
	 * @param  Illuminate\Routing\UrlGenerator  $url
	 * @param  string  $csrfToken
	 * @return void
	 */
	public function __construct(UrlGenerator $url, $csrfToken)
	{
		$this->url = $url;
		$this->csrfToken = $csrfToken;
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

		if (isset($options['files']) and $options['files'])
		{
			$options['enctype'] = 'multipart/form-data';
		}

		// Finally we're ready to create the final form HTML field. We will attribute
		// format the array of attributes. We will also add on the appendage which
		// is used to spoof the requests for PUT and DELETE requests to the app.
		$attributes = array_merge(
			$attributes, array_except($options, $this->reserved)
		);

		return '<form'.Html::attributes($attributes).'>'.$append;
	}

	/**
	 * Create a new model based form builder.
	 *
	 * @param  mixed  $model
	 * @param  array  $options
	 * @return string
	 */
	public function model($model, array $options)
	{
		$this->model = $model;

		return $this->open($options);
	}

	/**
	 * Close the current form.
	 *
	 * @return string
	 */
	public function close()
	{
		$this->labels = array();

		$this->model = null;

		return '</form>';
	}

	/**
	 * Generate a hidden field with the current CSRF token.
	 *
	 * @return string
	 */
	public function token()
	{
		return $this->hidden('_token', $this->csrfToken);
	}

	/**
	 * Create a form label element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function label($name, $value, $attributes = array())
	{
		$this->labels[] = $name;

		$attributes = Html::attributes($attributes);

		return '<label for="'.$name.'"'.$attributes.'>'.e($value).'</label>';
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

		// We will get the appropriate value for the given field. We will look for the
		// value in the session for the value in the old input data then we'll look
		// in the model instance if one is set. Otherwise we will just use empty.
		$id = $this->getIdAttribute($name, $attributes);

		$value = $this->getValueAttribute($name, $value);

		$merge = compact('type', 'value', 'id');

		// Once we have the type, value, and ID we can marge them into the rest of the
		// attributes array so we can convert them into their HTML attribute format
		// when creating the HTML element. Then, we will return the entire input.
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
	public function hidden($name, $value = null, $attributes = array())
	{
		return $this->input('hidden', $name, $value, $attributes);
	}

	/**
	 * Create an e-mail input field.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function email($name, $value = null, $attributes = array())
	{
		return $this->input('email', $name, $value, $attributes);
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
		$attributes = $this->setTextAreaSize($attributes);

		$value = (string) $this->getValueAttribute($value);

		return '<textarea'.Html::attributes($attributes).'>'.e($value).'</textarea>';
	}

	/**
	 * Set the text area size on the attributes.
	 *
	 * @param  array  $attributes
	 * @return array
	 */
	protected function setTextAreaSize($attributes)
	{
		if (isset($attributes['size']))
		{
			$segments = explode('x', $attributes['size']);

			return array_merge($attributes, array('cols' => $segments[0], 'rows' => $segments[1]));
		}

		return array_merge($attributes, array('cols' => 50, 'rows' => 10));
	}

	/**
	 * Create a select box field.
	 *
	 * @param  string  $name
	 * @param  array   $options
	 * @param  string  $selected
	 * @param  array   $attributes
	 * @return string
	 */
	public function select($name, $options = array(), $selected = null, $attributes = array())
	{
		// When building a select box the "value" attribute is really the selected one
		// so we will use that when checking the model or session for a value which
		// should provide a convenient method of re-populating the forms on post.
		$selected = $this->getValueAttribute($name, $selected);

		$attributes['id'] = $this->getIdAttribute($name, $attributes);

		$attributes['name'] = $name;

		// We will simply loop through the options and build an HTML value for each of
		// them until we have an array of HTML declarations. Then we will join them
		// all together into one single HTML element that can be put on the form.
		$html = array();

		foreach ($options as $value => $display)
		{
			$html[] = $this->getSelectOption($display, $value, $selected);
		}

		// Once we have all of this HTML, we can join this into a single element after
		// formatting the attributes into an HTML "attributes" string, then we will
		// build out a final select statement, which will contain all the values.
		$attributes = Html::attributes($attributes);

		$list = implode('', $html);

		return "<select{$attributes}>{$list}</select>";
	}

	/**
	 * Get the select option for the given value.
	 *
	 * @param  string  $display
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function getSelectOption($display, $value, $selected)
	{
		if (is_array($display))
		{
			return $this->optionGroup($display, $value, $selected);
		}
	
		return $this->option($display, $value, $selected);
	}

	/**
	 * Create an option group form element.
	 *
	 * @param  array   $options
	 * @param  string  $label
	 * @param  string  $selected
	 * @return string
	 */
	protected function optionGroup($options, $label, $selected)
	{
		$html = array();

		foreach ($options as $value => $display)
		{
			$html[] = $this->option($display, $value, $selected);
		}

		return '<optgroup label="'.e($label).'>'.implode('', $html).'</optgroup>';
	}

	/**
	 * Create a select element option.
	 *
	 * @param  string  $display
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function option($display, $value, $selected)
	{
		$selected = $this->getSelectedValue($value, $selected);

		$attributes = array('value' => e($value), 'selected' => $selected);

		return '<option'.Html::attributes($attributes).'>'.e($display).'</option>';
	}

	/**
	 * Determine if the value is selected.
	 *
	 * @param  string  $value
	 * @param  string  $selected
	 * @return string
	 */
	protected function getSelectedValue($value, $selected)
	{
		if (is_array($selected))
		{
			return in_array($value, $selected) ? 'selected' : null;
		}

		return ((string) $value == (string) $selected) ? 'selected' : null;
	}

	/**
	 * Create a checkbox input field.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $attributes
	 * @return string
	 */
	public function checkbox($name, $value = 1, $checked = null, $attributes = array())
	{
		return $this->checkable('checkbox', $name, $value, $checked, $attributes);
	}

	/**
	 * Create a radio button input field.
	 *
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $attributes
	 * @return string
	 */
	public function radio($name, $value = null, $checked = null, $attributes = array())
	{
		if (is_null($value)) $value = $name;

		return $this->checkable('radio', $name, $value, $checked, $attributes);
	}

	/**
	 * Create a checkable input field.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  mixed   $value
	 * @param  bool    $checked
	 * @param  array   $attributes
	 * @return string
	 */
	protected function checkable($type, $name, $value, $checked, $attributs)
	{
		if (is_null($checked)) $checked = (bool) $this->getValueAttribute($name, null);

		if ($checked) $attributes['checked'] = 'checked';

		return $this->input($type, $name, $value, $attributes);
	}

	/**
	 * Create a submit button element.
	 *
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function submit($value = null, $attributes = array())
	{
		return $this->input('submit', null, $value, $attributes);
	}

	/**
	 * Create a button element.
	 *
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function button($value = null, $attributes = array())
	{
		return '<button'.Html::attributes($attributes).'>'.e($value).'</button>';
	}

	/**
	 * Register a custom form macro.
	 *
	 * @param  string    $name
	 * @param  callable  $macro
	 * @return void
	 */
	public function macro($name, $macro)
	{
		$this->macros[$name] = $macro;
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

		if (isset($this->session) and $this->session->hasOldInput($name))
		{
			return $this->session->getOldInput($name);
		}

		if (isset($this->model) and isset($this->model[$name]))
		{
			return $this->model[$name];
		}
	}

	/**
	 * Get the session store implementation.
	 *
	 * @param  Illuminate\Session\Store  $session
	 * @return void
	 */
	public function getSessionStore()
	{
		return $this->session;
	}

	/**
	 * Set the session store implementation.
	 *
	 * @param  Illuminate\Session\Store  $session
	 * @return void
	 */
	public function setSessionStore(Session $session)
	{
		$this->session = $session;
	}

	/**
	 * Dynamically handle calls to the form builder.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (isset($this->macros[$method]))
		{
			return call_user_func_array($this->macros[$method], $parameters);
		}

		throw new \BadMethodCallException("Method {$method} does not exist.");
	}

}