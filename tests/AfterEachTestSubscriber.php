<?php

declare(strict_types=1);

namespace Illuminate\Tests;

use PHPUnit\Event\Test\AfterTestMethodFinished;
use PHPUnit\Event\Test\AfterTestMethodFinishedSubscriber;

final class AfterEachTestSubscriber implements AfterTestMethodFinishedSubscriber
{
    public function notify(AfterTestMethodFinished $event): void
    {
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }

        if (class_exists(\Illuminate\Support\Facades\Date::class)) {
            \Illuminate\Support\Facades\Date::useDefault();
            \Illuminate\Support\Facades\Date::setTestNow();
            \Illuminate\Support\Facades\Date::serializeUsing(null);
        }
    }
}
