<?php

namespace Illuminate\Tests\Testing\Fluent;

use Illuminate\Testing\Fluent\AssertableHtml;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[RequiresPhp('>= 8.4.0')]
class AssertableHtmlTest extends TestCase
{
    private function html(string $body = ''): AssertableHtml
    {
        return AssertableHtml::fromString("<html><body>{$body}</body></html>");
    }

    public function testFromString(): void
    {
        $assert = $this->html('<p>Hello</p>');

        $assert->has('p');
    }

    public function testFromResponse(): void
    {
        $response = TestResponse::fromBaseResponse(new Response('<html><body><p>Hello</p></body></html>'));

        AssertableHtml::fromResponse($response)->has('p');
    }

    public function testFromStreamedResponse(): void
    {
        $response = TestResponse::fromBaseResponse(new StreamedResponse(function () {
            echo '<html><body><p>Hello</p></body></html>';
        }));

        AssertableHtml::fromResponse($response)->has('p');
    }

    public function testHas(): void
    {
        $this->html('<nav><a href="/">Home</a></nav>')->has('nav');
    }

    public function testHasMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [nav] exists.');

        $this->html('<div></div>')->has('nav');
    }

    public function testHasAll(): void
    {
        $this->html('<header></header><main></main><footer></footer>')
            ->hasAll(['header', 'main', 'footer']);
    }

    public function testHasAllMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [footer] exists.');

        $this->html('<header></header><main></main>')
            ->hasAll(['header', 'main', 'footer']);
    }

    public function testMissing(): void
    {
        $this->html('<div></div>')->missing('nav');
    }

    public function testMissingPresentSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [nav] is absent.');

        $this->html('<nav></nav>')->missing('nav');
    }

    public function testMissingAll(): void
    {
        $this->html('<div></div>')
            ->missingAll(['.alert', '.error', '.warning']);
    }

    public function testMissingAllPresentSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [.error] is absent.');

        $this->html('<div class="error"></div>')
            ->missingAll(['.alert', '.error', '.warning']);
    }

    public function testCount(): void
    {
        $this->html('<ul><li>a</li><li>b</li></ul>')->count('li', 2);
    }

    public function testCountMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [li] matches 3 element(s), found 2.');

        $this->html('<ul><li>a</li><li>b</li></ul>')->count('li', 3);
    }

    public function testWhereText(): void
    {
        $this->html('<h1>Hello World</h1>')->whereText('h1', 'Hello World');
    }

    public function testWhereTextMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [h1] text equals [Goodbye], found [Hello World].');

        $this->html('<h1>Hello World</h1>')->whereText('h1', 'Goodbye');
    }

    public function testWhereTextMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [h2] exists.');

        $this->html('<h1>Hello</h1>')->whereText('h2', 'Hello');
    }

    public function testWhereTextWithWhitespace(): void
    {
        $this->html('<p>  Hello World  </p>')->whereText('p', 'Hello World');
    }

    public function testWhereNotTextWithWhitespace(): void
    {
        $this->html('<p>  Hello World  </p>')->whereNotText('p', 'Goodbye');
    }

    public function testWhereTextWithClosure(): void
    {
        $this->html('<p>Hello World</p>')
            ->whereText('p', fn ($text) => str_contains($text, 'World'));
    }

    public function testWhereTextWithClosureFailing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [p] text was marked as invalid using a closure.');

        $this->html('<p>Hello World</p>')
            ->whereText('p', fn ($text) => str_contains($text, 'Goodbye'));
    }

    public function testWhereAllText(): void
    {
        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAllText([
                'p.a' => 'Foo',
                'p.b' => 'Bar',
            ]);
    }

    public function testWhereAllTextMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAllText([
                'p.a' => 'Foo',
                'p.b' => 'Wrong',
            ]);
    }

    public function testWhereAllTextWithClosures(): void
    {
        $this->html('<p class="a">Foo</p><p class="b">Bar</p>')
            ->whereAllText([
                'p.a' => fn ($text) => str_starts_with($text, 'F'),
                'p.b' => 'Bar',
            ]);
    }

    public function testWhereNotText(): void
    {
        $this->html('<span class="badge">Paid</span>')->whereNotText('.badge', 'Overdue');
    }

    public function testWhereNotTextMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [.badge] text does not equal [Paid].');

        $this->html('<span class="badge">Paid</span>')->whereNotText('.badge', 'Paid');
    }

    public function testWhereNotTextWithClosure(): void
    {
        $this->html('<p>Hello World</p>')
            ->whereNotText('p', fn ($text) => str_contains($text, 'Goodbye'));
    }

    public function testWhereNotTextWithClosureFailing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [p] text was marked as invalid using a closure.');

        $this->html('<p>Hello World</p>')
            ->whereNotText('p', fn ($text) => str_contains($text, 'Hello'));
    }

    public function testWhereNotTextMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [h2] exists.');

        $this->html('<h1>Hello</h1>')->whereNotText('h2', 'Hello');
    }

    public function testWhereAttribute(): void
    {
        $this->html('<a href="/about">About</a>')->whereAttribute('a', 'href', '/about');
    }

    public function testWhereAttributeMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] equals [/contact], found [/about].');

        $this->html('<a href="/about">About</a>')->whereAttribute('a', 'href', '/contact');
    }

    public function testWhereAttributeWithClosure(): void
    {
        $this->html('<a href="/about">About</a>')
            ->whereAttribute('a', 'href', fn ($value) => str_starts_with($value, '/'));
    }

    public function testWhereAttributeWithClosureFailing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] was marked as invalid using a closure.');

        $this->html('<a href="/about">About</a>')
            ->whereAttribute('a', 'href', fn ($value) => str_starts_with($value, 'http'));
    }

    public function testHasAttribute(): void
    {
        $this->html('<input type="email" required>')->hasAttribute('input', 'required');
    }

    public function testHasAttributeMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] has attribute [disabled].');

        $this->html('<input type="email">')->hasAttribute('input', 'disabled');
    }

    public function testHasAttributes(): void
    {
        $this->html('<input type="email" required disabled>')->hasAttributes('input', ['required', 'disabled']);
    }

    public function testHasAttributesMissing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] has attribute [disabled].');

        $this->html('<input type="email" required>')->hasAttributes('input', ['required', 'disabled']);
    }

    public function testWhereNotAttribute(): void
    {
        $this->html('<a href="/about">About</a>')->whereNotAttribute('a', 'href', '/contact');
    }

    public function testWhereNotAttributeMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] does not equal [/about].');

        $this->html('<a href="/about">About</a>')->whereNotAttribute('a', 'href', '/about');
    }

    public function testWhereNotAttributeWithClosure(): void
    {
        $this->html('<a href="/about">About</a>')
            ->whereNotAttribute('a', 'href', fn ($v) => str_starts_with($v, 'http'));
    }

    public function testWhereNotAttributeWithClosureFailing(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [a] attribute [href] was marked as invalid using a closure.');

        $this->html('<a href="/about">About</a>')
            ->whereNotAttribute('a', 'href', fn ($v) => str_starts_with($v, '/'));
    }

    public function testWhereAttributes(): void
    {
        $this->html('<a href="/about" class="nav-link">About</a>')
            ->whereAttributes('a', [
                'href' => '/about',
                'class' => 'nav-link',
            ]);
    }

    public function testWhereAttributesWithClosures(): void
    {
        $this->html('<a href="/about" class="nav-link">About</a>')
            ->whereAttributes('a', [
                'href' => fn ($v) => str_starts_with($v, '/'),
                'class' => 'nav-link',
            ]);
    }

    public function testWhereAttributesMismatch(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<a href="/about" class="nav-link">About</a>')
            ->whereAttributes('a', [
                'href' => '/about',
                'class' => 'btn',
            ]);
    }

    public function testMissingAttribute(): void
    {
        $this->html('<input type="email">')->missingAttribute('input', 'disabled');
    }

    public function testMissingAttributePresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] does not have attribute [required].');

        $this->html('<input type="email" required>')->missingAttribute('input', 'required');
    }

    public function testMissingAttributes(): void
    {
        $this->html('<input type="email">')->missingAttributes('input', ['required', 'disabled']);
    }

    public function testMissingAttributesPresent(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [input] does not have attribute [required].');

        $this->html('<input type="email" required>')->missingAttributes('input', ['required', 'disabled']);
    }

    public function testScope(): void
    {
        $this->html('<nav><a href="/home">Home</a></nav><footer><a href="/about">About</a></footer>')
            ->scope('nav', function (AssertableHtml $nav) {
                $nav->has('a[href="/home"]');
                $nav->missing('a[href="/about"]');
            });
    }

    public function testScopeWithoutCallback(): void
    {
        $this->html('<nav><a href="/home">Home</a></nav><footer><a href="/about">About</a></footer>')
            ->scope('nav')
            ->has('a[href="/home"]')
            ->missing('a[href="/about"]');
    }

    public function testScopeMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [aside] exists.');

        $this->html('<nav></nav>')->scope('aside', function (AssertableHtml $el) {
            //
        });
    }

    public function testNth(): void
    {
        $this->html('<ul><li><span>First</span></li><li><span>Second</span></li><li><span>Third</span></li></ul>')
            ->nth('li', 0, fn (AssertableHtml $li) => $li->whereText('span', 'First'))
            ->nth('li', 1, fn (AssertableHtml $li) => $li->whereText('span', 'Second'))
            ->nth('li', 2, fn (AssertableHtml $li) => $li->whereText('span', 'Third'));
    }

    public function testNthWithoutCallback(): void
    {
        $this->html('<ul><li><span>First</span></li><li><span>Second</span></li><li><span>Third</span></li></ul>')
            ->nth('li', 1)
            ->whereText('span', 'Second');
    }

    public function testNthMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [li] exists.');

        $this->html('<ul></ul>')->nth('li', 0, fn (AssertableHtml $li) => null);
    }

    public function testNthOutOfBounds(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that [li] has an element at index [5], found 3 element(s).');

        $this->html('<ul><li>a</li><li>b</li><li>c</li></ul>')
            ->nth('li', 5, fn (AssertableHtml $li) => null);
    }

    public function testFirst(): void
    {
        $this->html('<ul><li><span>First</span></li><li><span>Second</span></li><li><span>Third</span></li></ul>')
            ->first('li', fn (AssertableHtml $li) => $li->whereText('span', 'First'));
    }

    public function testFirstWithoutCallback(): void
    {
        $this->html('<ul><li><span>First</span></li><li><span>Second</span></li><li><span>Third</span></li></ul>')
            ->first('li')
            ->whereText('span', 'First');
    }

    public function testFirstMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [li] exists.');

        $this->html('<ul></ul>')->first('li', fn (AssertableHtml $li) => null);
    }

    public function testLast(): void
    {
        $this->html('<ul><li><span>First</span></li><li><span>Second</span></li><li><span>Third</span></li></ul>')
            ->last('li', fn (AssertableHtml $li) => $li->whereText('span', 'Third'));
    }

    public function testLastWithoutCallback(): void
    {
        $this->html('<ul><li><span>First</span></li><li><span>Second</span></li><li><span>Third</span></li></ul>')
            ->last('li')
            ->whereText('span', 'Third');
    }

    public function testLastMissingSelector(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed asserting that element [li] exists.');

        $this->html('<ul></ul>')->last('li', fn (AssertableHtml $li) => null);
    }

    public function testEach(): void
    {
        $count = 0;

        $this->html('<ul><li>a</li><li>b</li><li>c</li></ul>')
            ->each('li', function (AssertableHtml $li, int $index) use (&$count) {
                $count++;
            });

        $this->assertSame(3, $count);
    }

    public function testEachFailingIndex(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('Failed assertion on element [li] at index [1]');

        $this->html('<ul><li><a href="/a">A</a></li><li><span>no link</span></li></ul>')
            ->each('li', function (AssertableHtml $li) {
                $li->has('a');
            });
    }

    public function testEachNoMatches(): void
    {
        $this->expectException(AssertionFailedError::class);

        $this->html('<div></div>')->each('li', fn ($li) => null);
    }

    public function testChaining(): void
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

    public function testFailureOutputIncludesHtml(): void
    {
        try {
            $this->html('<nav><a href="/">Home</a></nav>')->has('footer');
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('<nav>', $e->getMessage());

            return;
        }

        $this->fail('Expected an AssertionFailedError.');
    }

    public function testScopedFailureOutputIncludesScopedHtml(): void
    {
        try {
            $this->html('<nav><a href="/">Home</a></nav>')
                ->scope('nav', function (AssertableHtml $nav) {
                    $nav->has('button');
                });
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('<nav>', $e->getMessage());
            $this->assertStringNotContainsString('<html>', $e->getMessage());

            return;
        }

        $this->fail('Expected an AssertionFailedError.');
    }

    public function testFailureOutputIncludesFullSelectorPath(): void
    {
        try {
            $this->html('<nav><ul><li>Item</li></ul></nav>')
                ->scope('nav', function (AssertableHtml $nav) {
                    $nav->scope('ul', function (AssertableHtml $ul) {
                        $ul->has('a');
                    });
                });
        } catch (AssertionFailedError $e) {
            $this->assertStringContainsString('nav → ul → a', $e->getMessage());

            return;
        }

        $this->fail('Expected an AssertionFailedError.');
    }

    public function testWhereAttributeWithWhitespace(): void
    {
        $this->html('<a href="  /about  ">About</a>')->whereAttribute('a', 'href', '/about');
    }

    public function testWhereNotAttributeWithWhitespace(): void
    {
        $this->html('<a href="  /about  ">About</a>')->whereNotAttribute('a', 'href', '/contact');
    }
}
