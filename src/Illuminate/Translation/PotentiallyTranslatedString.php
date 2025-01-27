<?php

namespace Illuminate\Translation;

use Stringable;

class PotentiallyTranslatedString implements Stringable
{
    /**
     * The translated string.
     *
     * @var string|null
     */
    protected $translation;

    /**
     * Create a new potentially translated string.
     *
     * @param  string  $string The string that may be translated.
     * @param  \Illuminate\Contracts\Translation\Translator  $translator The validator that may perform the translation.
     */
    public function __construct(protected $string, protected $translator)
    {}

    /**
     * Translate the string.
     *
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translate($replace = [], $locale = null)
    {
        $this->translation = $this->translator->get($this->string, $replace, $locale);

        return $this;
    }

    /**
     * Translates the string based on a count.
     *
     * @param  \Countable|int|float|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translateChoice($number, array $replace = [], $locale = null)
    {
        $this->translation = $this->translator->choice($this->string, $number, $replace, $locale);

        return $this;
    }

    /**
     * Get the original string.
     *
     * @return string
     */
    public function original()
    {
        return $this->string;
    }

    /**
     * Get the potentially translated string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->translation ?? $this->string;
    }

    /**
     * Get the potentially translated string.
     *
     * @return string
     */
    public function toString()
    {
        return (string) $this;
    }
}
