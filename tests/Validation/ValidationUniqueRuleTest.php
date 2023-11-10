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

        $rule = new Unique(EloquentModelStub::class);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique(NoTableName::class);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:no_table_names,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique('Illuminate\Tests\Validation\NoTableName');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:no_table_names,NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique(ClassWithNonEmptyConstructor::class);
        $rule->where('foo', 'bar');
        $this->assertSame('unique:'.ClassWithNonEmptyConstructor::class.',NULL,NULL,id,foo,"bar"', (string) $rule);

        $rule = new Unique('table', 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique(PrefixedTableEloquentModelStub::class);
        $this->assertSame('unique:'.PrefixedTableEloquentModelStub::class.',NULL,NULL,id', (string) $rule);

        $rule = new Unique(EloquentModelStub::class, 'column');
        $rule->ignore('Taylor, Otwell', 'id_column');
        $rule->where('foo', 'bar');
        $this->assertSame('unique:table,column,"Taylor, Otwell",id_column,foo,"bar"', (string) $rule);

        $rule = new Unique(EloquentModelStub::class, 'column');
        $rule->where('foo', 'bar');
        $rule->when(true, function ($rule) {
            $rule->ignore('Taylor, Otwell', 'id_column');
        });
        $rule->unless(true, function ($rule) {
            $rule->ignore('Chris', 'id_column');
        });
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

    public function testItIgnoresSoftDeletes()
    {
        $rule = new Unique('table');
        $rule->withoutTrashed();
        $this->assertSame('unique:table,NULL,NULL,id,deleted_at,"NULL"', (string) $rule);

        $rule = new Unique('table');
        $rule->withoutTrashed('softdeleted_at');
        $this->assertSame('unique:table,NULL,NULL,id,softdeleted_at,"NULL"', (string) $rule);
    }

    public function testItOnlyTrashedSoftDeletes()
    {
        $rule = new Unique('table');
        $rule->onlyTrashed();
        $this->assertSame('unique:table,NULL,NULL,id,deleted_at,"NOT_NULL"', (string) $rule);

        $rule = new Unique('table');
        $rule->onlyTrashed('softdeleted_at');
        $this->assertSame('unique:table,NULL,NULL,id,softdeleted_at,"NOT_NULL"', (string) $rule);
    }
}

class EloquentModelStub extends Model
{
    protected $table = 'table';
    protected $primaryKey = 'id_column';
    protected $guarded = [];
}

class PrefixedTableEloquentModelStub extends Model
{
    protected $table = 'public.table';
    protected $primaryKey = 'id_column';
    protected $guarded = [];
}

class NoTableName extends Model
{
    protected $guarded = [];
    public $timestamps = false;
}

class ClassWithNonEmptyConstructor
{
    private $bar;
    private $baz;

    public function __construct($bar, $baz)
    {
        $this->bar = $bar;
        $this->baz = $baz;
    }
}
