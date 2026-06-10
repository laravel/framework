<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\Solution;

class BaseSolution implements Solution
{
    /** @var array<string, string> */
    private array $links = [];

    public function __construct(
        private readonly string $title,
        private readonly string $description = '',
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
