<?php

namespace Illuminate\Tests\Foundation\Concerns;

use Illuminate\Container\Container;
use Illuminate\Foundation\Concerns\Runnable;
use PHPUnit\Framework\TestCase;

class RunnableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function testItCanBeMadeThroughTheContainer()
    {
        Container::getInstance()->instance(RunnableDependency::class, $dependency = new RunnableDependency('resolved'));

        $runnable = RunnableStub::make();

        $this->assertSame($dependency, $runnable->dependency);
    }

    public function testItCanBeMadeWithParameters()
    {
        $runnable = ParameterizedRunnableStub::make(['value' => 'Taylor']);

        $this->assertSame('Taylor', $runnable->value);
    }

    public function testItCanBeRunThroughTheContainer()
    {
        Container::getInstance()->instance(RunnableDependency::class, new RunnableDependency('Hello'));

        $this->assertSame('Hello Taylor', RunnableStub::run('Taylor'));
    }

    public function testItCanBeFakedWithAResult()
    {
        $fake = RunnableStub::fake('Hello Taylor');

        $this->assertSame('Hello Taylor', RunnableStub::run('Taylor'));
        $this->assertSame($fake, Container::getInstance()->make(RunnableStub::class));

        $fake->shouldHaveReceived('handle')->once()->with('Taylor');
    }

    public function testItCanBeFakedWithAClosure()
    {
        $fake = RunnableStub::fake(fn (string $name) => "Hello {$name}");

        $this->assertSame('Hello Taylor', RunnableStub::run('Taylor'));

        $fake->shouldHaveReceived('handle')->once()->with('Taylor');
    }

    public function testItCanBeFakedWithoutAResult()
    {
        BooleanRunnableStub::fake();

        $this->assertFalse(BooleanRunnableStub::run());
    }
}

class RunnableStub
{
    use Runnable;

    public function __construct(public RunnableDependency $dependency)
    {
    }

    public function handle(string $name): string
    {
        return "{$this->dependency->value} {$name}";
    }
}

class ParameterizedRunnableStub
{
    use Runnable;

    public function __construct(public string $value)
    {
    }

    public function handle(): string
    {
        return $this->value;
    }
}

class RunnableDependency
{
    public function __construct(public string $value)
    {
    }
}

class BooleanRunnableStub
{
    use Runnable;

    public function handle(): bool
    {
        return true;
    }
}
