<?php

namespace Illuminate\Tests\Log;

use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Log\Context\Events\ContextDehydrating as Dehydrating;
use Illuminate\Log\Context\Events\ContextHydrated as Hydrated;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Orchestra\Testbench\TestCase;
use RuntimeException;

class ContextTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_it_can_set_values()
    {
        $values = [
            'string' => 'string',
            'bool' => false,
            'int' => 5,
            'float' => 5.5,
            'null' => null,
            'array' => [1, 2, 3],
            'hash' => ['foo' => 'bar'],
            'object' => (object) ['foo' => 'bar'],
            'enum' => Suit::Clubs,
            'backed_enum' => StringBackedSuit::Clubs,
        ];

        foreach ($values as $type => $value) {
            Context::add($type, $value);
        }

        foreach ($values as $type => $value) {
            $this->assertSame($value, Context::get($type));
        }
    }

    public function test_it_can_add_values_when_not_already_present()
    {
        Context::addIf('foo', 1);
        $this->assertSame(1, Context::get('foo'));

        Context::addIf('foo', 2);
        $this->assertSame(1, Context::get('foo'));
    }

    public function test_it_can_listen_to_the_hydrating_event()
    {
        Context::add('one', 1);
        Context::add('two', 2);
        Context::hydrated(function (Repository $context) {
            Context::add('two', 99);
            Context::add('three', 3);
        });
        Event::dispatch(new Hydrated(Context::getFacadeRoot()));

        $this->assertSame(1, Context::get('one'));
        $this->assertSame(99, Context::get('two'));
        $this->assertSame(3, Context::get('three'));
    }

    public function test_it_can_listen_to_the_dehydrated_event()
    {
        Context::add('one', 1);
        Context::add('two', 2);
        Context::dehydrating(function (Repository $context) {
            Context::add('two', 99);
            Context::add('three', 3);
        });
        Event::dispatch(new Dehydrating(Context::getFacadeRoot()));

        $this->assertSame(1, Context::get('one'));
        $this->assertSame(99, Context::get('two'));
        $this->assertSame(3, Context::get('three'));
    }

    public function test_it_can_modify_context_while_dehydrating_without_impacting_global_instance()
    {
        Context::add('one', 1);
        Context::dehydrating(function (Repository $context) {
            $context->add('one', 99);
        });

        $dehydrated = Context::dehydrate();
        $this->assertSame(1, Context::get('one'));

        Context::hydrate($dehydrated);
        $this->assertSame(99, Context::get('one'));
    }

    public function test_dehydrate_returns_null_when_empty()
    {
        $this->assertNull(Context::dehydrate());
    }

    public function test_hydrating_null_triggers_hydrating_event()
    {
        $called = false;
        Context::hydrated(function () use (&$called) {
            $called = true;
        });

        Context::hydrate(null);

        $this->assertTrue($called);
    }

    public function test_it_can_serialize_values()
    {
        Context::add([
            'string' => 'string',
            'bool' => false,
            'int' => 5,
            'float' => 5.5,
            'null' => null,
            'array' => [1, 2, 3],
            'hash' => ['foo' => 'bar'],
            'object' => (object) ['foo' => 'bar'],
            'enum' => Suit::Clubs,
            'backed_enum' => StringBackedSuit::Clubs,
        ]);
        Context::addHidden('number', 55);

        $dehydrated = Context::dehydrate();

        $this->assertSame([
            'data' => [
                'string' => 's:6:"string";',
                'bool' => 'b:0;',
                'int' => 'i:5;',
                'float' => 'd:5.5;',
                'null' => 'N;',
                'array' => 'a:3:{i:0;i:1;i:1;i:2;i:2;i:3;}',
                'hash' => 'a:1:{s:3:"foo";s:3:"bar";}',
                'object' => 'O:8:"stdClass":1:{s:3:"foo";s:3:"bar";}',
                'enum' => 'E:31:"Illuminate\Tests\Log\Suit:Clubs";',
                'backed_enum' => 'E:43:"Illuminate\Tests\Log\StringBackedSuit:Clubs";',
            ],
            'hidden' => [
                'number' => 'i:55;',
            ],
        ], $dehydrated);

        Context::flush();
        $this->assertNull(Context::get('string'));

        Context::hydrate($dehydrated);

        $this->assertSame(Context::get('string'), 'string');
        $this->assertSame(Context::get('bool'), false);
        $this->assertSame(Context::get('int'), 5);
        $this->assertSame(Context::get('float'), 5.5);
        $this->assertSame(Context::get('null'), null);
        $this->assertSame(Context::get('array'), [1, 2, 3]);
        $this->assertSame(Context::get('hash'), ['foo' => 'bar']);
        $this->assertEquals(Context::get('object'), (object) ['foo' => 'bar']);
        $this->assertSame(Context::get('enum'), Suit::Clubs);
        $this->assertSame(Context::get('backed_enum'), StringBackedSuit::Clubs);
        $this->assertSame(Context::getHidden('number'), 55);
    }

    public function test_it_can_push_to_list()
    {
        Context::push('breadcrumbs', 'foo');
        Context::push('breadcrumbs', 'bar');
        Context::push('breadcrumbs', 'baz', 'qux');

        $this->assertSame(['foo', 'bar', 'baz', 'qux'], Context::get('breadcrumbs'));
    }

    public function test_throws_when_pushing_to_non_array()
    {
        Context::add('breadcrumbs', 'foo');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to push value onto context stack for key [breadcrumbs].');
        Context::push('breadcrumbs', 'bar');
    }

    public function test_throws_when_pushing_to_non_list_array()
    {
        Context::add('breadcrumbs', ['foo' => 'bar']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to push value onto context stack for key [breadcrumbs].');
        Context::push('breadcrumbs', 'bar');
    }

    public function test_it_can_pop_from_list()
    {
        Context::push('breadcrumbs', 'foo', 'bar');

        $this->assertSame('bar', Context::pop('breadcrumbs'));
        $this->assertSame('foo', Context::pop('breadcrumbs'));
        $this->assertSame([], Context::get('breadcrumbs'));
    }

    public function test_throws_when_popping_from_empty_list()
    {
        Context::push('breadcrumbs', 'bar');
        Context::pop('breadcrumbs');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to pop value from context stack for key [breadcrumbs].');

        Context::pop('breadcrumbs');
    }

    public function test_throws_when_popping_from_non_list_array()
    {
        Context::add('breadcrumbs', ['foo' => 'bar']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to pop value from context stack for key [breadcrumbs].');
        Context::pop('breadcrumbs');
    }

    public function test_it_can_pop_from_hidden_list()
    {
        Context::pushHidden('breadcrumbs', 'foo', 'bar');

        $this->assertSame('bar', Context::popHidden('breadcrumbs'));
        $this->assertSame('foo', Context::popHidden('breadcrumbs'));
        $this->assertSame([], Context::getHidden('breadcrumbs'));
    }

    public function test_throws_when_popping_from_empty_hidden_list()
    {
        Context::pushHidden('breadcrumbs', 'bar');
        Context::popHidden('breadcrumbs');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to pop value from hidden context stack for key [breadcrumbs].');

        Context::popHidden('breadcrumbs');
    }

    public function test_throws_when_popping_from_hidden_non_list_array()
    {
        Context::addHidden('breadcrumbs', ['foo' => 'bar']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to pop value from hidden context stack for key [breadcrumbs].');
        Context::popHidden('breadcrumbs');
    }

    public function test_it_can_check_if_context_has_been_set()
    {
        Context::add('foo', 'bar');
        Context::add('null', null);

        $this->assertTrue(Context::has('foo'));
        $this->assertTrue(Context::has('null'));
        $this->assertFalse(Context::has('unset'));
    }

    public function test_it_can_check_if_value_is_in_context_stack()
    {
        Context::push('foo', 'bar', 'lorem');

        $this->assertTrue(Context::stackContains('foo', 'bar'));
        $this->assertTrue(Context::stackContains('foo', 'lorem'));
        $this->assertFalse(Context::stackContains('foo', 'doesNotExist'));
    }

    public function test_it_can_check_if_value_is_in_context_stack_with_closures()
    {
        Context::push('foo', 'bar', ['lorem'], 123);
        Context::pushHidden('baz');

        $this->assertTrue(Context::stackContains('foo', fn ($value) => $value === 'bar'));
        $this->assertFalse(Context::stackContains('foo', fn ($value) => $value === 'baz'));
    }

    public function test_it_can_check_if_value_is_in_hidden_context_stack()
    {
        Context::pushHidden('foo', 'bar', 'lorem');

        $this->assertTrue(Context::hiddenStackContains('foo', 'bar'));
        $this->assertTrue(Context::hiddenStackContains('foo', 'lorem'));
        $this->assertFalse(Context::hiddenStackContains('foo', 'doesNotExist'));
    }

    public function test_it_can_check_if_value_is_in_hidden_context_stack_with_closures()
    {
        Context::pushHidden('foo', 'baz');
        Context::push('foo', 'bar', ['lorem'], 123);

        $this->assertTrue(Context::hiddenStackContains('foo', fn ($value) => $value === 'baz'));
        $this->assertFalse(Context::hiddenStackContains('foo', fn ($value) => $value === 'bar'));
    }

    public function test_it_cannot_check_if_hidden_value_is_in_non_hidden_context_stack()
    {
        Context::pushHidden('foo', 'bar', 'lorem');

        $this->assertFalse(Context::stackContains('foo', 'bar'));
    }

    public function test_it_can_get_all_values()
    {
        Context::add('foo', 'bar');
        Context::add('null', null);

        $this->assertSame([
            'foo' => 'bar',
            'null' => null,
        ], Context::all());
    }

    public function test_it_silently_ignores_unset_values()
    {
        $this->assertNull(Context::get('foo'));
        $this->assertFalse(Context::has('foo'));
        $this->assertSame([], Context::all());
    }

    public function test_it_is_simple_key_value_system()
    {
        Context::add('parent.child', 5);

        $this->assertNull(Context::get('parent'));
        $this->assertSame(5, Context::get('parent.child'));
    }

    public function test_it_can_retrieve_subset_of_context()
    {
        Context::add('parent.child.1', 5);
        Context::add('parent.child.2', 6);
        Context::add('another', 7);

        $this->assertSame([
            'parent.child.1' => 5,
            'parent.child.2' => 6,
        ], Context::only([
            'parent.child.1',
            'parent.child.2',
        ]));
    }

    public function test_it_adds_context_to_logging()
    {
        $path = storage_path('logs/laravel.log');
        file_put_contents($path, '');
        Str::createUuidsUsingSequence(['expected-trace-id']);

        Context::add('trace_id', Str::uuid());
        Context::add('foo.bar', 123);
        Context::push('bar.baz', 456);
        Context::push('bar.baz', 789);

        Log::channel('single')->info('My name is {name}', [
            'name' => 'Tim',
            'framework' => 'Laravel',
        ]);
        $log = Str::after(file_get_contents(storage_path('logs/laravel.log')), '] ');

        $this->assertSame('testing.INFO: My name is Tim {"name":"Tim","framework":"Laravel"} {"trace_id":"expected-trace-id","foo.bar":123,"bar.baz":[456,789]}', trim($log));

        file_put_contents($path, '');
        Str::createUuidsNormally();
    }

    public function test_it_doesnt_override_log_instance_context()
    {
        $path = storage_path('logs/laravel.log');
        file_put_contents($path, '');
        Str::createUuidsUsingSequence(['expected-trace-id']);

        Context::add('name', 'James');

        Log::channel('single')->info('My name is {name}', [
            'name' => 'Tim',
        ]);
        $log = Str::after(file_get_contents($path), '] ');

        $this->assertSame('testing.INFO: My name is Tim {"name":"Tim"} {"name":"James"}', trim($log));

        file_put_contents($path, '');
        Str::createUuidsNormally();
    }

    public function test_it_doesnt_allow_context_to_be_used_as_parameters()
    {
        $path = storage_path('logs/laravel.log');
        file_put_contents($path, '');
        Str::createUuidsUsingSequence(['expected-trace-id']);

        Context::add('name', 'James');

        Log::channel('single')->info('My name is {name}');
        $log = Str::after(file_get_contents($path), '] ');

        $this->assertSame('testing.INFO: My name is {name}  {"name":"James"}', trim($log));

        file_put_contents($path, '');
        Str::createUuidsNormally();
    }

    public function test_does_not_add_hidden_context_to_logging()
    {
        $path = storage_path('logs/laravel.log');
        file_put_contents($path, '');
        Str::createUuidsUsingSequence(['expected-trace-id']);

        Context::addHidden('hidden_data', 'hidden_data');

        Log::channel('single')->info('My name is {name}', [
            'name' => 'Tim',
            'framework' => 'Laravel',
        ]);
        $log = Str::after(file_get_contents($path), '] ');

        $this->assertStringNotContainsString('hidden_data', trim($log));

        file_put_contents($path, '');
        Str::createUuidsNormally();
    }

    public function test_it_can_add_hidden()
    {
        Context::addHidden('foo', 'data');

        $this->assertFalse(Context::has('foo'));
        $this->assertTrue(Context::hasHidden('foo'));
        $this->assertNull(Context::get('foo'));
        $this->assertSame('data', Context::getHidden('foo'));
        $this->assertSame(['foo' => 'data'], Context::onlyHidden(['foo']));

        Context::forgetHidden('foo');

        $this->assertFalse(Context::has('foo'));
        $this->assertFalse(Context::hasHidden('foo'));
        $this->assertNull(Context::get('foo'));
        $this->assertNull(Context::getHidden('foo'));

        Context::pushHidden('foo', 1);
        Context::pushHidden('foo', 2);
        $this->assertSame([1, 2], Context::getHidden('foo'));

        Context::addHidden('foo', 'bar');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to push value onto hidden context stack for key [foo].');
        Context::pushHidden('foo', 2);
    }

    public function test_it_can_pull()
    {
        Context::add('foo', 'data');

        $this->assertSame('data', Context::pull('foo'));
        $this->assertNull(Context::get('foo'));

        Context::addHidden('foo', 'data');

        $this->assertSame('data', Context::pullHidden('foo'));
        $this->assertNull(Context::getHidden('foo'));
    }

    public function test_it_adds_context_to_logged_exceptions()
    {
        $path = storage_path('logs/laravel.log');
        file_put_contents($path, '');
        Str::createUuidsUsingSequence(['expected-trace-id']);

        Context::add('trace_id', Str::uuid());
        Context::add('foo.bar', 123);
        Context::push('bar.baz', 456);
        Context::push('bar.baz', 789);

        $this->app[ExceptionHandler::class]->report(new Exception('Whoops!'));
        $log = Str::after(file_get_contents($path), '] ');

        $this->assertStringEndsWith(' {"trace_id":"expected-trace-id","foo.bar":123,"bar.baz":[456,789]}', trim($log));

        file_put_contents($path, '');
        Str::createUuidsNormally();
    }
}

enum Suit
{
    case Hearts;
    case Diamonds;
    case Clubs;
    case Spades;
}

enum StringBackedSuit: string
{
    case Hearts = 'hearts';
    case Diamonds = 'diamonds';
    case Clubs = 'clubs';
    case Spades = 'spades';
}

class ContextModel extends Model
{
    //
}
