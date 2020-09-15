<?php

namespace Illuminate\Routing;

trait RouteRegexConstraintTrait
{
    /**
     * Set a number as regular expression requirement on the route.
     *
     * @param  string $names
     * @return $this
     */
    public function whereNumber(...$names)
    {
        foreach ($names as $name) {
            is_string($name)
                ? $this->where($name, '[0-9]+')
                : $this->whereNumber(...$name);
        }

        return $this;
    }

    /**
     * Set any character as regular expression requirement on the route.
     *
     * @param  string|array $parameter
     * @return $this
     */
    public function whereAnyChar(...$names)
    {
        foreach ($names as $name) {
            is_string($name)
                ? $this->where($name, '[A-Za-z]+')
                : $this->whereAnyChar(...$name);
        }

        return $this;
    }

    /**
     * Set lower character as regular expression requirement on the route.
     *
     * @param  string|array $parameter
     * @return $this
     */
    public function whereLowerChar(...$names)
    {
        foreach ($names as $name) {
            is_string($name)
                ? $this->where($name, '[a-z]+')
                : $this->whereLowerChar(...$name);
        }

        return $this;
    }

    /**
     * Set upper character as regular expression requirement on the route.
     *
     * @param  string|array $parameter
     * @return $this
     */
    public function whereUpperChar(...$names)
    {
        foreach ($names as $name) {
            is_string($name)
                ? $this->where($name, '[A-Z]+')
                : $this->whereUpperChar(...$name);
        }

        return $this;
    }

    /**
     * Set slash character as regular expression requirement on the route.
     *
     * @param  string|array $parameter
     * @return $this
     */
    public function whereSlash(string $name)
    {
        return $this->where($name, '.*');
    }
}
