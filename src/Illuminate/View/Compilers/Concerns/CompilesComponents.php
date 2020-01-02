<?php

namespace Illuminate\View\Compilers\Concerns;

use Illuminate\Support\Str;

trait CompilesComponents
{
    /**
     * The component name hash stack.
     *
     * @var array
     */
    protected static $componentHashStack = [];

    /**
     * Compile the component statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileComponent($expression)
    {
        [$component, $data] = strpos($expression, ',') !== false
                    ? array_map('trim', explode(',', trim($expression, '()'), 2))
                    : [trim($expression, '()'), null];

        static::$componentHashStack[] = $hash = sha1($component);

        if (Str::contains($component, ['::class', '\\'])) {
            return implode(PHP_EOL, [
                '<?php $__component'.$hash.' = app()->make('.$component.', '.($data ?: '[]').'); ?>',
                '<?php $__componentData'.$hash.' = $__component'.$hash.'->data(); ?>',
                '<?php $__componentDataOriginal'.$hash.' = []; ?>',
                '<?php foreach (array_keys($__componentData'.$hash.') as $__componentDataKey): ?>',
                '<?php if (isset($$__componentDataKey)) { $__componentDataOriginal'.$hash.'[$__componentDataKey] = $$__componentDataKey; } ?>',
                '<?php endforeach; ?>',
                '<?php extract($__componentData'.$hash.'); ?>',
                '<?php $__env->startComponent($__component'.$hash.'->view(), $__componentData'.$hash.'); ?>',
            ]);
        }

        return "<?php \$__env->startComponent{$expression}; ?>";
    }

    /**
     * Compile the end-component statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndComponent()
    {
        $hash = array_pop(static::$componentHashStack);

        return implode(PHP_EOL, [
            '<?php if (isset($__component'.$hash.')): ?>',
            '<?php foreach ($__componentDataOriginal'.$hash.' as $__componentDataOriginalKey => $__componentDataOriginalValue): ?>',
            '<?php $$__componentDataOriginalKey = $__componentDataOriginalValue; ?>',
            '<?php endforeach; ?>',
            '<?php unset($__component'.$hash.', $__componentData'.$hash.'); ?>',
            '<?php endif; ?>',
            '<?php echo $__env->renderComponent(); ?>'
        ]);
    }

    /**
     * Compile the slot statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileSlot($expression)
    {
        return "<?php \$__env->slot{$expression}; ?>";
    }

    /**
     * Compile the end-slot statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndSlot()
    {
        return '<?php $__env->endSlot(); ?>';
    }

    /**
     * Compile the component-first statements into valid PHP.
     *
     * @param  string  $expression
     * @return string
     */
    protected function compileComponentFirst($expression)
    {
        return "<?php \$__env->startComponentFirst{$expression}; ?>";
    }

    /**
     * Compile the end-component-first statements into valid PHP.
     *
     * @return string
     */
    protected function compileEndComponentFirst()
    {
        return $this->compileEndComponent();
    }
}
