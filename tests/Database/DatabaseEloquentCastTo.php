<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Database\Eloquent\Casts\To;
use Illuminate\Database\Eloquent\Casts\ToIterable;
use Illuminate\Database\Eloquent\Casts\ToString;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Encryption\Encrypter;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Fluent;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Support\Uri;
use Illuminate\Tests\Database\Fixtures\Enums\Foo;
use Illuminate\Tests\Database\Fixtures\Models\EloquentModelWithCastTo;
use InvalidArgumentException;
use Mockery as m;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;

use function implode;

class DatabaseEloquentCastTo extends TestCase
{
    protected $encrypter;
    protected $container;

    protected function setUp(): void
    {
        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();

        $this->container = new Application();
        $this->container->singleton('encrypter', fn () => $this->encrypter);

        Facade::setFacadeApplication($this->container);

        EloquentModelWithCastTo::$useCasts = null;
    }

    protected function tearDown(): void
    {
        $this->container->flush();

        Facade::clearResolvedInstances();

        EloquentModelWithCastTo::$useCasts = null;
    }

    protected function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->text('castable')->nullable();
            $table->timestamps();
        });
    }

    protected function crypt(): MockInterface
    {
        return $this->encrypter ??= m::mock(Encrypter::class);
    }

    protected function model(ToString|ToIterable $castTo, mixed $value): EloquentModelWithCastTo
    {
        EloquentModelWithCastTo::$useCasts = ['castable' => $castTo];

        return new EloquentModelWithCastTo(['castable' => $value]);
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
        return Eloquent::getConnectionResolver()->connection();
    }

    public function test_to_stringable(): void
    {
        $model = $this->model(To::stringable(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(Stringable::class, $instance);
        $this->assertEquals('test_string', $instance->toString());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_stringable_without_caching(): void
    {
        $model = $this->model(To::stringable()->withoutCaching(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(Stringable::class, $instance);
        $this->assertEquals('test_string', $instance->toString());
        $this->assertNotSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_stringable_encrypted(): void
    {
        $this->crypt()->expects('encryptString')->with('test_string')->andReturn('encrypted');
        $this->crypt()->expects('decryptString')->with('encrypted')->andReturn('test_string');

        $model = $this->model(To::stringable()->encrypted(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(Stringable::class, $instance);
        $this->assertEquals('test_string', $instance->toString());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('encrypted', DB::table('users')->value('castable'));
    }

    public function test_to_stringable_using(): void
    {
        $model = $this->model(To::stringable()->using(CustomStringable::class), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(CustomStringable::class, $instance);
        $this->assertEquals('test_string', $instance->toString());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_stringable_using_throws_when_incorrect_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided class must extend ['.Stringable::class.'].');

        $this->model(To::stringable()->using(Fluent::class), 'test_string');
    }

    public function test_to_html_string(): void
    {
        $model = $this->model(To::htmlString(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(HtmlString::class, $instance);
        $this->assertEquals('test_string', $instance->toHtml());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_html_string_without_caching(): void
    {
        $model = $this->model(To::htmlString()->withoutCaching(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(HtmlString::class, $instance);
        $this->assertEquals('test_string', $instance->toHtml());
        $this->assertNotSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_html_string_encrypted(): void
    {
        $this->crypt()->expects('encryptString')->with('test_string')->andReturn('encrypted');
        $this->crypt()->expects('decryptString')->with('encrypted')->andReturn('test_string');

        $model = $this->model(To::htmlString()->encrypted(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(HtmlString::class, $instance);
        $this->assertEquals('test_string', $instance->toHtml());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('encrypted', DB::table('users')->value('castable'));
    }

    public function test_to_html_string_using(): void
    {
        $model = $this->model(To::htmlString()->using(CustomHtmlString::class), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(CustomHtmlString::class, $instance);
        $this->assertEquals('test_string', $instance->toHtml());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_html_string_using_throws_when_incorrect_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided class must extend ['.HtmlString::class.'].');

        $this->model(To::htmlString()->using(Fluent::class), 'test_string');
    }

    public function test_to_uri(): void
    {
        $model = $this->model(To::uri(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(Uri::class, $instance);
        $this->assertEquals('test_string', (string) $instance);
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_uri_without_caching(): void
    {
        $model = $this->model(To::uri()->withoutCaching(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(Uri::class, $instance);
        $this->assertEquals('test_string', (string) $instance);
        $this->assertNotSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_uri_encrypted(): void
    {
        $this->crypt()->expects('encryptString')->with('test_string')->andReturn('encrypted');
        $this->crypt()->expects('decryptString')->with('encrypted')->andReturn('test_string');

        $model = $this->model(To::uri()->encrypted(), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(Uri::class, $instance);
        $this->assertEquals('test_string', (string) $instance);
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('encrypted', DB::table('users')->value('castable'));
    }

    public function test_to_uri_using(): void
    {
        $model = $this->model(To::htmlString()->using(CustomHtmlString::class), 'test_string');

        $instance = $model->castable;

        $this->assertInstanceOf(CustomHtmlString::class, $instance);
        $this->assertEquals('test_string', $instance->toHtml());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('test_string', DB::table('users')->value('castable'));
    }

    public function test_to_uri_using_throws_when_incorrect_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided class must extend ['.Uri::class.'].');

        $this->model(To::uri()->using(Fluent::class), 'test_string');
    }

    public function test_to_array_object(): void
    {
        $model = $this->model(To::arrayObject(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertSame('bar', $instance->foo);

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_without_caching(): void
    {
        $model = $this->model(To::arrayObject()->withoutCaching(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertNotSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_encrypted(): void
    {
        $this->crypt()->expects('encryptString')->with('{"foo":"bar"}')->andReturn('encrypted');
        $this->crypt()->expects('decryptString')->with('encrypted')->andReturn('{"foo":"bar"}');

        $model = $this->model(To::arrayObject()->encrypted(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('encrypted', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_using(): void
    {
        $model = $this->model(To::arrayObject()->using(CustomArrayObject::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(CustomArrayObject::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_using_throws_when_incorrect_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided class must extend ['.ArrayObject::class.'].');

        $this->model(To::arrayObject()->using(Fluent::class), 'test_string');
    }

    public function test_to_array_object_mapped_into(): void
    {
        $model = $this->model(To::arrayObject()->mappedInto(Stringable::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => new Stringable('bar')], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_mapped_into_enum(): void
    {
        $model = $this->model(To::arrayObject()->mappedInto(Foo::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => Foo::BAR], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_mapped(): void
    {
        $model = $this->model(To::arrayObject()->mapped([Str::class, 'upper']), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => 'BAR'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"BAR"}', DB::table('users')->value('castable'));
    }

    public function test_to_array_object_mapped_from_array(): void
    {
        $model = $this->model(To::arrayObject()->mappedFromArray(MappedFromArray::class), ['foo' => ['bar', 'baz']]);

        $instance = $model->castable;

        $this->assertInstanceOf(ArrayObject::class, $instance);
        $this->assertEquals(['foo' => 'bar+baz'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar+baz"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection(): void
    {
        $model = $this->model(To::collection(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertSame('bar', $instance->get('foo'));

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection_without_caching(): void
    {
        $model = $this->model(To::collection()->withoutCaching(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertNotSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection_encrypted(): void
    {
        $this->crypt()->expects('encryptString')->with('{"foo":"bar"}')->andReturn('encrypted');
        $this->crypt()->expects('decryptString')->with('encrypted')->andReturn('{"foo":"bar"}');

        $model = $this->model(To::collection()->encrypted(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('encrypted', DB::table('users')->value('castable'));
    }

    public function test_to_collection_using(): void
    {
        $model = $this->model(To::collection()->using(CustomCollection::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(CustomCollection::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection_using_throws_when_incorrect_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided class must extend ['.Collection::class.'].');

        $this->model(To::collection()->using(Fluent::class), 'test_string');
    }

    public function test_to_collection_mapped_into(): void
    {
        $model = $this->model(To::collection()->mappedInto(Stringable::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => new Stringable('bar')], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection_mapped_into_enum(): void
    {
        $model = $this->model(To::collection()->mappedInto(Foo::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => Foo::BAR], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection_mapped(): void
    {
        $model = $this->model(To::collection()->mapped([Str::class, 'upper']), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => 'BAR'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"BAR"}', DB::table('users')->value('castable'));
    }

    public function test_to_collection_mapped_from_array(): void
    {
        $model = $this->model(To::collection()->mappedFromArray(MappedFromArray::class), ['foo' => ['bar', 'baz']]);

        $instance = $model->castable;

        $this->assertInstanceOf(Collection::class, $instance);
        $this->assertEquals(['foo' => 'bar+baz'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar+baz"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent(): void
    {
        $model = $this->model(To::fluent(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertSame('bar', $instance->get('foo'));

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_without_caching(): void
    {
        $model = $this->model(To::fluent()->withoutCaching(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertNotSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_encrypted(): void
    {
        $this->crypt()->expects('encryptString')->with('{"foo":"bar"}')->andReturn('encrypted');
        $this->crypt()->expects('decryptString')->with('encrypted')->andReturn('{"foo":"bar"}');

        $model = $this->model(To::fluent()->encrypted(), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('encrypted', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_using(): void
    {
        $model = $this->model(To::fluent()->using(CustomFluent::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(CustomFluent::class, $instance);
        $this->assertEquals(['foo' => 'bar'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_using_throws_when_incorrect_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The provided class must extend ['.Fluent::class.'].');

        $this->model(To::fluent()->using(Collection::class), 'test_string');
    }

    public function test_to_fluent_mapped_into(): void
    {
        $model = $this->model(To::fluent()->mappedInto(Stringable::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => new Stringable('bar')], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_mapped_into_enum(): void
    {
        $model = $this->model(To::fluent()->mappedInto(Foo::class), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => Foo::BAR], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_mapped(): void
    {
        $model = $this->model(To::fluent()->mapped([Str::class, 'upper']), ['foo' => 'bar']);

        $instance = $model->castable;

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => 'BAR'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"BAR"}', DB::table('users')->value('castable'));
    }

    public function test_to_fluent_mapped_from_array(): void
    {
        $model = $this->model(To::fluent()->mappedFromArray(MappedFromArray::class), ['foo' => ['bar', 'baz']]);

        $instance = $model->castable;

        $this->assertInstanceOf(Fluent::class, $instance);
        $this->assertEquals(['foo' => 'bar+baz'], $instance->toArray());
        $this->assertSame($instance, $model->castable);

        $model->save();

        $this->assertSame('{"foo":"bar+baz"}', DB::table('users')->value('castable'));
    }
}

class CustomStringable extends Stringable
{
    //
}

class CustomHtmlString extends HtmlString
{
    //
}

class CustomUri extends Uri
{
    //
}

class CustomArrayObject extends ArrayObject
{
    //
}

class CustomCollection extends Collection
{
    //
}

class CustomFluent extends Fluent
{
    //
}

class MappedFromArray
{
    public static function fromArray($array)
    {
        return implode('+', $array);
    }
}
