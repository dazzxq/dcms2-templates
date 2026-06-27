<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Parity behavioral cho template `standard` — port từ post-header-standard.php.
 * Kiểm: cấu trúc block, escape ĐÚNG (text escape / href+src RAW), bỏ block theo điều kiện.
 * (Byte-parity tuyệt đối old-component == engine = test phía frontside F12.)
 */
final class StandardTemplateTest extends TestCase
{
    private function render(HeaderViewModel $vm, ?ArrayAdapter $adapter = null): string
    {
        return (new TemplateEngine())->render('standard', $vm, $adapter ?? new ArrayAdapter())->html;
    }

    private function richVm(): HeaderViewModel
    {
        return new HeaderViewModel(
            title: 'Tiêu đề "A" & <b>',
            categoryName: 'Thời sự',
            categorySlug: 'thoi-su',
            topicName: 'Biển Đông',
            topicSlug: 'bien-dong',
            topicAvatarSource: 'https://cdn/x.jpg',
            authorName: 'Nguyễn A',
            authorSlug: 'nguyen-a',
            publishedAt: '2026-06-06T14:30:00+07:00',
            sapo: 'Sapo & test',
            siteName: 'ZMAG',
        );
    }

    public function testFullStructureAndEscaping(): void
    {
        $html = $this->render($this->richVm());

        // Khung
        $this->assertStringContainsString('<header class="td-post-header">', $html);
        $this->assertStringContainsString('<div class="td-post-header__inner">', $html);

        // Kicker (category) — href RAW path, name escaped (ở đây không có ký tự đặc biệt)
        $this->assertStringContainsString('<a href="/thoi-su">', $html);
        $this->assertStringContainsString('Thời sự', $html);

        // Topic — href RAW, title/alt escaped, img src RAW (đặc trưng standard)
        $this->assertStringContainsString('href="/nhom-chu-de/bien-dong"', $html);
        $this->assertStringContainsString('title="Nhóm chủ đề: Biển Đông"', $html);
        $this->assertStringContainsString('src="https://cdn/x.jpg?w=400"', $html);
        $this->assertStringContainsString('alt="Biển Đông"', $html);

        // Headline — escaped đầy đủ
        $this->assertStringContainsString(
            '<h1 class="td-post-header__headline">Tiêu đề &quot;A&quot; &amp; &lt;b&gt;</h1>',
            $html,
        );

        // Byline author — href RAW, data-slug + name escaped, có separator
        $this->assertStringContainsString('href="/tac-gia/nguyen-a"', $html);
        $this->assertStringContainsString('data-author-slug="nguyen-a"', $html);
        $this->assertStringContainsString('>Nguyễn A</a>', $html);
        $this->assertStringContainsString('td-post-header__sep', $html);

        // Date — datetime RAW (iso), hiển thị từ adapter
        $this->assertStringContainsString('datetime="2026-06-06T14:30:00+07:00"', $html);
        $this->assertStringContainsString('06/06/2026 14:30', $html);

        // Excerpt — siteName prefix + sapo escaped
        $this->assertStringContainsString('<strong>(ZMAG)</strong> - Sapo &amp; test', $html);
    }

    public function testImageSourceIsRawNotEscaped(): void
    {
        // Standard KHÔNG escape img src → ký tự & phải giữ raw (khác longform escape ENT_QUOTES).
        $vm = new HeaderViewModel(
            title: 'T',
            topicName: 'X',
            topicSlug: 'x',
            topicAvatarSource: 'https://cdn/x.jpg?a=1&b=2',
        );
        $html = $this->render($vm);

        $this->assertStringContainsString('src="https://cdn/x.jpg?a=1&b=2?w=400"', $html);
        $this->assertStringNotContainsString('&amp;b=2', $html);
    }

    public function testMinimalVmOmitsConditionalBlocks(): void
    {
        $html = $this->render(new HeaderViewModel(title: 'Chỉ tiêu đề'));

        $this->assertStringContainsString('Chỉ tiêu đề', $html);
        $this->assertStringNotContainsString('td-post-header__kicker', $html);
        $this->assertStringNotContainsString('td-post-header__topic', $html);
        $this->assertStringNotContainsString('td-post-header__author', $html);
        $this->assertStringNotContainsString('td-post-header__excerpt', $html);
        // <time> luôn render.
        $this->assertStringContainsString('td-post-header__date', $html);
    }

    public function testAuthorHiddenBySettingButDateRemains(): void
    {
        $adapter = new ArrayAdapter(settings: ['show_author_name' => false]);
        $html = $this->render($this->richVm(), $adapter);

        $this->assertStringNotContainsString('td-post-header__author', $html);
        $this->assertStringNotContainsString('td-post-header__sep', $html);
        $this->assertStringContainsString('td-post-header__date', $html);
    }
}
