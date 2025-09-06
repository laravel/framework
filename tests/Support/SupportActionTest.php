<?php

namespace Illuminate\Tests\Support;

use Illuminate\Support\Action;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SupportActionTest extends TestCase
{
    public function test_dispatch_returns_handle_result_when_authorized(): void
    {
        $action = new class extends Action
        {
            public function handle(): string
            {
                return 'test-result';
            }
        };

        $result = $action::dispatch();

        $this->assertSame('test-result', $result);
    }

    public function test_dispatch_returns_null_when_not_authorized(): void
    {
        $action = new class extends Action
        {
            public function handle(): string
            {
                return 'test-result';
            }

            public function authorize(): bool
            {
                return false;
            }
        };

        $result = $action::dispatch();

        $this->assertNull($result);
    }

    public function test_dispatch_passes_parameters_to_make(): void
    {
        $action = new class('default') extends Action
        {
            public function __construct(
                private readonly string $value = 'default'
            ) {
            }

            public function handle(): string
            {
                return $this->value;
            }
        };

        $result = $action::dispatch('custom-value');

        $this->assertSame('custom-value', $result);
    }

    public function test_dispatch_handles_multiple_parameters(): void
    {
        $action = new class('', 0) extends Action
        {
            public function __construct(
                private readonly string $name = '',
                private readonly int $age = 0
            ) {
            }

            public function handle(): array
            {
                return ['name' => $this->name, 'age' => $this->age];
            }
        };

        $result = $action::dispatch('Marc', 29);

        $this->assertSame(['name' => 'Marc', 'age' => 29], $result);
    }

    public function test_make_creates_new_instance(): void
    {
        $action = new class extends Action
        {
            public function handle(): null
            {
                return null;
            }
        };

        $instance = $action::make();

        $this->assertInstanceOf(get_class($action), $instance);
        $this->assertNotSame($action, $instance);
    }

    public function test_make_passes_parameters_to_constructor(): void
    {
        $action = new class('default') extends Action
        {
            public function __construct(
                public readonly string $value = 'default'
            ) {
            }

            public function handle(): string
            {
                return $this->value;
            }
        };

        $instance = $action::make('test-value');

        $this->assertSame('test-value', $instance->value);
    }

    public function test_authorize_returns_true_by_default(): void
    {
        $action = new class extends Action
        {
            public function handle(): null
            {
                return null;
            }
        };

        $this->assertTrue($action->authorize());
    }

    public function test_authorize_can_be_overridden(): void
    {
        $authorizedAction = new class extends Action
        {
            public function handle(): null
            {
                return null;
            }

            public function authorize(): bool
            {
                return true;
            }
        };

        $unauthorizedAction = new class extends Action
        {
            public function handle(): null
            {
                return null;
            }

            public function authorize(): bool
            {
                return false;
            }
        };

        $this->assertTrue($authorizedAction->authorize());
        $this->assertFalse($unauthorizedAction->authorize());
    }

    public function test_handle_is_abstract(): void
    {
        $reflection = new ReflectionClass(Action::class);
        $method = $reflection->getMethod('handle');

        $this->assertTrue($method->isAbstract());
    }

    public function test_dispatch_works_with_null_return_from_handle(): void
    {
        $action = new class extends Action
        {
            public function handle(): null
            {
                return null;
            }
        };

        $result = $action::dispatch();

        $this->assertNull($result);
    }

    public function test_dispatch_works_with_array_return_from_handle(): void
    {
        $action = new class extends Action
        {
            public function handle(): array
            {
                return ['key' => 'value', 'number' => 42];
            }
        };

        $result = $action::dispatch();

        $this->assertSame(['key' => 'value', 'number' => 42], $result);
    }

    public function test_dispatch_works_with_object_return_from_handle(): void
    {
        $action = new class extends Action
        {
            public function handle(): object
            {
                return (object) ['property' => 'value'];
            }
        };

        $result = $action::dispatch();

        $this->assertEquals((object) ['property' => 'value'], $result);
    }

    public function test_dispatch_with_complex_authorization_logic(): void
    {
        $action = new class(true) extends Action
        {
            public function __construct(
                private readonly string $condition = 'some-condition'
            ) {
            }

            public function handle(): string
            {
                return 'authorized-result';
            }

            public function authorize(): bool
            {
                return $this->condition == 'some-condition';
            }
        };

        $authorizedResult = $action::dispatch('some-condition');
        $unauthorizedResult = $action::dispatch('another-condition');

        $this->assertSame('authorized-result', $authorizedResult);
        $this->assertNull($unauthorizedResult);
    }
}
