<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Parity behavioral cho template `longform-default` — port từ post-header-longform.php.
 * Giống `standard` về cấu trúc; KHÁC DUY NHẤT: topic img src ESCAPE (ENT_QUOTES).
 */
final class LongformTemplateTest extends TestCase
{
    private function render(HeaderViewModel $vm, ?ArrayAdapter $adapter = null): string
    {
        return (new TemplateEngine())->render('longform-default', $vm, $adapter ?? new ArrayAdapter())->html;
    }

    public function testRendersFullStructure(): void
    {
        $vm = new HeaderViewModel(
            title: 'Tiêu đề L1',
            categoryName: 'Phóng sự',
            categorySlug: 'phong-su',
            topicName: 'Chuyên đề',
            topicSlug: 'chuyen-de',
            topicAvatarSource: 'https://cdn/t.jpg',
            authorName: 'Tác giả',
            authorSlug: 'tac-gia-x',
            publishedAt: '2026-06-06T14:30:00+07:00',
            sapo: 'Sapo dài',
            siteName: 'ZMAG',
        );
        $html = $this->render($vm);

        $this->assertStringContainsString('<header class="td-post-header">', $html);
        $this->assertStringContainsString('<a href="/phong-su">', $html);
        $this->assertStringContainsString('href="/nhom-chu-de/chuyen-de"', $html);
        $this->assertStringContainsString('<h1 class="td-post-header__headline">Tiêu đề L1</h1>', $html);
        $this->assertStringContainsString('href="/tac-gia/tac-gia-x"', $html);
        $this->assertStringContainsString('datetime="2026-06-06T14:30:00+07:00"', $html);
        $this->assertStringContainsString('<strong>(ZMAG)</strong> - Sapo dài', $html);
    }

    public function testImageSourceIsEscaped(): void
    {
        // Longform ESCAPE img src (ENT_QUOTES) → & phải thành &amp; (ngược standard để raw).
        $vm = new HeaderViewModel(
            title: 'T',
            topicName: 'X',
            topicSlug: 'x',
            topicAvatarSource: 'https://cdn/x.jpg?a=1&b=2',
        );
        $html = $this->render($vm);

        $this->assertStringContainsString('src="https://cdn/x.jpg?a=1&amp;b=2?w=400"', $html);
        $this->assertStringNotContainsString('a=1&b=2?w=400', $html);
    }

    public function testMinimalVmOmitsConditionalBlocks(): void
    {
        $html = $this->render(new HeaderViewModel(title: 'Chỉ tiêu đề'));

        $this->assertStringContainsString('Chỉ tiêu đề', $html);
        $this->assertStringNotContainsString('td-post-header__kicker', $html);
        $this->assertStringNotContainsString('td-post-header__topic', $html);
        $this->assertStringNotContainsString('td-post-header__excerpt', $html);
        $this->assertStringContainsString('td-post-header__date', $html);
    }
}
