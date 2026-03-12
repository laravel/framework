<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Attributes\Sluggable;
use Illuminate\Database\Eloquent\CouldNotGenerateSlugException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentSluggableTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        Model::setEventDispatcher(new Dispatcher);
        Model::clearBootedModels();

        $this->createSchema();
    }

    protected function createSchema(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        $schema->create('sluggable_posts', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });

        $schema->create('sluggable_custom_posts', function ($table) {
            $table->increments('id');
            $table->string('title')->nullable();
            $table->string('url_slug')->nullable();
            $table->timestamps();
        });

        $schema->create('sluggable_scoped_posts', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('team_id')->nullable();
            $table->timestamps();
        });

        $schema->create('sluggable_multi_scoped_posts', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->integer('team_id')->nullable();
            $table->string('locale')->nullable();
            $table->timestamps();
        });

        $schema->create('sluggable_soft_delete_posts', function ($table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        $schema->create('sluggable_multi_source_posts', function ($table) {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        $schema = DB::connection()->getSchemaBuilder();

        $schema->dropIfExists('sluggable_posts');
        $schema->dropIfExists('sluggable_custom_posts');
        $schema->dropIfExists('sluggable_scoped_posts');
        $schema->dropIfExists('sluggable_multi_scoped_posts');
        $schema->dropIfExists('sluggable_soft_delete_posts');
        $schema->dropIfExists('sluggable_multi_source_posts');
    }

    // from (string, default)

    public function test_it_generates_slug_from_name_column_by_default()
    {
        $post = SluggablePost::create(['name' => 'My First Post']);

        $this->assertSame('my-first-post', $post->slug);
    }

    public function test_it_works_with_zero_config()
    {
        $post = SluggablePost::create(['name' => 'My Post']);

        $this->assertSame('my-post', $post->slug);
    }

    // from (array)

    public function test_it_generates_from_multiple_source_columns()
    {
        $post = SluggableMultiSourcePost::create(['first_name' => 'John', 'last_name' => 'Doe']);

        $this->assertSame('john-doe', $post->slug);
    }

    public function test_it_generates_from_multiple_source_columns_with_partial_null()
    {
        $post = SluggableMultiSourcePost::create(['first_name' => 'John', 'last_name' => null]);

        $this->assertSame('john', $post->slug);
    }

    // column (custom)

    public function test_it_works_with_custom_source_and_slug_columns()
    {
        $post = SluggableCustomColumnsPost::create(['title' => 'Hello World']);

        $this->assertSame('hello-world', $post->url_slug);
    }

    // separator

    public function test_it_works_with_custom_separator()
    {
        $post = SluggableCustomSeparatorPost::create(['name' => 'Hello World']);

        $this->assertSame('hello_world', $post->slug);
    }

    public function test_it_uses_custom_separator_in_collision_suffix()
    {
        SluggableCustomSeparatorPost::create(['name' => 'Hello World']);
        $post = SluggableCustomSeparatorPost::create(['name' => 'Hello World']);

        $this->assertSame('hello_world_2', $post->slug);
    }

    // language

    public function test_it_transliterates_with_custom_language()
    {
        $post = SluggableGermanPost::create(['name' => 'Über die Brücke']);

        $this->assertSame('uber-die-brucke', $post->slug);
    }

    // scope (string)

    public function test_it_respects_scoped_uniqueness()
    {
        SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 1]);
        $post = SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 1]);

        $this->assertSame('hello-2', $post->slug);
    }

    public function test_it_allows_same_slug_in_different_scopes()
    {
        SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 1]);
        $post = SluggableScopedPost::create(['name' => 'Hello', 'team_id' => 2]);

        $this->assertSame('hello', $post->slug);
    }

    // scope (array, multiple columns)

    public function test_it_respects_multi_column_scoped_uniqueness()
    {
        SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'en']);
        $post = SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'en']);

        $this->assertSame('hello-2', $post->slug);
    }

    public function test_it_allows_same_slug_in_different_multi_column_scopes()
    {
        SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'en']);
        $post = SluggableMultiScopedPost::create(['name' => 'Hello', 'team_id' => 1, 'locale' => 'fr']);

        $this->assertSame('hello', $post->slug);
    }

    // onCreating

    public function test_it_skips_generation_on_create_when_opted_out()
    {
        $post = SluggableNoCreatePost::create(['name' => 'Hello World']);

        $this->assertNull($post->slug);
    }

    public function test_it_preserves_manually_provided_slug_on_create()
    {
        $post = SluggablePost::create(['name' => 'My First Post', 'slug' => 'custom-slug']);

        $this->assertSame('custom-slug', $post->slug);
    }

    // onUpdating

    public function test_it_does_not_regenerate_slug_on_update_by_default()
    {
        $post = SluggablePost::create(['name' => 'Hello World']);
        $post->name = 'Updated Name';
        $post->save();

        $this->assertSame('hello-world', $post->slug);
    }

    public function test_it_regenerates_slug_on_update_when_opted_in_and_source_changed()
    {
        $post = SluggableUpdatePost::create(['name' => 'Hello World']);
        $post->name = 'Updated Name';
        $post->save();

        $this->assertSame('updated-name', $post->slug);
    }

    public function test_it_does_not_regenerate_slug_on_update_when_source_unchanged()
    {
        $post = SluggableUpdatePost::create(['name' => 'Hello World']);
        $original = $post->slug;
        $post->updated_at = now();
        $post->save();

        $this->assertSame($original, $post->slug);
    }

    public function test_it_preserves_manually_changed_slug_on_update()
    {
        $post = SluggableUpdatePost::create(['name' => 'Hello World']);
        $post->name = 'New Name';
        $post->slug = 'my-custom-slug';
        $post->save();

        $this->assertSame('my-custom-slug', $post->slug);
    }

    public function test_it_excludes_current_model_from_uniqueness_check_on_update()
    {
        $post = SluggableUpdatePost::create(['name' => 'Hello World']);
        $post->name = 'Hello World Updated';
        $post->save();

        $this->assertSame('hello-world-updated', $post->slug);
    }

    public function test_it_regenerates_slug_on_update_when_one_of_multiple_sources_changes()
    {
        $post = SluggableMultiSourceUpdatePost::create(['first_name' => 'John', 'last_name' => 'Doe']);
        $this->assertSame('john-doe', $post->slug);

        $post->last_name = 'Smith';
        $post->save();

        $this->assertSame('john-smith', $post->slug);
    }

    public function test_it_does_not_regenerate_slug_on_update_when_no_source_column_changes()
    {
        $post = SluggableMultiSourceUpdatePost::create(['first_name' => 'John', 'last_name' => 'Doe']);
        $post->updated_at = now();
        $post->save();

        $this->assertSame('john-doe', $post->slug);
    }

    // unique

    public function test_it_resolves_collisions_with_numeric_suffixes()
    {
        SluggablePost::create(['name' => 'Hello World']);
        $post = SluggablePost::create(['name' => 'Hello World']);

        $this->assertSame('hello-world-2', $post->slug);
    }

    public function test_it_resolves_multiple_collisions_incrementally()
    {
        SluggablePost::create(['name' => 'Hello World']);
        SluggablePost::create(['name' => 'Hello World']);
        $post = SluggablePost::create(['name' => 'Hello World']);

        $this->assertSame('hello-world-3', $post->slug);
    }

    public function test_it_allows_duplicate_slugs_when_unique_is_false()
    {
        SluggableNonUniquePost::create(['name' => 'Hello World']);
        $post = SluggableNonUniquePost::create(['name' => 'Hello World']);

        $this->assertSame('hello-world', $post->slug);
    }

    public function test_it_includes_soft_deleted_records_in_uniqueness_check()
    {
        $post = SluggableSoftDeletePost::create(['name' => 'Hello']);
        $post->delete();

        $newPost = SluggableSoftDeletePost::create(['name' => 'Hello']);

        $this->assertSame('hello-2', $newPost->slug);
    }

    // maxAttempts

    public function test_it_throws_after_maximum_attempts_exceeded()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a unique slug for [Illuminate\Tests\Database\SluggableMaxAttemptsPost] with base [hello] after 2 attempts.');

        SluggableMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableMaxAttemptsPost::create(['name' => 'Hello']);
    }

    // maxLength

    public function test_it_respects_maximum_length()
    {
        $post = SluggableMaxLengthPost::create(['name' => 'This Is A Very Long Title That Should Be Truncated']);

        $this->assertTrue(mb_strlen($post->slug) <= 20);
    }

    public function test_it_respects_maximum_length_with_collision_suffix()
    {
        SluggableMaxLengthPost::create(['name' => 'This Is A Very Long Title']);
        $post = SluggableMaxLengthPost::create(['name' => 'This Is A Very Long Title']);

        $this->assertTrue(mb_strlen($post->slug) <= 20);
        $this->assertStringEndsWith('-2', $post->slug);
    }

    // slug generation

    public static function slugProvider(): array
    {
        return [
            // basic
            'simple words' => ['Hello World', 'hello-world'],
            'single word' => ['Laravel', 'laravel'],
            'already lowercase' => ['hello world', 'hello-world'],
            'mixed case' => ['HeLLo WoRLd', 'hello-world'],
            'all uppercase' => ['HELLO WORLD', 'hello-world'],
            'single character' => ['A', 'a'],
            'two words' => ['Foo Bar', 'foo-bar'],
            'three words' => ['Foo Bar Baz', 'foo-bar-baz'],
            'long title' => ['The Quick Brown Fox Jumps Over The Lazy Dog', 'the-quick-brown-fox-jumps-over-the-lazy-dog'],

            // whitespace
            'multiple spaces' => ['hello   world', 'hello-world'],
            'leading spaces' => ['  hello world', 'hello-world'],
            'trailing spaces' => ['hello world  ', 'hello-world'],
            'tabs' => ["hello\tworld", 'hello-world'],
            'newlines' => ["hello\nworld", 'hello-world'],
            'mixed whitespace' => ["hello \t\n world", 'hello-world'],

            // numbers
            'numbers only' => ['123', '123'],
            'number prefix' => ['123 Example', '123-example'],
            'number suffix' => ['Example 456', 'example-456'],
            'numbers mixed' => ['123 456 Example', '123-456-example'],
            'version number' => ['Version 2', 'version-2'],
            'year in title' => ['Best Of 2024', 'best-of-2024'],
            'ordinal' => ['1st Place', '1st-place'],

            // hyphens and underscores
            'existing hyphens' => ['hello-world', 'hello-world'],
            'existing underscores' => ['hello_world', 'hello-world'],
            'multiple hyphens' => ['hello---world', 'hello-world'],
            'multiple underscores' => ['hello___world', 'hello-world'],
            'mixed hyphens underscores' => ['hello-_-world', 'hello-world'],
            'underscore in phrase' => ['Test_123_Test', 'test-123-test'],
            'leading hyphen' => ['-hello', 'hello'],
            'trailing hyphen' => ['hello-', 'hello'],

            // special characters
            'ampersand' => ['Tom & Jerry', 'tom-jerry'],
            'plus sign' => ['C++ Programming', 'c-programming'],
            'parentheses' => ['Hello (World)', 'hello-world'],
            'brackets' => ['Hello [World]', 'hello-world'],
            'curly braces' => ['Hello {World}', 'hello-world'],
            'exclamation' => ['Hello World!', 'hello-world'],
            'question mark' => ['What Is This?', 'what-is-this'],
            'comma' => ['Hello, World', 'hello-world'],
            'semicolon' => ['Example;Path', 'example-path'],
            'colon' => ['Example:Path', 'example-path'],
            'quotes single' => ["It's Here", 'it-s-here'],
            'quotes double' => ['"Hello World"', 'hello-world'],
            'at sign' => ['user@host', 'user-host'],
            'hash' => ['Hello#World', 'hello-world'],
            'dollar' => ['100$ Deal', '100-deal'],
            'percent' => ['100% Done', '100-done'],
            'caret' => ['Hello^World', 'hello-world'],
            'asterisk' => ['Hello*World', 'hello-world'],
            'tilde' => ['Hello~World', 'hello-world'],
            'pipe' => ['Hello|World', 'hello-world'],
            'forward slash' => ['Example/Path', 'example-path'],
            'backslash' => ['Example\\Path', 'example-path'],
            'mixed symbols' => ['Hello!@#$%^&*()', 'hello'],
            'equals sign' => ['A=B', 'a-b'],
            'angle brackets' => ['<Hello>', 'hello'],

            // dots
            'basic domain' => ['laravel.com', 'laravel.com'],
            'spaces with dot' => ['My Website.js', 'my-website.js'],
            'multiple dots' => ['sub.domain.example.com', 'sub.domain.example.com'],
            'leading dots' => ['...example', 'example'],
            'trailing dots' => ['example...', 'example'],
            'consecutive dots' => ['example..test', 'example.test'],
            'mixed content with dot' => ['Hello World.pdf', 'hello-world.pdf'],
            'dot at boundaries' => ['.hello.', 'hello'],
            'special chars with dots' => ['Café.résumé', 'cafe.resume'],
            'empty segments from dots' => ['a..b..c', 'a.b.c'],
            'dot with separator' => ['hello-world.test', 'hello-world.test'],
            'single char dot segments' => ['a.b.c', 'a.b.c'],
            'numbers with dots' => ['version.2.0', 'version.2.0'],
            'ip address' => ['192.168.1.1', '192.168.1.1'],
            'file extensions' => ['document.final.pdf', 'document.final.pdf'],
            'spaces around dots' => ['hello . world', 'hello.world'],
            'uppercase with dots' => ['HELLO.WORLD', 'hello.world'],
            'mixed case with dots' => ['Hello.World.Test', 'hello.world.test'],
            'dot with underscores' => ['hello_world.test_file', 'hello-world.test-file'],
            'multiple spaces and dots' => ['hello   world . foo   bar', 'hello-world.foo-bar'],
            'dot only between words' => ['hello.world', 'hello.world'],
            'dot and hyphen' => ['hello-.world', 'hello.world'],

            // unicode - accented characters
            'french accents' => ['Café Résumé', 'cafe-resume'],
            'spanish tilde' => ['Niño Español', 'nino-espanol'],
            'portuguese cedilla' => ['Ação Programação', 'acao-programacao'],
            'scandinavian' => ['Ångström Ölsen', 'angstrom-olsen'],
            'czech characters' => ['Příliš Žluťoučký', 'prilis-zlutoucky'],
            'polish characters' => ['Łódź Źródło', 'lodz-zrodlo'],
            'turkish characters' => ['İstanbul Güneş', 'istanbul-gunes'],
            'vietnamese' => ['Việt Nam', 'viet-nam'],
            'german umlauts' => ['Über die Brücke', 'uber-die-brucke'],
            'german eszett' => ['Straße', 'strasse'],
            'french combined' => ['Crème Brûlée', 'creme-brulee'],
            'nordic combined' => ['Fjörður Ísland', 'fjordur-island'],

            // unicode - non-latin scripts
            'chinese characters' => ['你好世界', 'ni-hao-shi-jie'],
            'chinese with latin' => ['如何安装 Laravel', 'ru-he-an-zhuang-laravel'],
            'mixed chinese english' => ['Hello 你好', 'hello-ni-hao'],
            'japanese hiragana' => ['こんにちは', 'konnichiha'],
            'korean' => ['안녕하세요', 'annyeonghaseyo'],
            'russian cyrillic' => ['Привет Мир', 'privet-mir'],
            'ukrainian' => ['Київ Україна', 'kiyiv-ukrayina'],
            'greek' => ['Αθήνα Ελλάδα', 'athena-ellada'],
            'arabic' => ['مرحبا بالعالم', 'mrhb-bl-lm'],
            'thai' => ['สวัสดี', 'swasdii'],
            'hindi devanagari' => ['नमस्ते', 'nmste'],

            // unicode with dots
            'accents with dots' => ['über.straße', 'uber.strasse'],
            'chinese with dots' => ['你好.世界', 'ni-hao.shi-jie'],
            'cyrillic with dots' => ['Привет.Мир', 'privet.mir'],

            // emojis
            'emoji suffix' => ['Example 😊', 'example'],
            'emoji prefix' => ['🚀 Example', 'example'],
            'emoji between words' => ['Hello 🌍 World', 'hello-world'],
            'emoji inline' => ['Test🔥Case', 'testcase'],
            'emoji only prefix' => ['💡Test', 'test'],
            'multiple emojis' => ['🎉 Hello 🌟 World 🚀', 'hello-world'],

            // edge cases
            'very long word' => ['Supercalifragilisticexpialidocious', 'supercalifragilisticexpialidocious'],
            'repeated word' => ['test test test', 'test-test-test'],
            'single letter words' => ['a b c d', 'a-b-c-d'],
            'number zero' => ['0', '0'],
            'mixed everything' => ['Hello, World! (2024) - A Test_Case', 'hello-world-2024-a-test-case'],
            'url like' => ['https://example.com/path', 'https-example.com-path'],
            'email like' => ['user@example.com', 'user-example.com'],
            'file path' => ['src/Components/Button.tsx', 'src-components-button.tsx'],

            // apostrophes
            'apostrophe in word' => ["hello'world", 'hello-world'],
            'im contraction' => ["I'm happy", 'i-m-happy'],
            'its contraction' => ["it's a test", 'it-s-a-test'],
            'rock n roll' => ["rock 'n' roll", 'rock-n-roll'],
            'dont contraction' => ["don't stop", 'don-t-stop'],

            // special symbols (transliterated)
            'trademark' => ['Test™ Product', 'test-tm-product'],
            'euro sign' => ['Price €100', 'price-eur100'],
            'pound sign' => ['Weight £50', 'weight-ps50'],
            'degree sign' => ['Temp °C', 'temp-degc'],
            'section sign' => ['Section §1', 'section-ss1'],
            'copyright' => ['©2024 Company', 'c-2024-company'],
            'registered' => ['®Brand', 'r-brand'],

            // dashes and typography
            'em dash with spaces' => ['Hello   —   World', 'hello-world'],
            'en dash' => ['Hello – World', 'hello-world'],
            'em dash' => ['Hello — World', 'hello-world'],
            'double hyphen' => ['hello--world', 'hello-world'],
            'leading double hyphen' => ['--hello--world--', 'hello-world'],
            'test with triple hyphens' => ['Test---Case', 'test-case'],
            'test with triple underscores' => ['Test___Case', 'test-case'],
            'test hyphen spaced' => ['Test - Case', 'test-case'],
            'test underscore spaced' => ['Test _ Case', 'test-case'],

            // complex real-world
            'filename with version and copy' => ['file_name-v2.0 (copy)', 'file-name-v2.0-copy'],
            'bracketed year report' => ['[2024] Annual Report (Final)', '2024-annual-report-final'],
            'qa with symbols' => ['Q&A: Common Questions!', 'q-a-common-questions'],
            'price with slash' => ['Price: $49.99/month', 'price-49.99-month'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('slugProvider')]
    public function test_slug_generation(string $input, string $expected)
    {
        $post = SluggablePost::create(['name' => $input]);

        $this->assertSame($expected, $post->slug);
    }

    // edge cases

    public function test_it_throws_when_source_produces_empty_slug()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a slug for [Illuminate\Tests\Database\SluggablePost] from the given source value.');

        SluggablePost::create(['name' => '!!!']);
    }

    public function test_it_throws_when_emoji_only_source_produces_empty_slug()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a slug for [Illuminate\Tests\Database\SluggablePost] from the given source value.');

        SluggablePost::create(['name' => '🚀🎯🔥']);
    }
}

#[Sluggable]
class SluggablePost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(onUpdating: true)]
class SluggableUpdatePost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(scope: 'team_id')]
class SluggableScopedPost extends Model
{
    protected $table = 'sluggable_scoped_posts';

    protected $guarded = [];
}

#[Sluggable(scope: ['team_id', 'locale'])]
class SluggableMultiScopedPost extends Model
{
    protected $table = 'sluggable_multi_scoped_posts';

    protected $guarded = [];
}

#[Sluggable]
class SluggableSoftDeletePost extends Model
{
    use SoftDeletes;

    protected $table = 'sluggable_soft_delete_posts';

    protected $guarded = [];
}

#[Sluggable(from: ['first_name', 'last_name'])]
class SluggableMultiSourcePost extends Model
{
    protected $table = 'sluggable_multi_source_posts';

    protected $guarded = [];
}

#[Sluggable(from: ['first_name', 'last_name'], onUpdating: true)]
class SluggableMultiSourceUpdatePost extends Model
{
    protected $table = 'sluggable_multi_source_posts';

    protected $guarded = [];
}

#[Sluggable(onCreating: false)]
class SluggableNoCreatePost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(unique: false)]
class SluggableNonUniquePost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(language: 'de')]
class SluggableGermanPost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(maxLength: 20)]
class SluggableMaxLengthPost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(maxAttempts: 2)]
class SluggableMaxAttemptsPost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(separator: '_')]
class SluggableCustomSeparatorPost extends Model
{
    protected $table = 'sluggable_posts';

    protected $guarded = [];
}

#[Sluggable(from: 'title', column: 'url_slug')]
class SluggableCustomColumnsPost extends Model
{
    protected $table = 'sluggable_custom_posts';

    protected $guarded = [];
}
