<?php
/**
 * Template view: video (content_kind=video) — bài video: player nhúng trên header (16:9),
 * text article bên dưới. KHÔNG có component gốc frontside (thiết kế MỚI per user 2026-06-28).
 *
 * Spec (user): player play được ngay trong header, tỉ lệ 16:9, max-width 1280px (theo
 * "wider width" frontsite). Sizing 16:9 + max 1280px = CSS (video.css, F10); template chỉ
 * dựng cấu trúc `__video` > `__video-frame` > iframe.
 *
 * 2 MODE (host set qua extra bag):
 *   - self-hosted → <video> HTML5: 'video_source_url' (https), 'video_poster_url' (optional https),
 *     'video_mime' (optional, chỉ chấp nhận video/*). Ưu tiên nếu có source.
 *   - provider iframe (youtube/vimeo/dailymotion) → <iframe>: 'video_embed_url' (https + host allowlist).
 *
 * BẢO MẬT: dữ liệu editorial (DCMS2 video_metadata). Defense-in-depth:
 *   - iframe: ALLOWLIST host + PATH-PREFIX provider (chỉ /embed//video//player) + chặn userinfo/port.
 *   - <video>: source/poster phải https + host ∈ setting `video_self_hosted_hosts` (site khai CDN
 *     tin cậy; rỗng = fail-closed). Chặn host tùy ý/private-IP/metadata/localhost/port.
 *   - escape ENT_QUOTES mọi attribute (chống breakout). mime chỉ video/*.
 *   ⚠️ HOST PHẢI cung cấp setting 'video_self_hosted_hosts' (mảng host) để self-hosted render.
 * Inner = kiểu article, ESCAPE topic img + mọi adapter output (template mới, không legacy-raw).
 *
 * @var \Dazzxq\Dcms2Templates\Model\HeaderViewModel  $vm
 * @var \Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter $adapter
 */

declare(strict_types=1);

$asString = static fn (mixed $v): string => is_string($v) ? $v : '';

// Helper: URL phải https + có host + KHÔNG userinfo + KHÔNG port lạ. Trả host (lowercase) hoặc ''.
$httpsHost = static function (string $url): string {
    if ($url === '') {
        return '';
    }
    $p = parse_url($url);
    if (!is_array($p)
        || strtolower((string) ($p['scheme'] ?? '')) !== 'https'
        || isset($p['user']) || isset($p['pass']) || isset($p['port'])
        || !isset($p['host']) || $p['host'] === ''
    ) {
        return '';
    }

    return strtolower((string) $p['host']);
};

// ---- Mode 1: self-hosted <video> ----
// Video trên hạ tầng CHÍNH MÌNH. Package dùng chung 3 site khác CDN → KHÔNG hardcode host
// self-hosted được; site PHẢI khai allowlist qua setting 'video_self_hosted_hosts' (mảng host
// CDN/storage tin cậy). Rỗng = fail-closed (KHÔNG render self-hosted) — mặc định an toàn.
// Allowlist tự loại private-IP/localhost/metadata host (không nằm trong danh sách site khai).
$selfHostedSetting = $adapter->setting('video_self_hosted_hosts', []);
$selfHostedHosts = is_array($selfHostedSetting)
    ? array_map('strtolower', array_values(array_filter($selfHostedSetting, 'is_string')))
    : [];

$inSelfHostedAllowlist = static function (string $url) use ($httpsHost, $selfHostedHosts): bool {
    $host = $httpsHost($url);

    return $host !== '' && in_array($host, $selfHostedHosts, true);
};

$videoSourceUrl = $asString($vm->extra('video_source_url'));
$videoPosterUrl = $asString($vm->extra('video_poster_url'));
$videoMime = $asString($vm->extra('video_mime'));

$hasSelfHosted = $inSelfHostedAllowlist($videoSourceUrl);
$validPoster = $inSelfHostedAllowlist($videoPosterUrl);
$validMime = $videoMime !== '' && preg_match('#^video/[a-z0-9.+-]+$#i', $videoMime) === 1;

// ---- Mode 2: iframe provider ----
// ALLOWLIST host + PATH-PREFIX (chỉ URL player/embed thật, chặn trang non-player frameable
// / redirect / surface user-controlled trên host provider). Vẫn chặn userinfo/port (httpsHost).
$allowedIframeProviders = [
    'www.youtube.com' => '/embed/',
    'youtube.com' => '/embed/',
    'www.youtube-nocookie.com' => '/embed/',
    'youtube-nocookie.com' => '/embed/',
    'player.vimeo.com' => '/video/',
    'www.dailymotion.com' => '/embed/video/',
    'dailymotion.com' => '/embed/video/',
    // Prefix đều kết bằng '/' → str_starts_with delimiter-safe (chặn /playerXYZ, /player.evil...).
    'geo.dailymotion.com' => '/player/',
];

$videoEmbedUrl = $asString($vm->extra('video_embed_url'));
$hasIframe = false;
if (!$hasSelfHosted && $videoEmbedUrl !== '') {
    $host = $httpsHost($videoEmbedUrl);
    if ($host !== '' && isset($allowedIframeProviders[$host])) {
        $path = (string) (parse_url($videoEmbedUrl, PHP_URL_PATH) ?? '');
        $hasIframe = str_starts_with($path, $allowedIframeProviders[$host]);
    }
}

?>
<header class="td-post-header td-post-header--video">
    <?php if ($hasSelfHosted): ?>
    <div class="td-post-header__video">
        <div class="td-post-header__video-frame">
            <video class="td-post-header__video-el" controls preload="metadata"<?= $validPoster ? ' poster="' . htmlspecialchars($videoPosterUrl, ENT_QUOTES) . '"' : '' ?>>
                <source src="<?= htmlspecialchars($videoSourceUrl, ENT_QUOTES) ?>"<?= $validMime ? ' type="' . htmlspecialchars($videoMime, ENT_QUOTES) . '"' : '' ?>>
            </video>
        </div>
    </div>
    <?php elseif ($hasIframe): ?>
    <div class="td-post-header__video">
        <div class="td-post-header__video-frame">
            <iframe src="<?= htmlspecialchars($videoEmbedUrl, ENT_QUOTES) ?>"
                    title="<?= htmlspecialchars($vm->title) ?>"
                    loading="lazy"
                    referrerpolicy="strict-origin-when-cross-origin"
                    allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen></iframe>
        </div>
    </div>
    <?php endif; ?>

    <div class="td-post-header__inner">
        <?php if (!empty($vm->categoryName)): ?>
        <nav class="td-post-header__kicker" aria-label="Breadcrumb">
            <a href="<?= htmlspecialchars($adapter->urlFor($vm->categorySlug ?? ''), ENT_QUOTES) ?>">
                <?= htmlspecialchars($vm->categoryName) ?>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (!empty($vm->topicAvatarSource) && !empty($vm->topicSlug)): ?>
        <div class="td-post-header__topic">
            <a href="<?= htmlspecialchars($adapter->urlFor('nhom-chu-de/' . $vm->topicSlug), ENT_QUOTES) ?>"
               title="Nhóm chủ đề: <?= htmlspecialchars($vm->topicName ?? '') ?>">
                <img src="<?= htmlspecialchars($adapter->imageUrl($vm->topicAvatarSource, $adapter::VARIANT_TOPIC), ENT_QUOTES) ?>"
                     alt="<?= htmlspecialchars($vm->topicName ?? '') ?>"
                     loading="eager">
            </a>
        </div>
        <?php endif; ?>

        <h1 class="td-post-header__headline"><?= htmlspecialchars($vm->title) ?></h1>

        <div class="td-post-header__byline">
            <?php if ($adapter->setting('show_author_name') && !empty($vm->authorName)): ?>
            <?php if (!empty($vm->authorSlug)): ?>
            <a href="<?= htmlspecialchars($adapter->urlFor('tac-gia/' . $vm->authorSlug), ENT_QUOTES) ?>" class="td-post-header__author" data-author-slug="<?= htmlspecialchars($vm->authorSlug) ?>"><?= htmlspecialchars($vm->authorName) ?></a>
            <?php else: ?>
            <span class="td-post-header__author"><?= htmlspecialchars($vm->authorName) ?></span>
            <?php endif; ?>
            <span class="td-post-header__sep">•</span>
            <?php endif; ?>
            <time class="td-post-header__date" datetime="<?= htmlspecialchars($adapter->formatDate($vm->publishedAt, 'iso'), ENT_QUOTES) ?>">
                <?= htmlspecialchars($adapter->formatDate($vm->publishedAt, 'default')) ?>
            </time>
        </div>

        <?php if (!empty($vm->sapo)): ?>
        <div class="td-post-header__excerpt">
            <?php if (!empty($vm->siteName)): ?>
            <strong>(<?= htmlspecialchars($vm->siteName) ?>)</strong> - <?= htmlspecialchars($vm->sapo) ?>
            <?php else: ?>
            <?= htmlspecialchars($vm->sapo) ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</header>
