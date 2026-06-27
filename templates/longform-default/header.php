<?php
/**
 * Template view: longform-default (type_id=2, "Bài L1") — bài dài, full-width.
 * Port 1:1 từ frontsite views/components/post-header-longform.php.
 *
 * KHÁC `standard` DUY NHẤT ở markup: topic img src ESCAPE htmlspecialchars(ENT_QUOTES)
 * (standard để raw). Phần còn lại byte-identical. Khác biệt thị giác L1 vs M = CSS (F10).
 *
 * Scope: chỉ có $vm (HeaderViewModel) + $adapter (HeaderViewAdapter). KHÔNG host global.
 *
 * @var \Dazzxq\Dcms2Templates\Model\HeaderViewModel  $vm
 * @var \Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter $adapter
 */

declare(strict_types=1);

?>
<header class="td-post-header">
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
