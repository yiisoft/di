<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\CodingStyle\Rector\ArrowFunction\ArrowFunctionDelegatingCallToFirstClassCallableRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php81\Rector\Array_\ArrayToFirstClassCallableRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withPhpSets(php81: true)
    ->withRules([
        InlineConstructorDefaultToPropertyRector::class,
    ])
    ->withSkip([
        ClosureToArrowFunctionRector::class,
        /**
         * The rule breaks `$this` rebinding via `Closure::call()` used in these tests.
         * @see https://github.com/rectorphp/rector/issues/9964
         */
        ArrowFunctionDelegatingCallToFirstClassCallableRector::class => [
            __DIR__ . '/tests/Unit/ContainerTest.php',
        ],
        ArrayToFirstClassCallableRector::class => [
            __DIR__ . '/tests/Unit/Helpers/DefinitionParserTest.php',
        ],
    ]);
