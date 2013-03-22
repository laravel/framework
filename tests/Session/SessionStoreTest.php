<?php

use Mockery as m;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionStoreTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testValidSessionIsSet()
	{
		$store = $this->storeMock('isInvalid');
		$session = $this->dummySession();
		$cookies = m::mock('Illuminate\Cookie\CookieJar');
		$cookies->shouldReceive('get')->once()->with('name')->andReturn('foo');
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue($session));
		$store->expects($this->once())->method('isInvalid')->with($this->equalTo($session))->will($this->returnValue(false));
		$store->start($cookies, 'name');
		$this->assertEquals($session, $store->getSession());
	}


	public function testInvalidSessionCreatesFresh()
	{
		$store = $this->storeMock('isInvalid');
		$session = $this->dummySession();
		$cookies = m::mock('Illuminate\Cookie\CookieJar');
		$cookies->shouldReceive('get')->once()->with('name')->andReturn('foo');
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue($session));
		$store->expects($this->once())->method('isInvalid')->with($this->equalTo($session))->will($this->returnValue(true));
		$store->start($cookies, 'name');

		$session = $store->getSession();
		$this->assertFalse($store->sessionExists());
		$this->assertTrue(strlen($session['id']) == 40);
		$this->assertTrue(strlen($session['data']['_token']) == 40);
		$this->assertEquals($session['data']['_token'], $store->getToken());
		$this->assertFalse(isset($session['last_activity']));
	}


	public function testOldSessionsAreConsideredInvalid()
	{
		$store = $this->storeMock('createFreshSession');
		$cookies = m::mock('Illuminate\Cookie\CookieJar');
		$cookies->shouldReceive('get')->once()->with('name')->andReturn('foo');
		$session = $this->dummySession();
		$session['last_activity'] = '1111111111';
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue($session));
		$store->expects($this->once())->method('createFreshSession');
		$store->start($cookies, 'name');
	}


	public function testNullSessionsAreConsideredInvalid()
	{
		$store = $this->storeMock('createFreshSession');
		$cookies = m::mock('Illuminate\Cookie\CookieJar');
		$cookies->shouldReceive('get')->once()->with('name')->andReturn('foo');
		$store->expects($this->once())->method('retrieveSession')->with($this->equalTo('foo'))->will($this->returnValue(null));
		$store->expects($this->once())->method('createFreshSession');
		$store->start($cookies, 'name');
	}


	public function testBasicPayloadManipulation()
	{
		$store = $this->storeMock('isInvalid');
		$cookies = m::mock('Illuminate\Cookie\CookieJar');
		$cookies->shouldReceive('get')->once()->with('name')->andReturn('foo');
		$store->start($cookies, 'name');

		$store->put('foo', 'bar');
		$this->assertEquals('bar', $store->get('foo'));
		$this->assertTrue($store->has('foo'));
		$store->forget('foo');
		$this->assertFalse($store->has('foo'));
		$this->assertEquals('taylor', $store->get('bar', 'taylor'));
		$this->assertEquals('taylor', $store->get('bar', function() { return 'taylor'; }));
	}


	public function testFlashDataCanBeRetrieved()
	{
		$store = $this->storeMock();
		$store->setSession(array('id' => '1', 'data' => array(':new:' => array('foo' => 'bar'), ':old:' => array('baz' => 'boom'))));
		$this->assertEquals('bar', $store->get('foo'));
		$this->assertEquals('boom', $store->get('baz'));
	}


	public function testFlashMethodPutsDataInNewArray()
	{
		$store = $this->storeMock();
		$store->setSession(array('id' => '1', 'data' => array(':new:' => array(), ':old:' => array())));
		$store->flash('foo', 'bar');
		$session = $store->getSession();
		$this->assertEquals('bar', $session['data'][':new:']['foo']);
	}


	public function testReflashMethod()
	{
		$store = $this->storeMock();
		$store->setSession(array('id' => '1', 'data' => array(':new:' => array(), ':old:' => array('foo' => 'bar'))));
		$store->reflash();
		$session = $store->getSession();
		$this->assertEquals(array('foo' => 'bar'), $session['data'][':new:']);
	}


	public function testKeepMethod()
	{
		$store = $this->storeMock();
		$store->setSession(array('id' => '1', 'data' => array(':new:' => array(), ':old:' => array('foo' => 'bar', 'baz' => 'boom'))));
		$store->keep(array('foo'));
		$session = $store->getSession();
		$this->assertEquals(array('foo' => 'bar'), $session['data'][':new:']);
	}


	public function testFlushMethod()
	{
		$store = $this->storeMock(array('createData'));
		$store->setSession(array('id' => '1', 'data' => array(':new:' => array('foo' => 'bar'))));
		$store->expects($this->once())->method('createData');
		$store->flush();
	}


	public function testArrayAccess()
	{
		$store = $this->storeMock();
		$store->setSession(array('id' => '1', 'data' => array()));

		$store['foo'] = 'bar';
		$this->assertEquals('bar', $store['foo']);
		unset($store['foo']);
		$this->assertFalse(isset($store['foo']));
	}


	public function testRegenerateMethod()
	{
		$store = $this->storeMock();
		$store->setSession(array('id' => '1'));
		$store->regenerate();
		$session = $store->getSession();
		$this->assertTrue(strlen($session['id']) == 40);
		$this->assertFalse($store->sessionExists());
	}


	public function testFinishMethodCallsUpdateMethodAndAgesFlashData()
	{
		$store = $this->storeMock('getCurrentTime');
		$store->setSession($session = array('id' => '1', 'data' => array(':old:' => array('foo' => 'bar'), ':new:' => array('baz' => 'boom'))));
		$store->expects($this->any())->method('getCurrentTime')->will($this->returnValue(1));
		$session['last_activity'] = 1;
		$session['data'] = array(':old:' => array('baz' => 'boom'), ':new:' => array());
		$store->expects($this->once())->method('updateSession')->with($this->equalTo('1'), $this->equalTo($session));
		$response = new Response;
		$cookie = $this->getCookieJarMock();
		$store->finish($response, $cookie, 0);
	}


	public function testFinishMethodCallsCreateMethodAndAgesFlashData()
	{
		$store = $this->storeMock('getCurrentTime');
		$store->setSession($session = array('id' => '1', 'data' => array(':old:' => array('foo' => 'bar'), ':new:' => array('baz' => 'boom'))));
		$store->expects($this->any())->method('getCurrentTime')->will($this->returnValue(1));
		$session['last_activity'] = 1;
		$session['data'] = array(':old:' => array('baz' => 'boom'), ':new:' => array());
		$store->expects($this->once())->method('createSession')->with($this->equalTo('1'), $this->equalTo($session));
		$store->setExists(false);
		$response = new Response;
		$cookie = $this->getCookieJarMock();
		$store->finish($response, $cookie, 0);
	}


	public function testSweepersAreCalled()
	{
		$stub = $this->storeMock(array('getCurrentTime', 'sweep'), 'SweeperStub');
		$stub->setSession($this->dummySession());
		$stub->expects($this->any())->method('getCurrentTime')->will($this->returnValue(1));
		$stub->expects($this->once())->method('sweep')->with($this->equalTo(1 - (120 * 60)));
		$stub->setSweepLottery(array(100, 100));
		$stub->finish(new Symfony\Component\HttpFoundation\Response, $this->getCookieJarMock(), 0);
	}


	public function testSweeperIsNotCalledAgainstOdds()
	{
		$stub = $this->storeMock(array('getCurrentTime', 'sweep'), 'SweeperStub');
		$stub->setSession($this->dummySession());
		$stub->expects($this->any())->method('getCurrentTime')->will($this->returnValue(1));
		$stub->expects($this->never())->method('sweep');
		$stub->setSweepLottery(array(0, 100));
		$stub->finish(new Symfony\Component\HttpFoundation\Response, $this->getCookieJarMock(), 0);
	}


	public function testFlashInputFlashesInput()
	{
		$store = $this->storeMock();
		$store->setSession($this->dummySession());
		$store->flashInput(array('foo' => 'bar'));
		$session = $store->getSession();
		$this->assertEquals(array('foo' => 'bar'), $session['data'][':new:']['__old_input']);
		$this->assertEquals('bar', $store->getOldInput('foo'));
		$this->assertEquals(array('foo' => 'bar'), $store->getOldInput());
		$this->assertEquals('bar', $store->getOldInput('adslkasd', 'bar'));
		$this->assertEquals('bar', $store->getOldInput('adlkasdf', function() { return 'bar'; }));
	}


	public function testComplexArrayPayloadManipulation()
	{
		$store = $this->storeMock('isInvalid');
		$cookies = m::mock('Illuminate\Cookie\CookieJar');
		$cookies->shouldReceive('get')->once()->with('name')->andReturn('foo');
		$store->start($cookies, 'name');

		$store->put('foo.bar', 'baz');
		$this->assertEquals('baz', $store->get('foo.bar'));
		$this->assertEquals(array('bar' => 'baz'), $store->get('foo'));
		$store->put('foo.bat', 'qux');
		$this->assertCount(2, $store->get('foo'));
		$this->assertEquals(array('bar' => 'baz', 'bat' => 'qux'), $store->get('foo'));
		$this->assertTrue($store->has('foo.bat'));
		$store->forget('foo.bat');
		$this->assertEquals(array('bar' => 'baz'), $store->get('foo'));
		$this->assertFalse($store->has('foo.bat'));
		$store->flash('flash.foo', 'bar');
		$this->assertEquals(array('foo' => 'bar'), $store->get('flash'));
		$store->forget('flash');
	}


	protected function dummySession()
	{
		return array('id' => '123', 'data' => array(':old:' => array(), ':new:' => array()), 'last_activity' => '9999999999');
	}


	protected function storeMock($stub = array(), $class = 'Illuminate\Session\Store')
	{
		$stub = array_merge((array) $stub, array('retrieveSession', 'createSession', 'updateSession'));
		return $this->getMock($class, $stub);
	}


	protected function getCookieJarMock()
	{
		$mock = m::mock('Illuminate\Cookie\CookieJar');
		$mock->shouldReceive('make')->andReturn(new Symfony\Component\HttpFoundation\Cookie('foo', 'bar'));
		return $mock;
	}


	protected function getCookieJar()
	{
		return new Illuminate\Cookie\CookieJar(Request::create('/foo', 'GET'), m::mock('Illuminate\Encryption\Encrypter'), array('path' => '//', 'domain' => 'foo.com', 'secure' => false, 'httpOnly' => false));
	}

}


class SweeperStub extends Illuminate\Session\Store implements Illuminate\Session\Sweeper {

	public function retrieveSession($id)
	{
		//
	}

	public function createSession($id, array $session, Response $response)
	{
		//
	}

	public function updateSession($id, array $session, Response $response)
	{
		//
	}

	public function sweep($expiration)
	{
		//
	}

}
