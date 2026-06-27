<?php

declare(strict_types=1);

// Manifest CỐ Ý SAI: contentKindDefaults trỏ slug 'ghost' không tồn tại trong templates.
// Engine phải throw RuntimeException ngay lúc construct (validation).
return [
    'engineVersion' => '0.1.0',
    'contentKindDefaults' => [
        'article' => 'ghost',
    ],
    'templates' => [
        'standard' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'standard/header.php',
            'css' => [],
        ],
    ],
];
