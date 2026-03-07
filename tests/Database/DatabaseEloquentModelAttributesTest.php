<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentModelAttributesTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'secondary');

        $db->bootEloquent();
        $db->setAsGlobal();

        Model::clearBootedModels();
    }

    public function test_table_attribute(): void
    {
        $model = new ModelWithTableAttribute;

        $this->assertSame('custom_table_name', $model->getTable());
    }

    public function test_table_property_takes_precedence(): void
    {
        $model = new ModelWithTableAttributeAndProperty;

        $this->assertSame('property_table', $model->getTable());
    }

    public function test_primary_key_attribute(): void
    {
        $model = new ModelWithPrimaryKeyAttribute;

        $this->assertSame('custom_id', $model->getKeyName());
    }

    public function test_primary_key_property_takes_precedence(): void
    {
        $model = new ModelWithPrimaryKeyAttributeAndProperty;

        $this->assertSame('property_id', $model->getKeyName());
    }

    public function test_primary_key_attribute_with_type(): void
    {
        $model = new ModelWithPrimaryKeyTypeAttribute;

        $this->assertSame('uuid', $model->getKeyName());
        $this->assertSame('string', $model->getKeyType());
    }

    public function test_primary_key_attribute_with_incrementing(): void
    {
        $model = new ModelWithPrimaryKeyIncrementingAttribute;

        $this->assertSame('uuid', $model->getKeyName());
        $this->assertFalse($model->getIncrementing());
    }

    public function test_primary_key_attribute_with_all_options(): void
    {
        $model = new ModelWithFullPrimaryKeyAttribute;

        $this->assertSame('uuid', $model->getKeyName());
        $this->assertSame('string', $model->getKeyType());
        $this->assertFalse($model->getIncrementing());
    }

    public function test_connection_attribute(): void
    {
        $model = new ModelWithConnectionAttribute;

        $this->assertSame('secondary', $model->getConnectionName());
    }

    public function test_timestamps_attribute(): void
    {
        $model = new ModelWithTimestampsFalseAttribute;

        $this->assertFalse($model->usesTimestamps());
    }

    public function test_without_timestamps_attribute(): void
    {
        $model = new ModelWithoutTimestampsAttribute;

        $this->assertFalse($model->usesTimestamps());
    }

    public function test_timestamps_property_takes_precedence(): void
    {
        $model = new ModelWithTimestampsAttributeAndProperty;

        $this->assertFalse($model->usesTimestamps());
    }

    public function test_date_format_attribute(): void
    {
        $model = new ModelWithDateFormatAttribute;

        $this->assertSame('U', $model->getDateFormat());
    }

    public function test_fillable_attribute(): void
    {
        $model = new ModelWithFillableAttribute;

        $this->assertSame(['name', 'email'], $model->getFillable());
    }

    public function test_fillable_property_takes_precedence(): void
    {
        $model = new ModelWithFillableAttributeAndProperty;

        $this->assertSame(['title'], $model->getFillable());
    }

    public function test_guarded_attribute(): void
    {
        $model = new ModelWithGuardedAttribute;

        $this->assertSame(['id', 'secret'], $model->getGuarded());
    }

    public function test_guarded_property_takes_precedence(): void
    {
        $model = new ModelWithGuardedAttributeAndProperty;

        $this->assertSame(['token'], $model->getGuarded());
    }

    public function test_unguarded_attribute(): void
    {
        $model = new ModelWithUnguardedAttribute;

        $this->assertSame([], $model->getGuarded());
        $this->assertFalse($model->isGuarded('anything'));
    }

    public function test_guarded_attribute_is_inherited(): void
    {
        $model = new ModelExtendingGuardedParent;

        $this->assertSame(['id', 'secret'], $model->getGuarded());
    }

    public function test_hidden_attribute(): void
    {
        $model = new ModelWithHiddenAttribute;

        $this->assertSame(['password', 'secret'], $model->getHidden());
    }

    public function test_visible_attribute(): void
    {
        $model = new ModelWithVisibleAttribute;

        $this->assertSame(['id', 'name'], $model->getVisible());
    }

    public function test_appends_attribute(): void
    {
        $model = new ModelWithAppendsAttribute;

        $this->assertSame(['full_name', 'is_admin'], $model->getAppends());
    }

    public function test_touches_attribute(): void
    {
        $model = new ModelWithTouchesAttribute;

        $this->assertSame(['post', 'author'], $model->getTouchedRelations());
    }

    public function test_merge_fillable_works_with_attribute(): void
    {
        $model = new ModelWithFillableAttribute;

        $this->assertSame(['name', 'email'], $model->getFillable());

        $model->mergeFillable(['phone']);

        $this->assertSame(['name', 'email', 'phone'], $model->getFillable());
    }

    public function test_merge_hidden_works_with_attribute(): void
    {
        $model = new ModelWithHiddenAttribute;

        $this->assertSame(['password', 'secret'], $model->getHidden());

        $model->mergeHidden(['api_key']);

        $this->assertSame(['password', 'secret', 'api_key'], $model->getHidden());
    }

    public function test_set_fillable_overrides_attribute(): void
    {
        $model = new ModelWithFillableAttribute;

        $this->assertSame(['name', 'email'], $model->getFillable());

        $model->fillable(['only_this']);

        $this->assertSame(['only_this'], $model->getFillable());
    }

    public function test_set_hidden_overrides_attribute(): void
    {
        $model = new ModelWithHiddenAttribute;

        $this->assertSame(['password', 'secret'], $model->getHidden());

        $model->setHidden(['only_this']);

        $this->assertSame(['only_this'], $model->getHidden());
    }

    public function test_is_ignoring_touch_with_timestamps_attribute(): void
    {
        $this->assertTrue(ModelWithoutTimestampsAttribute::isIgnoringTouch());
        $this->assertTrue(ModelWithTimestampsFalseAttribute::isIgnoringTouch());
        $this->assertFalse(ModelWithFillableAttribute::isIgnoringTouch());
    }
}

#[Table('custom_table_name')]
class ModelWithTableAttribute extends Model
{
    //
}

#[Table('attribute_table')]
class ModelWithTableAttributeAndProperty extends Model
{
    protected $table = 'property_table';
}

#[Table(key: 'custom_id')]
class ModelWithPrimaryKeyAttribute extends Model
{
    //
}

#[Table(key: 'attribute_id')]
class ModelWithPrimaryKeyAttributeAndProperty extends Model
{
    protected $primaryKey = 'property_id';
}

#[Table(key: 'uuid', keyType: 'string')]
class ModelWithPrimaryKeyTypeAttribute extends Model
{
    //
}

#[Table(key: 'uuid', incrementing: false)]
class ModelWithPrimaryKeyIncrementingAttribute extends Model
{
    //
}

#[Table(key: 'uuid', keyType: 'string', incrementing: false)]
class ModelWithFullPrimaryKeyAttribute extends Model
{
    //
}

#[Connection('secondary')]
class ModelWithConnectionAttribute extends Model
{
    //
}

#[Table(timestamps: false)]
class ModelWithTimestampsFalseAttribute extends Model
{
    //
}

#[Table(timestamps: false)]
class ModelWithoutTimestampsAttribute extends Model
{
    //
}

#[Table(timestamps: false)]
class ModelWithTimestampsAttributeAndProperty extends Model
{
    public $timestamps = false;
}

#[Table(dateFormat: 'U')]
class ModelWithDateFormatAttribute extends Model
{
    //
}

#[Fillable(['name', 'email'])]
class ModelWithFillableAttribute extends Model
{
    //
}

#[Fillable(['name', 'email'])]
class ModelWithFillableAttributeAndProperty extends Model
{
    protected $fillable = ['title'];
}

#[Guarded(['id', 'secret'])]
class ModelWithGuardedAttribute extends Model
{
    //
}

#[Guarded(['id', 'secret'])]
class ModelWithGuardedAttributeAndProperty extends Model
{
    protected $guarded = ['token'];
}

#[Guarded(['id', 'secret'])]
class GuardedBaseModel extends Model
{
    //
}

class ModelExtendingGuardedParent extends GuardedBaseModel
{
    //
}

#[Unguarded]
class ModelWithUnguardedAttribute extends Model
{
    //
}

#[Hidden(['password', 'secret'])]
class ModelWithHiddenAttribute extends Model
{
    //
}

#[Visible(['id', 'name'])]
class ModelWithVisibleAttribute extends Model
{
    //
}

#[Appends(['full_name', 'is_admin'])]
class ModelWithAppendsAttribute extends Model
{
    //
}

#[Touches(['post', 'author'])]
class ModelWithTouchesAttribute extends Model
{
    //
}
