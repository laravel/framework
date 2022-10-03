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
    protected string|null $translation;

    /**
     * Create a new potentially translated string.
     *
     * @param  string  $string  The string that may be translated.
     * @param  \Illuminate\Contracts\Translation\Translator  $translator  The validator that may perform the translation.
     */
    public function __construct(protected string $string, protected \Illuminate\Contracts\Translation\Translator $translator)
    {
    }

    /**
     * Translate the string.
     *
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translate(array $replace = [], string|null $locale = null): self
    {
        $this->translation = $this->translator->get($this->string, $replace, $locale);

        return $this;
    }

    /**
     * Translates the string based on a count.
     *
     * @param  \Countable|int|array  $number
     * @param  array  $replace
     * @param  string|null  $locale
     * @return $this
     */
    public function translateChoice(\Countable|int|array $number, array $replace = [], string|null $locale = null): self
    {
        $this->translation = $this->translator->choice($this->string, $number, $replace, $locale);

        return $this;
    }

    /**
     * Get the original string.
     *
     * @return string
     */
    public function original(): string
    {
        return $this->string;
    }

    /**
     * Get the potentially translated string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->translation ?? $this->string;
    }

    /**
     * Get the potentially translated string.
     *
     * @return string
     */
    public function toString(): string
    {
        return (string) $this;
    }
}
