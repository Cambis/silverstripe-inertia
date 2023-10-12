<?php

declare(strict_types=1);

use Cambis\Inertia\Inertia;
use Cambis\Inertia\Extension\InertiaPageControllerExtension;
use Netwerkstatt\SilverstripeRector\Rector\Misc\AddConfigPropertiesRector;
use Netwerkstatt\SilverstripeRector\Rector\Injector\UseCreateRector;
use Netwerkstatt\SilverstripeRector\Set\SilverstripeLevelSetList;
use Netwerkstatt\SilverstripeRector\Set\SilverstripeSetList;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\Closure\ClosureToArrowFunctionRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->autoloadPaths([]);

    $rectorConfig->paths([
        __DIR__ . '/_config.php',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    $rectorConfig->rules([
        UseCreateRector::class,
    ]);

    $rectorConfig->ruleWithConfiguration(
        AddConfigPropertiesRector::class,
        [
            Inertia::class => [
                'manifest_file',
                'root_view',
                'ssr_enabled',
                'ssr_host',
            ],
            InertiaPageControllerExtension::class => [
                'dependencies',
            ],
        ],
    );

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_81,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SilverstripeLevelSetList::UP_TO_SS_5_1,
        SilverstripeSetList::CODE_STYLE,
    ]);

    $rectorConfig->skip([
        ClassPropertyAssignToConstructorPromotionRector::class,
        ClosureToArrowFunctionRector::class,
        ReadOnlyPropertyRector::class,
    ]);
};
