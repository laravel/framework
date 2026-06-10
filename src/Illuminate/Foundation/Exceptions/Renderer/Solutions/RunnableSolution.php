<?php

namespace Illuminate\Foundation\Exceptions\Renderer\Solutions;

use Illuminate\Foundation\Exceptions\Renderer\Solutions\Contracts\RunnableSolution as RunnableSolutionContract;

class RunnableSolution extends BaseSolution implements RunnableSolutionContract
{
    /** @var array<int, string> */
    private array $arguments = [];

    public function __construct(
        string $title,
        string $description,
        private readonly string $command,
    ) {
        parent::__construct($title, $description);
    }

    /**
     * @param  array<int, string>  $arguments
     */
    public static function artisan(string $title, string $description, string $command, array $arguments = []): static
    {
        $solution = new static($title, $description, $command);
        $solution->arguments = $arguments;

        return $solution;
    }

    public function command(): string
    {
        return $this->command;
    }

    public function commandArguments(): array
    {
        return $this->arguments;
    }
}
