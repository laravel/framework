<?php

namespace Illuminate\Testing\Fluent;

use Closure;
use Dom\Element;
use Dom\HTMLDocument;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssertableHtml
{
    use Conditionable, Macroable, Tappable;

    /**
     * Create a new assertable HTML instance.
     *
     * @param  \Dom\Element|\Dom\HTMLDocument  $scope
     * @param  string|null  $selector
     */
    protected function __construct(
        protected Element|HTMLDocument $scope,
        protected ?string $selector = null,
    ) {
    }

    /**
     * Create a new instance from a test response.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     * @param  int  $options
     * @return static
     */
    public static function fromResponse(TestResponse $response, int $options = LIBXML_NOERROR): static
    {
        $content = $response->baseResponse instanceof StreamedResponse
            ? $response->streamedContent()
            : $response->getContent();

        return new static(HTMLDocument::createFromString($content, $options));
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
        return new static(HTMLDocument::createFromString($html, $options));
    }

    /**
     * Assert that an element matching the given selector exists.
     *
     * @param  string  $selector
     * @return $this
     */
    public function has(string $selector): static
    {
        $this->findOrFail($selector);

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
     * Assert that no element matches the given selector.
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
     * Assert that the given selector matches the expected number of elements.
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
     * Assert that the matched element's text content equals the given value.
     *
     * @param  string  $selector
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereText(string $selector, string|Closure $expected): static
    {
        $actual = $this->textContent($selector);

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
     * Assert that each selector's matched element has the expected text content.
     *
     * @param  array<string, string|\Closure>  $bindings
     * @return $this
     */
    public function whereAllText(array $bindings): static
    {
        foreach ($bindings as $selector => $expected) {
            $this->whereText($selector, $expected);
        }

        return $this;
    }

    /**
     * Assert that the matched element's text content does not equal the given value.
     *
     * @param  string  $selector
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereNotText(string $selector, string|Closure $expected): static
    {
        $actual = $this->textContent($selector);

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
     * Assert that the matched element's given attribute equals the given value.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereAttribute(string $selector, string $attribute, string|Closure $expected): static
    {
        $actual = $this->attributeValue($selector, $attribute);

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
    public function hasAttribute(string $selector, string $attribute): static
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
     * Assert that the matched element has all of the given attributes.
     *
     * @param  string  $selector
     * @param  array<int, string>  $attributes
     * @return $this
     */
    public function hasAttributes(string $selector, array $attributes): static
    {
        foreach ($attributes as $attribute) {
            $this->hasAttribute($selector, $attribute);
        }

        return $this;
    }

    /**
     * Assert that the matched element's given attribute does not equal the given value.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string|\Closure  $expected
     * @return $this
     */
    public function whereNotAttribute(string $selector, string $attribute, string|Closure $expected): static
    {
        $actual = $this->attributeValue($selector, $attribute);

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
     * Assert that the matched element's attributes equal the given values.
     *
     * @param  string  $selector
     * @param  array<string, string|\Closure>  $bindings
     * @return $this
     */
    public function whereAttributes(string $selector, array $bindings): static
    {
        foreach ($bindings as $attribute => $expected) {
            $this->whereAttribute($selector, $attribute, $expected);
        }

        return $this;
    }

    /**
     * Assert that the matched element does not have the given attribute.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @return $this
     */
    public function missingAttribute(string $selector, string $attribute): static
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
     * Assert that the matched element does not have any of the given attributes.
     *
     * @param  string  $selector
     * @param  array<int, string>  $attributes
     * @return $this
     */
    public function missingAttributes(string $selector, array $attributes): static
    {
        foreach ($attributes as $attribute) {
            $this->missingAttribute($selector, $attribute);
        }

        return $this;
    }

    /**
     * Scope into the element at the given index matching the selector and optionally invoke the callback.
     *
     * @param  string  $selector
     * @param  int  $index
     * @param  \Closure|null  $callback
     * @return $this
     */
    public function nth(string $selector, int $index, ?Closure $callback = null): static
    {
        $nodes = $this->scope->querySelectorAll($selector);

        if ($nodes->length === 0) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        if ($index < 0 || $index >= $nodes->length) {
            $this->fail(
                "Failed asserting that [{$selector}] has an element at index [{$index}], found {$nodes->length} element(s).",
                $selector
            );
        }

        $scoped = new static($nodes->item($index), $this->buildSelector($selector));

        if ($callback !== null) {
            $callback($scoped);

            return $this;
        }

        return $scoped;
    }

    /**
     * Scope into the first element matching the selector and optionally invoke the callback.
     *
     * @param  string  $selector
     * @param  \Closure|null  $callback
     * @return $this
     */
    public function first(string $selector, ?Closure $callback = null): static
    {
        return $this->nth($selector, 0, $callback);
    }

    /**
     * Scope into the last element matching the selector and optionally invoke the callback.
     *
     * @param  string  $selector
     * @param  \Closure|null  $callback
     * @return $this
     */
    public function last(string $selector, ?Closure $callback = null): static
    {
        return $this->nth($selector, $this->scope->querySelectorAll($selector)->length - 1, $callback);
    }

    /**
     * Scope into the first element matching the selector and optionally invoke the callback.
     *
     * @param  string  $selector
     * @param  \Closure|null  $callback
     * @return $this
     */
    public function scope(string $selector, ?Closure $callback = null): static
    {
        return $this->first($selector, $callback);
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
                $callback(new static($node, $this->buildSelector($selector)), $index);
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
        dd($this->getHtml());
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
     * Get the trimmed text content of the first element matching the selector or fail.
     *
     * @param  string  $selector
     * @return string
     */
    protected function textContent(string $selector): string
    {
        return trim($this->findOrFail($selector)->textContent);
    }

    /**
     * Get the trimmed value of the given attribute on the first element matching the selector or fail.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @return string
     */
    protected function attributeValue(string $selector, string $attribute): string
    {
        return trim($this->findOrFail($selector)->getAttribute($attribute));
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
            ? $this->selector.' → '.$selector
            : $this->selector ?? $selector;
    }

    /**
     * Serialize the current scope to an HTML string.
     *
     * @return string
     */
    protected function getHtml(): string
    {
        assert($this->scope instanceof HTMLDocument || $this->scope->ownerDocument !== null);

        return $this->scope instanceof HTMLDocument
            ? $this->scope->documentElement->innerHTML
            : $this->scope->ownerDocument->saveHtml($this->scope);
    }
}
