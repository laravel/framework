<?php

namespace Illuminate\Database\Eloquent\Attributes\Relations;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class MorphTo implements RelationAttribute
{
    use HasArguments;

    public string $morphName;

    /**
     * @var array<string|class-string>
     */
    public array $arguments = [];

    /**
     * @param  array<string>  ...$arguments
     */
    public function __construct(string $morphName, string ...$arguments)
    {
        $this->morphName = $morphName;
        $this->arguments = [$morphName, ...$arguments];
    }

    public function relationName(): string
    {
        return $this->morphName;
    }
}
