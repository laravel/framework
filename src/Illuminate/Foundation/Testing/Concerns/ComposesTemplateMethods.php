<?php

namespace Illuminate\Foundation\Testing\Concerns;

trait ComposesTemplateMethods
{
    /**
     * Cache of template methods for given test cases.
     *
     * @var array
     */
    protected static $traitTemplateMethods = [];

    /**
     * Get a list of template methods from traits.
     *
     * @param  string  $template
     * @return array
     */
    protected static function getTraitTemplateMethods($template)
    {
        $class = static::class;

        if (! isset(static::$traitTemplateMethods[$class])) {
            $templateMethods = ['setUp', 'tearDown'];
            static::$traitTemplateMethods[$class] = array_fill_keys($templateMethods, []);

            foreach (class_uses_recursive($class) as $trait) {
                foreach ($templateMethods as $templateMethod) {
                    if (method_exists($class, $method = $templateMethod.class_basename($trait))) {
                        static::$traitTemplateMethods[$class][$templateMethod][] = $method;
                    }
                }
            }
        }

        return static::$traitTemplateMethods[$class][$template];
    }

    /**
     * Call composed template methods for all used traits.
     *
     * @param  string  $template
     * @return void
     */
    protected function callTraitTemplateMethods($template)
    {
        foreach (static::getTraitTemplateMethods($template) as $method) {
            $this->$method();
        }
    }
}
