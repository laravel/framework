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
     * Create a new assertable HTML instance.
     *
     * @param  \Dom\HTMLDocument  $document
     * @param  \Dom\Element|\Dom\HTMLDocument|null  $scope
     */
    protected function __construct(HTMLDocument $document, Element|HTMLDocument|null $scope = null)
    {
        $this->document = $document;
        $this->scope = $scope ?? $document;
    }

    /**
     * Create a new instance from a test response.
     *
     * @param  \Illuminate\Testing\TestResponse  $response
     * @return static
     */
    public static function fromResponse(TestResponse $response): static
    {
        return static::fromString($response->getContent());
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
        $document = HTMLDocument::createFromString($html, $options);

        return new static($document);
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
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        if (is_int($countOrCallback)) {
            $actual = $this->scope->querySelectorAll($selector)->length;

            if ($actual !== $countOrCallback) {
                $this->fail(
                    "Failed asserting that [{$selector}] matches {$countOrCallback} element(s), found {$actual}.",
                    $selector
                );
            }

            if ($callback !== null) {
                $callback(new static($this->document, $element));
            }
        } elseif ($countOrCallback instanceof Closure) {
            $countOrCallback(new static($this->document, $element));
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
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        $actual = trim($element->textContent);

        if ($expected instanceof Closure) {
            if (! $expected($actual)) {
                $this->fail(
                    "Failed asserting that [{$selector}] text was accepted by the truth test.",
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
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        $actual = trim($element->textContent);

        if ($expected instanceof Closure) {
            if ($expected($actual)) {
                $this->fail(
                    "Failed asserting that [{$selector}] text was rejected by the truth test.",
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
     * Assert that the matched element's attribute equals the expected value.
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string  $expected
     * @return $this
     */
    public function whereAttr(string $selector, string $attribute, string $expected): static
    {
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        $actual = $element->getAttribute($attribute);

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
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        if (! $element->hasAttribute($attribute)) {
            $this->fail(
                "Failed asserting that [{$selector}] has attribute [{$attribute}].",
                $selector
            );
        }

        return $this;
    }

    /**
     * Alias for whereAttr().
     *
     * @param  string  $selector
     * @param  string  $attribute
     * @param  string  $expected
     * @return $this
     */
    public function whereAttribute(string $selector, string $attribute, string $expected): static
    {
        return $this->whereAttr($selector, $attribute, $expected);
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
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed asserting that element [{$selector}] exists.", $selector);
        }

        if ($element->hasAttribute($attribute)) {
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
        $element = $this->scope->querySelector($selector);

        if ($element === null) {
            $this->fail("Failed to find element matching selector [{$selector}].", $selector);
        }

        $callback(new static($this->document, $element));

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

        PHPUnit::assertGreaterThan(
            0,
            $nodes->length,
            "Failed asserting that any elements match [{$selector}]."
        );

        foreach ($nodes as $index => $node) {
            try {
                $callback(new static($this->document, $node), $index);
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
        dump($this->scopeHtml());

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
     * Fail an assertion with the selector and scope HTML appended to the message.
     *
     * @param  string  $message
     * @param  string|null  $selector
     * @return never
     */
    private function fail(string $message, ?string $selector = null): never
    {
        $parts = [$message];

        if ($selector !== null) {
            $parts[] = "Selector: {$selector}";
        }

        $parts[] = "Scope HTML:\n".$this->scopeHtml();

        PHPUnit::fail(implode("\n", $parts));
    }

    /**
     * Serialize the current scope to an HTML string.
     *
     * @return string
     */
    private function scopeHtml(): string
    {
        if ($this->scope instanceof HTMLDocument) {
            return $this->document->saveHtml();
        }

        return $this->document->saveHtml($this->scope);
    }
}
