<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class NotificationFakeTest extends PHPUnit_Framework_TestCase
{

	public function setup()
	{
		$this->notification = new NotificationFake();
	}

	public function tearDown()
    {
        Mockery::close();
    }

	public function testNotificationsCanBeSentToASingleNotifiable()
    {
		$notifiable = static::mockNotifiable();
		$notification = static::mockNotification();

		$this->notification->send( $notifiable, $notification );
        $this->notification->assertSentTo( $notifiable, $notification );
    }

	public function testNotificationsCanBeSentToAnArray()
    {
        $notifiable1 = static::mockNotifiable();
        $notifiable2 = static::mockNotifiable();
		$notification = static::mockNotification();

		$this->notification->send( [ $notifiable1, $notifiable2 ], $notification );

		$this->notification->assertSentTo( $notifiable1, $notification );
        $this->notification->assertSentTo( $notifiable2, $notification );
    }



	private static function mockNotifiable()
	{
		$observable = Mockery::mock();
		$observable->shouldReceive('getKey')
			->andReturn( rand(1,999) );
		return $observable;
	}

	private static function mockNotification()
	{
		$notification = Mockery::mock(Notification::class);
		$notification->shouldReceive('via')
			->andReturn([]);
		return $notification;
	}

}