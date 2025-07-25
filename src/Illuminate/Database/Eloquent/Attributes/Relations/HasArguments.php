<?php



namespace Illuminate\Database\Eloquent\Attributes\Relations;

/**
 * @internal
 */
trait HasArguments
{
    /**
     * @return array<mixed>
     */
    public function relationArguments(): array
    {
        return $this->arguments;
    }
}
