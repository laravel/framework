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
        $this->expectExceptionMessage('Failed asserting that [p] text was accepted by the truth test.');

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

    // --- whereAttr ---

    public function testWhereAttrPassesWhenAttributeMatches(): void
    {
        $this->html('<a href="/about">About</a>')->whereAttr('a', 'href', '/about');
    }

    public function testWhereAttrFailsWhenAttributeMismatches(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] equals [/contact], found [/about].');

        $this->html('<a href="/about">About</a>')->whereAttr('a', 'href', '/contact');
    }

    // --- hasAttr ---

    public function testHasAttrPassesWhenAttributePresent(): void
    {
        $this->html('<input type="email" required>')->hasAttr('input', 'required');
    }

    public function testHasAttrFailsWhenAttributeAbsent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] has attribute [disabled].');

        $this->html('<input type="email">')->hasAttr('input', 'disabled');
    }

    // --- whereAttribute / hasAttribute aliases ---

    public function testWhereAttributeIsAliasForWhereAttr(): void
    {
        $this->html('<a href="/about">About</a>')->whereAttribute('a', 'href', '/about');
    }

    public function testHasAttributeIsAliasForHasAttr(): void
    {
        $this->html('<input type="email" required>')->hasAttribute('input', 'required');
    }

    // --- missingAttr ---

    public function testMissingAttrPassesWhenAttributeAbsent(): void
    {
        $this->html('<input type="email">')->missingAttr('input', 'disabled');
    }

    public function testMissingAttrFailsWhenAttributePresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] does not have attribute [required].');

        $this->html('<input type="email" required>')->missingAttr('input', 'required');
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
        $this->expectExceptionMessage('Failed to find element matching selector [aside].');

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
            ->whereContains('nav', 'Home')
            ->whereAttr('a', 'href', '/')
            ->hasAttr('a', 'class')
            ->missingAttr('a', 'disabled');

        $this->assertSame($assert, $result);
    }

    // --- failure output includes scope HTML ---

    public function testFailureMessageIncludesScopeHtml(): void
    {
        try {
            $this->html('<nav><a href="/">Home</a></nav>')->has('footer');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('Scope HTML:', $e->getMessage());
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
            $this->assertStringContainsString('Scope HTML:', $e->getMessage());
            $this->assertStringContainsString('<nav>', $e->getMessage());
            // Should not include the full document
            $this->assertStringNotContainsString('<html>', $e->getMessage());

            return;
        }

        $this->fail('Expected an AssertionFailedError.');
    }
}
