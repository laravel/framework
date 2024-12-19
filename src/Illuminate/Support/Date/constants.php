<?php

namespace Illuminate\Support\Date;

if (! defined(__NAMESPACE__.'\\'.($constant = 'SECONDS_PER_MINUTE'))) {
    define(__NAMESPACE__.'\\'.$constant, 60);
}

if (! defined(__NAMESPACE__.'\\'.($constant = 'MINUTES_PER_HOUR'))) {
    define(__NAMESPACE__.'\\'.$constant, 60);
}

if (! defined(__NAMESPACE__.'\\'.($constant = 'HOURS_PER_DAY'))) {
    define(__NAMESPACE__.'\\'.$constant, 24);
}

if (! defined(__NAMESPACE__.'\\'.($constant = 'DAYS_PER_WEEK'))) {
    define(__NAMESPACE__.'\\'.$constant, 7);
}

if (! defined(__NAMESPACE__.'\\'.($constant = 'MONTHS_PER_YEAR'))) {
    define(__NAMESPACE__.'\\'.$constant, 12);
}
