<?php

namespace Illuminate\Tests\Foundation;

use Error;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Tests\Foundation\Fixtures\LegacyPost;
use Illuminate\Tests\Foundation\Fixtures\Models\BlogPost;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FoundationFormRequestModelTest extends TestCase
{
    protected $db;

    protected $app;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new DB;
        $this->db->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $this->db->bootEloquent();

        $this->db->getConnection()->getSchemaBuilder()->create('blog_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('account_id');
            $table->string('slug')->unique();
        });

        $this->app = new class extends Application
        {
            public function getNamespace()
            {
                return 'Illuminate\\Tests\\Foundation\\Fixtures\\';
            }
        };
    }

    protected function tearDown(): void
    {
        BlogPost::clearBootedModels();
        Model::unsetConnectionResolver();
        $this->db->getConnection()->disconnect();
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testModelCanBeRetrievedUsingAnExplicitClass()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['article_id' => $post->id]);

        $this->assertTrue($request->model('article_id', BlogPost::class)->is($post));
    }

    public function testModelClassCanBeDiscoveredFromTheInputKey()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $this->assertTrue($request->model('blog_post_id')->is($post));
    }

    public function testDiscoveryPrefersTheModelsNamespaceWhenBothClassesExist()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $this->assertSame(BlogPost::class, $request->model('blog_post_id')::class);
    }

    public function testDiscoveryFallsBackToTheApplicationNamespace()
    {
        $post = LegacyPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['legacy_post_id' => $post->id]);
        $request->setContainer($this->app);

        $model = $request->model('legacy_post_id');

        $this->assertInstanceOf(LegacyPost::class, $model);
        $this->assertTrue($model->is($post));
    }

    public function testCallbackCanBePassedAsTheSecondArgument()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $model = $request->model('blog_post_id', function (Builder $query) {
            $query->where('account_id', 1);
        });

        $this->assertTrue($model->is($post));

        $this->assertNull($request->model('blog_post_id', function (Builder $query) {
            $query->where('account_id', 2);
        }));
    }

    public function testCallbackCanBePassedWithAnExplicitClass()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['article_id' => $post->id]);

        $model = $request->model('article_id', BlogPost::class, function (Builder $query) {
            $query->where('account_id', 1);
        });

        $this->assertTrue($model->is($post));

        $this->assertNull($request->model('article_id', BlogPost::class, function (Builder $query) {
            $query->where('account_id', 2);
        }));
    }

    public function testCallbackCanBePassedWithANullClass()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $this->assertNull($request->model('blog_post_id', null, function (Builder $query) {
            $query->where('account_id', 2);
        }));
    }

    #[DataProvider('emptyInputProvider')]
    public function testEmptyInputReturnsNullWithoutResolvingAClassOrCallingTheCallback($input)
    {
        $request = FormRequest::create('/', 'POST', $input);

        $this->assertNull($request->model('blog_post_id', function () {
            $this->fail('The callback should not be called for empty input.');
        }));
    }

    public static function emptyInputProvider()
    {
        return [
            [[]],
            [['blog_post_id' => null]],
            [['blog_post_id' => '']],
            [['blog_post_id' => '   ']],
        ];
    }

    public function testArrayInputReturnsNull()
    {
        $first = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $second = BlogPost::create(['account_id' => 1, 'slug' => 'second']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => [$first->id, $second->id]]);
        $request->setContainer($this->app);

        $this->assertNull($request->model('blog_post_id'));
    }

    public function testArrayInputReturnsNullForStringKeys()
    {
        SlugKeyedBlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['slug' => ['first']]);

        $this->assertNull($request->model('slug', SlugKeyedBlogPost::class));
    }

    public function testMissingRecordReturnsNull()
    {
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => 1]);
        $request->setContainer($this->app);

        $this->assertNull($request->model('blog_post_id'));
    }

    public function testZeroIsAValidIdentifier()
    {
        $post = BlogPost::create(['id' => 0, 'account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => 0]);
        $request->setContainer($this->app);

        $this->assertTrue($request->model('blog_post_id')->is($post));
    }

    public function testGlobalScopesAreAppliedAndCanBeRemovedByTheCallback()
    {
        $post = BlogPost::create(['account_id' => 2, 'slug' => 'first']);
        BlogPost::addGlobalScope('account', function (Builder $query) {
            $query->where('account_id', 1);
        });

        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $this->assertNull($request->model('blog_post_id'));
        $this->assertTrue($request->model('blog_post_id', function (Builder $query) {
            $query->withoutGlobalScope('account');
        })->is($post));
    }

    public function testCustomStringPrimaryKeyCanBeUsedWithNestedInput()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['article' => ['slug' => $post->slug]]);

        $model = $request->model('article.slug', SlugKeyedBlogPost::class);

        $this->assertInstanceOf(SlugKeyedBlogPost::class, $model);
        $this->assertSame($post->slug, $model->getKey());
    }

    public function testUnknownModelClassThrowsAnError()
    {
        $request = FormRequest::create('/', 'POST', ['unknown_id' => 1]);
        $request->setContainer($this->app);

        $this->expectException(Error::class);
        $this->expectExceptionMessage('Illuminate\\Tests\\Foundation\\Fixtures\\Unknown');

        $request->model('unknown_id');
    }

    public function testModelOrFailCanDiscoverTheModelClass()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $this->assertTrue($request->modelOrFail('blog_post_id')->is($post));
    }

    public function testModelOrFailSupportsAnExplicitClassAndCallback()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['article_id' => $post->id]);

        $model = $request->modelOrFail('article_id', BlogPost::class, function (Builder $query) {
            $query->where('account_id', 1);
        });

        $this->assertTrue($model->is($post));
    }

    public function testModelOrFailSupportsACallbackAsTheSecondArgument()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $model = $request->modelOrFail('blog_post_id', function (Builder $query) {
            $query->where('account_id', 1);
        });

        $this->assertTrue($model->is($post));
    }

    public function testModelOrFailThrowsWhenTheRecordDoesNotExist()
    {
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => 123]);
        $request->setContainer($this->app);

        $this->expectExceptionObject((new ModelNotFoundException)->setModel(BlogPost::class));

        $request->modelOrFail('blog_post_id');
    }

    public function testModelOrFailThrowsWhenTheCallbackExcludesTheRecord()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $this->expectExceptionObject((new ModelNotFoundException)->setModel(BlogPost::class));

        $request->modelOrFail('blog_post_id', function (Builder $query) {
            $query->where('account_id', 2);
        });
    }

    #[DataProvider('emptyInputProvider')]
    public function testModelOrFailThrowsForEmptyInput($input)
    {
        BlogPost::create(['id' => 0, 'account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', $input);

        $this->expectExceptionObject((new ModelNotFoundException)->setModel(BlogPost::class));

        $request->modelOrFail('blog_post_id', BlogPost::class, function (Builder $query) {
            $query->where('account_id', 1);
        });
    }

    public function testModelOrFailThrowsForArrayInput()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => [$post->id]]);

        $this->expectExceptionObject((new ModelNotFoundException)->setModel(BlogPost::class));

        $request->modelOrFail('blog_post_id', BlogPost::class);
    }

    public function testModelOrFailCanRetrieveAZeroIdentifier()
    {
        $post = BlogPost::create(['id' => 0, 'account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => 0]);

        $this->assertTrue($request->modelOrFail('blog_post_id', BlogPost::class)->is($post));
    }

    public function testModelsCanBeRetrievedUsingAnExplicitClass()
    {
        $first = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $second = BlogPost::create(['account_id' => 1, 'slug' => 'second']);
        BlogPost::create(['account_id' => 1, 'slug' => 'third']);

        $request = FormRequest::create('/', 'POST', [
            'article_ids' => [$first->id, $second->id, $first->id, 999],
        ]);

        $models = $request->models('article_ids', BlogPost::class);

        $this->assertInstanceOf(Collection::class, $models);
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $models->modelKeys());
    }

    #[DataProvider('collectionKeyProvider')]
    public function testModelsCanDiscoverTheModelClass($key)
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', [$key => [$post->id]]);
        $request->setContainer($this->app);

        $models = $request->models($key);

        $this->assertSame(BlogPost::class, $models->first()::class);
        $this->assertSame([$post->id], $models->modelKeys());
    }

    public static function collectionKeyProvider()
    {
        return [
            ['blog_post_ids'],
            ['blog_posts'],
        ];
    }

    public function testModelsCanDiscoverAModelInTheApplicationNamespace()
    {
        $post = LegacyPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['legacy_post_ids' => [$post->id]]);
        $request->setContainer($this->app);

        $this->assertInstanceOf(LegacyPost::class, $request->models('legacy_post_ids')->first());
    }

    public function testModelsSupportsACallbackAsTheSecondArgument()
    {
        $first = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $second = BlogPost::create(['account_id' => 2, 'slug' => 'second']);
        $request = FormRequest::create('/', 'POST', ['blog_post_ids' => [$first->id, $second->id]]);
        $request->setContainer($this->app);

        $models = $request->models('blog_post_ids', function (Builder $query) {
            $query->where('account_id', 1);
        });

        $this->assertSame([$first->id], $models->modelKeys());
    }

    public function testModelsSupportsAnExplicitClassAndCallback()
    {
        $first = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $second = BlogPost::create(['account_id' => 2, 'slug' => 'second']);
        $request = FormRequest::create('/', 'POST', ['article_ids' => [$first->id, $second->id]]);

        $models = $request->models('article_ids', BlogPost::class, function (Builder $query) {
            $query->where('account_id', 2);
        });

        $this->assertSame([$second->id], $models->modelKeys());
    }

    public function testModelsSupportsANullClassAndCallback()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_ids' => [$post->id]]);
        $request->setContainer($this->app);

        $models = $request->models('blog_post_ids', null, function (Builder $query) {
            $query->where('account_id', 2);
        });

        $this->assertTrue($models->isEmpty());
    }

    public function testModelsReturnsAnEmptyCollectionWithoutQueryingForMissingOrEmptyInput()
    {
        $request = FormRequest::create('/', 'POST', ['article_ids' => []]);
        $this->db->getConnection()->enableQueryLog();

        $this->assertTrue($request->models('article_ids', BlogPost::class)->isEmpty());
        $this->assertTrue($request->models('missing_ids', BlogPost::class)->isEmpty());
        $this->assertSame([], $this->db->getConnection()->getQueryLog());
    }

    public function testModelsPreservesCustomCollectionsForEmptyInput()
    {
        $request = FormRequest::create('/', 'POST', ['article_ids' => []]);

        $this->assertInstanceOf(
            BlogPostCollection::class, $request->models('article_ids', CollectedBlogPost::class)
        );
    }

    public function testModelsUsesCustomPrimaryKeysAndNestedInput()
    {
        $post = BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['articles' => ['slugs' => [$post->slug]]]);

        $models = $request->models('articles.slugs', SlugKeyedBlogPost::class);

        $this->assertSame([$post->slug], $models->modelKeys());
    }

    public function testModelGroupsCallbackConditionsBeforeApplyingTheIdentifier()
    {
        BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $post = BlogPost::create(['account_id' => 2, 'slug' => 'second']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => $post->id]);
        $request->setContainer($this->app);

        $model = $request->model('blog_post_id', function (Builder $query) {
            $query->where('account_id', 1)->orWhere('account_id', 2);
        });

        $this->assertTrue($model->is($post));
    }

    public function testModelOrFailGroupsCallbackConditionsBeforeApplyingTheIdentifier()
    {
        BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $request = FormRequest::create('/', 'POST', ['blog_post_id' => 999]);
        $request->setContainer($this->app);

        $this->expectException(ModelNotFoundException::class);

        $request->modelOrFail('blog_post_id', function (Builder $query) {
            $query->where('account_id', 1)->orWhere('account_id', 2);
        });
    }

    public function testModelsGroupsCallbackConditionsBeforeApplyingTheIdentifiers()
    {
        BlogPost::create(['account_id' => 1, 'slug' => 'first']);
        $post = BlogPost::create(['account_id' => 2, 'slug' => 'second']);
        $request = FormRequest::create('/', 'POST', ['blog_post_ids' => [$post->id]]);
        $request->setContainer($this->app);

        $models = $request->models('blog_post_ids', function (Builder $query) {
            $query->where('account_id', 1)->orWhere('account_id', 2);
        });

        $this->assertSame([$post->id], $models->modelKeys());
    }
}

class SlugKeyedBlogPost extends BlogPost
{
    protected $table = 'blog_posts';

    protected $primaryKey = 'slug';

    protected $keyType = 'string';

    public $incrementing = false;
}

class CollectedBlogPost extends BlogPost
{
    protected $table = 'blog_posts';

    public function newCollection(array $models = [])
    {
        return new BlogPostCollection($models);
    }
}

class BlogPostCollection extends Collection
{
}
