<?php

namespace Illuminate\Foundation;

class DevCommand
{
    /**
     * Blue color.
     *
     * @var string
     */
    public const BLUE = '#93c5fd';

    /**
     * Purple color.
     *
     * @var string
     */
    public const PURPLE = '#c4b5fd';

    /**
     * Pink color.
     *
     * @var string
     */
    public const PINK = '#fb7185';

    /**
     * Orange color.
     *
     * @var string
     */
    public const ORANGE = '#fdba74';

    /**
     * Green color.
     *
     * @var string
     */
    public const GREEN = '#86efac';

    /**
     * Yellow color.
     *
     * @var string
     */
    public const YELLOW = '#fcd34d';

    /**
     * Color of the command when output to the console.
     *
     * @var string|null
     */
    protected ?string $color = null;

    /**
     * Create a new DevCommand instance.
     *
     * @param  string  $command
     * @param  string|null  $name
     * @return void
     */
    public function __construct(protected string $command, protected ?string $name = null)
    {
        $this->name ??= collect(explode(' ', $command))->first();
    }

    /**
     * Get the command name.
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Set the command color.
     *
     * @param  string  $color
     * @return self
     */
    public function color(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Set the command color to blue.
     *
     * @return self
     */
    public function blue(): self
    {
        return $this->color(self::BLUE);
    }

    /**
     * Set the command color to purple.
     *
     * @return self
     */
    public function purple(): self
    {
        return $this->color(self::PURPLE);
    }

    /**
     * Set the command color to pink.
     *
     * @return self
     */
    public function pink(): self
    {
        return $this->color(self::PINK);
    }

    /**
     * Set the command color to orange.
     *
     * @return self
     */
    public function orange(): self
    {
        return $this->color(self::ORANGE);
    }

    /**
     * Set the command color to green.
     *
     * @return self
     */
    public function green(): self
    {
        return $this->color(self::GREEN);
    }

    /**
     * Set the command color to yellow.
     *
     * @return self
     */
    public function yellow(): self
    {
        return $this->color(self::YELLOW);
    }

    /**
     * Get the command as an array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'name' => $this->name,
            'color' => $this->color,
        ];
    }
}
