<?php declare(strict_types = 1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

$config = new Configuration();

return $config
    ->ignoreErrors([
        ErrorType::UNKNOWN_CLASS,
        ErrorType::UNUSED_DEPENDENCY,
        ErrorType::DEV_DEPENDENCY_IN_PROD,
    ]);
