<?php

namespace Illuminate\Foundation\Testing\Constraints;

use Illuminate\Database\Connection;
use PHPUnit\Framework\Constraint\Constraint;

class SeeInOrder extends Constraint
{
    /**
     * The value that failed. Used to display in the error message.
     * 
     * @var string
     */
    protected $failedValue;

    /**
     * The string we want to check the content of.
     * 
     * @var
     */
    protected $content;

    /**
     * Create a new constraint instance.
     *
     * @param  string $content
     * @return void
     */
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    /**
     * Check if the data is found in the given table.
     *
     * @param  array  $values
     * @return bool
     */
    public function matches($values) : bool
    {
        $position = 0;

        foreach ($values as $value) {
            $valuePosition = mb_strpos($this->content, $value, $position);

            if ($valuePosition === false || $valuePosition < $position) {
                $this->failedValue = $value;
                return false;
            }

            $position = $valuePosition + mb_strlen($value);
        }
        return true;
    }

    /**
     * Get the description of the failure.
     *
     * @param  array  $values
     * @return string
     */
    public function failureDescription($values) : string
    {
        return sprintf(
            'Failed asserting that \'%s\' contains "%s" in specified order.',
            $this->content,
            $this->failedValue
        );
    }

    /**
     * Get a string representation of the object.
     *
     * @param  int  $options
     * @return string
     */
    public function toString($options = 0) : string
    {
        return json_encode($this->data, $options);
    }
}
