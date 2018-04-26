<?php

namespace Illuminate\Tests\Validation;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;

class ValidationUniqueRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new \Illuminate\Validation\Rules\Unique('table');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,NULL,NULL,id,foo,bar', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,"Taylor, Otwell",id_column,foo,bar', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore(null, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,NULL,id_column,foo,bar', (string) $rule);

        $model = new EloquentModelStub(['id_column' => 1]);

        $rule = new \Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore($model);
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,"1",id_column,foo,bar', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique('table', 'column');
        $rule->ignore($model, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertEquals('unique:table,column,"1",id_column,foo,bar', (string) $rule);
    }
}

class EloquentModelStub extends Model
{
    protected $primaryKey = 'id_column';
    protected $guarded = [];
}
