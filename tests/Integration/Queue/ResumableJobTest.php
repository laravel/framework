<?php

namespace Illuminate\Tests\Integration\Queue;

use Illuminate\Contracts\Queue\Resumable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\ResumableTrait;
use Illuminate\Support\Facades\Mail;
use Orchestra\Testbench\Attributes\WithMigration;

#[WithMigration]
#[WithMigration('cache')]
#[WithMigration('queue')]
class ResumableJobTest extends QueueTestCase
{
    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set('cache.default', 'database');
        $app['config']->set('queue.default', 'database');
    }

    protected function setUp(): void
    {
        parent::setUp();
        TestResumableJob::flush();
    }

    public function test_job()
    {
        Mail::fake();
        TestResumableJob::$throwException = true;
        TestResumableJob::dispatch(1234);


    }
}

class TestResumableJob implements Resumable, ShouldQueue
{
    use InteractsWithQueue;
    use ResumableTrait;
    use Dispatchable;

    public static bool $throwException = false;
    public static array $timesCalled = [];

    public static function flush(): void
    {
        self::$throwException = false;
        self::$timesCalled = [];
    }

    public function __construct(
        public int $userId
    ) {

    }
    public function handle()
    {
        $response = $this->step('get_data', $this->getData(...));
        $this->step('update_database', $this->updateDatabase(...));

        $emailsSent = $this->step('send_email', $this->sendEmail(...));

        event('emails_sent', $emailsSent);
    }

    private function getData()
    {
        self::$timesCalled['getData'] ??= 0;
        self::$timesCalled['getData']++;

        return [
            'data' => [
                [
                    'id' => 9876,
                    'email' => 'taylor@laravel.com',
                ],
                [
                    'id' => 1002,
                    'email' => 'abby@laravel.com',
                ],
            ],
        ];
    }

    private function updateDatabase()
    {
        self::$timesCalled['updateDatabase'] ??= 0;
        self::$timesCalled['updateDatabase']++;
        if (self::$throwException) {
            self::$throwException = false;

            throw new \Exception('You asked me to throw this');
        }

    }

    private function sendEmail(): int
    {
        self::$timesCalled['sendEmail'] ??= 0;
        self::$timesCalled['sendEmail']++;
        $emailsSent = 0;

        $users = $this->context->getState()->resultFor('get_data')['data'];
        foreach($users as $user) {
            Mail::to($user['email'])->send((new Mailable)->subject('test email: '.$this->userId));
            $emailsSent++;
        }

        return $emailsSent;
    }

}
