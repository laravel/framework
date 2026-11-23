<?php

namespace Illuminate\Tests\Integration\Mail;

use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Markdown;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\Factories\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;

class MailableWithSecuredEncodingTest extends MailableTestCase
{
    use LazilyRefreshDatabase;

    /** {@inheritdoc} */
    #[\Override]
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        Markdown::withSecuredEncoding();
    }

    #[WithMigration]
    #[DataProvider('markdownEncodedTemplateDataProvider')]
    public function testItCanAssertMarkdownEncodedStringUsingTemplate($given, $expected)
    {
        $user = UserFactory::new()->create([
            'name' => $given,
        ]);

        $mailable = new class($user) extends Mailable
        {
            public $theme = 'taylor';

            public function __construct(public User $user)
            {
                //
            }

            public function build()
            {
                return $this->markdown('message-with-template');
            }
        };

        $mailable->assertSeeInHtml($expected, false);
    }

    #[WithMigration]
    #[DataProvider('markdownEncodedTemplateDataProvider')]
    public function testItCanAssertMarkdownEncodedStringUsingTemplateWithTable($given, $expected)
    {
        $user = UserFactory::new()->create([
            'name' => $given,
        ]);

        $mailable = new class($user) extends Mailable
        {
            public $theme = 'taylor';

            public function __construct(public User $user)
            {
                //
            }

            public function build()
            {
                return $this->markdown('table-with-template');
            }
        };

        $mailable->assertSeeInHtml($expected, false);
        $mailable->assertSeeInHtml('<p>This is a subcopy</p>', false);
        $mailable->assertSeeInHtml(<<<'TABLE'
<table>
<thead>
<tr>
<th>Laravel</th>
<th align="center">Table</th>
<th align="right">Example</th>
</tr>
</thead>
<tbody>
<tr>
<td>Col 2 is</td>
<td align="center">Centered</td>
<td align="right">$10</td>
</tr>
<tr>
<td>Col 3 is</td>
<td align="center">Right-Aligned</td>
<td align="right">$20</td>
</tr>
</tbody>
</table>
TABLE, false);
    }

    public static function markdownEncodedTemplateDataProvider()
    {
        yield ['[Laravel](https://laravel.com)', '<em>Hi</em> [Laravel](https://laravel.com)'];

        yield [
            '![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)',
            '<em>Hi</em> ![Welcome to Laravel](https://laravel.com/assets/img/welcome/background.svg)',
        ];

        yield [
            'Visit https://laravel.com/docs to browse the documentation',
            '<em>Hi</em> Visit https://laravel.com/docs to browse the documentation',
        ];

        yield [
            'Visit <https://laravel.com/docs> to browse the documentation',
            '<em>Hi</em> Visit &lt;https://laravel.com/docs&gt; to browse the documentation',
        ];

        yield [
            'Visit <span>https://laravel.com/docs</span> to browse the documentation',
            '<em>Hi</em> Visit &lt;span&gt;https://laravel.com/docs&lt;/span&gt; to browse the documentation',
        ];

        yield ['Tea & coffee &amp; cake', '<em>Hi</em> Tea &amp; coffee &amp;amp; cake'];

        yield ['\[Laravel](https://laravel.com)', '<em>Hi</em> [Laravel](https://laravel.com)'];

        yield ['!\[Laravel](https://laravel.com)', '<em>Hi</em> ![Laravel](https://laravel.com)'];
    }

    #[WithMigration]
    #[DataProvider('markdownEncodedLinkTextTemplateDataProvider')]
    public function testItCanAssertMarkdownEncodedStringUsingTemplateWithLinkText($given, $expected)
    {
        $user = UserFactory::new()->create([
            'name' => $given,
        ]);

        $mailable = new class($user) extends Mailable
        {
            public $theme = 'taylor';

            public function __construct(public User $user)
            {
                //
            }

            public function build()
            {
                return $this->markdown('link-with-template');
            }
        };

        $mailable->assertSeeInHtml($expected, false);
        $mailable->assertDontSeeInHtml('https://example.com"', false);
    }

    public static function markdownEncodedLinkTextTemplateDataProvider()
    {
        yield ['Taylor', '<a href="https://laravel.com">Taylor</a>'];

        yield ['Taylor](https://example.com)', '<a href="https://laravel.com">Taylor](https://example.com)</a>'];

        yield ['Taylor\](https://example.com)', '<a href="https://laravel.com">Taylor](https://example.com)</a>'];
    }

    #[DataProvider('attributeValues')]
    public function testSecuredEncodingProtectsHtmlAttributes($value)
    {
        $mailable = new class($value) extends Mailable
        {
            public function __construct(public string $value)
            {
                //
            }

            public function build()
            {
                return $this->markdown('attributes-with-template');
            }
        };

        $document = new DOMDocument;
        $document->loadHTML($mailable->render());
        $xpath = new DOMXPath($document);

        $image = $xpath->query('//img[@id="test-image"]')->item(0);
        $this->assertNotNull($image);
        $this->assertSame($value, $image->getAttribute('alt'));
        $this->assertSame($value, $image->getAttribute('title'));
        $this->assertFalse($image->hasAttribute('onerror'));

        $helper = $xpath->query('//span[@id="test-helper"]')->item(0);
        $this->assertNotNull($helper);
        $this->assertSame(html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8'), $helper->getAttribute('title'));
        $this->assertFalse($helper->hasAttribute('onerror'));

        $button = $xpath->query('//a[contains(@class, "button")]')->item(0);
        $this->assertNotNull($button);
        $this->assertSame($value, rawurldecode($button->getAttribute('href')));
        $this->assertFalse($button->hasAttribute('onerror'));
    }

    public static function attributeValues()
    {
        yield 'double quote' => ['Taylor" onerror="alert(1)'];
        yield 'single quote' => ["Taylor' onerror='alert(1)"];
        yield 'entities and ampersands' => ['Taylor &quot; &#39; &amp; & test'];
        yield 'markdown and html' => ['[Taylor] <example>'];
    }
}
