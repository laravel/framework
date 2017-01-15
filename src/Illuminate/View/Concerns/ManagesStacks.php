<?php

namespace Illuminate\View\Concerns;

use InvalidArgumentException;

trait ManagesStacks
{
    /**
     * All of the finished, captured push sections.
     *
     * @var array
     */
    protected $pushes = [];

    /**
     * The stack of in-progress push sections.
     *
     * @var array
     */
    protected $pushStack = [];

    /**
     * Start injecting content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPush($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            $this->extendPush($section, $content);
        }
    }

    /**
     * Stop injecting content into a push section.
     *
     * @return string
     * @throws \InvalidArgumentException
     */
    public function stopPush()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a push stack without first starting one.');
        }

        return tap(array_pop($this->pushStack), function ($last) {
            $this->extendPush($last, ob_get_clean());
        });
    }

    /**
     * Append content to a given push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPush($section, $content)
    {
        if (! isset($this->pushes[$section])) {
            $this->pushes[$section] = [];
        }

        if (! isset($this->pushes[$section][$this->renderCount])) {
            $this->pushes[$section][$this->renderCount] = $content;
        } else {
            $this->pushes[$section][$this->renderCount] .= $content;
        }
    }

    /**
     * Get the string contents of a push section.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldPushContent($section, $default = '')
    {
        if (isset($this->pushes[$section])) {
            return implode($this->pushes[$section]);
        }

        return $default;
    }

    /**
     * Flush all of the stacks.
     *
     * @return void
     */
    public function flushStacks()
    {
        $this->pushes = [];
        $this->pushStack = [];
    }
}
