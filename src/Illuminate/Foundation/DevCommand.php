<?php

namespace Illuminate\Foundation;

class DevCommand
{
    /**
     * The priority level for default commands that are registered by the framework.
     */
    const PRIORITY_DEFAULT = 0;

    /**
     * The priority level for commands that are registered by packages in the vendor directory.
     */
    const PRIORITY_VENDOR = 1;

    /**
     * The priority level for commands that are registered by the user in their application.
     */
    const PRIORITY_USERLAND = 2;

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
     * @param  array{'file': string, 'line': int, 'class'?: string, 'function'?: string}  $source
     * @param  string|null  $name
     * @param  int  $priority
     * @return void
     */
    public function __construct(
        protected string $command,
        protected array $source,
        protected ?string $name = null,
        protected int $priority = self::PRIORITY_USERLAND,
    ) {
        $this->name ??= self::nameFromCommand($command);
    }

    /**
     * Derive the name from a command string.
     *
     * @param  string  $command
     * @return string
     */
    public static function nameFromCommand(string $command): string
    {
        return strstr($command, ' ', true) ?: $command;
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
     * Get the command priority.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority;
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
        return $this->color(DevCommandColor::BLUE->value);
    }

    /**
     * Set the command color to purple.
     *
     * @return self
     */
    public function purple(): self
    {
        return $this->color(DevCommandColor::PURPLE->value);
    }

    /**
     * Set the command color to pink.
     *
     * @return self
     */
    public function pink(): self
    {
        return $this->color(DevCommandColor::PINK->value);
    }

    /**
     * Set the command color to orange.
     *
     * @return self
     */
    public function orange(): self
    {
        return $this->color(DevCommandColor::ORANGE->value);
    }

    /**
     * Set the command color to green.
     *
     * @return self
     */
    public function green(): self
    {
        return $this->color(DevCommandColor::GREEN->value);
    }

    /**
     * Set the command color to yellow.
     *
     * @return self
     */
    public function yellow(): self
    {
        return $this->color(DevCommandColor::YELLOW->value);
    }

    /**
     * Get the command as an array.
     *
     * @return array{command: string, name: string, color: string|null, source: array{'file': string, 'line': int, 'class'?: string, 'function'?: string, 'priority': int}}
     */
    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'name' => $this->name,
            'color' => $this->color,
            'source' => $this->source,
            'priority' => $this->priority,
        ];
    }
}
