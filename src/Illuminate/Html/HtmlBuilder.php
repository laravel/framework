<?php namespace Illuminate\Html;

class HtmlBuilder {

	/**
	 * Generate an ordered list of items.
	 *
	 * @param  array   $items
	 * @param  array   $attributes
	 * @return string
	 */
	public static function ol($list, $attributes = array())
	{
		return static::listing('ol', $list, $attributes);
	}

	/**
	 * Generate an un-ordered list of items.
	 *
	 * @param  array   $items
	 * @param  array   $attributes
	 * @return string
	 */
	public static function ul($list, $attributes = array())
	{
		return static::listing('ul', $list, $attributes);
	}

	/**
	 * Create a listing HTML element.
	 *
	 * @param  string  $type
	 * @param  array   $list
	 * @param  array   $attributes
	 * @return string
	 */
	protected static function listing($type, $list, $attributes)
	{
		$html = '';

		if (count($list) == 0) return $html;

		// Essentially we will just spin through the list and build the list of the HTML
		// elements from the array. We will also handled nested lists in case that is
		// present in the array. Then we will build out the final listing elements.
		foreach ($list as $key => $value)
		{
			$html .= static::listingElement($key, $type, $value);
		}

		$attributes = static::attributes($attributes);

		return "<{$type}{$attributes}>{$html}</{$type}>";
	}

	/**
	 * Create the HTML for a listing element.
	 *
	 * @param  mied    $key
	 * @param  string  $type
	 * @param  string  $value
	 * @return string
	 */
	protected static function listingElement($key, $type, $value)
	{
		if (is_array($value))
		{
			return static::nestedListing($key, $type, $value);
		}
		else
		{
			return '<li>'.e($value).'</li>';
		}
	}

	/**
	 * Create the HTML for a nested listing attribute.
	 *
	 * @param  mied    $key
	 * @param  string  $type
	 * @param  string  $value
	 * @return string
	 */
	protected static function nestedListing($key, $type, $value)
	{
		if (is_int($key))
		{
			return static::listing($type, $value);
		}
		else
		{
			return '<li>'.$key.static::listing($type, $value).'</li>';
		}
	}

	/**
	 * Build an HTML attribute string from an array.
	 *
	 * @param  array  $attributes
	 * @return string
	 */
	public static function attributes($attributes)
	{
		$html = array();

		// For numeric keys we will assume that the key and the value are the same
		// as this will convert HTML attributes such as "required" to a correct
		// form like required="required" instead of using incorrect numerics.
		foreach ((array) $attributes as $key => $value)
		{
			$element = static::attributeElement($key, $value);

			if ( ! is_null($element)) $html[] = $element;
		}

		return count($html) > 0 ? ' '.implode(' ', $html) : '';
	}

	/**
	 * Build a single attribute element.
	 *
	 * @param  string  $key
	 * @param  string  $value
	 * @return string
	 */
	protected static function attributeElement($key, $value)
	{
		if (is_numeric($key)) $key = $value;

		if ( ! is_null($value)) return $key.'="'.e($value).'"';
	}

}