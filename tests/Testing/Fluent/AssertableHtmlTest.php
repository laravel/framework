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

    // --- fromString ---

    public function testFromStringParsesValidHtml(): void
    {
        $assert = $this->html('<p>Hello</p>');

        $assert->has('p');
    }

    // --- has ---

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

    // --- hasAll ---

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

    // --- missing ---

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

    // --- missingAll ---

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

    // --- count ---

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

    // --- where ---

    public function testWherePassesWhenTextMatches(): void
    {
        $this->html('<h1>Hello World</h1>')->where('h1', 'Hello World');
    }

    public function testWhereFailsWhenTextMismatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [h1] text equals [Goodbye], found [Hello World].');

        $this->html('<h1>Hello World</h1>')->where('h1', 'Goodbye');
    }

    public function testWhereFailsWhenSelectorMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [h2] exists.');

        $this->html('<h1>Hello</h1>')->where('h2', 'Hello');
    }

    public function testWherePassesWithClosureTruthTest(): void
    {
        $this->html('<p>Hello World</p>')
            ->where('p', fn ($text) => str_contains($text, 'World'));
    }

    public function testWhereFailsWhenClosureReturnsFalse(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [p] text was marked as invalid using a closure.');

        $this->html('<p>Hello World</p>')
            ->where('p', fn ($text) => str_contains($text, 'Goodbye'));
    }

    // --- whereAll ---

    public function testWhereAllPassesForAllSelectors(): void
    {
        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAll([
                'p.a' => 'Foo',
                'p.b' => 'Bar',
            ]);
    }

    public function testWhereAllFailsOnFirstMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAll([
                'p.a' => 'Foo',
                'p.b' => 'Wrong',
            ]);
    }

    public function testWhereAllPassesWithClosures(): void
    {
        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAll([
                'p.a' => fn ($text) => str_starts_with($text, 'F'),
                'p.b' => 'Bar',
            ]);
    }

    // --- whereNot ---

    public function testWhereNotPassesWhenTextDoesNotMatch(): void
    {
        $this->html('<span class="badge">Paid</span>')->whereNot('.badge', 'Overdue');
    }

    public function testWhereNotFailsWhenTextMatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [.badge] text does not equal [Paid].');

        $this->html('<span class="badge">Paid</span>')->whereNot('.badge', 'Paid');
    }

    public function testWhereNotPassesWhenClosureReturnsFalse(): void
    {
        $this->html('<p>Hello World</p>')
            ->whereNot('p', fn ($text) => str_contains($text, 'Goodbye'));
    }

    public function testWhereNotFailsWhenClosureReturnsTrue(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [p] text was marked as invalid using a closure.');

        $this->html('<p>Hello World</p>')
            ->whereNot('p', fn ($text) => str_contains($text, 'Hello'));
    }

    public function testWhereNotFailsWhenSelectorMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [h2] exists.');

        $this->html('<h1>Hello</h1>')->whereNot('h2', 'Hello');
    }

    // --- whereAttribute ---

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

    // --- hasAttribute ---

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

    // --- hasAttributes ---

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

    // --- whereNotAttribute ---

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

    // --- whereAttributes ---

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

    // --- missingAttribute ---

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

    // --- scope ---

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

    // --- each ---

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

    // --- chaining ---

    public function testMethodsReturnStaticForChaining(): void
    {
        $assert = $this->html('<nav><a href="/" class="active">Home</a></nav>');

        $result = $assert
            ->has('nav')
            ->missing('aside')
            ->count('a', 1)
            ->where('a', 'Home')
            ->where('nav', fn ($text) => str_contains($text, 'Home'))
            ->whereAttribute('a', 'href', '/')
            ->hasAttribute('a', 'class')
            ->missingAttribute('a', 'disabled');

        $this->assertSame($assert, $result);
    }

    // --- failure output includes scope HTML ---

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
