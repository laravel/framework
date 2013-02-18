<?php namespace Illuminate\Html;

class Html {

	/**
	 * Build an HTML attribute string from an array.
	 *
	 * @param  array  $attributes
	 * @return string
	 */
	public static function attributes($attributes)
	{
		$html = array();

		foreach ((array) $attributes as $key => $value)
		{
			// For numeric keys we will assume that the key and the value are the same
			// as this will convert HTML attributes such as "required" to a correct
			// form like required="required" instead of using incorrect numerics.
			if (is_numeric($key))
			{
				$key = $value;
			}

			// Before we add the attribute, we will make sure the value actually has a
			// value so we don't append any null valued attributes onto the list of
			// our attributes. We will just skip them over so we do not add them.
			if ( ! is_null($value))
			{
				$html[] = $key.'="'.e($value).'"';
			}
		}

		return count($html) > 0 ? ' '.implode(' ', $html) : '';
	}

}