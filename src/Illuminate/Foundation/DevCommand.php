<?php

namespace Illuminate\Foundation;

class DevCommand
{
    public const BLUE = '#93c5fd';

    public const PURPLE = '#c4b5fd';

    public const PINK = '#fb7185';

    public const ORANGE = '#fdba74';

    public const GREEN = '#86efac';

    public const YELLOW = '#fcd34d';

    /**
     * Color of the command when output to the console.
     *
     * @var string
     */
    protected ?string $color = null;

    /**
     * Create a new DevCommand instance.
     *
     * @param string $command
     * @param null|string $name
     * @return void
     */
    public function __construct(protected string $command, protected ?string $name = null)
    {
        $this->name ??= collect(explode(' ', $command))->first();
    }

    /**
     * Set the command color.
     *
     * @param string $color
     * @return DevCommand
     */
    public function color(string $color): self
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Set the command color to blue.
     *
     * @return DevCommand
     */
    public function blue(): self
    {
        return $this->color(self::BLUE);
    }

    /**
     * Set the command color to purple.
     *
     * @return DevCommand
     */
    public function purple(): self
    {
        return $this->color(self::PURPLE);
    }

    /**
     * Set the command color to pink.
     *
     * @return DevCommand
     */
    public function pink(): self
    {
        return $this->color(self::PINK);
    }

    /**
     * Set the command color to orange.
     *
     * @return DevCommand
     */
    public function orange(): self
    {
        return $this->color(self::ORANGE);
    }

    /**
     * Set the command color to green.
     *
     * @return DevCommand
     */
    public function green(): self
    {
        return $this->color(self::GREEN);
    }

    /**
     * Set the command color to yellow.
     *
     * @return DevCommand
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
