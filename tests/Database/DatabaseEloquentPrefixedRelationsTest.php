<?php
/**
 * Created by IntelliJ IDEA.
 * User: Cole
 * Date: 10/18/2017
 * Time: 9:05 AM
 */

namespace Illuminate\Tests\Database;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;
use Mockery as m;
use Illuminate\Database\Capsule\Manager as DB;

class DatabaseEloquentPrefixedRelationsTest extends TestCase
{
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();
    }

    public function testPrefixedHasOne()
    {
        $model = new SampleModel();
        $relation = $model->samplePrefixedHasOne();
        $this->assertEquals($model->foreignTableKey, $relation->getQualifiedForeignKeyName());
    }

    public function testNonPrefixedHasOne()
    {
        $model = new SampleModel();
        $relation = $model->sampleNonPrefixedHasOne();
        $this->assertEquals('test2.sample_model_id', $relation->getQualifiedForeignKeyName());
    }

    public function testNonPrefixedHasOneWithKey()
    {
        $model = new SampleModel();
        $relation = $model->sampleNonPrexifedHasOneWithForeignKey();
        $this->assertEquals('test2.sample_model_id', $relation->getQualifiedForeignKeyName());
    }

    public function testPrefixedHasMany()
    {
        $model = new SampleModel();
        $relation = $model->samplePrefixedHasMany();
        $this->assertEquals($model->foreignTableKey, $relation->getQualifiedForeignKeyName());
    }

    public function testNonPrefixedHasMany()
    {
        $model = new SampleModel();
        $relation = $model->sampleNonPrefixedHasMany();
        $this->assertEquals('test2.sample_model_id', $relation->getQualifiedForeignKeyName());
    }

    public function testNonPrefixedHasManyWithKey()
    {
        $model = new SampleModel();
        $relation = $model->sampleNonPrexifedHasManyWithForeignKey();
        $this->assertEquals('test2.sample_model_id', $relation->getQualifiedForeignKeyName());
    }


}

class SampleModel2 extends Model
{
    protected $table = 'test2';
}

class SampleModel extends Model
{
    public $foreignTableKey = 'foreigntable.key';
    public $table = 'test';

    public function samplePrefixedHasOne()
    {
           return $this->hasOne('Illuminate\Tests\Database\SampleModel2', $this->foreignTableKey,'id');
    }

    public function sampleNonPrefixedHasOne()
    {
        return $this->hasOne('Illuminate\Tests\Database\SampleModel2');
    }

    public function sampleNonPrexifedHasOneWithForeignKey()
    {
        return $this->hasOne('Illuminate\Tests\Database\SampleModel2', 'sample_model_id','id');
    }

    public function samplePrefixedHasMany()
    {
        return $this->hasOne('Illuminate\Tests\Database\SampleModel2', $this->foreignTableKey,'id');
    }

    public function sampleNonPrefixedHasMany()
    {
        return $this->hasMany('Illuminate\Tests\Database\SampleModel2');
    }

    public function sampleNonPrexifedHasManyWithForeignKey()
    {
        return $this->hasOne('Illuminate\Tests\Database\SampleModel2', 'sample_model_id','id');
    }
}
