<?php

use Rector\Config\RectorConfig;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;
use Rector\CodeQuality\Rector\Isset_\IssetOnPropertyObjectToPropertyExistsRector;
use Rector\CodeQuality\Rector\Empty_\SimplifyEmptyCheckOnEmptyArrayRector;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withPhpSets()
    ->withSkip([
        DisallowedEmptyRuleFixerRector::class,
        IssetOnPropertyObjectToPropertyExistsRector::class,
        SimplifyEmptyCheckOnEmptyArrayRector::class,
    ])
    ->withPreparedSets(true, true);
