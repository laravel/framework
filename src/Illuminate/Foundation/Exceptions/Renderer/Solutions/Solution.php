<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\Solution as SolutionContract;

class Solution implements SolutionContract
{
    /** @var array<string, string> */
    protected array $links = [];

    public function __construct(
        protected readonly string $title,
        protected readonly string $description = '',
    ) {
    }

    public static function create(string $title, string $description = ''): static
    {
        return new static($title, $description);
    }

    public function title(): string
    {
        return $this->title;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function links(): array
    {
        return $this->links;
    }

    /**
     * @param  array<string, string>  $links
     */
    public function withLinks(array $links): static
    {
        $this->links = $links;

        return $this;
    }
}
