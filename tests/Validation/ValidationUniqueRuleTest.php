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

        $model = new ValidationUniqueRuleTestModel;

        $rule = new \Illuminate\Validation\Rules\Unique($model);
        $this->assertEquals('unique:table,NULL,NULL,id_column', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique($model, 'column');
        $this->assertEquals('unique:table,column,NULL,id_column', (string) $rule);

        $model->setAttribute($model->getKeyName(), 'Connor Parks');

        $rule = new \Illuminate\Validation\Rules\Unique($model);
        $this->assertEquals('unique:table,NULL,"Connor Parks",id_column', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique($model, 'column');
        $this->assertEquals('unique:table,column,"Connor Parks",id_column', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique(ValidationUniqueRuleTestModel::class);
        $this->assertEquals('unique:table,NULL,NULL,id_column', (string) $rule);

        $rule = new \Illuminate\Validation\Rules\Unique(ValidationUniqueRuleTestModel::class, 'column');
        $this->assertEquals('unique:table,column,NULL,id_column', (string) $rule);

        $rule = (new \Illuminate\Validation\Rules\Unique(ValidationUniqueRuleTestModel::class))->ignore($model);
        $this->assertEquals('unique:table,NULL,"Connor Parks",id_column', (string) $rule);

        $rule = (new \Illuminate\Validation\Rules\Unique(ValidationUniqueRuleTestModel::class, 'column'))->ignore($model);
        $this->assertEquals('unique:table,column,"Connor Parks",id_column', (string) $rule);
    }
}

class ValidationUniqueRuleTestModel extends Model
{
    protected $table = 'table';
    protected $primaryKey = 'id_column';
    protected $keyType = 'string';
    public $incrementing = false;
}
