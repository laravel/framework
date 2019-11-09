<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\TestCase;

class ValidationUniqueRuleTest extends TestCase
{
    public function testItCorrectlyFormatsAStringVersionOfTheRule()
    {
        $rule = new Unique('table');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore('Taylor, Otwell"\'..-"', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell\"\\\'..-\"",id_column,foo,"bar"', (string) $rule);
        $this->assertSame('Taylor, Otwell"\'..-"', stripslashes(str_getcsv('table,column,"Taylor, Otwell\"\\\'..-\"",id_column,foo,"bar"')[2]));
        $this->assertSame('id_column', stripslashes(str_getcsv('table,column,"Taylor, Otwell\"\\\'..-\"",id_column,foo,"bar"')[3]));

        $rule = new Unique('table', 'column');
        $rule->ignore(null, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,NULL,id_column,foo,"bar"', (string) $rule);

        $model = new EloquentModelStub(['id_column' => 1]);

        $rule = new Unique('table', 'column');
        $rule->ignore($model);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"1",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore($model, 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"1",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique('table');
        $rule->where('foo', '"bar"');
        $this->assertSame('unique:table,NULL,NULL,id,foo,"""bar"""', (string) $rule);
    }
}

class EloquentModelStub extends Model
{
    protected $primaryKey = 'id_column';
    protected $guarded = [];
}
