<?php
declare(strict_types=1);

namespace Illuminate\Database\Eloquent\Attributes;

use Attribute;
use InvalidArgumentException;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Increment
{
    public function __construct(protected string $type)
    {
        $this->validate();
    }

    private function validate(): void
    {
        $allowed = [
            'string',
            'int'
        ];

        if (!in_array($this->type, $allowed)) {
            throw new InvalidArgumentException("Invalid cast type: $this->type");
        }
    }
}