<?php

namespace Illuminate\Testing\Fluent;

use Closure;
use Dom\HTMLDocument;
use Dom\Element;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

class AssertableHtml
{
    use Conditionable, Macroable, Tappable;

    /**
     * The parsed HTML document.
     *
     * @var \Dom\HTMLDocument
     */
    protected HTMLDocument $document;

    /**
     * The current scope (the document root or a scoped element).
     *
     * @var \Dom\Element|\Dom\HTMLDocument
     */
    protected Element|HTMLDocument $scope;

    /**
     * The selector path to the current scope.
     *
     * @var string|null
     */
    protected ?string $selector;

    /**
     * Create a new assertable HTML instance.
     *
     * @param  \Dom\HTMLDocument  $document
     * @param  \Dom\Element|\Dom\HTMLDocument|null  $scope
     * @param  string|null  $selector
     */
    protected function __construct(HTMLDocument $document, Element|HTMLDocument|null $scope = null, ?string $selector = null)
    {
        $this->document = $document;
        $this->scope = $scope ?? $document;
        $this->selector = $selector;
    }

    /**
     * Create a new instance from a test response.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     * @return static
     */
    public static function fromResponse(TestResponse $response, int $options = LIBXML_NOERROR): static
    {
        return new static(
            HTMLDocument::createFromString($response->getContent(), $options),
        );
    }

    /**
     * Create a new instance from an HTML string.
     *
     * @param  string  $html
     * @param  int  $options
     * @return static
     */
    public static function fromString(string $html, int $options = LIBXML_NOERROR): static
    {
        return new static(
            HTMLDocument::createFromString($html, $options),
        );
    }

    /**
     * Assert that an element matching the selector exists, with optional count and scoped callback.
     *
     * @param  string  $selector
     * @param  int|\Closure|null  $countOrCallback
     * @param  \Closure|null  $callback
     * @return $this
     */
    public function has(string $selector, int|Closure|null $countOrCallback = null, ?Closure $callback = null): static
    {
        $element = $this->findOrFail($selector);

        if (is_int($countOrCallback)) {
            $actual = $this->scope->querySelectorAll($selector)->length;

            if ($actual !== $countOrCallback) {
                $this->fail(
                    "Failed asserting that [{$selector}] matches {$countOrCallback} element(s), found {$actual}.",
                    $selector
                );
            }

            if ($callback !== null) {
                $callback(new static($this->document, $element, $this->buildSelector($selector)));
            }
        } elseif ($countOrCallback instanceof Closure) {
            $countOrCallback(new static($this->document, $element, $this->buildSelector($selector)));
        }

        return $this;
    }

    /**
     * Assert that elements matching all of the given selectors exist.
     *
     * @param  array<int, string>  $selectors
     * @return $this
     */
    public function hasAll(array $selectors): static
    {
        foreach ($selectors as $selector) {
            $this->has($selector);
        }

        return $this;
    }

    /**
     * Assert that no element matches the selector.
     *
     * @param  string  $selector
     * @return $this
     */
    public function missing(string $selector): static
    {
        if ($this->scope->querySelector($selector) !== null) {
            $this->fail("Failed asserting that [{$selector}] is absent.", $selector);
        }

        return $this;
    }

    /**
     * Assert that no elements matching any of the given selectors exist.
     *
     * @param  array<int, string>  $selectors
     * @return $this
     */
    public function missingAll(array $selectors): static
    {
        foreach ($selectors as $selector) {
            $this->missing($selector);
        }

        return $this;
    }

    /**
     * Assert that exactly N elements match the selector.
     *
     * @param  string  $selector
     * @param  int  $expected
     * @return $this
     */
    public function count(string $selector, int $expected): static
    {
        $actual = $this->scope->querySelectorAll($selector)->length;

        if ($actual !== $expected) {
            $this->fail(
                "Failed asserting that [{$selector}] matches {$expected} element(s), found {$actual}.",
                $selector
            );
        }

        return $this;
    }

    /**
     * Assert that the matched element's text equals the expected value, or passes a truth test.
     *
     * @param  string  $selector
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function where(string $selector, string|Closure $expected): static
    {
        $actual = trim($this->findOrFail($selector)->textContent);

        if ($expected instanceof Closure) {
            if (! $expected($actual)) {
                $this->fail(
                    "Failed asserting that [{$selector}] text was marked as invalid using a closure.",
                    $selector
                );
            }

            return $this;
        }

        if ($actual !== $expected) {
            $this->fail(
                "Failed asserting that [{$selector}] text equals [{$expected}], found [{$actual}].",
                $selector
            );
        }

        return $this;
    }


    /**
     * Assert multiple selector → text (or closure) pairs at once.
     *
     * @param  array<string, string|\Closure>  $bindings
     * @return $this
     */
    public function whereAll(array $bindings): static
    {
        foreach ($bindings as $selector => $expected) {
            $this->where($selector, $expected);
        }

        return $this;
    }

    /**
     * Assert that the matched element's text does not equal the expected value, or does not pass a truth test.
     *
     * @param  string  $selector
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereNot(string $selector, string|Closure $expected): static
    {
        $actual = trim($this->findOrFail($selector)->textContent);

        if ($expected instanceof Closure) {
            if ($expected($actual)) {
                $this->fail(
                    "Failed asserting that [{$selector}] text was marked as invalid using a closure.",
                    $selector
                );
            }

            return $this;
        }

        if ($actual === $expected) {
            $this->fail(
                "Failed asserting that [{$selector}] text does not equal [{$expected}].",
                $selector
            );
        }

        return $this;
    }

    /**
     * Assert that the matched element's attribute equals the expected value, or passes a truth test.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereAttr(string $selector, string $attribute, string|Closure $expected): static
    {
        $actual = $this->findOrFail($selector)->getAttribute($attribute);

        if ($expected instanceof Closure) {
            if (! $expected($actual)) {
                $this->fail(
                    "Failed asserting that [{$selector}] attribute [{$attribute}] was marked as invalid using a closure.",
                    $selector
                );
            }

            return $this;
        }

        if ($actual !== $expected) {
            $this->fail(
                "Failed asserting that [{$selector}] attribute [{$attribute}] equals [{$expected}], found [{$actual}].",
                $selector
            );
        }

        return $this;
    }

    /**
     * Assert that the matched element has the given attribute.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @return $this
     */
    public function hasAttr(string $selector, string $attribute): static
    {
        if (! $this->findOrFail($selector)->hasAttribute($attribute)) {
            $this->fail(
                "Failed asserting that [{$selector}] has attribute [{$attribute}].",
                $selector
            );
        }

        return $this;
    }

    /**
     * Assert that the matched element's attribute does not equal the expected value, or does not pass a truth test.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereNotAttr(string $selector, string $attribute, string|Closure $expected): static
    {
        $actual = $this->findOrFail($selector)->getAttribute($attribute);

        if ($expected instanceof Closure) {
            if ($expected($actual)) {
                $this->fail(
                    "Failed asserting that [{$selector}] attribute [{$attribute}] was marked as invalid using a closure.",
                    $selector
                );
            }

            return $this;
        }

        if ($actual === $expected) {
            $this->fail(
                "Failed asserting that [{$selector}] attribute [{$attribute}] does not equal [{$expected}].",
                $selector
            );
        }

        return $this;
    }

    /**
     * Assert multiple attribute → value (or closure) pairs on the matched element at once.
     *
     * @param  string  $selector
     * @param  array<string, string|\Closure>  $bindings
     * @return $this
     */
    public function whereAttrs(string $selector, array $bindings): static
    {
        foreach ($bindings as $attribute => $expected) {
            $this->whereAttr($selector, $attribute, $expected);
        }

        return $this;
    }

    /**
     * Alias for whereAttr().
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereAttribute(string $selector, string $attribute, string|Closure $expected): static
    {
        return $this->whereAttr($selector, $attribute, $expected);
    }

    /**
     * Alias for whereNotAttr().
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereNotAttribute(string $selector, string $attribute, string|Closure $expected): static
    {
        return $this->whereNotAttr($selector, $attribute, $expected);
    }

    /**
     * Alias for whereAttrs().
     *
     * @param  string  $selector
     * @param  array<string, string|\Closure>  $bindings
     * @return $this
     */
    public function whereAttributes(string $selector, array $bindings): static
    {
        return $this->whereAttrs($selector, $bindings);
    }

    /**
     * Alias for hasAttr().
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @return $this
     */
    public function hasAttribute(string $selector, string $attribute): static
    {
        return $this->hasAttr($selector, $attribute);
    }

    /**
     * Assert that the matched element does not have the given attribute.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @return $this
     */
    public function missingAttr(string $selector, string $attribute): static
    {
        if ($this->findOrFail($selector)->hasAttribute($attribute)) {
            $this->fail(
                "Failed asserting that [{$selector}] does not have attribute [{$attribute}].",
                $selector
            );
        }

        return $this;
    }

    /**
     * Scope into the first element matching the selector and invoke the callback.
     *
     * @param  string  $selector
     * @param  \Closure  $callback
     * @return $this
     */
    public function scope(string $selector, Closure $callback): static
    {
        $callback(new static($this->document, $this->findOrFail($selector), $this->buildSelector($selector)));

        return $this;
    }

    /**
     * Iterate all elements matching the selector, invoking the callback for each.
     *
     * @param  string  $selector
     * @param  \Closure  $callback
     * @return $this
     */
    public function each(string $selector, Closure $callback): static
    {
        $nodes = $this->scope->querySelectorAll($selector);

        if ($nodes->length === 0) {
            $this->fail("Failed asserting that any elements match [{$selector}].", $selector);
        }

        foreach ($nodes as $index => $node) {
            try {
                $callback(new static($this->document, $node, $this->buildSelector($selector)), $index);
            } catch (AssertionFailedError $e) {
                PHPUnit::fail("Failed assertion on element [{$selector}] at index [{$index}]:\n".$e->getMessage());
            }
        }

        return $this;
    }

    /**
     * Dump the current scope's HTML for debugging.
     *
     * @return $this
     */
    public function dump(): static
    {
        dump($this->getHtml());

        return $this;
    }

    /**
     * Dump the current scope's HTML and stop execution.
     *
     * @return never
     */
    public function dd(): never
    {
        $this->dump();

        exit(1);
    }

    /**
     * Fail an assertion with the full selector path and scope HTML appended to the message.
     *
     * @param  string  $message
     * @param  string|null  $selector
     * @return never
     */
    protected function fail(string $message, ?string $selector = null): never
    {
        $parts = [$message];

        $path = $this->buildSelector($selector);

        if ($path !== null) {
            $parts[] = "[{$path}]";
        }

        $parts[] = $this->getHtml();

        PHPUnit::fail(implode("\n\n", $parts));
    }


    /**
     * Find the first element matching the selector or fail.
     *
     * @param  string  $selector
     * @return \Dom\Element
     */
    protected function findOrFail(string $selector): Element
    {
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        return $element;
    }

    /**
     * Build the full selector path from the current scope to the given selector.
     *
     * @param  string|null  $selector
     * @return string|null
     */
    protected function buildSelector(?string $selector = null): ?string
    {
        return $this->selector && $selector
            ? $this->selector . ' → ' . $selector
            : $this->selector ?? $selector;
    }

    /**
     * Serialize the current scope to an HTML string.
     *
     * @return string
     */
    protected function getHtml(): string
    {
        return $this->scope instanceof HTMLDocument
            ? $this->document->documentElement->innerHTML
            : $this->document->saveHtml($this->scope);
    }
}
