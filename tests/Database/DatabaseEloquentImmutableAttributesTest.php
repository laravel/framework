<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Attributes\Immutable;
use Illuminate\Database\Eloquent\ImmutableAttributeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Events\Dispatcher;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentImmutableAttributesTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->setEventDispatcher(new Dispatcher);
        $db->bootEloquent();
        $db->setAsGlobal();

        Model::clearBootedModels();

        DB::schema()->create('immutable_invoices', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('tenant_id');
            $table->string('number');
            $table->integer('total');
        });
    }

    protected function tearDown(): void
    {
        DB::schema()->drop('immutable_invoices');

        Model::unsetEventDispatcher();
        Model::clearBootedModels();
    }

    public function test_immutable_attributes_can_be_set_when_creating(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $this->assertTrue($invoice->exists);
        $this->assertSame(1, $invoice->fresh()->tenant_id);
    }

    public function test_updating_an_immutable_attribute_throws(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $invoice->tenant_id = 2;

        try {
            $invoice->save();

            $this->fail('Expected an ImmutableAttributeException.');
        } catch (ImmutableAttributeException $e) {
            $this->assertSame(
                'The attribute [tenant_id] on model ['.ImmutableInvoiceWithProperty::class.'] is immutable and cannot be updated.',
                $e->getMessage()
            );
            $this->assertSame(['tenant_id'], $e->attributes);
            $this->assertSame($invoice, $e->model);
        }

        $this->assertSame(1, $invoice->fresh()->tenant_id);
    }

    public function test_the_whole_update_is_rejected_when_an_immutable_attribute_is_dirty(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $invoice->tenant_id = 2;
        $invoice->total = 200;

        try {
            $invoice->save();

            $this->fail('Expected an ImmutableAttributeException.');
        } catch (ImmutableAttributeException) {
            //
        }

        $this->assertSame(100, $invoice->fresh()->total);
    }

    public function test_every_dirty_immutable_attribute_is_reported(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $this->expectException(ImmutableAttributeException::class);
        $this->expectExceptionMessage('The attributes [tenant_id, number] on model ['.ImmutableInvoiceWithProperty::class.'] are immutable and cannot be updated.');

        $invoice->update(['tenant_id' => 2, 'number' => 'INV-2']);
    }

    public function test_mutable_attributes_can_still_be_updated(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $invoice->update(['total' => 200]);

        $this->assertSame(200, $invoice->fresh()->total);
    }

    public function test_assigning_the_current_value_is_allowed(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $invoice->tenant_id = '1';
        $invoice->total = 200;
        $invoice->save();

        $this->assertSame(200, $invoice->fresh()->total);
    }

    public function test_force_fill_cannot_bypass_immutable_attributes(): void
    {
        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $this->expectException(ImmutableAttributeException::class);

        $invoice->forceFill(['tenant_id' => 2])->save();
    }

    public function test_changes_made_by_updating_listeners_are_checked(): void
    {
        ImmutableInvoiceWithProperty::updating(function ($invoice) {
            $invoice->tenant_id = 2;
        });

        $invoice = ImmutableInvoiceWithProperty::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $this->expectException(ImmutableAttributeException::class);

        $invoice->update(['total' => 200]);
    }

    public function test_immutable_class_attribute(): void
    {
        $invoice = ImmutableInvoiceWithAttribute::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);

        $this->assertSame(['tenant_id', 'number'], $invoice->getImmutable());
        $this->assertTrue($invoice->isImmutable('tenant_id'));
        $this->assertFalse($invoice->isImmutable('total'));

        $this->expectException(ImmutableAttributeException::class);

        $invoice->update(['number' => 'INV-2']);
    }

    public function test_immutable_class_attribute_variadic(): void
    {
        $invoice = new ImmutableInvoiceWithVariadicAttribute;

        $this->assertSame(['tenant_id', 'number'], $invoice->getImmutable());
    }

    public function test_immutable_property_merges_with_attribute(): void
    {
        $invoice = new ImmutableInvoiceWithAttributeAndProperty;

        $this->assertSame(['number', 'tenant_id'], $invoice->getImmutable());
    }

    public function test_models_have_no_immutable_attributes_by_default(): void
    {
        $invoice = new MutableInvoice;

        $this->assertSame([], $invoice->getImmutable());

        $invoice = MutableInvoice::create(['tenant_id' => 1, 'number' => 'INV-1', 'total' => 100]);
        $invoice->update(['tenant_id' => 2]);

        $this->assertSame(2, $invoice->fresh()->tenant_id);
    }
}

class ImmutableInvoiceWithProperty extends Model
{
    protected $table = 'immutable_invoices';

    public $timestamps = false;

    protected $guarded = [];

    protected $immutable = ['tenant_id', 'number'];

    protected $casts = ['tenant_id' => 'integer', 'total' => 'integer'];
}

#[Immutable(['tenant_id', 'number'])]
class ImmutableInvoiceWithAttribute extends Model
{
    protected $table = 'immutable_invoices';

    public $timestamps = false;

    protected $guarded = [];
}

#[Immutable('tenant_id', 'number')]
class ImmutableInvoiceWithVariadicAttribute extends Model
{
    protected $table = 'immutable_invoices';
}

#[Immutable(['tenant_id'])]
class ImmutableInvoiceWithAttributeAndProperty extends Model
{
    protected $table = 'immutable_invoices';

    protected $immutable = ['number'];
}

class MutableInvoice extends Model
{
    protected $table = 'immutable_invoices';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = ['tenant_id' => 'integer'];
}
