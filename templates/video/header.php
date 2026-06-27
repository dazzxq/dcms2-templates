<?php
/**
 * Template view: video (content_kind=video) — bài video: player nhúng trên header (16:9),
 * text article bên dưới. KHÔNG có component gốc frontside (thiết kế MỚI per user 2026-06-28).
 *
 * Spec (user): player play được ngay trong header, tỉ lệ 16:9, max-width 1280px (theo
 * "wider width" frontsite). Sizing 16:9 + max 1280px = CSS (video.css, F10); template chỉ
 * dựng cấu trúc `__video` > `__video-frame` > iframe.
 *
 * BẢO MẬT: embed_url là dữ liệu editorial (DCMS2 video_metadata). Defense-in-depth:
 *   - CHỈ render iframe khi URL bắt đầu `https://` (chặn javascript:/data:/http).
 *   - escape ENT_QUOTES vào attribute (chống breakout).
 * Inner = kiểu article, ESCAPE topic img (chuẩn template mới, không theo standard-raw legacy).
 *
 * VM dùng `extra` bag: 'video_embed_url' (URL embed sẵn sàng, host chuẩn bị).
 *
 * @var \Dazzxq\Dcms2Templates\Model\HeaderViewModel  $vm
 * @var \Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter $adapter
 */

declare(strict_types=1);

// Allowlist host provider video tin cậy. Guard `https://` đơn thuần KHÔNG đủ: editor bị
// compromise / pipeline editorial bị thao túng có thể nhúng trang HTTPS tùy ý (phishing,
// UI lừa, API trình duyệt lạm dụng) NGAY trong header tin cậy. Chỉ cho host trong allowlist.
// Mở rộng khi cần thêm provider.
$allowedVideoHosts = [
    'www.youtube.com', 'youtube.com',
    'www.youtube-nocookie.com', 'youtube-nocookie.com',
    'player.vimeo.com',
];

$rawVideoEmbedUrl = $vm->extra('video_embed_url', '');
$videoEmbedUrl = is_string($rawVideoEmbedUrl) ? $rawVideoEmbedUrl : '';

$hasVideo = false;
if ($videoEmbedUrl !== '') {
    $parts = parse_url($videoEmbedUrl);
    $hasVideo = is_array($parts)
        && ($parts['scheme'] ?? null) === 'https'
        && !isset($parts['user']) && !isset($parts['pass']) && !isset($parts['port'])
        && in_array(strtolower((string) ($parts['host'] ?? '')), $allowedVideoHosts, true);
}

?>
<header class="td-post-header td-post-header--video">
    <?php if ($hasVideo): ?>
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
