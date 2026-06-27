<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Template `video` (content_kind=video) — thiết kế mới: player 16:9 max-1280 (CSS F10) +
 * inner article. Đọc 'video_embed_url' từ extra bag. SECURITY: chỉ render iframe khi https.
 */
final class VideoTemplateTest extends TestCase
{
    private function render(HeaderViewModel $vm): string
    {
        return (new TemplateEngine())->render('video', $vm, new ArrayAdapter())->html;
    }

    private function vm(string $embedUrl): HeaderViewModel
    {
        return new HeaderViewModel(
            title: 'Bài video',
            categoryName: 'Giải trí',
            categorySlug: 'giai-tri',
            authorName: 'PV',
            publishedAt: '2026-06-06T14:30:00+07:00',
            sapo: 'Mô tả',
            extra: ['video_embed_url' => $embedUrl],
        );
    }

    public function testRendersPlayerWhenHttpsEmbed(): void
    {
        $html = $this->render($this->vm('https://www.youtube.com/embed/abc123'));

        $this->assertStringContainsString('<div class="td-post-header__video">', $html);
        $this->assertStringContainsString('<iframe src="https://www.youtube.com/embed/abc123"', $html);
        $this->assertStringContainsString('allowfullscreen', $html);
        // Inner article vẫn render.
        $this->assertStringContainsString('<h1 class="td-post-header__headline">Bài video</h1>', $html);
        $this->assertStringContainsString('td-post-header__date', $html);
    }

    public function testEmbedUrlEscapedInAttribute(): void
    {
        $html = $this->render($this->vm('https://player.vimeo.com/video/1?a=1&b=2'));

        $this->assertStringContainsString('src="https://player.vimeo.com/video/1?a=1&amp;b=2"', $html);
    }

    /**
     * @dataProvider unsafeEmbedUrls
     */
    public function testOmitsPlayerForNonHttpsEmbed(string $unsafe): void
    {
        $html = $this->render($this->vm($unsafe));

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringNotContainsString('td-post-header__video', $html);
        // Header vẫn render phần inner article (không vỡ).
        $this->assertStringContainsString('td-post-header__inner', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function unsafeEmbedUrls(): array
    {
        return [
            'javascript' => ['javascript:alert(1)'],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'http (không TLS)' => ['http://youtube.com/embed/x'],
            'protocol-relative' => ['//youtube.com/embed/x'],
            'rỗng' => [''],
        ];
    }

    /**
     * @dataProvider disallowedHttpsHosts
     */
    public function testOmitsPlayerForDisallowedHttpsHost(string $url): void
    {
        // HTTPS nhưng host KHÔNG trong allowlist provider → bỏ player (chặn nhúng trang tùy ý).
        $html = $this->render($this->vm($url));

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function disallowedHttpsHosts(): array
    {
        return [
            'host lạ' => ['https://evil.example/embed/1'],
            'userinfo' => ['https://user@www.youtube.com/embed/x'],
            'port lạ' => ['https://www.youtube.com:8443/embed/x'],
            'subdomain giả mạo' => ['https://www.youtube.com.evil.com/embed/x'],
        ];
    }

    public function testAllowsKnownProviderHosts(): void
    {
        foreach (['https://www.youtube.com/embed/a', 'https://youtube-nocookie.com/embed/b', 'https://player.vimeo.com/video/1'] as $url) {
            $this->assertStringContainsString('<iframe', $this->render($this->vm($url)), "Phải cho phép: {$url}");
        }
    }

    public function testAdapterUrlsAreEscapedInAttributes(): void
    {
        // Template MỚI → escape mọi output adapter (href/datetime), chống attribute breakout.
        $vm = new HeaderViewModel(
            title: 'T',
            categoryName: 'C',
            categorySlug: 'a"b',
            extra: ['video_embed_url' => 'https://x/embed/1'],
        );
        $html = $this->render($vm);

        $this->assertStringContainsString('href="/a&quot;b"', $html);
        $this->assertStringNotContainsString('href="/a"b"', $html);
    }

    public function testNonStringEmbedUrlOmitsPlayerSafely(): void
    {
        // extra bag lỡ chứa array → KHÔNG cast bừa (no warning), chỉ bỏ player.
        $vm = new HeaderViewModel(
            title: 'T',
            extra: ['video_embed_url' => ['oops' => 'array']],
        );
        $html = $this->render($vm);

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
    }
}
