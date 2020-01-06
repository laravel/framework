<?php

namespace Illuminate\Database\Eloquent\Concerns;

trait AppendsToJson
{
    /**
     * @param  string  $field
     * @param  string  $key
     * @param  mixed   $value
     */
    public function appendToArray($field, $key, $value)
    {
        $temp = $this->{$field};
        $temp[$key] = $value;

        $this->{$field} = $temp;
    }

    /**
     * @param  string  $field
     * @param  string  $key
     * @param  mixed   $value
     */
    public function appendToCollection($field, $key, $value)
    {
        $this->appendToArray($field, $key, $value);
    }

    /**
     * @param  string  $field
     * @param  string  $key
     * @param  mixed   $value
     */
    public function appendToObject($field, $key, $value)
    {
        $temp = $this->{$field};

        if (is_null($temp)) {
            $temp = new \stdClass;
        }

        $temp->{$key} = $value;

        $this->{$field} = $temp;
    }
}
