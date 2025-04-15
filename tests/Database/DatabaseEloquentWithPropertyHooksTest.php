<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithPropertyHooksTest extends TestCase
{
    protected function setUp(): void
    {
        if (PHP_VERSION_ID < 80400) {
            static::markTestSkipped(
                'Property Hooks are not available to test in PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
            );
        }

        parent::setUp();

        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    protected function createSchema()
    {
        $this->schema()->create('test', function ($table) {
            $table->increments('id');
            $table->text('foo_bar');
            $table->timestamps();
        });
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

    public function test_collects_setter_hooks(): void
    {
        $model = new class extends Model {
            protected $table = 'test';

            public $fooFoo {
                get => true;
            }

            public $barBar {
                set {
                    //
                }
            }

            public $bazBaz {
                get => true;
                set {
                    //
                }
            }

            public $quzQuz;

            public function getCache()
            {
                return static::$attributePropertyHookGetterCache[get_class($this)];
            }

            public function setCache()
            {
                return static::$attributePropertyHookSetterCache[get_class($this)];
            }
        };

        $this->assertTrue($model->hasPropertyHookGetter('fooFoo'));
        $this->assertFalse($model->hasPropertyHookSetter('fooFoo'));

        $this->assertFalse($model->hasPropertyHookGetter('barBar'));
        $this->assertTrue($model->hasPropertyHookSetter('barBar'));

        $this->assertTrue($model->hasPropertyHookGetter('bazBaz'));
        $this->assertTrue($model->hasPropertyHookSetter('bazBaz'));

        $this->assertFalse($model->hasPropertyHookGetter('quzQuz'));
        $this->assertFalse($model->hasPropertyHookSetter('quzQuz'));

        $this->assertSame([
            'fooFoo' => true,
            'barBar' => false,
            'bazBaz' => true,
            'quzQuz' => false,
        ], $model->getCache());

        $this->assertSame([
            'fooFoo' => false,
            'barBar' => true,
            'bazBaz' => true,
            'quzQuz' => false,
        ], $model->setCache());
    }


    public function test_casts_with_property_hooks(): void
    {
        $model = new class extends Model {
            protected $table = 'test';

            protected $fooBar {
                get => isset($this->attributes['foo_bar']) ? strtoupper($this->attributes['foo_bar']) : null;
                set {
                    $this->attributes['foo_bar'] = strtolower($value);
                }
            }
        };

        $this->assertNull($model->foo_bar);

        $model->foo_bar = 'bAz';

        $this->assertSame('BAZ', $model->foo_bar);
        $this->assertSame('baz', $model->getAttributes()['foo_bar']);

        $model->save();

        $model = $model->newQuery()->find(1);

        $this->assertSame('BAZ', $model->foo_bar);
        $this->assertSame('baz', $model->getAttributes()['foo_bar']);
    }

    public function test_cast_with_property_hook_get(): void
    {
        $model = new class extends Model {
            protected $table = 'test';

            protected $fooBar {
                get => isset($this->attributes['foo_bar']) ? strtoupper($this->attributes['foo_bar']) : null;
            }
        };

        $this->assertNull($model->foo_bar);

        $model->foo_bar = 'bAz';

        $this->assertSame('BAZ', $model->foo_bar);
        $this->assertSame('bAz', $model->getAttributes()['foo_bar']);

        $model->save();

        $model = $model->newQuery()->find(1);

        $this->assertSame('BAZ', $model->fooBar);
        $this->assertSame('BAZ', $model->foo_bar);
        $this->assertSame('bAz', $model->getAttributes()['foo_bar']);
    }

    public function test_cast_with_property_hook_set(): void
    {
        $model = new class extends Model {
            protected $table = 'test';

            protected $fooBar {
                set {
                    $this->attributes['foo_bar'] = strtolower($value);
                }
            }
        };

        $this->assertNull($model->foo_bar);

        $model->foo_bar = 'BAZ';

        $this->assertSame('baz', $model->foo_bar);
        $this->assertSame('baz', $model->getAttributes()['foo_bar']);

        $model->save();

        $model = $model->newQuery()->find(1);

        $this->assertSame('baz', $model->foo_bar);
        $this->assertSame('baz', $model->getAttributes()['foo_bar']);
    }

    public function test_property_hook_get_is_appended_using_getters(): void
    {
        $model = new class extends Model {
            protected $table = 'test';

            protected $appends = [
                'baz_quz',
            ];

            protected $bazQuz {
                get => 'baz_QUZ';
            }
        };

        $model->foo_bar = 'bAz';

        $this->assertSame(['foo_bar' => 'bAz', 'baz_quz' => 'baz_QUZ'], $model->toArray());
    }

    public function test_property_hook_takes_precedence_over_mutator_and_attribute(): void
    {
        $model = new class extends Model {
            protected $table = 'test';

            protected $bazQuz {
                get => $this->attributes['foo_bar'];
                set {
                    $this->attributes['foo_bar'] = $value;
                }
            }

            protected function getBazQuzAttribute()
            {
                return 'invalid';
            }

            protected function setBazQuzAttribute()
            {
                return $this->attributes['foo_bar'] = 'invalid';
            }

            protected function bazQuz(): Attribute
            {
                return Attribute::make(fn () => 'invalid', fn() => ['foo_bar' => 'invalid']);
            }
        };

        $model->baz_quz = 'valid';

        $this->assertSame('valid', $model->baz_quz);
    }
}
