<?php

namespace Illuminate\Database\Eloquent\Attributes\Relations;

/**
 * @internal
 */
interface RelationAttribute
{
    public function relationName(): string;

    /**
     * @return array<mixed>
     */
    public function relationArguments(): array;
}
