<?php

namespace Illuminate\Tests\Session;

use Illuminate\Cookie\CookieJar;
use Illuminate\Session\CookieSessionHandler;
use Illuminate\Session\Store;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SessionHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

class SessionStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSessionIsLoadedFromHandler()
    {
        $session = $this->getSession();
        $session->getHandler()->shouldReceive('read')->once()->with($this->getSessionId())->andReturn(serialize(['foo' => 'bar', 'bagged' => ['name' => 'taylor']]));
        $session->start();

        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('baz', $session->get('bar', 'baz'));
        $this->assertTrue($session->has('foo'));
        $this->assertFalse($session->has('bar'));
        $this->assertTrue($session->isStarted());

        $session->put('baz', 'boom');
        $this->assertTrue($session->has('baz'));
    }

    public function testSessionMigration()
    {
        $session = $this->getSession();
        $oldId = $session->getId();
        $session->getHandler()->shouldReceive('destroy')->never();
        $this->assertTrue($session->migrate());
        $this->assertNotEquals($oldId, $session->getId());

        $session = $this->getSession();
        $oldId = $session->getId();
        $session->getHandler()->shouldReceive('destroy')->once()->with($oldId);
        $this->assertTrue($session->migrate(true));
        $this->assertNotEquals($oldId, $session->getId());
    }

    public function testSessionRegeneration()
    {
        $session = $this->getSession();
        $oldId = $session->getId();
        $session->getHandler()->shouldReceive('destroy')->never();
        $this->assertTrue($session->regenerate());
        $this->assertNotEquals($oldId, $session->getId());
    }

    public function testCantSetInvalidId()
    {
        $session = $this->getSession();
        $this->assertTrue($session->isValidId($session->getId()));

        $session->setId(null);
        $this->assertNotNull($session->getId());
        $this->assertTrue($session->isValidId($session->getId()));

        $session->setId(['a']);
        $this->assertNotSame(['a'], $session->getId());

        $session->setId('wrong');
        $this->assertNotSame('wrong', $session->getId());
    }

    public function testSessionInvalidate()
    {
        $session = $this->getSession();
        $oldId = $session->getId();

        $session->put('foo', 'bar');
        $this->assertGreaterThan(0, count($session->all()));

        $session->flash('name', 'Taylor');
        $this->assertTrue($session->has('name'));

        $session->getHandler()->shouldReceive('destroy')->once()->with($oldId);
        $this->assertTrue($session->invalidate());

        $this->assertFalse($session->has('name'));
        $this->assertNotEquals($oldId, $session->getId());
        $this->assertCount(0, $session->all());
    }

    public function testBrandNewSessionIsProperlySaved()
    {
        $session = $this->getSession();
        $session->getHandler()->shouldReceive('read')->once()->andReturn(serialize([]));
        $session->start();
        $session->put('foo', 'bar');
        $session->flash('baz', 'boom');
        $session->now('qux', 'norf');
        $session->getHandler()->shouldReceive('write')->once()->with(
            $this->getSessionId(),
            serialize([
                '_token' => $session->token(),
                'foo' => 'bar',
                'baz' => 'boom',
                '_flash' => [
                    'new' => [],
                    'old' => ['baz'],
                ],
            ])
        );
        $session->save();

        $this->assertFalse($session->isStarted());
    }

    public function testSessionIsProperlyUpdated()
    {
        $session = $this->getSession();
        $session->getHandler()->shouldReceive('read')->once()->andReturn(serialize([
            '_token' => Str::random(40),
            'foo' => 'bar',
            'baz' => 'boom',
            '_flash' => [
                'new' => [],
                'old' => ['baz'],
            ],
        ]));
        $session->start();

        $session->getHandler()->shouldReceive('write')->once()->with(
            $this->getSessionId(),
            serialize([
                '_token' => $session->token(),
                'foo' => 'bar',
                '_flash' => [
                    'new' => [],
                    'old' => [],
                ],
            ])
        );

        $session->save();

        $this->assertFalse($session->isStarted());
    }

    public function testSessionIsReSavedWhenNothingHasChanged()
    {
        $session = $this->getSession();
        $session->getHandler()->shouldReceive('read')->once()->andReturn(serialize([
            '_token' => Str::random(40),
            'foo' => 'bar',
            'baz' => 'boom',
            '_flash' => [
                'new' => [],
                'old' => [],
            ],
        ]));
        $session->start();

        $session->getHandler()->shouldReceive('write')->once()->with(
            $this->getSessionId(),
            serialize([
                '_token' => $session->token(),
                'foo' => 'bar',
                'baz' => 'boom',
                '_flash' => [
                    'new' => [],
                    'old' => [],
                ],
            ])
        );

        $session->save();

        $this->assertFalse($session->isStarted());
    }

    public function testSessionIsReSavedWhenNothingHasChangedExceptSessionId()
    {
        $session = $this->getSession();
        $oldId = $session->getId();
        $token = Str::random(40);
        $session->getHandler()->shouldReceive('read')->once()->with($oldId)->andReturn(serialize([
            '_token' => $token,
            'foo' => 'bar',
            'baz' => 'boom',
            '_flash' => [
                'new' => [],
                'old' => [],
            ],
        ]));
        $session->start();

        $oldId = $session->getId();
        $session->migrate();
        $newId = $session->getId();

        $this->assertNotEquals($newId, $oldId);

        $session->getHandler()->shouldReceive('write')->once()->with(
            $newId,
            serialize([
                '_token' => $token,
                'foo' => 'bar',
                'baz' => 'boom',
                '_flash' => [
                    'new' => [],
                    'old' => [],
                ],
            ])
        );

        $session->save();

        $this->assertFalse($session->isStarted());
    }

    public function testOldInputFlashing()
    {
        $session = $this->getSession();
        $session->put('boom', 'baz');
        $session->flashInput(['foo' => 'bar', 'bar' => 0, 'name' => null]);

        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->getOldInput('foo'));
        $this->assertEquals(0, $session->getOldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));

        $session->ageFlashData();

        $this->assertTrue($session->hasOldInput('foo'));
        $this->assertSame('bar', $session->getOldInput('foo'));
        $this->assertEquals(0, $session->getOldInput('bar'));
        $this->assertFalse($session->hasOldInput('boom'));

        $this->assertSame('default', $session->getOldInput('input', 'default'));
        $this->assertNull($session->getOldInput('name', 'default'));
    }

    public function testDataFlashing()
    {
        $session = $this->getSession();
        $session->flash('foo', 'bar');
        $session->flash('bar', 0);
        $session->flash('baz');

        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));
        $this->assertTrue($session->get('baz'));

        $session->ageFlashData();

        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));

        $session->ageFlashData();

        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }

    public function testDataFlashingNow()
    {
        $session = $this->getSession();
        $session->now('foo', 'bar');
        $session->now('bar', 0);

        $this->assertTrue($session->has('foo'));
        $this->assertSame('bar', $session->get('foo'));
        $this->assertEquals(0, $session->get('bar'));

        $session->ageFlashData();

        $this->assertFalse($session->has('foo'));
        $this->assertNull($session->get('foo'));
    }

    public function testDataMergeNewFlashes()
    {
        $session = $this->getSession();
        $session->flash('foo', 'bar');
        $session->put('fu', 'baz');
        $session->put('_flash.old', ['qu']);
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('fu', $session->get('_flash.new')));
        $session->keep(['fu', 'qu']);
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertNotFalse(array_search('fu', $session->get('_flash.new')));
        $this->assertNotFalse(array_search('qu', $session->get('_flash.new')));
        $this->assertFalse(array_search('qu', $session->get('_flash.old')));
    }

    public function testReflash()
    {
        $session = $this->getSession();
        $session->flash('foo', 'bar');
        $session->put('_flash.old', ['foo']);
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('foo', $session->get('_flash.old')));
    }

    public function testReflashWithNow()
    {
        $session = $this->getSession();
        $session->now('foo', 'bar');
        $session->reflash();
        $this->assertNotFalse(array_search('foo', $session->get('_flash.new')));
        $this->assertFalse(array_search('foo', $session->get('_flash.old')));
    }

    public function testOnly()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $session->put('qu', 'ux');
        $this->assertEquals(['foo' => 'bar', 'qu' => 'ux'], $session->all());
        $this->assertEquals(['qu' => 'ux'], $session->only(['qu']));
    }

    public function testExcept()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $session->put('bar', 'baz');
        $session->put('qu', 'ux');

        $this->assertEquals(['foo' => 'bar', 'qu' => 'ux', 'bar' => 'baz'], $session->all());
        $this->assertEquals(['bar' => 'baz', 'qu' => 'ux'], $session->except(['foo']));
    }

    public function testReplace()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $session->put('qu', 'ux');
        $session->replace(['foo' => 'baz']);
        $this->assertSame('baz', $session->get('foo'));
        $this->assertSame('ux', $session->get('qu'));
    }

    public function testRemove()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $pulled = $session->remove('foo');
        $this->assertFalse($session->has('foo'));
        $this->assertSame('bar', $pulled);
    }

    public function testClear()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');

        $session->flush();
        $this->assertFalse($session->has('foo'));

        $session->put('foo', 'bar');

        $session->flush();
        $this->assertFalse($session->has('foo'));
    }

    public function testIncrement()
    {
        $session = $this->getSession();

        $session->put('foo', 5);
        $foo = $session->increment('foo');
        $this->assertEquals(6, $foo);
        $this->assertEquals(6, $session->get('foo'));

        $foo = $session->increment('foo', 4);
        $this->assertEquals(10, $foo);
        $this->assertEquals(10, $session->get('foo'));

        $session->increment('bar');
        $this->assertEquals(1, $session->get('bar'));
    }

    public function testDecrement()
    {
        $session = $this->getSession();

        $session->put('foo', 5);
        $foo = $session->decrement('foo');
        $this->assertEquals(4, $foo);
        $this->assertEquals(4, $session->get('foo'));

        $foo = $session->decrement('foo', 4);
        $this->assertEquals(0, $foo);
        $this->assertEquals(0, $session->get('foo'));

        $session->decrement('bar');
        $this->assertEquals(-1, $session->get('bar'));
    }

    public function testHasOldInputWithoutKey()
    {
        $session = $this->getSession();
        $session->flash('boom', 'baz');
        $this->assertFalse($session->hasOldInput());

        $session->flashInput(['foo' => 'bar']);
        $this->assertTrue($session->hasOldInput());
    }

    public function testHandlerNeedsRequest()
    {
        $session = $this->getSession();
        $this->assertFalse($session->handlerNeedsRequest());
        $session->getHandler()->shouldReceive('setRequest')->never();

        $session = new Store('test', m::mock(new CookieSessionHandler(new CookieJar, 60, false)));
        $this->assertTrue($session->handlerNeedsRequest());
        $session->getHandler()->shouldReceive('setRequest')->once();
        $request = new Request;
        $session->setRequestOnHandler($request);
    }

    public function testToken()
    {
        $session = $this->getSession();
        $this->assertEquals($session->token(), $session->token());
    }

    public function testRegenerateToken()
    {
        $session = $this->getSession();
        $token = $session->token();
        $session->regenerateToken();
        $this->assertNotEquals($token, $session->token());
    }

    public function testName()
    {
        $session = $this->getSession();
        $this->assertEquals($session->getName(), $this->getSessionName());
        $session->setName('foo');
        $this->assertSame('foo', $session->getName());
    }

    public function testForget()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $this->assertTrue($session->has('foo'));
        $session->forget('foo');
        $this->assertFalse($session->has('foo'));

        $session->put('foo', 'bar');
        $session->put('bar', 'baz');
        $session->forget(['foo', 'bar']);
        $this->assertFalse($session->has('foo'));
        $this->assertFalse($session->has('bar'));
    }

    public function testSetPreviousUrl()
    {
        $session = $this->getSession();
        $session->setPreviousUrl('https://example.com/foo/bar');

        $this->assertTrue($session->has('_previous.url'));
        $this->assertSame('https://example.com/foo/bar', $session->get('_previous.url'));

        $url = $session->previousUrl();
        $this->assertSame('https://example.com/foo/bar', $url);
    }

    public function testPasswordConfirmed()
    {
        $session = $this->getSession();
        $this->assertFalse($session->has('auth.password_confirmed_at'));
        $session->passwordConfirmed();
        $this->assertTrue($session->has('auth.password_confirmed_at'));
    }

    public function testKeyPush()
    {
        $session = $this->getSession();
        $session->put('language', ['PHP' => ['Laravel']]);
        $session->push('language.PHP', 'Symfony');

        $this->assertEquals(['PHP' => ['Laravel', 'Symfony']], $session->get('language'));
    }

    public function testKeyPull()
    {
        $session = $this->getSession();
        $session->put('name', 'Taylor');

        $this->assertSame('Taylor', $session->pull('name'));
        $this->assertSame('Taylor Otwell', $session->pull('name', 'Taylor Otwell'));
        $this->assertNull($session->pull('name'));
    }

    public function testKeyHas()
    {
        $session = $this->getSession();
        $session->put('first_name', 'Mehdi');
        $session->put('last_name', 'Rajabi');

        $this->assertTrue($session->has('first_name'));
        $this->assertTrue($session->has('last_name'));
        $this->assertTrue($session->has('first_name', 'last_name'));
        $this->assertTrue($session->has(['first_name', 'last_name']));

        $this->assertFalse($session->has('first_name', 'foo'));
        $this->assertFalse($session->has('foo', 'bar'));
    }

    public function testKeyExists()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $this->assertTrue($session->exists('foo'));
        $session->put('baz', null);
        $session->put('hulk', ['one' => true]);
        $this->assertFalse($session->has('baz'));
        $this->assertTrue($session->exists('baz'));
        $this->assertFalse($session->exists('bogus'));
        $this->assertTrue($session->exists(['foo', 'baz']));
        $this->assertFalse($session->exists(['foo', 'baz', 'bogus']));
        $this->assertTrue($session->exists(['hulk.one']));
        $this->assertFalse($session->exists(['hulk.two']));
    }

    public function testKeyMissing()
    {
        $session = $this->getSession();
        $session->put('foo', 'bar');
        $this->assertFalse($session->missing('foo'));
        $session->put('baz', null);
        $session->put('hulk', ['one' => true]);
        $this->assertFalse($session->has('baz'));
        $this->assertFalse($session->missing('baz'));
        $this->assertTrue($session->missing('bogus'));
        $this->assertFalse($session->missing(['foo', 'baz']));
        $this->assertTrue($session->missing(['foo', 'baz', 'bogus']));
        $this->assertFalse($session->missing(['hulk.one']));
        $this->assertTrue($session->missing(['hulk.two']));
    }

    public function testRememberMethodCallsPutAndReturnsDefault()
    {
        $session = $this->getSession();
        $session->getHandler()->shouldReceive('get')->andReturn(null);
        $result = $session->remember('foo', function () {
            return 'bar';
        });
        $this->assertSame('bar', $session->get('foo'));
        $this->assertSame('bar', $result);
    }

    public function testRememberMethodReturnsPreviousValueIfItAlreadySets()
    {
        $session = $this->getSession();
        $session->put('key', 'foo');
        $result = $session->remember('key', function () {
            return 'bar';
        });
        $this->assertSame('foo', $session->get('key'));
        $this->assertSame('foo', $result);
    }

    public function testValidationErrorsCanBeSerializedAsJson()
    {
        $session = $this->getSession('json');
        $session->getHandler()->shouldReceive('read')->once()->andReturn(serialize([]));
        $session->start();
        $session->put('errors', $errorBag = new ViewErrorBag);
        $messageBag = new MessageBag([
            'first_name' => [
                'Your first name is required',
                'Your first name must be at least 1 character',
            ],
        ]);
        $messageBag->setFormat('<p>:message</p>');
        $errorBag->put('default', $messageBag);

        $session->getHandler()->shouldReceive('write')->once()->with(
            $this->getSessionId(),
            json_encode([
                '_token' => $session->token(),
                'errors' => [
                    'default' => [
                        'format' => '<p>:message</p>',
                        'messages' => [
                            'first_name' => [
                                'Your first name is required',
                                'Your first name must be at least 1 character',
                            ],
                        ],
                    ],
                ],
                '_flash' => [
                    'old' => [],
                    'new' => [],
                ],
            ])
        );
        $session->save();

        $this->assertFalse($session->isStarted());
    }

    public function testValidationErrorsCanBeReadAsJson()
    {
        $session = $this->getSession('json');
        $session->getHandler()->shouldReceive('read')->once()->with($this->getSessionId())->andReturn(json_encode([
            'errors' => [
                'default' => [
                    'format' => '<p>:message</p>',
                    'messages' => [
                        'first_name' => [
                            'Your first name is required',
                            'Your first name must be at least 1 character',
                        ],
                    ],
                ],
            ],
        ]));
        $session->start();

        $errors = $session->get('errors');

        $this->assertInstanceOf(ViewErrorBag::class, $errors);
        $this->assertInstanceOf(MessageBag::class, $errors->getBags()['default']);
        $this->assertEquals('<p>:message</p>', $errors->getBags()['default']->getFormat());
        $this->assertEquals(['first_name' => [
            'Your first name is required',
            'Your first name must be at least 1 character',
        ]], $errors->getBags()['default']->getMessages());
    }

    public function testItIsMacroable()
    {
        $this->getSession()->macro('foo', function () {
            return 'macroable';
        });

        $this->assertSame('macroable', $this->getSession()->foo());
    }

    public function getSession($serialization = 'php')
    {
        $reflection = new ReflectionClass(Store::class);

        return $reflection->newInstanceArgs($this->getMocks($serialization));
    }

    public function getMocks($serialization = 'json')
    {
        return [
            $this->getSessionName(),
            m::mock(SessionHandlerInterface::class),
            $this->getSessionId(),
            $serialization,
        ];
    }

    public function getSessionId()
    {
        return 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    }

    public function getSessionName()
    {
        return 'name';
    }
}
