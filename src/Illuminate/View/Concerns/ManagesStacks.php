<?php

namespace Illuminate\View\Concerns;

use Illuminate\Support\Str;
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
     * All of the finished, captured prepend sections.
     *
     * @var array
     */
    protected $prepends = [];

    /**
     * The stack of in-progress push sections.
     *
     * @var array
     */
    protected $pushStack = [];

    /**
     * Push and prepend operations in the order they ran.
     *
     * @var array<int, array{section: string, content: string, prepend: bool}>
     */
    protected $stackOperations = [];

    /**
     * Placeholders emitted by @stack that are replaced after rendering.
     *
     * @var array<int, array{token: string, section: string, snapshot: string, was_empty: bool, cursor: int}>
     */
    protected $deferredStackPlaceholders = [];

    /**
     * Monotonic counter used to keep stack placeholders unique.
     *
     * @var int
     */
    protected $stackPlaceholderSequence = 0;

    /**
     * Salt used to generate stack placeholder tokens.
     *
     * @var string
     */
    protected $stackPlaceholderSalt;

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
     *
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

        $this->stackOperations[] = [
            'section' => $section,
            'content' => $content,
            'prepend' => false,
        ];
    }

    /**
     * Start prepending content into a push section.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    public function startPrepend($section, $content = '')
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            $this->extendPrepend($section, $content);
        }
    }

    /**
     * Stop prepending content into a push section.
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function stopPrepend()
    {
        if (empty($this->pushStack)) {
            throw new InvalidArgumentException('Cannot end a prepend operation without first starting one.');
        }

        return tap(array_pop($this->pushStack), function ($last) {
            $this->extendPrepend($last, ob_get_clean());
        });
    }

    /**
     * Prepend content to a given stack.
     *
     * @param  string  $section
     * @param  string  $content
     * @return void
     */
    protected function extendPrepend($section, $content)
    {
        if (! isset($this->prepends[$section])) {
            $this->prepends[$section] = [];
        }

        if (! isset($this->prepends[$section][$this->renderCount])) {
            $this->prepends[$section][$this->renderCount] = $content;
        } else {
            $this->prepends[$section][$this->renderCount] = $content.$this->prepends[$section][$this->renderCount];
        }

        $this->stackOperations[] = [
            'section' => $section,
            'content' => $content,
            'prepend' => true,
        ];
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
        if ($this->isStackEmpty($section)) {
            return $default;
        }

        $output = '';

        if (isset($this->prepends[$section])) {
            $output .= implode('', array_reverse($this->prepends[$section]));
        }

        if (isset($this->pushes[$section])) {
            $output .= implode('', $this->pushes[$section]);
        }

        return $output;
    }

    /**
     * Get a placeholder for a stack that is filled in when rendering finishes.
     *
     * @stack is compiled to this instead of yielding immediately. Pushes that
     * run after the directive are then included, while an earlier @stack for
     * the same name keeps the contents it had when it rendered.
     *
     * @param  string  $section
     * @param  string  $default
     * @return string
     */
    public function yieldStackPlaceholder($section, $default = '')
    {
        $token = '##stack-placeholder-'.hash('xxh128', $this->stackPlaceholderSalt().'|'.$this->stackPlaceholderSequence).'##';

        $this->stackPlaceholderSequence++;

        $this->deferredStackPlaceholders[] = [
            'token' => $token,
            'section' => $section,
            'snapshot' => $this->yieldPushContent($section, $default),
            'was_empty' => $this->isStackEmpty($section),
            'cursor' => count($this->stackOperations),
        ];

        return $token;
    }

    /**
     * Replace stack placeholders in rendered output.
     *
     * @param  mixed  $rendered
     * @return mixed
     */
    public function substituteDeferredStacks($rendered)
    {
        if ($this->deferredStackPlaceholders === []) {
            return $rendered;
        }

        $groups = [];

        foreach ($this->deferredStackPlaceholders as $index => $placeholder) {
            $groups[$placeholder['section']][] = $index;
        }

        $replacements = [];

        foreach ($groups as $indexes) {
            $last = $indexes[array_key_last($indexes)];

            foreach ($indexes as $index) {
                $placeholder = $this->deferredStackPlaceholders[$index];

                $replacements[$placeholder['token']] = $index === $last
                    ? $this->renderDeferredStack($placeholder)
                    : $placeholder['snapshot'];
            }
        }

        return $this->replaceDeferredStackPlaceholders($rendered, $replacements);
    }

    /**
     * Render one stack, appending only operations that ran after its placeholder.
     *
     * Later pushes are appended in execution order instead of being folded back
     * into the render-count buckets yieldPushContent() uses. Those buckets would
     * move a late push ahead of content that had already been rendered.
     *
     * @param  array{token: string, section: string, snapshot: string, was_empty: bool, cursor: int}  $placeholder
     * @return string
     */
    protected function renderDeferredStack(array $placeholder)
    {
        $additions = [];

        foreach (array_slice($this->stackOperations, $placeholder['cursor']) as $operation) {
            if ($operation['section'] === $placeholder['section']) {
                $additions[] = $operation;
            }
        }

        if ($additions === []) {
            return $placeholder['snapshot'];
        }

        // An empty stack's snapshot is the @stack default. Real content replaces it.
        $output = $placeholder['was_empty'] ? '' : $placeholder['snapshot'];

        foreach ($additions as $operation) {
            $output = $operation['prepend']
                ? $operation['content'].$output
                : $output.$operation['content'];
        }

        return $output;
    }

    /**
     * Replace stack placeholder tokens in a rendered string or array of strings.
     *
     * @param  mixed  $rendered
     * @param  array<string, string>  $replacements
     * @return mixed
     */
    protected function replaceDeferredStackPlaceholders($rendered, array $replacements)
    {
        if (is_string($rendered)) {
            return strtr($rendered, $replacements);
        }

        if (is_array($rendered)) {
            foreach ($rendered as $key => $value) {
                $rendered[$key] = $this->replaceDeferredStackPlaceholders($value, $replacements);
            }
        }

        return $rendered;
    }

    /**
     * Get the salt used to generate stack placeholder tokens.
     *
     * @return string
     */
    protected function stackPlaceholderSalt()
    {
        if (! isset($this->stackPlaceholderSalt)) {
            $this->stackPlaceholderSalt = Str::random(40);
        }

        return $this->stackPlaceholderSalt;
    }

    /**
     * Determine if the stack has any content in it.
     */
    public function isStackEmpty(string $section): bool
    {
        return ! isset($this->pushes[$section]) && ! isset($this->prepends[$section]);
    }

    /**
     * Flush all of the stacks.
     *
     * @return void
     */
    public function flushStacks()
    {
        $this->pushes = [];
        $this->prepends = [];
        $this->pushStack = [];
        $this->stackOperations = [];
        $this->deferredStackPlaceholders = [];
    }
}
