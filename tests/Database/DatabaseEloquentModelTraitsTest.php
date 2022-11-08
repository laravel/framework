<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Database\Eloquent\Concerns\HasRelationships;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\RestrictsAttributes;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentModelTraitsTest extends TestCase
{
    public function testRestrictsAttributesOnCustomModel()
    {
        $model = new CustomModelWithRestrictsAttributesStub();

        $this->assertIsBool($model->preventsSilentlyDiscardingAttributes());
        $this->assertIsBool($model->preventsAccessingMissingAttributes());

        // Accessing missing attributes is allowed by default:
        $this->assertNull($model->not_existing_attribute);

        // Disable accessing missing attributes: should throw undefined property error for `exists` since that is an
        // \Illuminate\Database\Eloquent\Model property.
        $model->preventAccessingMissingAttributes();
        $this->expectError();
        $notExistingAttribute = $model->not_existing_attribute;
    }

    public function testHasAttributesWorksWithoutRestrictsAttributesOnCustomModel()
    {
        $model = new CustomModelWithoutRestrictsAttributesStub();
        $model->setRawAttributes([
            'hobby' => 'Otwellian programming',
        ]);

        // Accessing properties should work without error for existing and not-existing properties.
        $this->assertNull($model->not_existing_attribute);

        $this->assertEquals('Otwellian programming', $model->hobby);
    }
}

class CustomModelWithoutRestrictsAttributesStub
{
    use HasAttributes;
    use HasRelationships;
    use HasUlids;

    /**
     * Get the primary key for the model.
     *
     * @return string
     */
    public function getKeyName()
    {
        return null;
    }
}

class CustomModelWithRestrictsAttributesStub extends CustomModelWithoutRestrictsAttributesStub
{
    use RestrictsAttributes;
}
