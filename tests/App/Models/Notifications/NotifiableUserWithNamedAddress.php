<?php

namespace Illuminate\Tests\App\Models\Notifications;

class NotifiableUserWithNamedAddress extends NotifiableUser
{
    public function routeNotificationForMail($notification)
    {
        return [
            $this->email => $this->name,
            'foo_'.$this->email,
        ];
    }
}
