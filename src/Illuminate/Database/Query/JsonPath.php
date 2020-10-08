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
    public static function make(?string $scope = null)
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
     * The current scope, either a column name or null.
     *
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
    public function __get(string $name)
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
    public function allCells()
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
    public function allMembers()
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
    public function cell($index)
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
        $lastType = null;

        return array_reduce($this->path, static function ($carry, array $item) use (&$lastType) {
            switch ($item['type']) {
                case 'member':
                    $carry .= self::SEPARATOR . '"' . $item['name'] . '"';
                    break;
                case 'identifier':
                    $carry .= self::SEPARATOR . $item['name'];
                    break;
                case 'cell':
                    $carry .= '[' . $item['name'] . ']';
                    break;
                case 'match':
                    $carry .= (in_array($lastType, ['cell', 'match']) ? '.' : '') . '**"' . $item['name'] . '"';
                    break;
            }

            $lastType = $item['type'] ?? null;

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
    public function identifier(string $name)
    {
        return $this->addPathLeg('identifier', $name);
    }

    /**
     * Add a match path leg for a suffix.
     *
     * Generates paths using the `**` token. Paths generated are like
     * `$.foo**suffix` and `$.foo[0].**suffix`.
     *
     * @param string $suffix
     *
     * @return $this
     */
    public function matches(string $suffix)
    {
        return $this->addPathLeg('match', $suffix);
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
    public function member(string $name)
    {
        // If the member is an asterisk we want to select all members.
        if ($name === '*') {
            return $this->allMembers();
        }

        // If the member contains a double asterisk it's a match path leg
        // so we're going to want to pass that where appropriate.
        if (Str::contains($name, '**')) {
            [$prefix, $suffix] = explode('**', $name);

            if (! empty($prefix)) {
                return $this->member($prefix)->matches($suffix);
            }

            return $this->matches($suffix);
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
    public function offsetExists($offset)
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

    /**
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        // This method is intentionally empty
    }

    /**
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        // This method is intentionally empty
    }

    /**
     * Adds a leg to the Json path.
     *
     * @param string $type
     * @param string $name
     *
     * @return static
     */
    protected function addPathLeg(string $type, string $name)
    {
        $this->path[] = [
            'type' => $type,
            'name' => $name,
        ];

        return $this;
    }
}
