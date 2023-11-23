<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        'no_unused_imports' => true,
    ])
    ->setFinder($finder);
