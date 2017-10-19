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



class DatabaseEloquentPrefixedRelationsTest extends TestCase
{
    public function testPrefixedRelation()
    {
        $model = new SampleModel();
        $relation = $model->samplePrefixedRelation();
        assertEquals($model->foreignTableKey, $relation->getQualifiedForeignKeyName());
    }

    public function testNonPrefixedRelation()
    {
        $model = new SampleModel();
        $model2 = new SampleModel2;
        $relation = $model->sampleNonPrefixedRelation();
        assertEquals($model2->getDefaultRelation(), $relation->getQualifiedForeignKeyName());
    }


}

class SampleModel2 extends Model
{
    protected $table = 'test2';
    protected $primaryKey = 'id2';

    public function getDefaultRelation()
    {
        return $this->table .'.'. $this->primaryKey;
    }
}

class SampleModel extends Model
{
    public $foreignTableKey = 'foreigntable.key';
    public $table = 'test';
    public $primaryKey = 'id';

    public function samplePrefixedRelation()
    {
           return $this->hasOne('Illuminate\Tests\Database\SampleModel2', $this->foreignTableKey,'id');
    }

    public function sampleNonPrefixedRelation()
    {
        return $this->hasOne('Illuminate\Tests\Database\SampleModel2');
    }

    public function getDefaultRelation()
    {

    }
}
