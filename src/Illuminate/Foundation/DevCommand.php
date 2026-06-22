<?php

namespace Illuminate\Foundation;

class DevCommand
{
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
    public function __construct(
        protected string $command,
        protected array $source,
        protected ?string $name = null,
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
     * @return array{command: string, name: string, color: string|null, source: string}
     */
    public function toArray(): array
    {
        return [
            'command' => $this->command,
            'name' => $this->name,
            'color' => $this->color,
            'source' => $this->sourceFormatted(),
        ];
    }

    /**
     * Format the source information for display.
     *
     * @return string
     */
    protected function sourceFormatted(): string
    {
        $file = $this->source['file'] ?? null;
        $line = $this->source['line'] ?? null;
        $class = $this->source['class'] ?? null;
        $function = $this->source['function'] ?? null;

        if ($class) {
            return "{$class}@{$function}";
        }

        return implode(':', array_filter([$file, $line]));
    }
}
