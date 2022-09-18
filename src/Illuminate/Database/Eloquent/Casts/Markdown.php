<?php

namespace Illuminate\Database\Eloquent\Casts;

use Illuminate\Support\Str;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\Castable;

class Markdown implements Castable
{
    /**
     * Get the name of the caster class to use when casting from / to this cast target.
     *
     * @param  array  $arguments
     * @return string
     */
    public static function castUsing(array $arguments)
    {
        return new class($arguments) implements CastsAttributes {
            /**
             * Instruct it to convert string using inline HTML converter
             *
             * @var bool
             */
            protected $useInlineConverter = false;

            /**
             * Hold user defined options
             *
             * @var array
             */
            protected $options = [];

            /**
             * Create a new instance of the class.
             *
             * @param bool $useInlineConverter
             * @param array $options
             * @return void
             */
            public function __construct($options = [])
            {
                $this->setOptions($options);
            }

            /**
             * Parse user defined options
             *
             * @param array $options
             * @return void
             */
            protected function setOptions($options)
            {
                $this->options = collect($options)->flatMap(function ($values) {
                    $parts = explode("=", $values);

                    // Use Inline HTML converter
                    if ($parts[0] == "inline"){
                        $this->useInlineConverter = true;
                    }

                    // Execlude any option defined without value
                    if (!isset($parts[1])){
                        return [];
                    }

                    // Properly type cast option integer value
                    $value = is_numeric($parts[1]) ? (int) $parts[1] : $parts[1];

                    return [$parts[0] => $value];
                });
            }

            /**
             * Get options as array
             *
             * @return mixed
             */
            protected function getOptions()
            {
                return $this->options->undot()->toArray();
            }

            /**
             * Cast the given value into markdown.
             *
             * @param \Illuminate\Database\Eloquent\Model $model
             * @param string $key
             * @param mixed $value
             * @param array $attributes
             * @return string
             */
            public function get($model, $key, $value, $attributes)
            {
                if ($this->useInlineConverter) {
                    return Str::inlineMarkdown($value, $this->getOptions());
                }

                return Str::markdown($value, $this->getOptions());
            }

            /**
             * Prepare the given value for storage.
             *
             * @param \Illuminate\Database\Eloquent\Model $model
             * @param string $key
             * @param mixed $value
             * @param array $attributes
             * @return string
             */
            public function set($model, $key, $value, $attributes)
            {
                return $value;
            }
        };
    }
}
