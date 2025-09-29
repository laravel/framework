<?php

namespace Illuminate\Tests\Database;

use Illuminate\Foundation\Testing\TestCase;

class DatabaseEloquentSerializationExcludedFromStyleCiTest extends TestCase
{
    public function testModelSkipsVirtualPropertiesOnSerialization()
    {
        if (version_compare(PHP_VERSION, '8.4.0-dev', '<')) {
            $this->markTestSkipped('Requires Virtual Properties.');
        }

        $model = new EloquentModelWithVirtualPropertiesStub();

        $serialized = serialize($model);

        $this->assertStringNotContainsString('virtualGet', $serialized);
        $this->assertStringNotContainsString('virtualSet', $serialized);
    }
}

if (version_compare(PHP_VERSION, '8.4.0-dev', '>=')) {
    eval(<<<'MODEL'
<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Eloquent\Model;

class EloquentModelWithVirtualPropertiesStub extends Model
{
    public $virtualGet {
        get => $this->foo;
    }

    public $virtualSet {
        get => $this->foo;
        set {
            //
        }
    }
}
MODEL
    );
}
