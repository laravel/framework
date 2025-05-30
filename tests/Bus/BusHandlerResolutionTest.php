<?php

namespace Tests\Bus;

use Illuminate\Bus\Attributes\HandledBy;
use Illuminate\Bus\Dispatcher;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;

class BusHandlerResolutionTest extends TestCase
{
    public function test_command_handler_correctly_resolved_via_attribute()
    {
        $resolution = (new Dispatcher(new Container()))
            ->getCommandHandler(new CommandForAttributeHandler());

        $this->assertInstanceOf(AttributeHandler::class, $resolution);
    }

    public function test_command_handler_correctly_resolved_via_map()
    {
        $resolution = tap(
            new Dispatcher(new Container()),
            function ($dispatcher) {
                $dispatcher->map([CommandForMapHandler::class => MapHandler::class]);
            })->getCommandHandler(new CommandForMapHandler());

        $this->assertInstanceOf(MapHandler::class, $resolution);
    }

    public function test_handler_resolution_returns_false_when_no_attribute_or_mapping()
    {
        $resolution = (new Dispatcher(new Container()))
            ->getCommandHandler(new CommandForMapHandler());

        $this->assertFalse($resolution);
    }

    public function test_mapped_handler_takes_precedence_over_attribute_handler()
    {
        $resolution = tap(
            new Dispatcher(new Container()),
            function ($dispatcher) {
                $dispatcher->map([CommandForAttributeHandler::class => MapHandler::class]);
            })->getCommandHandler(new CommandForAttributeHandler());

        $this->assertInstanceOf(MapHandler::class, $resolution);
        $this->assertNotInstanceOf(AttributeHandler::class, $resolution);
    }

    public function test_attribute_resolution_handles_reflection_exceptions_gracefully()
    {
        $resolution = (new Dispatcher(new Container()))
            ->getCommandHandler(new \stdClass());

        $this->assertFalse($resolution);
    }
}

#[HandledBy(AttributeHandler::class)]
class CommandForAttributeHandler
{
}

class CommandForMapHandler
{
}

class AttributeHandler
{
    public function handle($command)
    {
    }
}

class MapHandler
{
    public function handle($command)
    {
    }
}
