<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Parity behavioral cho template `cover` (type_id=3) — post-header-cover-story.php.
 * Đặc trưng: cover-top block + inner (giống longform: topic img escape, byline CÓ separator).
 */
final class CoverTemplateTest extends TestCase
{
    private function render(HeaderViewModel $vm, ?ArrayAdapter $adapter = null): string
    {
        return (new TemplateEngine())->render('cover', $vm, $adapter ?? new ArrayAdapter())->html;
    }

    private function vmWithCover(): HeaderViewModel
    {
        return new HeaderViewModel(
            title: 'Bài bìa',
            categoryName: 'Sự kiện',
            categorySlug: 'su-kien',
            topicAvatarSource: 'https://cdn/t.jpg',
            topicSlug: 'chu-de',
            topicName: 'Chủ đề',
            authorName: 'Tác giả',
            publishedAt: '2026-06-06T14:30:00+07:00',
            sapo: 'Sapo',
            coverImageSource: 'https://cdn/cover.jpg',
        );
    }

    public function testRendersCoverTopBlockAndInner(): void
    {
        $html = $this->render($this->vmWithCover());

        $this->assertStringContainsString('<div class="td-post-header__cover">', $html);
        $this->assertStringContainsString('class="td-post-header__cover-img"', $html);
        $this->assertStringContainsString('alt="Bài bìa"', $html);
        $this->assertStringContainsString('<div class="td-post-header__inner">', $html);
        // cover dùng byline CÓ separator (giống standard/longform).
        $this->assertStringContainsString('td-post-header__sep', $html);
        // topic img ESCAPE (giống longform).
        $this->assertStringContainsString('src="https://cdn/t.jpg?w=400"', $html);
    }

    public function testNoCoverBlockWhenSourceEmpty(): void
    {
        $vm = new HeaderViewModel(title: 'Không bìa', categoryName: 'X', categorySlug: 'x');
        $html = $this->render($vm);

        $this->assertStringNotContainsString('td-post-header__cover', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
    }

    public function testCoverSrcEscaped(): void
    {
        $vm = new HeaderViewModel(title: 'T', coverImageSource: 'https://cdn/c.jpg?a=1&b=2');
        $html = $this->render($vm);

        // VARIANT_COVER không thêm query (ArrayAdapter pass-through) → src = escape của source.
        $this->assertStringContainsString('src="https://cdn/c.jpg?a=1&amp;b=2"', $html);
    }
}
