<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Attributes\Sluggable;
use Illuminate\Database\Eloquent\CouldNotGenerateSlugException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;

class EloquentSluggableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('sluggable_validation_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->string('url_slug')->nullable();
            $table->timestamps();
        });

        Schema::create('sluggable_validation_multi_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('sluggable_validation_posts');
        Schema::dropIfExists('sluggable_validation_multi_posts');

        parent::tearDown();
    }

    public function test_it_generates_slug_on_create()
    {
        $post = SluggableValidationPost::create(['name' => 'Hello World']);

        $this->assertSame('hello-world', $post->slug);
    }

    public function test_exception_converts_to_validation_exception_via_inner_exception()
    {
        try {
            SluggableValidationPost::create(['name' => '!!!']);
            $this->fail('Expected CouldNotGenerateSlugException was not thrown.');
        } catch (CouldNotGenerateSlugException $e) {
            $inner = $e->getInnerException();

            $this->assertInstanceOf(ValidationException::class, $inner);
            $this->assertArrayHasKey('name', $inner->errors());
            $this->assertSame('The name must be able to generate a valid slug.', $inner->errors()['name'][0]);
        }
    }

    public function test_exception_with_custom_error_key()
    {
        try {
            SluggableValidationCustomErrorPost::create(['name' => '!!!']);
            $this->fail('Expected CouldNotGenerateSlugException was not thrown.');
        } catch (CouldNotGenerateSlugException $e) {
            $inner = $e->getInnerException();

            $this->assertInstanceOf(ValidationException::class, $inner);
            $this->assertArrayHasKey('custom_field', $inner->errors());
        }
    }

    public function test_exception_with_custom_error_message()
    {
        try {
            SluggableValidationCustomMessagePost::create(['name' => '!!!']);
            $this->fail('Expected CouldNotGenerateSlugException was not thrown.');
        } catch (CouldNotGenerateSlugException $e) {
            $inner = $e->getInnerException();

            $this->assertInstanceOf(ValidationException::class, $inner);
            $this->assertSame('Please enter a valid name.', $inner->errors()['name'][0]);
        }
    }

    public function test_exception_with_custom_error_key_and_message()
    {
        try {
            SluggableValidationFullCustomPost::create(['name' => '!!!']);
            $this->fail('Expected CouldNotGenerateSlugException was not thrown.');
        } catch (CouldNotGenerateSlugException $e) {
            $inner = $e->getInnerException();

            $this->assertInstanceOf(ValidationException::class, $inner);
            $this->assertArrayHasKey('title', $inner->errors());
            $this->assertSame('Please provide a valid title.', $inner->errors()['title'][0]);
        }
    }

    public function test_uniqueness_exception_uses_column_as_error_key()
    {
        $e = new CouldNotGenerateSlugException('Could not generate a unique slug.', 'slug', 'The slug could not be generated.');
        $inner = $e->getInnerException();

        $this->assertInstanceOf(ValidationException::class, $inner);
        $this->assertArrayHasKey('slug', $inner->errors());
    }

    public function test_it_throws_when_source_produces_empty_slug()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a slug for [Illuminate\Tests\Integration\Database\SluggableValidationPost] using column(s) [name].');

        SluggableValidationPost::create(['name' => '!!!']);
    }

    public function test_it_throws_when_emoji_only_source_produces_empty_slug()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a slug for [Illuminate\Tests\Integration\Database\SluggableValidationPost] using column(s) [name].');

        SluggableValidationPost::create(['name' => '🚀🎯🔥']);
    }

    public function test_it_throws_when_source_column_is_null()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a slug for [Illuminate\Tests\Integration\Database\SluggableValidationPost] using column(s) [name].');

        SluggableValidationPost::create([]);
    }

    public function test_it_throws_after_maximum_attempts_exceeded()
    {
        $this->expectException(CouldNotGenerateSlugException::class);
        $this->expectExceptionMessage('Could not generate a unique slug for [Illuminate\Tests\Integration\Database\SluggableValidationMaxAttemptsPost] with base [hello] after 2 attempts.');

        SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
        SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
    }

    public function test_json_request_returns_422_with_validation_errors()
    {
        Route::post('/posts', function () {
            SluggableValidationPost::create(['name' => '!!!']);
        });

        $this->postJson('/posts')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_web_request_redirects_back_with_errors()
    {
        Route::post('/posts', function () {
            SluggableValidationPost::create(['name' => '!!!']);
        });

        $this->from('/create')
            ->post('/posts')
            ->assertRedirect('/create')
            ->assertSessionHasErrors(['name']);
    }

    public function test_json_request_returns_custom_error_key_and_message()
    {
        Route::post('/posts', function () {
            SluggableValidationFullCustomPost::create(['name' => '!!!']);
        });

        $this->postJson('/posts')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title'])
            ->assertJson([
                'errors' => [
                    'title' => ['Please provide a valid title.'],
                ],
            ]);
    }

    public function test_multi_source_error_message_lists_all_columns()
    {
        try {
            SluggableValidationMultiSourcePost::create(['first_name' => '!!!', 'last_name' => '!!!']);
            $this->fail('Expected CouldNotGenerateSlugException was not thrown.');
        } catch (CouldNotGenerateSlugException $e) {
            $inner = $e->getInnerException();
            $this->assertArrayHasKey('first_name', $inner->errors());
            $this->assertSame('The first name and last name must be able to generate a valid slug.', $inner->errors()['first_name'][0]);
        }
    }

    public function test_custom_column_appears_in_error_message()
    {
        try {
            SluggableValidationCustomColumnPost::create(['name' => '!!!']);
            $this->fail('Expected CouldNotGenerateSlugException was not thrown.');
        } catch (CouldNotGenerateSlugException $e) {
            $inner = $e->getInnerException();
            $this->assertSame('The name must be able to generate a valid url slug.', $inner->errors()['name'][0]);
        }
    }

    public function test_uniqueness_failure_returns_422_via_json()
    {
        Route::post('/posts', function () {
            SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
            SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
            SluggableValidationMaxAttemptsPost::create(['name' => 'Hello']);
        });

        $this->postJson('/posts')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_uniqueness_failure_uses_custom_error_key()
    {
        Route::post('/posts', function () {
            SluggableValidationCustomErrorMaxAttemptsPost::create(['name' => 'Hello']);
            SluggableValidationCustomErrorMaxAttemptsPost::create(['name' => 'Hello']);
            SluggableValidationCustomErrorMaxAttemptsPost::create(['name' => 'Hello']);
        });

        $this->postJson('/posts')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['custom_field']);
    }
}

#[Sluggable]
class SluggableValidationPost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}

#[Sluggable(errorKey: 'custom_field')]
class SluggableValidationCustomErrorPost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}

#[Sluggable(errorMessage: 'Please enter a valid name.')]
class SluggableValidationCustomMessagePost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}

#[Sluggable(errorKey: 'title', errorMessage: 'Please provide a valid title.')]
class SluggableValidationFullCustomPost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}

#[Sluggable(from: ['first_name', 'last_name'])]
class SluggableValidationMultiSourcePost extends Model
{
    protected $table = 'sluggable_validation_multi_posts';

    protected $guarded = [];
}

#[Sluggable(from: 'name', to: 'url_slug')]
class SluggableValidationCustomColumnPost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}

#[Sluggable(maxAttempts: 2)]
class SluggableValidationMaxAttemptsPost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}

#[Sluggable(maxAttempts: 2, errorKey: 'custom_field')]
class SluggableValidationCustomErrorMaxAttemptsPost extends Model
{
    protected $table = 'sluggable_validation_posts';

    protected $guarded = [];
}
