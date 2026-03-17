<?php

namespace Illuminate\Testing\Constraints;

use PHPUnit\Framework\Constraint\Constraint;
use ReflectionClass;

class SeeInHtml extends Constraint
{
    /**
     * The string under validation.
     *
     * @var string
     */
    protected $content;

    /**
     * Indicates the values must appear in order.
     *
     * @var bool
     */
    protected $ordered;

    /**
     * Indicates whether to negate the assertion.
     *
     * @var bool
     */
    protected $negate;

    /**
     * The last value that failed to pass validation.
     *
     * @var string
     */
    protected $failedValue;

    /**
     * Create a new constraint instance.
     *
     * @param  string  $content
     */
    public function __construct($content, $ordered = false, $negate = false)
    {
        $this->content = $content;
        $this->ordered = $ordered;
        $this->negate = $negate;
    }

    /**
     * Determine if the rule passes validation.
     *
     * @param  array  $values
     */
    public function matches($values): bool
    {
        $normalizedContent = $this->normalize($this->content);

        $position = 0;

        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }

            $normalizedValue = $this->normalize($value);

            $valuePosition = mb_strpos($normalizedContent, $normalizedValue, $position);

            if ($this->negate) {
                if ($valuePosition !== false) {
                    $this->failedValue = $value;

                    return false;
                }

                continue;
            }

            if ($valuePosition === false || $valuePosition < $position) {
                $this->failedValue = $value;

                return false;
            }

            if ($this->ordered) {
                $position = $valuePosition + mb_strlen($normalizedValue);
            }
        }

        return true;
    }

    /**
     * Get the description of the failure.
     *
     * @param  array  $values
     */
    public function failureDescription($values): string
    {
        if ($this->negate) {
            return sprintf(
                '\'%s\' does not contain "%s".',
                $this->content,
                $this->failedValue
            );
        }

        return sprintf(
            '\'%s\' contains "%s"%s',
            $this->content,
            $this->failedValue,
            $this->ordered ? ' in specified order.' : '.'
        );
    }

    /**
     * Normalize the given value.
     */
    protected function normalize(string $value): ?string
    {
        $value = strip_tags($value);
        $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }

    /**
     * Get a string representation of the object.
     */
    public function toString(): string
    {
        return (new ReflectionClass($this))->name;
    }
}
