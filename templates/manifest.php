<?php

declare(strict_types=1);

/**
 * Manifest template — nguồn sự thật cho engine biết có template nào, version, CSS.
 * KHÔNG chứa logic; thuần dữ liệu. Engine require file này.
 *
 *   engineVersion        : version engine hiện tại (semver) — getCurrentEngineVersion()
 *   contentKindDefaults  : content_kind -> slug mặc định (renderWithFallback). CHỈ liệt kê kind
 *                          đã CÓ template; kind lạ → lùi về 'article' (base, bắt buộc tồn tại).
 *   templates[slug]      :
 *       minEngineVersion : version engine tối thiểu render được slug
 *       contentKind      : loại nội dung gốc
 *       view             : path tương đối tới file view (so với thư mục templates/)
 *       css              : list file CSS (tên tương đối; URL resolve ở F10/getTemplateCss)
 *
 * v0.1.0 sẽ đủ 7 slug; hiện mới `standard` (F3 vertical slice). F4-F9 thêm dần.
 */
return [
    'engineVersion' => '0.1.0',

    'contentKindDefaults' => [
        // CHỉ kind đã có template. 'article' BẮT BUỘC (base fallback). video thêm khi
        // template video ship (F8) — tới đó kind video vẫn lùi về 'article'/standard.
        'article' => 'standard',
        'photostory' => 'photostory',
    ],

    'templates' => [
        'standard' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'standard/header.php',
            'css' => ['standard.css'],
        ],
        'longform-default' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'longform-default/header.php',
            'css' => ['longform-default.css'],
        ],
        'cover' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'cover/header.php',
            'css' => ['cover.css'],
        ],
        'split' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'split/header.php',
            'css' => ['split.css'],
        ],
        'photostory' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'photostory',
            'view' => 'photostory/header.php',
            'css' => ['photostory.css'],
        ],
    ],
];
