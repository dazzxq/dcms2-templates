<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Parity behavioral cho template `split` (type_id=4) — post-header-split.php.
 * Đặc trưng: headline-block wrapper, byline KHÔNG separator, cover-right TRONG inner,
 * excerpt NGOÀI inner.
 */
final class SplitTemplateTest extends TestCase
{
    private function render(HeaderViewModel $vm): string
    {
        return (new TemplateEngine())->render('split', $vm, new ArrayAdapter())->html;
    }

    private function fullVm(): HeaderViewModel
    {
        return new HeaderViewModel(
            title: 'Bài split',
            categoryName: 'Mục',
            categorySlug: 'muc',
            authorName: 'Tác giả',
            authorSlug: 'tg',
            publishedAt: '2026-06-06T14:30:00+07:00',
            sapo: 'Sapo split',
            coverImageSource: 'https://cdn/cover.jpg',
        );
    }

    public function testHeadlineBlockWrapperAndNoSeparator(): void
    {
        $html = $this->render($this->fullVm());

        $this->assertStringContainsString('<div class="td-post-header__headline-block">', $html);
        // split byline KHÔNG có separator (khác standard/longform/cover/photostory).
        $this->assertStringNotContainsString('td-post-header__sep', $html);
        // Author vẫn render.
        $this->assertStringContainsString('href="/tac-gia/tg"', $html);
        $this->assertStringContainsString('td-post-header__date', $html);
    }

    public function testCoverRightInsideInnerExcerptOutside(): void
    {
        $html = $this->render($this->fullVm());

        $coverPos = strpos($html, 'td-post-header__cover');
        $excerptPos = strpos($html, 'td-post-header__excerpt');

        $this->assertNotFalse($coverPos);
        $this->assertNotFalse($excerptPos);
        // excerpt nằm SAU cover (excerpt ngoài inner, cover cuối inner) → vị trí lớn hơn.
        $this->assertGreaterThan($coverPos, $excerptPos);
    }

    public function testMinimalVmOmitsBlocks(): void
    {
        $html = $this->render(new HeaderViewModel(title: 'Chỉ tiêu đề'));

        $this->assertStringContainsString('td-post-header__headline-block', $html);
        $this->assertStringNotContainsString('td-post-header__cover', $html);
        $this->assertStringNotContainsString('td-post-header__excerpt', $html);
        $this->assertStringNotContainsString('td-post-header__kicker', $html);
    }
}
