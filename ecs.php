<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/app', __DIR__ . '/tests'])
    ->withPreparedSets(psr12: true)
    ->withRules([
        NoUnusedImportsFixer::class,
        DeclareStrictTypesFixer::class,
    ]);
