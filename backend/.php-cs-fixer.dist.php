<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in(dirs: [__DIR__ . '/src', __DIR__ . '/tests']);

$config = new PhpCsFixer\Config();

return $config
    ->setRules(rules: [
        '@PSR12' => true,
        'yoda_style' => [
            'equal' => true,
            'identical' => true,
            'less_and_greater' => null,
        ],
        'strict_comparison' => true,
        'declare_strict_types' => true,
        'array_syntax' => ['syntax' => 'short'],
        'no_unused_imports' => true,
    ])
    ->setFinder(finder: $finder);
