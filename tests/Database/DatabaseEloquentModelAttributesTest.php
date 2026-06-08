<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\Touches;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Attributes\Visible;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
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

    public function test_child_table_attribute_overrides_inherited_table_property(): void
    {
        $model = new ChildModelWithTableAttribute;

        $this->assertSame('child_attr', $model->getTable());
    }

    public function test_child_inherits_parent_table_attribute(): void
    {
        $model = new ChildModelWithNoTable;

        $this->assertSame('parent_attr', $model->getTable());
    }

    public function test_child_table_property_overrides_parent_table_attribute(): void
    {
        $model = new ChildModelWithTableProperty;

        $this->assertSame('child_prop', $model->getTable());
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

    public function test_dedicated_without_incrementing_attribute(): void
    {
        $model = new ModelWithDedicatedWithoutIncrementingAttribute;

        $this->assertFalse($model->getIncrementing());
    }

    public function test_dedicated_without_incrementing_attribute_overrides_table_incrementing(): void
    {
        $model = new ModelWithWithoutIncrementingAttributeOverride;

        $this->assertFalse($model->getIncrementing());
    }

    public function test_table_attribute_incrementing_applies_to_pivot_models(): void
    {
        $model = new PivotWithIncrementing;

        $this->assertTrue($model->getIncrementing());
    }

    public function test_connection_attribute(): void
    {
        $model = new ModelWithConnectionAttribute;

        $this->assertSame('secondary', $model->getConnectionName());
    }

    public function test_connection_attribute_with_unit_enum(): void
    {
        $model = new ModelWithUnitEnumConnectionAttribute;

        $this->assertSame('secondary', $model->getConnectionName());
    }

    public function test_connection_attribute_with_backed_enum(): void
    {
        $model = new ModelWithBackedEnumConnectionAttribute;

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

    public function test_dedicated_date_format_attribute(): void
    {
        $model = new ModelWithDedicatedDateFormatAttribute;

        $this->assertSame('Y-m-d', $model->getDateFormat());
    }

    public function test_dedicated_date_format_attribute_overrides_table_date_format(): void
    {
        $model = new ModelWithDateFormatAttributeOverride;

        $this->assertSame('Y-m-d', $model->getDateFormat());
    }

    public function test_dedicated_without_timestamps_attribute(): void
    {
        $model = new ModelWithDedicatedWithoutTimestampsAttribute;

        $this->assertFalse($model->usesTimestamps());
    }

    public function test_dedicated_without_timestamps_attribute_overrides_table_timestamps(): void
    {
        $model = new ModelWithWithoutTimestampsAttributeOverride;

        $this->assertFalse($model->usesTimestamps());
    }

    public function test_fillable_attribute(): void
    {
        $model = new ModelWithFillableAttribute;

        $this->assertSame(['name', 'email'], $model->getFillable());
    }

    public function test_fillable_attribute_variadic(): void
    {
        $model = new ModelWithFillableAttributeVariadic;

        $this->assertSame(['name', 'email'], $model->getFillable());
    }

    public function test_fillable_property_merges_with_attribute(): void
    {
        $model = new ModelWithFillableAttributeAndProperty;

        $this->assertEqualsCanonicalizing(['title', 'name', 'email'], $model->getFillable());
    }

    public function test_guarded_attribute(): void
    {
        $model = new ModelWithGuardedAttribute;

        $this->assertSame(['id', 'secret'], $model->getGuarded());
    }

    public function test_guarded_attribute_variadic(): void
    {
        $model = new ModelWithGuardedAttributeVariadic;

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

    public function test_hidden_attribute_variadic(): void
    {
        $model = new ModelWithHiddenAttributeVariadic;

        $this->assertSame(['password', 'secret'], $model->getHidden());
    }

    public function test_visible_attribute(): void
    {
        $model = new ModelWithVisibleAttribute;

        $this->assertSame(['id', 'name'], $model->getVisible());
    }

    public function test_visible_attribute_variadic(): void
    {
        $model = new ModelWithVisibleAttributeVariadic;

        $this->assertSame(['id', 'name'], $model->getVisible());
    }

    public function test_appends_attribute(): void
    {
        $model = new ModelWithAppendsAttribute;

        $this->assertSame(['full_name', 'is_admin'], $model->getAppends());
    }

    public function test_appends_attribute_variadic(): void
    {
        $model = new ModelWithAppendsAttributeVariadic;

        $this->assertSame(['full_name', 'is_admin'], $model->getAppends());
    }

    public function test_touches_attribute(): void
    {
        $model = new ModelWithTouchesAttribute;

        $this->assertSame(['post', 'author'], $model->getTouchedRelations());
    }

    public function test_touches_attribute_variadic(): void
    {
        $model = new ModelWithTouchesAttributeVariadic;

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

    public function test_merge_fillable_with_empty_array_is_noop(): void
    {
        $model = new ModelWithFillableAttribute;
        $original = $model->getFillable();

        $result = $model->mergeFillable([]);

        $this->assertSame($model, $result);
        $this->assertSame($original, $model->getFillable());
    }

    public function test_merge_hidden_with_empty_array_is_noop(): void
    {
        $model = new ModelWithHiddenAttribute;
        $original = $model->getHidden();

        $result = $model->mergeHidden([]);

        $this->assertSame($model, $result);
        $this->assertSame($original, $model->getHidden());
    }

    public function test_merge_visible_with_empty_array_is_noop(): void
    {
        $model = new ModelWithVisibleAttribute;
        $original = $model->getVisible();

        $result = $model->mergeVisible([]);

        $this->assertSame($model, $result);
        $this->assertSame($original, $model->getVisible());
    }

    public function test_merge_appends_with_empty_array_is_noop(): void
    {
        $model = new ModelWithAppendsAttribute;
        $original = $model->getAppends();

        $result = $model->mergeAppends([]);

        $this->assertSame($model, $result);
        $this->assertSame($original, $model->getAppends());
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

    public function test_trait_initializer_merges_appends_with_attribute(): void
    {
        $model = new ModelWithAppendsAttributeAndTrait;

        $this->assertEqualsCanonicalizing(['full_name', 'is_admin', 'url'], $model->getAppends());
    }

    public function test_trait_initializer_merges_hidden_with_attribute(): void
    {
        $model = new ModelWithHiddenAttributeAndTrait;

        $this->assertEqualsCanonicalizing(['password', 'secret', 'api_token'], $model->getHidden());
    }

    public function test_trait_initializer_merges_visible_with_attribute(): void
    {
        $model = new ModelWithVisibleAttributeAndTrait;

        $this->assertEqualsCanonicalizing(['id', 'name', 'email'], $model->getVisible());
    }

    public function test_trait_initializer_merges_fillable_with_attribute(): void
    {
        $model = new ModelWithFillableAttributeAndTrait;

        $this->assertEqualsCanonicalizing(['name', 'email', 'phone'], $model->getFillable());
    }
}

enum ConnectionUnitEnum
{
    case secondary;
}

enum ConnectionBackedEnum: string
{
    case Secondary = 'secondary';
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

class ParentModelWithTableProperty extends Model
{
    protected $table = 'parent_prop';
}

#[Table(name: 'child_attr')]
class ChildModelWithTableAttribute extends ParentModelWithTableProperty
{
    //
}

#[Table(name: 'parent_attr')]
class ParentModelWithTableAttribute extends Model
{
    //
}

class ChildModelWithNoTable extends ParentModelWithTableAttribute
{
    //
}

class ChildModelWithTableProperty extends ParentModelWithTableAttribute
{
    protected $table = 'child_prop';
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

#[Connection(ConnectionUnitEnum::secondary)]
class ModelWithUnitEnumConnectionAttribute extends Model
{
    //
}

#[Connection(ConnectionBackedEnum::Secondary)]
class ModelWithBackedEnumConnectionAttribute extends Model
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

#[Fillable('name', 'email')]
class ModelWithFillableAttributeVariadic extends Model
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

#[Guarded('id', 'secret')]
class ModelWithGuardedAttributeVariadic extends Model
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

#[Hidden('password', 'secret')]
class ModelWithHiddenAttributeVariadic extends Model
{
    //
}

#[Visible(['id', 'name'])]
class ModelWithVisibleAttribute extends Model
{
    //
}

#[Visible('id', 'name')]
class ModelWithVisibleAttributeVariadic extends Model
{
    //
}

#[Appends(['full_name', 'is_admin'])]
class ModelWithAppendsAttribute extends Model
{
    //
}

#[Appends('full_name', 'is_admin')]
class ModelWithAppendsAttributeVariadic extends Model
{
    //
}

#[Touches(['post', 'author'])]
class ModelWithTouchesAttribute extends Model
{
    //
}

#[Touches('post', 'author')]
class ModelWithTouchesAttributeVariadic extends Model
{
    //
}

#[DateFormat('Y-m-d')]
class ModelWithDedicatedDateFormatAttribute extends Model
{
    //
}

#[Table(dateFormat: 'U')]
#[DateFormat('Y-m-d')]
class ModelWithDateFormatAttributeOverride extends Model
{
    //
}

#[WithoutTimestamps]
class ModelWithDedicatedWithoutTimestampsAttribute extends Model
{
    //
}

#[Table(timestamps: true)]
#[WithoutTimestamps]
class ModelWithWithoutTimestampsAttributeOverride extends Model
{
    //
}

#[WithoutIncrementing]
class ModelWithDedicatedWithoutIncrementingAttribute extends Model
{
    //
}

#[Table(incrementing: true)]
#[WithoutIncrementing]
class ModelWithWithoutIncrementingAttributeOverride extends Model
{
    //
}

#[Table(incrementing: true)]
class PivotWithIncrementing extends \Illuminate\Database\Eloquent\Relations\Pivot
{
    //
}

// Traits for testing trait initializer + Attribute collision

trait AddsUrlAppend
{
    protected function initializeAddsUrlAppend()
    {
        $this->mergeAppends(['url']);
    }
}

trait AddsApiTokenHidden
{
    protected function initializeAddsApiTokenHidden()
    {
        $this->mergeHidden(['api_token']);
    }
}

trait AddsEmailVisible
{
    protected function initializeAddsEmailVisible()
    {
        $this->mergeVisible(['email']);
    }
}

trait AddsPhoneFillable
{
    protected function initializeAddsPhoneFillable()
    {
        $this->mergeFillable(['phone']);
    }
}

#[Appends(['full_name', 'is_admin'])]
class ModelWithAppendsAttributeAndTrait extends Model
{
    use AddsUrlAppend;
}

#[Hidden(['password', 'secret'])]
class ModelWithHiddenAttributeAndTrait extends Model
{
    use AddsApiTokenHidden;
}

#[Visible(['id', 'name'])]
class ModelWithVisibleAttributeAndTrait extends Model
{
    use AddsEmailVisible;
}

#[Fillable(['name', 'email'])]
class ModelWithFillableAttributeAndTrait extends Model
{
    use AddsPhoneFillable;
}
