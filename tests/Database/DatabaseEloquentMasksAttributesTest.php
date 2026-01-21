<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentMasksAttributesTest extends TestCase
{
    public function testMaskedAttributesAreMaskedOnRetrieval()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'name' => 'John Doe',
        ]);

        $this->assertSame('j**n@example.com', $model->email);
        $this->assertSame('123****890', $model->phone);
        $this->assertSame('John Doe', $model->name);
    }

    public function testEmailMaskingWorksCorrectly()
    {
        $model = new EloquentMasksAttributesModelStub;

        // Standard email
        $model->setRawAttributes(['email' => 'john@example.com']);
        $this->assertSame('j**n@example.com', $model->email);

        // Short name email
        $model->setRawAttributes(['email' => 'jo@example.com']);
        $this->assertSame('**@example.com', $model->email);

        // Single character name
        $model->setRawAttributes(['email' => 'j@example.com']);
        $this->assertSame('*@example.com', $model->email);

        // Long name email
        $model->setRawAttributes(['email' => 'johnathan@example.com']);
        $this->assertSame('j*******n@example.com', $model->email);
    }

    public function testTextMaskingWorksCorrectly()
    {
        $model = new EloquentMasksAttributesModelStub;

        // Standard phone
        $model->setRawAttributes(['phone' => '1234567890']);
        $this->assertSame('123****890', $model->phone);

        // Short text (less than start + end)
        $model->setRawAttributes(['phone' => '12345']);
        $this->assertSame('*****', $model->phone);

        // Exactly start + end length
        $model->setRawAttributes(['phone' => '123456']);
        $this->assertSame('******', $model->phone);

        // Just over start + end length
        $model->setRawAttributes(['phone' => '1234567']);
        $this->assertSame('123*567', $model->phone);
    }

    public function testNonMaskedAttributesAreNotAffected()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'name' => 'John Doe',
            'address' => '123 Main Street',
        ]);

        $this->assertSame('John Doe', $model->name);
        $this->assertSame('123 Main Street', $model->address);
    }

    public function testEmptyValuesAreNotMasked()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => '',
            'phone' => null,
        ]);

        $this->assertSame('', $model->email);
        $this->assertNull($model->phone);
    }

    public function testNonStringValuesAreNotMasked()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 123,
            'phone' => ['array', 'value'],
        ]);

        $this->assertSame(123, $model->email);
        $this->assertSame(['array', 'value'], $model->phone);
    }

    public function testWithoutMaskingDisablesAllMasking()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $model->withoutMasking();

        $this->assertSame('john@example.com', $model->email);
        $this->assertSame('1234567890', $model->phone);
    }

    public function testWithoutMaskingForSpecificAttributes()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $model->withoutMasking('email');

        $this->assertSame('john@example.com', $model->email);
        $this->assertSame('123****890', $model->phone);
    }

    public function testWithoutMaskingCallback()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        // Within callback, masking should be disabled
        $result = $model->withoutMaskingCallback(function ($model) {
            return [
                'email' => $model->email,
                'phone' => $model->phone,
            ];
        });

        $this->assertSame('john@example.com', $result['email']);
        $this->assertSame('1234567890', $result['phone']);

        // After callback, masking should be re-enabled
        $this->assertSame('j**n@example.com', $model->email);
        $this->assertSame('123****890', $model->phone);
    }

    public function testGetMaskedReturnsArray()
    {
        $model = new EloquentMasksAttributesModelStub;

        $this->assertSame(['email', 'phone'], $model->getMasked());
    }

    public function testSetMaskedUpdatesArray()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setMasked(['name', 'address']);

        $this->assertSame(['name', 'address'], $model->getMasked());
    }

    public function testMergeMaskedCombinesArrays()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->mergeMasked(['name', 'email']); // email already exists

        $this->assertSame(['email', 'phone', 'name'], $model->getMasked());
    }

    public function testMaskedAttributesInToArray()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'name' => 'John Doe',
        ]);

        $array = $model->toArray();

        $this->assertSame('j**n@example.com', $array['email']);
        $this->assertSame('123****890', $array['phone']);
        $this->assertSame('John Doe', $array['name']);
    }

    public function testMaskedAttributesInJsonSerialize()
    {
        $model = new EloquentMasksAttributesModelStub;
        $model->setRawAttributes([
            'email' => 'john@example.com',
            'phone' => '1234567890',
        ]);

        $json = json_encode($model);
        $decoded = json_decode($json, true);

        $this->assertSame('j**n@example.com', $decoded['email']);
        $this->assertSame('123****890', $decoded['phone']);
    }
}

class EloquentMasksAttributesModelStub extends Model
{
    protected $table = 'stub';

    protected $masked = ['email', 'phone'];
}
