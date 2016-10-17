<?php

namespace Illuminate\Validation\Rules;

class Mimes
{
    /**
     * The mimes and mimetypes.
     *
     * @var array
     */
    protected $mimes = [];

    /**
     * Create a new mime rule instance.
     *
     * @param array $mimes
     */
    public function __construct(array $mimes)
    {
        $this->mimes = $mimes;
    }

    /**
     * Set a mimetype.
     *
     * @param string|array $value
     *
     * @return $this
     */
    public function type($value)
    {
        if (is_array($value)) {
            foreach ($value as $type) {
                $this->mimes['types'][] = $type;
            }
        } else {
            $this->mimes['types'][] = $value;
        }

        return $this;
    }

    /**
     * Set a mime.
     *
     * @param string|array $value
     *
     * @return $this
     */
    public function rule($value)
    {
        if (is_array($value)) {
            foreach ($value as $rule) {
                $this->mimes['rules'][] = $rule;
            }
        } else {
            $this->mimes['rules'][] = $value;
        }

        return $this;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString()
    {
        if (isset($this->mimes['types'])) {
            return 'mimetypes:'.implode(',', $this->mimes['types']);
        }

        if (isset($this->mimes['rules'])) {
            return 'mimes:'.implode(',', $this->mimes['rules']);
        }
    }
}
