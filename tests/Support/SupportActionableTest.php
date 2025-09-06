<?php

namespace Illuminate\Tests\Support;

use BadMethodCallException;
use Illuminate\Container\Container;
use Illuminate\Support\Traits\Actionable;
use Illuminate\Validation\UnauthorizedException;
use PHPUnit\Framework\TestCase;

class SupportActionableTest extends TestCase
{
    public function test_dispatch_returns_handle_result_when_authorized(): void
    {
        $result = TestParameterPassingAction::dispatch();

        $this->assertSame('default', $result);
    }

    public function test_dispatch_throws_exception_when_not_authorized(): void
    {
        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('This action is unauthorized.');

        TestUnauthorizedAction::dispatch();
    }

    public function test_dispatch_passes_parameters_to_constructor(): void
    {
        $result = TestParameterPassingAction::dispatch(message: 'custom-value');

        $this->assertSame('custom-value', $result);
    }

    public function test_dispatch_handles_multiple_parameters(): void
    {
        $result = TestMultipleParametersAction::dispatch(name: 'Marc', age: 29);

        $this->assertSame(['name' => 'Marc', 'age' => 29], $result);
    }

    public function test_dispatch_works_with_null_return_from_handle(): void
    {
        $result = TestNullReturnAction::dispatch();

        $this->assertNull($result);
    }

    public function test_dispatch_works_with_array_return_from_handle(): void
    {
        $result = TestArrayReturnAction::dispatch();

        $this->assertSame(['key' => 'value', 'number' => 42], $result);
    }

    public function test_dispatch_works_with_object_return_from_handle(): void
    {
        $result = TestObjectReturnAction::dispatch();

        $this->assertEquals((object) ['property' => 'value'], $result);
    }

    public function test_dispatch_with_complex_authorization_logic(): void
    {
        $authorizedResult = TestComplexAuthorizationAction::dispatch(condition: 'authorized-condition');
        $this->assertSame('authorized-result', $authorizedResult);

        $this->expectException(UnauthorizedException::class);
        TestComplexAuthorizationAction::dispatch(condition: 'unauthorized-condition');
    }

    public function test_non_bool_authorize_throws_exception(): void
    {
        $this->expectException(UnauthorizedException::class);

        TestStringAuthorizeAction::dispatch();
    }

    public function test_unauthorized_method_called_when_authorization_fails(): void
    {
        $result = TestUnauthorizedMethodAction::dispatch();

        $this->assertSame('this action is unauthorized.', $result);
    }

    public function test_dispatch_falls_back_to_invoke_when_no_handle(): void
    {
        $result = TestInvokeFallbackAction::dispatch();

        $this->assertSame('invoked', $result);
    }

    public function test_container_auto_resolves_dependencies(): void
    {
        $container = Container::getInstance();
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);

        $result = TestContainerResolutionAction::dispatch();
        $this->assertSame('auto-resolved-service', $result);
    }

    public function test_container_resolves_dependencies_with_mixed_parameters(): void
    {
        $serviceClass = new class implements TestServiceInterface
        {
            public function getPrefix(): string
            {
                return 'Service:';
            }
        };

        $container = Container::getInstance();
        $container->instance(TestServiceInterface::class, $serviceClass);

        $result = TestMixedParametersAction::dispatch(message: 'Hello World');

        $this->assertSame('Service: Hello World', $result);
    }

    public function test_container_resolves_interface_dependencies(): void
    {
        $implementation = new class implements TestServiceInterface
        {
            public function getPrefix(): string
            {
                return 'interface-resolved';
            }
        };

        $container = Container::getInstance();
        $container->instance(TestServiceInterface::class, $implementation);

        $result = TestInterfaceDependencyAction::dispatch();

        $this->assertSame('interface-resolved', $result);
    }

    public function test_container_resolves_multiple_different_dependencies(): void
    {
        $logger = new class implements LoggerServiceInterface
        {
            public function log(string $message): string
            {
                return 'logged: '.$message;
            }
        };

        $cache = new class implements CacheServiceInterface
        {
            public function get(string $key): string
            {
                return 'cached: '.$key;
            }
        };

        $database = new class implements DatabaseServiceInterface
        {
            public function find(int $id): string
            {
                return 'record: '.$id;
            }
        };

        $container = Container::getInstance();
        $container->instance(LoggerServiceInterface::class, $logger);
        $container->instance(CacheServiceInterface::class, $cache);
        $container->instance(DatabaseServiceInterface::class, $database);

        $result = TestMultipleDependenciesAction::dispatch(id: 42);

        $expected = [
            'log' => 'logged: action executed',
            'cache' => 'cached: result',
            'database' => 'record: 42',
        ];

        $this->assertSame($expected, $result);
    }

    public function test_container_handles_optional_dependencies(): void
    {
        $optionalService = new class implements OptionalServiceInterface
        {
            public function getValue(): string
            {
                return 'optional-service';
            }
        };

        $container = Container::getInstance();
        $container->instance(OptionalServiceInterface::class, $optionalService);

        $result = TestOptionalDependencyAction::dispatch('default-value');

        $this->assertSame('optional-service-default-value', $result);
    }

    public function test_container_resolution_with_authorization_check(): void
    {
        $authService = new class implements AuthServiceInterface
        {
            public function can(string $action): bool
            {
                return $action === 'allowed-action';
            }
        };

        $container = Container::getInstance();
        $container->instance(AuthServiceInterface::class, $authService);

        $authorizedResult = TestAuthorizationAction::dispatch(action: 'allowed-action');
        $this->assertSame('authorized: allowed-action', $authorizedResult);

        $this->expectException(UnauthorizedException::class);
        TestAuthorizationAction::dispatch(action: 'forbidden-action');
    }

    public function test_dispatch_with_no_parameters(): void
    {
        $result = TestNoParametersAction::dispatch();

        $this->assertSame('no-parameters', $result);
    }

    public function test_dispatch_with_all_parameters_resolvable_from_container(): void
    {
        $container = Container::getInstance();
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);

        $result = TestAllParametersResolvableAction::dispatch();

        $this->assertSame('all-resolvable', $result);
    }

    public function test_dispatch_with_no_parameters_resolvable_from_container(): void
    {
        $result = TestParameterPassingAction::dispatch(message: 'none-resolvable');

        $this->assertSame('none-resolvable', $result);
    }

    public function test_dispatch_with_mixed_resolvable_parameters(): void
    {
        $container = Container::getInstance();
        $container->bind(IContainerContractStub::class, ContainerImplementationStub::class);

        $result = TestMixedResolvableParametersAction::dispatch(message: 'resolvable');

        $this->assertSame('mixed-resolvable', $result);
    }

    public function test_bad_method_call_exception_thrown_when_missing_handle_and_invoke(): void
    {
        $this->expectException(BadMethodCallException::class);

        TestBadMethodCallAction::dispatch();
    }
}

interface IContainerContractStub
{
}

class ContainerImplementationStub implements IContainerContractStub
{
}

class TestParameterPassingAction
{
    use Actionable;

    public function __construct(
        private readonly string $message = 'default'
    ) {
    }

    public function handle(): string
    {
        return $this->message;
    }

    public function authorize(): bool
    {
        return true;
    }
}

class TestUnauthorizedAction
{
    use Actionable;

    public function handle(): string
    {
        return 'hello world';
    }

    public function authorize(): bool
    {
        return false;
    }
}

class TestMultipleParametersAction
{
    use Actionable;

    public function __construct(
        private readonly string $name = '',
        private readonly int $age = 0
    ) {
    }

    public function handle(): array
    {
        return ['name' => $this->name, 'age' => $this->age];
    }
}

class TestNullReturnAction
{
    use Actionable;

    public function handle(): null
    {
        return null;
    }
}

class TestArrayReturnAction
{
    use Actionable;

    public function handle(): array
    {
        return ['key' => 'value', 'number' => 42];
    }
}

class TestObjectReturnAction
{
    use Actionable;

    public function handle(): object
    {
        return (object) ['property' => 'value'];
    }
}

class TestComplexAuthorizationAction
{
    use Actionable;

    public function __construct(
        private readonly string $condition = 'authorized-condition'
    ) {
    }

    public function handle(): string
    {
        return 'authorized-result';
    }

    public function authorize(): bool
    {
        return $this->condition == 'authorized-condition';
    }
}

class TestStringAuthorizeAction
{
    use Actionable;

    public function handle(): string
    {
        return 'hello world';
    }

    public function authorize(): string
    {
        return 'i am a string';
    }
}

class TestUnauthorizedMethodAction
{
    use Actionable;

    public function handle(): string
    {
        return 'hello world';
    }

    public function authorize(): bool
    {
        return false;
    }

    public function unauthorized(): string
    {
        return 'this action is unauthorized.';
    }
}

class TestInvokeFallbackAction
{
    use Actionable;

    public function __invoke(): string
    {
        return 'invoked';
    }
}

class TestContainerResolutionAction
{
    use Actionable;

    public function __construct(
        private readonly IContainerContractStub $service
    ) {
    }

    public function handle(): string
    {
        return 'auto-resolved-service';
    }
}

class TestAllParametersResolvableAction
{
    use Actionable;

    public function __construct(
        private readonly IContainerContractStub $service
    ) {
    }

    public function handle(): string
    {
        return 'all-resolvable';
    }
}

class TestMixedResolvableParametersAction
{
    use Actionable;

    public function __construct(
        private readonly IContainerContractStub $service,
        private readonly string $message = 'default'
    ) {
    }

    public function handle(): string
    {
        return 'mixed-'.$this->message;
    }
}

class TestNoParametersAction
{
    use Actionable;

    public function handle(): string
    {
        return 'no-parameters';
    }
}

interface TestServiceInterface
{
    public function getPrefix(): string;
}

class TestMixedParametersAction
{
    use Actionable;

    public function __construct(
        private readonly TestServiceInterface $service,
        private readonly string $message = 'default'
    ) {
    }

    public function handle(): string
    {
        return $this->service->getPrefix().' '.$this->message;
    }
}

class TestInterfaceDependencyAction
{
    use Actionable;

    public function __construct(
        private readonly TestServiceInterface $service
    ) {
    }

    public function handle(): string
    {
        return $this->service->getPrefix();
    }
}

interface LoggerServiceInterface
{
    public function log(string $message): string;
}
interface CacheServiceInterface
{
    public function get(string $key): string;
}
interface DatabaseServiceInterface
{
    public function find(int $id): string;
}

class TestMultipleDependenciesAction
{
    use Actionable;

    public function __construct(
        private readonly LoggerServiceInterface $logger,
        private readonly CacheServiceInterface $cache,
        private readonly DatabaseServiceInterface $database,
        private readonly int $id = 0
    ) {
    }

    public function handle(): array
    {
        return [
            'log' => $this->logger->log('action executed'),
            'cache' => $this->cache->get('result'),
            'database' => $this->database->find($this->id),
        ];
    }
}

interface OptionalServiceInterface
{
    public function getValue(): string;
}

class TestOptionalDependencyAction
{
    use Actionable;

    public function __construct(
        private readonly OptionalServiceInterface $service,
        private readonly string $fallback = 'default-value'
    ) {
    }

    public function handle(): string
    {
        return $this->service->getValue().'-'.$this->fallback;
    }
}

interface AuthServiceInterface
{
    public function can(string $action): bool;
}

class TestAuthorizationAction
{
    use Actionable;

    public function __construct(
        private readonly AuthServiceInterface $authService,
        private readonly string $action = 'default'
    ) {
    }

    public function authorize(): bool
    {
        return $this->authService->can($this->action);
    }

    public function handle(): string
    {
        return 'authorized: '.$this->action;
    }
}

class TestBadMethodCallAction
{
    use Actionable;
}
