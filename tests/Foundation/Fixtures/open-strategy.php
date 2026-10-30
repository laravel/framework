<?php

use Symfony\Component\Process\Process;

return function ($url) {
    Process::fromShellCommandline(sprintf('echo %s > %s', escapeshellarg($url.'?expected-query=1'), escapeshellarg($GLOBALS['open-strategy-output-path'])))->run();
};
