<?php

namespace Pusher;

// Stands in for the optional pusher/pusher-php-server package so it can be doubled.
if (! class_exists(Pusher::class)) {
    class Pusher
    {
        public function socket_auth($channel, $socketId, $customData = null)
        {
            //
        }

        public function presence_auth($channel, $socketId, $userId, $userInfo = null)
        {
            //
        }

        public function getSettings()
        {
            //
        }
    }
}
