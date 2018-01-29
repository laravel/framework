<?php

namespace Illuminate\Database\Schema\Columns;

/**
 * Class Enum
 *
 * @property-read string[] $allowed
 */
class Enum extends Text
{
    /**
     * @var string[]
     */
    protected $allowed;

    /**
     * Enum constructor.
     *
     * @param string $type
     * @param string $name
     * @param string[] $allowed
     */
    public function __construct(string $type, string $name, array $allowed)
    {
        parent::__construct($type, $name);

        $this->allowed = $allowed;
    }
}
