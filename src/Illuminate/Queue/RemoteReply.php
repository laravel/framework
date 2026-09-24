<?php

namespace Illuminate\Queue;

use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Queue\Job;

class RemoteReply
{
    /**
     * Create a new remote reply handler instance.
     *
     * @param  \Illuminate\Contracts\Encryption\Encrypter  $encrypter
     */
    public function __construct(protected Encrypter $encrypter)
    {
        //
    }

    /**
     * Run the callbacks registered for the remote job's outcome.
     *
     * @param  \Illuminate\Contracts\Queue\Job  $job
     * @param  array  $data
     * @return void
     */
    public function handle(Job $job, array $data)
    {
        // The token was encrypted by this application, so a forged or altered token fails to decrypt...
        $reply = unserialize($this->encrypter->decryptString($data['token']));

        if (($data['status'] ?? null) === 'completed') {
            foreach ($reply['then'] as $callback) {
                $callback($data['result'] ?? null);
            }
        } else {
            $exception = new RemoteJobFailed(
                $reply['name'],
                $data['error']['message'] ?? "Remote job [{$reply['name']}] failed.",
                $data['error']['type'] ?? null,
            );

            foreach ($reply['catch'] as $callback) {
                $callback($exception);
            }
        }

        $job->delete();
    }
}
