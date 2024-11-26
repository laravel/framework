<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentWithAttributesTest extends TestCase
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
    }

    public function testAddsAttributes(): void
    {
        $key = 'a key';
        $value = 'the value';

        $query = WithAttributesModel::query()
            ->withAttributes([$key => $value]);

        $model = $query->make();

        $this->assertSame($value, $model->$key);
    }

    public function testAddsWheres(): void
    {
        $key = 'a key';
        $value = 'the value';

        $query = WithAttributesModel::query()
            ->withAttributes([$key => $value]);

        $wheres = $query->toBase()->wheres;

        $this->assertContains([
            'type' => 'Basic',
            'column' => $key,
            'operator' => '=',
            'value' => $value,
            'boolean' => 'and',
        ], $wheres);
    }
}

class WithAttributesModel extends Model
{
    protected $guarded = [];
}
