<?php

namespace Illuminate\Support;

use Exception;
use Illuminate\Support\Exceptions\PasswordInvalidArgumentsException;
use Illuminate\Support\Exceptions\PasswordInvalidLengthArgument;

class Password
{
    /**
     * Password length.
     *
     * @var int
     */
    protected int $length = 32;

    /**
     * Using letters.
     *
     * @var bool
     */
    protected bool $withLetters = true;

    /**
     * Using numbers.
     *
     * @var bool
     */
    protected bool $withNumbers = true;

    /**
     * Using special symbols.
     *
     * @var bool
     */
    protected bool $withSymbols = true;

    /**
     * Using spaces.
     *
     * @var bool
     */
    protected bool $withSpaces = false;

    /**
     * Valid letters.
     *
     * @var string[]
     */
    protected array $allowedLetters = [
        'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k',
        'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v',
        'w', 'x', 'y', 'z', 'A', 'B', 'C', 'D', 'E', 'F', 'G',
        'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z',
    ];

    /**
     * Valid numbers.
     *
     * @var string[]
     */
    protected array $allowedNumbers = [
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
    ];

    /**
     * Valid special symbols.
     *
     * @var string[]
     */
    protected array $allowedSymbols = [
        '~', '!', '#', '$', '%', '^', '&', '*', '(', ')', '-',
        '_', '.', ',', '<', '>', '?', '/', '\\', '{', '}', '[',
        ']', '|', ':', ';',
    ];

    /**
     * Setting the length.
     *
     * @param  int  $length
     * @return $this
     */
    public function setLength(int $length): self
    {
        $this->length = $length;

        return $this;
    }

    /**
     * Setting the flag for using letters.
     *
     * @param  bool  $withLetters
     * @return $this
     */
    public function withLetters(bool $withLetters): self
    {
        $this->withLetters = $withLetters;

        return $this;
    }

    /**
     * Setting the flag for using numbers.
     *
     * @param  bool  $withNumbers
     * @return $this
     */
    public function withNumbers(bool $withNumbers): self
    {
        $this->withNumbers = $withNumbers;

        return $this;
    }

    /**
     * Setting the flag for using special symbols.
     *
     * @param  bool  $withSymbols
     * @return $this
     */
    public function withSymbols(bool $withSymbols): self
    {
        $this->withSymbols = $withSymbols;

        return $this;
    }

    /**
     * @param  bool  $withSpaces
     * @return $this
     */
    public function withSpaces(bool $withSpaces): self
    {
        $this->withSpaces = $withSpaces;

        return $this;
    }

    /**
     * Setting valid letters.
     *
     * @param  string[]  $letters
     * @return $this
     */
    public function allowedLetters(array $letters): self
    {
        $this->allowedLetters = $letters;

        return $this;
    }

    /**
     * Setting valid numbers.
     *
     * @param  string[]  $numbers
     * @return $this
     */
    public function allowedNumbers(array $numbers): self
    {
        $this->allowedNumbers = $numbers;

        return $this;
    }

    /**
     * Setting valid special symbols.
     *
     * @param  string[]  $symbols
     * @return $this
     */
    public function allowedSymbols(array $symbols): self
    {
        $this->allowedSymbols = $symbols;

        return $this;
    }

    /**
     * Generate password.
     *
     * @return string
     *
     * @throws Exception
     */
    public function build(): string
    {
        $minLength = (int) $this->withLetters +
            (int) $this->withNumbers +
            (int) $this->withSymbols +
            (int) $this->withSpaces;

        if ($this->length < $minLength) {
            throw new PasswordInvalidLengthArgument(
                sprintf('Minimum password length must be %d characters', $minLength)
            );
        }

        $letters = $this->allowedLetters;
        $numbers = $this->allowedNumbers;
        $symbols = $this->allowedSymbols;

        $combination = (new Collection)
            ->when($this->withLetters, fn ($c) => $c->merge($letters))
            ->when($this->withNumbers, fn ($c) => $c->merge($numbers))
            ->when($this->withSymbols, fn ($c) => $c->merge($symbols));

        if ($combination->count() === 0) {
            throw new PasswordInvalidArgumentsException('Not a valid set of parameters for generating a password');
        }

        $chars = new Collection();
        while ($chars->count() < $this->length) {
            $chars = $chars->merge($combination);
        }

        return $chars
            ->shuffle()
            ->take($this->length)
            ->when($this->withLetters, fn ($c) => $c->splice(1)->merge($letters[array_rand($letters)]))
            ->when($this->withNumbers, fn ($c) => $c->splice(1)->merge($numbers[array_rand($numbers)]))
            ->when($this->withSymbols, fn ($c) => $c->splice(1)->merge($symbols[array_rand($symbols)]))
            ->when($this->withSpaces, function ($c) use ($minLength) {
                $count = random_int(1, max(1, intval(($this->length - 2) / $minLength)));
                $result = $c->slice($count);

                for ($i = 0; $i < $count; $i++) {
                    $pos = random_int(1, max(1, $result->count() - 2));
                    $result = $result
                        ->slice(0, $pos)
                        ->add(' ')
                        ->merge($result->slice($pos));
                }

                return $result;
            })
            ->implode('');
    }
}
