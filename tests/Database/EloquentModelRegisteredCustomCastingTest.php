<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('integration')]
class EloquentModelRegisteredCustomCastingTest extends TestCase
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
        // $this->createSchema();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // register custom casts
        Model::registerCustomCast('uppercase', UppercaseCaster::class);
        Model::registerCustomCast('uppercase-argument', UppercaseCasterWithArgument::class);
    }

    public function testBasicCustomCasting()
    {
        $model = new TestModel;
        $model->uppercase = 'vinicius de santana';

        $this->assertSame('VINICIUS DE SANTANA', $model->uppercase);
        $this->assertSame('VINICIUS DE SANTANA', $model->getAttributes()['uppercase']);
        $this->assertSame('VINICIUS DE SANTANA', $model->toArray()['uppercase']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->uppercase);
        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->getAttributes()['uppercase']);
        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->toArray()['uppercase']);
    }

    public function testCustomCastingWithoutArgument()
    {
        $model = new TestModel;
        $model->uppercase_without_argument = 'vinicius de santana';

        $this->assertSame('VINICIUS DE SANTANA', $model->uppercase_without_argument);
        $this->assertSame('VINICIUS DE SANTANA', $model->getAttributes()['uppercase_without_argument']);
        $this->assertSame('VINICIUS DE SANTANA', $model->toArray()['uppercase_without_argument']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->uppercase_without_argument);
        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->getAttributes()['uppercase_without_argument']);
        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->toArray()['uppercase_without_argument']);
    }

    public function testCustomCastingWithArgument()
    {
        $model = new TestModel;
        $model->uppercase_with_argument = 'vinicius de santana';

        $this->assertSame('argument', $model->uppercase_with_argument);
        $this->assertSame('VINICIUS DE SANTANA', $model->getAttributes()['uppercase_with_argument']);
        $this->assertSame('argument', $model->toArray()['uppercase_with_argument']);

        $unserializedModel = unserialize(serialize($model));

        $this->assertSame('argument', $unserializedModel->uppercase_with_argument);
        $this->assertSame('VINICIUS DE SANTANA', $unserializedModel->getAttributes()['uppercase_with_argument']);
        $this->assertSame('argument', $unserializedModel->toArray()['uppercase_with_argument']);
    }
}

class TestModel extends Model
{
    protected $table = 'test_model';
    public $timestamps = false;
    protected $casts = [
        'uppercase' => 'uppercase',
        'uppercase_without_argument' => 'uppercase-argument',
        'uppercase_with_argument' => 'uppercase-argument:argument',
    ];
}

class UppercaseCaster implements CastsAttributes
{
    public function get($model, $key, $value, $attributes)
    {
        return strtoupper($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return [$key => strtoupper($value)];
    }
}

class UppercaseCasterWithArgument implements CastsAttributes
{
    private $argument;

    public function __construct($argument = null)
    {
        $this->argument = $argument;
    }

    public function get($model, $key, $value, $attributes)
    {
        if ($this->argument) {
            return $this->argument;
        }

        return strtoupper($value);
    }

    public function set($model, $key, $value, $attributes)
    {
        return strtoupper($value);
    }
}
