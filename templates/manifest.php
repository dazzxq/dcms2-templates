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
        // CHỈ kind đã có template. 'article' BẮT BUỘC (base fallback). photostory/video
        // thêm vào khi template tương ứng ship (F8/F9) — đến lúc đó chúng vẫn lùi về 'standard'.
        'article' => 'standard',
    ],

    'templates' => [
        'standard' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'standard/header.php',
            'css' => ['standard.css'],
        ],
    ],
];
