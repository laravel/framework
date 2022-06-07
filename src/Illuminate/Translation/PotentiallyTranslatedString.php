<?php

namespace Illuminate\Translation;

use RuntimeException;
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
     * Create a new potentially translated string.
     *
     * @param  string  $string
     * @param  \Illuminate\Contracts\Translation\Translator  $translator
     */
    public function __construct($string, $translator)
    {
        $this->string = $string;

        $this->translator = $translator;
    }

    /**
     * Translate the string.
     *
     * @return $this
     */
    public function translate()
    {
        if (! $this->translator->has($this->string)) {
            throw new RuntimeException("Unable to find translation [{$this->string}] for locale [{$this->translator->getLocale()}].");
        }

        $this->translation = $this->translator->get($this->string);

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
