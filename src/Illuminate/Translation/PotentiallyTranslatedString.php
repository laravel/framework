<?php

namespace Illuminate\Translation;

use Stringable;

class PotentiallyTranslatedString implements Stringable
{
    /**
     * The string that may be translated.
     *
     * @var string
     */
    protected $string;

    /**
     * The translated string.
     *
     * @var string|null
     */
    protected $translation;

    /**
     * The validator that may perform the translation.
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $translator;

    /**
     * Default replacement parameters.
     *
     * @var array<string, Stringable|string>
     */
    protected array $replace = [];

    /**
     * Create a new potentially translated string.
     *
     * @param  string  $string
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     * @param  array<string, Stringable|string>  $replace
     */
    public function __construct($string, $translator, array $replace = [])
    {
        $this->string = $string;

        $this->translator = $translator;

        $this->replace = $replace;
    }

    /**
     * Translate the string.
     *
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translate($replace = [], $locale = null)
    {
        $this->translation = $this->translator->get($this->string, array_merge($this->replace, $replace), $locale);

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
        $this->translation = $this->translator->choice($this->string, $number, array_merge($this->replace, $replace), $locale);

        return $this;
    }

    /**
     * @var array<string, Stringable|string>
     */
    public function addReplace(array $replace): self
    {
        $this->replace = array_merge($this->replace, $replace);

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
