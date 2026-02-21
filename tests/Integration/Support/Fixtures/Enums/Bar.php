<?php

namespace Illuminate\Tests\Integration\Support\Fixtures\Enums;

enum Bar: string
{
    case MariaDb = 'mariadb';

    case MonitoringDb = 'monitoring-db';
}
