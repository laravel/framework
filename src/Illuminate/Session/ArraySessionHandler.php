<?php

namespace Illuminate\Session;

use Illuminate\Support\InteractsWithTime;
use SessionHandlerInterface;

use const Illuminate\Support\Date\SECONDS_PER_MINUTE;

class ArraySessionHandler implements SessionHandlerInterface
{
    use InteractsWithTime;

    /**
     * The array of stored values.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * The number of minutes the session should be valid.
     *
     * @var int
     */
    protected $minutes;

    /**
     * Create a new array driven handler instance.
     *
     * @param  int  $minutes
     * @return void
     */
    public function __construct($minutes)
    {
        $this->minutes = $minutes;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function close(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return string|false
     */
    public function read($sessionId): string|false
    {
        if (! isset($this->storage[$sessionId])) {
            return '';
        }

        $session = $this->storage[$sessionId];

        $expiration = $this->calculateExpiration($this->minutes * SECONDS_PER_MINUTE);

        if (isset($session['time']) && $session['time'] >= $expiration) {
            return $session['data'];
        }

        return '';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function write($sessionId, $data): bool
    {
        $this->storage[$sessionId] = [
            'data' => $data,
            'time' => $this->currentTime(),
        ];

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function destroy($sessionId): bool
    {
        if (isset($this->storage[$sessionId])) {
            unset($this->storage[$sessionId]);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return int
     */
    public function gc($lifetime): int
    {
        $expiration = $this->calculateExpiration($lifetime);

        $deletedSessions = 0;

        foreach ($this->storage as $sessionId => $session) {
            if ($session['time'] < $expiration) {
                unset($this->storage[$sessionId]);
                $deletedSessions++;
            }
        }

        return $deletedSessions;
    }

    /**
     * Get the expiration time of the session.
     *
     * @param  int  $seconds
     * @return int
     */
    protected function calculateExpiration($seconds)
    {
        return $this->currentTime() - $seconds;
    }
}
