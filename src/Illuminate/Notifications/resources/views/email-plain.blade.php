<?php
if ($greeting !== null) {
    echo e($greeting);
} else {
    echo $level == 'error' ? 'Whoops!' : 'Hello!';
}

?>

<?php

if (! empty($introLines)) {
    echo implode("\r\n", $introLines), "\r\n\r\n";
}

if (isset($actionText)) {
    echo "{$actionText}: {$actionUrl}\r\n\r\n";
}

if (! empty($outroLines)) {
    echo implode("\r\n", $outroLines), "\r\n\r\n";
}

?>
Regards,
{{ config('app.name') }}
