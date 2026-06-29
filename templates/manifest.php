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
    'engineVersion' => '0.3.0',

    'contentKindDefaults' => [
        // CHỉ kind đã có template. 'article' BẮT BUỘC (base fallback).
        //
        // v0.3.0 (dcms2 PR 5, 2026-06-30): DROPPED 'photostory' default entry post-R44
        // frontside flag-flip 3/3 sites verified clean. Photostory is now an additive body
        // modifier on longform (content_kind='longform' + is_photostory=true) — handled
        // via SlugResolver primary path, NOT a dedicated content_kind.
        //
        // The 'photostory' TEMPLATE entry below in templates[] is KEPT — backwards-compat
        // for explicit $engine->render('photostory', ...) callers. Only the default
        // mapping is removed.
        'article' => 'standard',
        'longform' => 'longform-default',
        'video' => 'video',
        'emagazine' => 'emagazine',
    ],

    // CSS: post-header.css = stylesheet DÙNG CHUNG (verbatim từ frontside, scope qua parent
    // class .td-post--{layout} mà host wrap ở F12). KHÔNG split per-template vì responsive section
    // gộp nhiều layout trong 1 selector. video thêm video.css (player 16:9 max-1280). emagazine = [].
    //
    // wrapperClass = class host PHẢI wrap quanh header để CSS scope đúng (slug KHÔNG 1:1 với class:
    // cover→cover-story, longform-default→longform). emagazine=null (no header chrome). Lấy qua
    // getWrapperClass() — F12 frontside + preview DCMS2 dùng để bọc đúng.
    'templates' => [
        'standard' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'standard/header.php',
            'css' => ['post-header.css'],
            'wrapperClass' => 'td-post--standard',
        ],
        'longform-default' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'longform-default/header.php',
            'css' => ['post-header.css'],
            'wrapperClass' => 'td-post--longform',
        ],
        'cover' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'cover/header.php',
            'css' => ['post-header.css'],
            'wrapperClass' => 'td-post--cover-story',
        ],
        'split' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'split/header.php',
            'css' => ['post-header.css'],
            'wrapperClass' => 'td-post--split',
        ],
        'photostory' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'photostory',
            'view' => 'photostory/header.php',
            'css' => ['post-header.css'],
            'wrapperClass' => 'td-post--photostory',
        ],
        'video' => [
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'video',
            'view' => 'video/header.php',
            'css' => ['post-header.css', 'video.css'],
            'wrapperClass' => 'td-post--video',
        ],
        'emagazine' => [
            // eMagazine không có header chrome → render rỗng, không CSS header, không wrapper.
            'minEngineVersion' => '0.1.0',
            'contentKind' => 'article',
            'view' => 'emagazine/header.php',
            'css' => [],
            'wrapperClass' => null,
        ],
    ],
];
