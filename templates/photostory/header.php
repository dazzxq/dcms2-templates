<?php
/**
 * Template view: photostory (type_id=7) — bài ảnh: cover lớn trên, text center dưới.
 * Port 1:1 từ frontsite views/components/post-header-photostory.php.
 *
 * Markup HIỆN identical với `cover` (cover-top + inner). Khác biệt thị giác photostory
 * vs cover = CSS (scope qua body-wrapper layout) — F10. Tách file riêng (KHÔNG share view)
 * để 2 template độc lập + parity-test riêng + cho phép diverge sau.
 *
 * @var \Dazzxq\Dcms2Templates\Model\HeaderViewModel  $vm
 * @var \Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter $adapter
 */

declare(strict_types=1);

?>
<header class="td-post-header">
    <?php if (!empty($vm->coverImageSource)): ?>
    <div class="td-post-header__cover">
        <div class="td-post-header__cover-box">
            <img class="td-post-header__cover-img"
                 src="<?= htmlspecialchars($adapter->imageUrl($vm->coverImageSource, $adapter::VARIANT_COVER), ENT_QUOTES) ?>"
                 alt="<?= htmlspecialchars($vm->title) ?>"
                 loading="eager">
        </div>
    </div>
    <?php endif; ?>

    <div class="td-post-header__inner">
        <?php if (!empty($vm->categoryName)): ?>
        <nav class="td-post-header__kicker" aria-label="Breadcrumb">
            <a href="<?= $adapter->urlFor($vm->categorySlug ?? '') ?>">
                <?= htmlspecialchars($vm->categoryName) ?>
            </a>
        </nav>
        <?php endif; ?>

        <?php if (!empty($vm->topicAvatarSource) && !empty($vm->topicSlug)): ?>
        <div class="td-post-header__topic">
            <a href="<?= $adapter->urlFor('nhom-chu-de/' . $vm->topicSlug) ?>"
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
            <a href="<?= $adapter->urlFor('tac-gia/' . $vm->authorSlug) ?>" class="td-post-header__author" data-author-slug="<?= htmlspecialchars($vm->authorSlug) ?>"><?= htmlspecialchars($vm->authorName) ?></a>
            <?php else: ?>
            <span class="td-post-header__author"><?= htmlspecialchars($vm->authorName) ?></span>
            <?php endif; ?>
            <span class="td-post-header__sep">•</span>
            <?php endif; ?>
            <time class="td-post-header__date" datetime="<?= $adapter->formatDate($vm->publishedAt, 'iso') ?>">
                <?= $adapter->formatDate($vm->publishedAt, 'default') ?>
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
