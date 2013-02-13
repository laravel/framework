<?php namespace Illuminate\Support;

class Slugifier {

	/**
	 * Array of foreign characters and their 7-bit ASCII equivalents.
	 *
	 * @var array
	 */
	protected static $ascii = array(
		'/æ|ǽ/' => 'ae',
		'/œ/' => 'oe',
		'/À|Á|Â|Ã|Ä|Å|Ǻ|Ā|Ă|Ą|Ǎ|А/' => 'A',
		'/à|á|â|ã|ä|å|ǻ|ā|ă|ą|ǎ|ª|а/' => 'a',
		'/Б/' => 'B',
		'/б/' => 'b',
		'/Ç|Ć|Ĉ|Ċ|Č|Ц/' => 'C',
		'/ç|ć|ĉ|ċ|č|ц/' => 'c',
		'/Ð|Ď|Đ|Д/' => 'Dj',
		'/ð|ď|đ|д/' => 'dj',
		'/È|É|Ê|Ë|Ē|Ĕ|Ė|Ę|Ě|Е|Ё|Э/' => 'E',
		'/è|é|ê|ë|ē|ĕ|ė|ę|ě|е|ё|э/' => 'e',
		'/Ф/' => 'F',
		'/ƒ|ф/' => 'f',
		'/Ĝ|Ğ|Ġ|Ģ|Г/' => 'G',
		'/ĝ|ğ|ġ|ģ|г/' => 'g',
		'/Ĥ|Ħ|Х/' => 'H',
		'/ĥ|ħ|х/' => 'h',
		'/Ì|Í|Î|Ï|Ĩ|Ī|Ĭ|Ǐ|Į|İ|И/' => 'I',
		'/ì|í|î|ï|ĩ|ī|ĭ|ǐ|į|ı|и/' => 'i',
		'/Ĵ|Й/' => 'J',
		'/ĵ|й/' => 'j',
		'/Ķ|К/' => 'K',
		'/ķ|к/' => 'k',
		'/Ĺ|Ļ|Ľ|Ŀ|Ł|Л/' => 'L',
		'/ĺ|ļ|ľ|ŀ|ł|л/' => 'l',
		'/М/' => 'M',
		'/м/' => 'm',
		'/Ñ|Ń|Ņ|Ň|Н/' => 'N',
		'/ñ|ń|ņ|ň|ŉ|н/' => 'n',
		'/Ö|Ò|Ó|Ô|Õ|Ō|Ŏ|Ǒ|Ő|Ơ|Ø|Ǿ|О/' => 'O',
		'/ö|ò|ó|ô|õ|ō|ŏ|ǒ|ő|ơ|ø|ǿ|º|о/' => 'o',
		'/П/' => 'P',
		'/п/' => 'p',
		'/Ŕ|Ŗ|Ř|Р/' => 'R',
		'/ŕ|ŗ|ř|р/' => 'r',
		'/Ś|Ŝ|Ş|Ș|Š|С/' => 'S',
		'/ś|ŝ|ş|ș|š|ſ|с/' => 's',
		'/Ţ|Ț|Ť|Ŧ|Т/' => 'T',
		'/ţ|ț|ť|ŧ|т/' => 't',
		'/Ù|Ú|Û|Ũ|Ū|Ŭ|Ů|Ü|Ű|Ų|Ư|Ǔ|Ǖ|Ǘ|Ǚ|Ǜ|У/' => 'U',
		'/ù|ú|û|ũ|ū|ŭ|ů|ü|ű|ų|ư|ǔ|ǖ|ǘ|ǚ|ǜ|у/' => 'u',
		'/В/' => 'V',
		'/в/' => 'v',
		'/Ý|Ÿ|Ŷ|Ы/' => 'Y',
		'/ý|ÿ|ŷ|ы/' => 'y',
		'/Ŵ/' => 'W',
		'/ŵ/' => 'w',
		'/Ź|Ż|Ž|З/' => 'Z',
		'/ź|ż|ž|з/' => 'z',
		'/Æ|Ǽ/' => 'AE',
		'/ß/'=> 'ss',
		'/Ĳ/' => 'IJ',
		'/ĳ/' => 'ij',
		'/Œ/' => 'OE',
		'/Ч/' => 'Ch',
		'/ч/' => 'ch',
		'/Ю/' => 'Ju',
		'/ю/' => 'ju',
		'/Я/' => 'Ja',
		'/я/' => 'ja',
		'/Ш/' => 'Sh',
		'/ш/' => 'sh',
		'/Щ/' => 'Shch',
		'/щ/' => 'shch',
		'/Ж/' => 'Zh',
		'/ж/' => 'zh',
	);

	/**
	 * Array of replacements to be made when creating
	 * a slug value.
	 *
	 * @var array
	 */
	protected static $replacements = array(
		'/[^\x09\x0A\x0D\x20-\x7E]/' => '',
	);

	/**
	 * Creates a slug from the given string.
	 *
	 * @param  string  $value
	 * @param  string  $separator
	 * @return string
	 */
	public static function slug($value, $separator = '-')
	{
		// Firstly, we'll do a big replacement to strip out foreign keys
		// along with any custom replacements required to create a slug.
		$value = preg_replace(
			array_merge(array_keys(static::$ascii), array_keys(static::$replacements)),
			array_merge(array_values(static::$ascii), array_values(static::$replacements)),
			$value
		);

		// Remove all characters that are not the separator, letters, numbers, or whitespace.
		$value = preg_replace('![^'.preg_quote($separator).'\pL\pN\s]+!u', '', strtolower($value));

		// Replace all separator characters and whitespace by a single separator
		$value = preg_replace('!['.preg_quote($separator).'\s]+!u', $separator, $value);

		return trim($value, $separator);
	}

}
