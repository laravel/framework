<?php

namespace Illuminate\Database\Query;

use Illuminate\Support\Str;

class JsonPath extends Expression implements \ArrayAccess
{
    public const SEPARATOR = '.';

    /**
     * Create a new instance of the JsonPath expression.
     *
     * @param string|null $scope
     *
     * @return static
     */
    public static function make(?string $scope = null): self
    {
        return new static($scope);
    }

    /**
     * The Json path.
     *
     * @var array
     */
    protected $path = [];

    /**
     * @var string|null
     */
    protected $scope;

    /**
     * @param string|null $scope
     */
    public function __construct(?string $scope = null)
    {
        parent::__construct(null);
        $this->scope = $scope ?? '$';
    }

    /**
     * Allows for a member leg path to be set using magic properties.
     *
     * @param string $name
     *
     * @return static
     */
    public function __get(string $name): self
    {
        return $this->member($name);
    }

    /**
     *
     * Add a path leg for all cells.
     *
     * Uses the asterisk wildcard to select all child cells.
     * Creates paths like `$.foo[*].name`.
     *
     * @return static
     */
    public function allCells(): self
    {
        return $this->cell('*');
    }

    /**
     * Add a path leg for all members.
     *
     * Uses the asterisk wildcard to select all child members.
     * Creates paths like `$.foo[0].*`.
     *
     * @return static
     */
    public function allMembers(): self
    {
        return $this->identifier('*');
    }

    /**
     * Add a cell index path leg.
     *
     * Cell indexes are Json array keys. In the example
     * `$.foo[0].name` the 0 in `[0]` is the cell index.
     *
     * @param string|int $index Should be either a positive index or an asterisk
     *
     * @return static
     */
    public function cell($index): self
    {
        return $this->addPathLeg('cell', $index);
    }

    /**
     * Get the value of the expression.
     *
     * @return mixed
     */
    public function getValue()
    {
        return array_reduce($this->path, static function ($carry, array $item) {
            switch ($item['type']) {
                case 'member':
                    return $carry . self::SEPARATOR . '"' . $item['name'] . '"';
                case 'identifier':
                    return $carry . self::SEPARATOR . $item['name'];
                case 'cell':
                    return $carry . '[' . $item['name'] . ']';
                case 'match':
                    return $carry . self::SEPARATOR . ($item['prefix'] ? '[' . $item['prefix'] . ']' : '') . '**' . $item['suffix'];
            }

            return $carry;
        }, $this->scope);
    }

    /**
     * Add a path for an ECMAScript identifier.
     *
     * @param string $name
     *
     * @return $this
     *
     * @link http://www.ecma-international.org/ecma-262/5.1/#sec-7.6
     */
    public function identifier(string $name): self
    {
        return $this->addPathLeg('identifier', $name);
    }

    /**
     * Add a match path leg for a suffix and optional prefix.
     *
     * Generates paths using the `**` token. Paths generated are like
     * `$.foo[0].[prefix]**suffix` and `$.foo[0].**suffix`.
     *
     * @param string      $suffix
     * @param string|null $prefix
     *
     * @return $this
     */
    public function matches(string $suffix, ?string $prefix = null): self
    {
        return $this->addPathLeg('match', [
            'prefix' => $prefix,
            'suffix' => $suffix,
        ]);
    }

    /**
     * Add a member path leg.
     *
     * Member path legs are Json object keys. In the example
     * `$.foo[0].name` both `foo` and `name` are members.
     *
     * @param string $name
     *
     * @return static
     */
    public function member(string $name): self
    {
        // If the member is an asterisk we want to select all members.
        if ($name === '*') {
            return $this->allMembers();
        }

        // If the member contains a double asterisk it's a match path leg
        // so we're going to want to pass that where appropriate.
        if (Str::contains($name, '**')) {
            return $this->matches(...array_reverse(explode('**', $name)));
        }

        return $this->addPathLeg('member', $name);
    }

    /**
     * Always returns false as there's no real reason to
     * check whether an index exists.
     *
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return false;
    }

    /**
     * Allows for the setting of cell index as if accessing the object as
     * an array.
     *
     * @param mixed $offset
     *
     * @return static
     */
    public function offsetGet($offset)
    {
        return $this->cell($offset);
    }

    public function offsetSet($offset, $value): void
    {
        // This method is intentionally empty
    }

    public function offsetUnset($offset): void
    {
        // This method is intentionally empty
    }

    /**
     * Adds a leg to the Json path.
     *
     * @param string       $type
     * @param string|array $details
     *
     * @return static
     */
    protected function addPathLeg(string $type, $details): self
    {
        $path = compact('type');

        if (is_array($details)) {
            $path = array_merge($path, $details);
        } else {
            $path['name'] = $details;
        }

        $this->path[] = $path;

        return $this;
    }
}
