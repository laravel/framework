<?php

namespace Illuminate\Broadcasting\Broadcasters;

class NullBroadcaster extends Broadcaster
{
    /**
     * {@inheritdoc}
     */
    public function broadcast(array $channels, $event, array $payload = [])
    {
        //
    }
    
       public function check($request)
    {
        // 
    }

    public function validAuthenticationResponse($request, $result)
    {
        // 
    }
    
}
