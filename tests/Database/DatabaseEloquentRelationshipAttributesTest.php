<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Attributes\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Attributes\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Attributes\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Attributes\Relations\MorphedByMany;
use Illuminate\Database\Eloquent\Attributes\Relations\MorphMany;
use Illuminate\Database\Eloquent\Attributes\Relations\MorphOne;
use Illuminate\Database\Eloquent\Attributes\Relations\MorphTo;
use Illuminate\Database\Eloquent\Attributes\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentRelationshipAttributesTest extends TestCase
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

        $this->createSchema();
    }

    public function testLoadsBelongsToRelationship()
    {
        $user = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $post = Post::create([
            'title' => fake()->sentence,
            'content' => fake()->paragraph,
        ]);

        $post->author()->associate($user);
        $post->save();

        $this->assertEquals($post->author->id, $user->id);
        $this->assertEquals($post->user_id, $user->id);

        $post = Post::query()->find($post->id);
        $this->assertEquals($post->author->id, $user->id);
        $this->assertEquals($post->user_id, $user->id);

        $postWithoutUser = Post::create([
            'title' => fake()->sentence,
            'content' => fake()->paragraph,
        ]);

        $this->assertNull($postWithoutUser->author);

        $postWithoutUser = Post::query()->find($postWithoutUser->id);
        $this->assertNull($postWithoutUser->author);
    }

    public function testLoadsBelongsToManyRelationship()
    {
        $user = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $role = Role::create();
        $user->roleList()->attach($role);

        $this->assertCount(1, $user->roleList);
        $this->assertEquals($role->id, $user->roleList->first()->id);
        $this->assertCount(1, $role->users);
        $this->assertEquals($user->id, $role->users->first()->id);

        $user = User::query()->find($user->id);
        $this->assertCount(1, $user->roleList);
        $this->assertEquals($role->id, $user->roleList->first()->id);

        $role = Role::query()->find($role->id);
        $this->assertCount(1, $role->users);
        $this->assertEquals($user->id, $role->users->first()->id);

        $userWithoutRoles = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);
        $this->assertCount(0, $userWithoutRoles->roleList);

        $userWithoutRoles = User::query()->find($userWithoutRoles->id);
        $this->assertCount(0, $userWithoutRoles->roleList);

        $roleWithoutUsers = Role::create();
        $this->assertCount(0, $roleWithoutUsers->users);

        $roleWithoutUsers = Role::query()->find($roleWithoutUsers->id);
        $this->assertCount(0, $roleWithoutUsers->users);
    }

    public function testLoadsHasManyRelationship()
    {
        $user = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $post = Post::create([
            'title' => fake()->sentence,
            'content' => fake()->paragraph,
        ]);
        $user->articles()->save($post);

        $this->assertCount(1, $user->articles);
        $this->assertEquals($post->id, $user->articles->first()->id);
        $this->assertEquals($user->id, $post->author->id);
        $this->assertEquals($user->id, $post->user_id);

        $user = User::query()->find($user->id);
        $this->assertCount(1, $user->articles);
        $this->assertEquals($post->id, $user->articles->first()->id);

        $post = Post::query()->find($post->id);
        $this->assertEquals($user->id, $post->author->id);
        $this->assertEquals($user->id, $post->user_id);

        $userWithoutPosts = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $this->assertCount(0, $userWithoutPosts->articles);
    }

    public function testLoadsHasManyThroughRelationship()
    {
        $country = Country::create();

        $user = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $post = Post::create([
            'title' => fake()->sentence,
            'content' => fake()->paragraph,
        ]);

        $country->users()->save($user);
        $user->articles()->save($post);

        $this->assertCount(1, $country->posts);
        $this->assertEquals($post->id, $country->posts->first()->id);

        $country = Country::query()->find($country->id);
        $this->assertCount(1, $country->posts);
        $this->assertEquals($post->id, $country->posts->first()->id);

        $countryWithoutPosts = Country::create();
        $this->assertCount(0, $countryWithoutPosts->posts);
    }

    public function testLoadsHasOneRelationship()
    {
        $user = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $phone = Phone::create();
        $user->phone()->save($phone);

        $this->assertEquals($phone->id, $user->phone->id);
        $this->assertEquals($user->id, $phone->user->id);
        $this->assertEquals($user->id, $phone->user_id);

        $user = User::query()->find($user->id);
        $this->assertEquals($phone->id, $user->phone->id);

        $phone = Phone::query()->find($phone->id);
        $this->assertEquals($user->id, $phone->user->id);
        $this->assertEquals($user->id, $phone->user_id);

        $userWithoutPhone = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);

        $this->assertNull($userWithoutPhone->phone);
    }

    public function testLoadsHasOneThroughRelationship()
    {
        $seller = Seller::create();
        $computer = Computer::create();
        $manufacturer = Manufacturer::create();

        $seller->computer()->save($computer);
        $computer->manufacturer()->save($manufacturer);

        $this->assertEquals($manufacturer->id, $seller->manufacturer->id);
        $this->assertEquals($computer->id, $seller->computer->id);
        $this->assertEquals($manufacturer->id, $computer->manufacturer->id);

        $seller = Seller::query()->find($seller->id);
        $this->assertEquals($manufacturer->id, $seller->manufacturer->id);

        $sellerWithoutManufacturer = Seller::create();
        $this->assertNull($sellerWithoutManufacturer->manufacturer);
    }

    public function testLoadsMorphyManyAndMorphToRelationship()
    {
        $post = Post::create([
            'title' => fake()->sentence,
            'content' => fake()->paragraph,
        ]);
        $image = $post->images()->save(new Image());

        $this->assertCount(1, $post->images);
        $this->assertEquals($image->id, $post->images->first()->id);
        $this->assertEquals($post->id, $image->imageable->id);

        $post = Post::query()->find($post->id);
        $this->assertCount(1, $post->images);
        $this->assertEquals($image->id, $post->images->first()->id);

        $image = Image::query()->find($image->id);
        $this->assertEquals($post->id, $image->imageable->id);
    }

    public function testLoadsMorphyOneRelationship()
    {
        $user = User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ]);
        $image = $user->image()->save(new Image());

        $this->assertEquals($image->id, $user->image->id);
        $this->assertEquals($user->id, $image->imageable->id);

        $user = User::query()->find($user->id);
        $this->assertEquals($image->id, $user->image->id);

        $image = Image::query()->find($image->id);
        $this->assertEquals($user->id, $image->imageable->id);
    }

    public function testLoadsMorphyToManyAndMorphedByManyRelationship()
    {
        $post = Post::create([
            'title' => fake()->sentence,
            'content' => fake()->paragraph,
        ]);
        $tag = Tag::create();

        $post->tags()->attach($tag);

        $this->assertCount(1, $post->tags);
        $this->assertEquals($tag->id, $post->tags->first()->id);
        $this->assertCount(1, $tag->posts);
        $this->assertEquals($post->id, $tag->posts->first()->id);
    }

    public function testLoadsCamelCaseRelationship()
    {
        $bookCase = BookCase::create([
            'name' => fake()->name,
        ]);

        $book = $bookCase->books()->create([
            'name' => fake()->name,
        ]);

        $this->assertCount(1, $bookCase->books);
        $this->assertEquals($book->id, $bookCase->books->first()->id);
        $this->assertEquals($bookCase->id, $book->bookCase->id);
        $this->assertEquals($bookCase->id, $book->book_case_id);

        $bookCase = BookCase::query()->find($bookCase->id);
        $this->assertCount(1, $bookCase->books);
        $this->assertEquals($book->id, $bookCase->books->first()->id);

        $book = Book::query()->find($book->id);
        $this->assertEquals($bookCase->id, $book->bookCase->id);
        $this->assertEquals($bookCase->id, $book->book_case_id);
    }

    public function testWillNotAddUnnecessaryKeys()
    {
        User::create([
            'name' => fake()->name,
            'email' => fake()->unique()->safeEmail,
            'password' => 's3Cr3T@!!!',
        ])->workBooks()->create([
            'name' => fake()->name,
        ]);

        Library::create()->libraryBooks()->create([
            'name' => fake()->name,
        ]);

        $workBook = WorkBook::query()->first();
        $libraryBook = LibraryBook::query()->first();

        $this->assertTrue($workBook->save());
        $this->assertTrue($libraryBook->save());
    }

    public function testLoadsRelationshipWithArguments()
    {
        $book = Book::create([
            'name' => fake()->name,
        ]);

        $price = $book->prices()->create([
            'price' => fake()->numberBetween(1, 500),
        ]);

        $this->assertCount(1, $book->prices);
        $this->assertEquals($price->id, $book->prices->first()->id);
        $this->assertEquals($book->id, $price->book->id);
        $this->assertEquals($book->id, $price->custom_id);

        $book = Book::query()->find($book->id);
        $this->assertCount(1, $book->prices);
        $this->assertEquals($price->id, $book->prices->first()->id);

        $price = Price::query()->find($price->id);
        $this->assertEquals($book->id, $price->book->id);
        $this->assertEquals($book->id, $price->custom_id);
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->foreignId('country_id')->nullable()->constrained();
            $table->boolean('active')->default(false);
            $table->timestamps();
        });

        $this->schema()->create('products', function ($table) {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->integer('random_number');
            $table->integer('another_random_number')->nullable();
            $table->json('json_column')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->id();
            $table->string('title');
            $table->float('content');
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        $this->schema()->create('roles', function ($table) {
            $table->id();
            $table->timestamps();
        });

        $this->schema()->create('role_user', function ($table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('role_id')->constrained();
            $table->timestamps();
        });

        $this->schema()->create('countries', function ($table) {
            $table->id();
            $table->timestamps();
        });

        $this->schema()->create('phones', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->timestamps();
        });

        $this->schema()->create('sellers', function ($table) {
            $table->id();
            $table->timestamps();
        });

        $this->schema()->create('computers', function ($table) {
            $table->id();
            $table->foreignId('seller_id')->nullable()->constrained();
            $table->timestamps();
        });

        $this->schema()->create('manufacturers', function ($table) {
            $table->id();
            $table->foreignId('computer_id')->nullable()->constrained();
            $table->timestamps();
        });

        $this->schema()->create('images', function ($table) {
            $table->id();
            $table->morphs('imageable');
            $table->timestamps();
        });

        $this->schema()->create('tags', function ($table) {
            $table->id();
            $table->timestamps();
        });

        $this->schema()->create('taggables', function ($table) {
            $table->id();
            $table->foreignId('tag_id')->constrained();
            $table->morphs('taggable');
            $table->timestamps();
        });

        $this->schema()->create('book_cases', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('books', function ($table) {
            $table->id();
            $table->foreignId('book_case_id')->nullable()->constrained();
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('libraries', function ($table) {
            $table->id();
            $table->timestamps();
        });

        $this->schema()->create('library_books', function ($table) {
            $table->id();
            $table->foreignId('library_id')->nullable()->constrained();
            $table->foreignId('book_case_id')->nullable()->constrained();
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('work_books', function ($table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('book_case_id')->nullable()->constrained();
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('prices', function ($table) {
            $table->id();
            $table->foreignId('custom_id')->nullable()->constrained();
            $table->decimal('price');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
        $this->schema()->drop('products');
        $this->schema()->drop('posts');
        $this->schema()->drop('roles');
        $this->schema()->drop('role_user');
        $this->schema()->drop('countries');
        $this->schema()->drop('phones');
        $this->schema()->drop('sellers');
        $this->schema()->drop('computers');
        $this->schema()->drop('manufacturers');
        $this->schema()->drop('images');
        $this->schema()->drop('tags');
        $this->schema()->drop('taggables');
        $this->schema()->drop('book_cases');
        $this->schema()->drop('books');
        $this->schema()->drop('libraries');
        $this->schema()->drop('library_books');
        $this->schema()->drop('work_books');
        $this->schema()->drop('prices');
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    protected function connection()
    {
        return Model::getConnectionResolver()->connection();
    }
}

#[BelongsTo(BookCase::class)]
#[HasMany(Price::class, null, 'custom_id', 'id')]
class Book extends Model
{
    protected $guarded = [];
}

#[HasMany(Book::class)]
class BookCase extends Model
{
    protected $guarded = [];
}

#[HasOne(Manufacturer::class)]
class Computer extends Model
{
    protected $guarded = [];
}

#[HasMany(User::class)]
#[HasManyThrough(Post::class, User::class)]
class Country extends Model
{
    protected $guarded = [];
}

#[MorphTo('imageable')]
class Image extends Model
{
    protected $guarded = [];
}

#[HasMany(LibraryBook::class)]
class Library extends Model
{
    protected $guarded = [];
}

#[BelongsTo(Library::class)]
class LibraryBook extends Book
{
    protected $guarded = [];
}

class Manufacturer extends Model
{
    protected $guarded = [];
}

#[BelongsTo(User::class)]
class Phone extends Model
{
    protected $guarded = [];
}

#[BelongsTo(related: User::class, name: 'author')]
#[MorphMany(Image::class, 'imageable')]
#[MorphToMany(Tag::class, 'taggable')]
class Post extends Model
{
    protected $guarded = [];
}

#[BelongsTo(Book::class, null, 'custom_id', 'id')]
class Price extends Model
{
    protected $guarded = [];
}

#[BelongsToMany(User::class)]
class Role extends Model
{
    protected $guarded = [];
}

#[HasOneThrough(Manufacturer::class, Computer::class)]
#[HasOne(Computer::class)]
class Seller extends Model
{
    protected $guarded = [];
}

#[MorphedByMany(Post::class, 'taggable')]
class Tag extends Model
{
    protected $guarded = [];
}

#[BelongsToMany(related: Role::class, name: 'roleList')]
#[HasMany(related: Post::class, name: 'articles')]
#[HasMany(WorkBook::class)]
#[HasOne(Phone::class)]
#[MorphOne(Image::class, 'imageable')]
class User extends Model
{
    protected $guarded = [];
}

#[BelongsTo(User::class)]
final class WorkBook extends Book
{
    protected $guarded = [];
}
