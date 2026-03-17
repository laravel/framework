<?php

namespace Illuminate\Tests\Testing\Fluent;

use Illuminate\Testing\Fluent\AssertableHtml;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

class AssertableHtmlTest extends TestCase
{
    private function html(string $body = ''): AssertableHtml
    {
        return AssertableHtml::fromString("<html><body>{$body}</body></html>");
    }

    public function testFromStringParsesValidHtml(): void
    {
        $assert = $this->html('<p>Hello</p>');

        $assert->has('p');
    }

    public function testHasPassesWhenSelectorExists(): void
    {
        $this->html('<nav><a href="/">Home</a></nav>')->has('nav');
    }

    public function testHasFailsWhenSelectorMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [nav] exists.');

        $this->html('<div></div>')->has('nav');
    }

    public function testHasWithCountPassesWhenCountMatches(): void
    {
        $this->html('<ul><li>a</li><li>b</li><li>c</li></ul>')->has('li', 3);
    }

    public function testHasWithCountFailsWhenCountMismatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [li] matches 2 element(s), found 3.');

        $this->html('<ul><li>a</li><li>b</li><li>c</li></ul>')->has('li', 2);
    }

    public function testHasWithCallbackScopesIntoElement(): void
    {
        $this->html('<nav><a href="/about">About</a></nav>')
            ->has('nav', function (AssertableHtml $nav) {
                $nav->has('a[href="/about"]');
            });
    }

    public function testHasWithCountAndCallbackScopesIntoFirstMatch(): void
    {
        $this->html('<ul><li class="item"><a href="/a">A</a></li><li class="item"><a href="/b">B</a></li></ul>')
            ->has('li.item', 2, function (AssertableHtml $li) {
                $li->has('a');  // scoped into first li.item
            });
    }

    public function testHasAllPassesWhenAllSelectorsExist(): void
    {
        $this->html('<header></header><main></main><footer></footer>')
            ->hasAll(['header', 'main', 'footer']);
    }

    public function testHasAllFailsWhenOneIsMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [footer] exists.');

        $this->html('<header></header><main></main>')
            ->hasAll(['header', 'main', 'footer']);
    }

    public function testMissingPassesWhenSelectorAbsent(): void
    {
        $this->html('<div></div>')->missing('nav');
    }

    public function testMissingFailsWhenSelectorPresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [nav] is absent.');

        $this->html('<nav></nav>')->missing('nav');
    }

    public function testMissingAllPassesWhenAllSelectorsAbsent(): void
    {
        $this->html('<div></div>')
            ->missingAll(['.alert', '.error', '.warning']);
    }

    public function testMissingAllFailsWhenOneIsPresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [.error] is absent.');

        $this->html('<div class="error"></div>')
            ->missingAll(['.alert', '.error', '.warning']);
    }

    public function testCountPassesWhenCountMatches(): void
    {
        $this->html('<ul><li>a</li><li>b</li></ul>')->count('li', 2);
    }

    public function testCountFailsWhenCountMismatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [li] matches 3 element(s), found 2.');

        $this->html('<ul><li>a</li><li>b</li></ul>')->count('li', 3);
    }

    public function testWhereTextPassesWhenTextMatches(): void
    {
        $this->html('<h1>Hello World</h1>')->whereText('h1', 'Hello World');
    }

    public function testWhereTextFailsWhenTextMismatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [h1] text equals [Goodbye], found [Hello World].');

        $this->html('<h1>Hello World</h1>')->whereText('h1', 'Goodbye');
    }

    public function testWhereTextFailsWhenSelectorMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [h2] exists.');

        $this->html('<h1>Hello</h1>')->whereText('h2', 'Hello');
    }

    public function testWhereTextPassesWithClosureTruthTest(): void
    {
        $this->html('<p>Hello World</p>')
            ->whereText('p', fn ($text) => str_contains($text, 'World'));
    }

    public function testWhereTextFailsWhenClosureReturnsFalse(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [p] text was marked as invalid using a closure.');

        $this->html('<p>Hello World</p>')
            ->whereText('p', fn ($text) => str_contains($text, 'Goodbye'));
    }

    public function testWhereAllTextPassesForAllSelectors(): void
    {
        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAllText([
                'p.a' => 'Foo',
                'p.b' => 'Bar',
            ]);
    }

    public function testWhereAllTextFailsOnFirstMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAllText([
                'p.a' => 'Foo',
                'p.b' => 'Wrong',
            ]);
    }

    public function testWhereAllTextPassesWithClosures(): void
    {
        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAllText([
                'p.a' => fn ($text) => str_starts_with($text, 'F'),
                'p.b' => 'Bar',
            ]);
    }

    public function testWhereNotTextPassesWhenTextDoesNotMatch(): void
    {
        $this->html('<span class="badge">Paid</span>')->whereNotText('.badge', 'Overdue');
    }

    public function testWhereNotTextFailsWhenTextMatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [.badge] text does not equal [Paid].');

        $this->html('<span class="badge">Paid</span>')->whereNotText('.badge', 'Paid');
    }

    public function testWhereNotTextPassesWhenClosureReturnsFalse(): void
    {
        $this->html('<p>Hello World</p>')
            ->whereNotText('p', fn ($text) => str_contains($text, 'Goodbye'));
    }

    public function testWhereNotTextFailsWhenClosureReturnsTrue(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [p] text was marked as invalid using a closure.');

        $this->html('<p>Hello World</p>')
            ->whereNotText('p', fn ($text) => str_contains($text, 'Hello'));
    }

    public function testWhereNotTextFailsWhenSelectorMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [h2] exists.');

        $this->html('<h1>Hello</h1>')->whereNotText('h2', 'Hello');
    }

    public function testWhereAttributePassesWhenAttributeMatches(): void
    {
        $this->html('<a href="/about">About</a>')->whereAttribute('a', 'href', '/about');
    }

    public function testWhereAttributeFailsWhenAttributeMismatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] equals [/contact], found [/about].');

        $this->html('<a href="/about">About</a>')->whereAttribute('a', 'href', '/contact');
    }

    public function testWhereAttributePassesWithClosureTruthTest(): void
    {
        $this->html('<a href="/about">About</a>')
            ->whereAttribute('a', 'href', fn ($value) => str_starts_with($value, '/'));
    }

    public function testWhereAttributeFailsWhenClosureReturnsFalse(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] was marked as invalid using a closure.');

        $this->html('<a href="/about">About</a>')
            ->whereAttribute('a', 'href', fn ($value) => str_starts_with($value, 'http'));
    }

    public function testHasAttributePassesWhenAttributePresent(): void
    {
        $this->html('<input type="email" required>')->hasAttribute('input', 'required');
    }

    public function testHasAttributeFailsWhenAttributeAbsent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] has attribute [disabled].');

        $this->html('<input type="email">')->hasAttribute('input', 'disabled');
    }

    public function testHasAttributesPassesWhenAllAttributesPresent(): void
    {
        $this->html('<input type="email" required disabled>')->hasAttributes('input', ['required', 'disabled']);
    }

    public function testHasAttributesFailsWhenOneAttributeAbsent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] has attribute [disabled].');

        $this->html('<input type="email" required>')->hasAttributes('input', ['required', 'disabled']);
    }

    public function testWhereNotAttributePassesWhenAttributeDoesNotMatch(): void
    {
        $this->html('<a href="/about">About</a>')->whereNotAttribute('a', 'href', '/contact');
    }

    public function testWhereNotAttributeFailsWhenAttributeMatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] does not equal [/about].');

        $this->html('<a href="/about">About</a>')->whereNotAttribute('a', 'href', '/about');
    }

    public function testWhereNotAttributePassesWhenClosureReturnsFalse(): void
    {
        $this->html('<a href="/about">About</a>')
            ->whereNotAttribute('a', 'href', fn ($v) => str_starts_with($v, 'http'));
    }

    public function testWhereNotAttributeFailsWhenClosureReturnsTrue(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] was marked as invalid using a closure.');

        $this->html('<a href="/about">About</a>')
            ->whereNotAttribute('a', 'href', fn ($v) => str_starts_with($v, '/'));
    }

    public function testWhereAttributesPassesForAllAttributes(): void
    {
        $this->html('<a href="/about" class="nav-link">About</a>')
            ->whereAttributes('a', [
                'href'  => '/about',
                'class' => 'nav-link',
            ]);
    }

    public function testWhereAttributesPassesWithClosures(): void
    {
        $this->html('<a href="/about" class="nav-link">About</a>')
            ->whereAttributes('a', [
                'href'  => fn ($v) => str_starts_with($v, '/'),
                'class' => 'nav-link',
            ]);
    }

    public function testWhereAttributesFailsOnFirstMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<a href="/about" class="nav-link">About</a>')
            ->whereAttributes('a', [
                'href'  => '/about',
                'class' => 'btn',
            ]);
    }

    public function testMissingAttributePassesWhenAttributeAbsent(): void
    {
        $this->html('<input type="email">')->missingAttribute('input', 'disabled');
    }

    public function testMissingAttributeFailsWhenAttributePresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] does not have attribute [required].');

        $this->html('<input type="email" required>')->missingAttribute('input', 'required');
    }

    public function testMissingAttributesPassesWhenAllAttributesAbsent(): void
    {
        $this->html('<input type="email">')->missingAttributes('input', ['required', 'disabled']);
    }

    public function testMissingAttributesFailsWhenOneAttributePresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] does not have attribute [required].');

        $this->html('<input type="email" required>')->missingAttributes('input', ['required', 'disabled']);
    }

    public function testScopeNarrowsAssertionsToMatchedElement(): void
    {
        $this->html('<nav><a href="/home">Home</a></nav><footer><a href="/about">About</a></footer>')
            ->scope('nav', function (AssertableHtml $nav) {
                $nav->has('a[href="/home"]');
                $nav->missing('a[href="/about"]');
            });
    }

    public function testScopeFailsWhenSelectorMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [aside] exists.');

        $this->html('<nav></nav>')->scope('aside', function (AssertableHtml $el) {
            //
        });
    }

    public function testEachIteratesAllMatchingElements(): void
    {
        $count = 0;

        $this->html('<ul><li>a</li><li>b</li><li>c</li></ul>')
            ->each('li', function (AssertableHtml $li, int $index) use (&$count) {
                $count++;
            });

        $this->assertSame(3, $count);
    }

    public function testEachReportsFailingIndex(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed assertion on element [li] at index [1]');

        $this->html('<ul><li><a href="/a">A</a></li><li><span>no link</span></li></ul>')
            ->each('li', function (AssertableHtml $li) {
                $li->has('a');
            });
    }

    public function testEachFailsWhenNoElementsMatch(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<div></div>')->each('li', fn ($li) => null);
    }

    public function testMethodsReturnStaticForChaining(): void
    {
        $assert = $this->html('<nav><a href="/" class="active">Home</a></nav>');

        $result = $assert
            ->has('nav')
            ->missing('aside')
            ->count('a', 1)
            ->whereText('a', 'Home')
            ->whereText('nav', fn ($text) => str_contains($text, 'Home'))
            ->whereAttribute('a', 'href', '/')
            ->hasAttribute('a', 'class')
            ->missingAttribute('a', 'disabled');

        $this->assertSame($assert, $result);
    }

    public function testFailureMessageIncludesScopeHtml(): void
    {
        try {
            $this->html('<nav><a href="/">Home</a></nav>')->has('footer');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('<nav>', $e->getMessage());

            return;
        }

        $this->fail('Expected an AssertionFailedError.');
    }

    public function testScopedFailureMessageIncludesScopedElementHtml(): void
    {
        try {
            $this->html('<nav><a href="/">Home</a></nav>')
                ->scope('nav', function (AssertableHtml $nav) {
                    $nav->has('button');
                });
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('<nav>', $e->getMessage());
            // Should not include the full document
            $this->assertStringNotContainsString('<html>', $e->getMessage());

            return;
        }

        $this->fail('Expected an AssertionFailedError.');
    }
}
