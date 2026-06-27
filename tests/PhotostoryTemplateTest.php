<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Parity behavioral cho template `photostory` (type_id=7) — post-header-photostory.php.
 * Markup hiện identical `cover` (cover-top + inner, byline CÓ separator).
 */
final class PhotostoryTemplateTest extends TestCase
{
    private function render(HeaderViewModel $vm): string
    {
        return (new TemplateEngine())->render('photostory', $vm, new ArrayAdapter())->html;
    }

    public function testRendersCoverTopAndInnerWithSeparator(): void
    {
        $vm = new HeaderViewModel(
            title: 'Bộ ảnh',
            categoryName: 'Ảnh',
            categorySlug: 'anh',
            authorName: 'Phóng viên',
            publishedAt: '2026-06-06T14:30:00+07:00',
            coverImageSource: 'https://cdn/photo.jpg',
        );
        $html = $this->render($vm);

        $this->assertStringContainsString('<div class="td-post-header__cover">', $html);
        $this->assertStringContainsString('alt="Bộ ảnh"', $html);
        $this->assertStringContainsString('<div class="td-post-header__inner">', $html);
        $this->assertStringContainsString('td-post-header__sep', $html);
    }

    public function testNoCoverWhenSourceEmpty(): void
    {
        $html = $this->render(new HeaderViewModel(title: 'Không ảnh'));

        $this->assertStringNotContainsString('td-post-header__cover', $html);
        $this->assertStringContainsString('Không ảnh', $html);
    }
}
