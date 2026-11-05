<?php

namespace Illuminate\Console\Scheduling;

enum ScheduledTaskSkipReason: string
{
    case ConditionNotMet = 'condition_not_met';
    case Paused = 'paused';
    case RanOnAnotherServer = 'ran_on_another_server';
}
