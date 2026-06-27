<?php

declare(strict_types=1);

// Manifest fixture cho test version-gate + fallback (KHÔNG phải manifest production).
return [
    'engineVersion' => '0.1.0',
    'contentKindDefaults' => [
        'article' => 'present',
    ],
    'templates' => [
        'present' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'present/header.php',
            'css' => [],
        ],
        'future' => [
            'minEngineVersion' => '9.9.9',
            'contentKind' => 'article',
            'view' => 'future/header.php',
            'css' => ['future.css'],
        ],
    ],
];
