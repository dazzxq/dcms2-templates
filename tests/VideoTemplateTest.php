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
    private function render(HeaderViewModel $vm, ?ArrayAdapter $adapter = null): string
    {
        // Mặc định khai cdn.zmag.vn là host self-hosted tin cậy (site khai qua setting).
        $adapter ??= new ArrayAdapter(settings: [
            'show_author_name' => true,
            'video_self_hosted_hosts' => ['cdn.zmag.vn'],
        ]);

        return (new TemplateEngine())->render('video', $vm, $adapter)->html;
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
        $allowed = [
            'https://www.youtube.com/embed/a',
            'https://youtube-nocookie.com/embed/b',
            'https://player.vimeo.com/video/1',
            'https://www.dailymotion.com/embed/video/c',
            'https://geo.dailymotion.com/player/x.html',
        ];
        foreach ($allowed as $url) {
            $this->assertStringContainsString('<iframe', $this->render($this->vm($url)), "Phải cho phép: {$url}");
        }
    }

    /**
     * @dataProvider disallowedEmbedPaths
     */
    public function testOmitsIframeForWrongProviderPath(string $url): void
    {
        // Host provider đúng nhưng PATH không phải embed/player thật → chặn (trang non-player frameable).
        $html = $this->render($this->vm($url));

        $this->assertStringNotContainsString('<iframe', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function disallowedEmbedPaths(): array
    {
        return [
            'youtube watch (không /embed/)' => ['https://www.youtube.com/watch?v=x'],
            'youtube root' => ['https://www.youtube.com/'],
            'dailymotion /video/ (cần /embed/video/)' => ['https://www.dailymotion.com/video/x'],
            'geo dailymotion sai path' => ['https://geo.dailymotion.com/embed/x'],
            'vimeo root' => ['https://player.vimeo.com/'],
            'geo playerXYZ (không delimiter)' => ['https://geo.dailymotion.com/playerXYZ'],
            'geo player.evil' => ['https://geo.dailymotion.com/player.evil/x'],
            'geo player-redirect' => ['https://geo.dailymotion.com/player-redirect'],
        ];
    }

    private function vmExtra(array $extra): HeaderViewModel
    {
        return new HeaderViewModel(
            title: 'Bài video',
            categoryName: 'Giải trí',
            categorySlug: 'giai-tri',
            publishedAt: '2026-06-06T14:30:00+07:00',
            extra: $extra,
        );
    }

    public function testSelfHostedRendersVideoElement(): void
    {
        $html = $this->render($this->vmExtra([
            'video_source_url' => 'https://cdn.zmag.vn/v/clip.mp4',
            'video_poster_url' => 'https://cdn.zmag.vn/v/poster.jpg',
            'video_mime' => 'video/mp4',
        ]));

        $this->assertStringContainsString('<video class="td-post-header__video-el" controls', $html);
        $this->assertStringContainsString('poster="https://cdn.zmag.vn/v/poster.jpg"', $html);
        $this->assertStringContainsString('<source src="https://cdn.zmag.vn/v/clip.mp4" type="video/mp4">', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function testSelfHostedTakesPriorityOverEmbed(): void
    {
        // Cả hai cùng có → self-hosted thắng (KHÔNG render iframe).
        $html = $this->render($this->vmExtra([
            'video_source_url' => 'https://cdn.zmag.vn/v/clip.mp4',
            'video_embed_url' => 'https://www.youtube.com/embed/x',
        ]));

        $this->assertStringContainsString('<video', $html);
        $this->assertStringNotContainsString('<iframe', $html);
    }

    public function testSelfHostedOmittedWhenNotHttps(): void
    {
        $html = $this->render($this->vmExtra(['video_source_url' => 'http://cdn.zmag.vn/v/clip.mp4']));

        $this->assertStringNotContainsString('<video', $html);
        $this->assertStringNotContainsString('td-post-header__video', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
    }

    public function testSelfHostedOmittedWhenHostlessHttps(): void
    {
        // 'https:cdn.zmag.vn/clip.mp4' (thiếu //) → parse_url không có host → KHÔNG render video.
        $html = $this->render($this->vmExtra(['video_source_url' => 'https:cdn.zmag.vn/v/clip.mp4']));

        $this->assertStringNotContainsString('<video', $html);
        $this->assertStringNotContainsString('td-post-header__video', $html);
    }

    public function testHostlessHttpsSourceDoesNotSuppressValidIframe(): void
    {
        // source hostless (invalid) + embed hợp lệ → KHÔNG được chặn iframe fallback.
        $html = $this->render($this->vmExtra([
            'video_source_url' => 'https:broken',
            'video_embed_url' => 'https://www.youtube.com/embed/x',
        ]));

        $this->assertStringContainsString('<iframe', $html);
        $this->assertStringNotContainsString('<video', $html);
    }

    public function testSelfHostedHostlessPosterOmittedButVideoRenders(): void
    {
        $html = $this->render($this->vmExtra([
            'video_source_url' => 'https://cdn.zmag.vn/v/clip.mp4',
            'video_poster_url' => 'https:poster.jpg',
        ]));

        $this->assertStringContainsString('<video', $html);
        $this->assertStringNotContainsString('poster=', $html);
    }

    public function testSelfHostedOmitsInvalidMimeAndPoster(): void
    {
        // mime không phải video/* + poster không https → bỏ 2 attr nhưng <video>/<source> vẫn render.
        $html = $this->render($this->vmExtra([
            'video_source_url' => 'https://cdn.zmag.vn/v/clip.mp4',
            'video_poster_url' => 'http://insecure/poster.jpg',
            'video_mime' => 'text/html"><script>',
        ]));

        $this->assertStringContainsString('<source src="https://cdn.zmag.vn/v/clip.mp4">', $html);
        $this->assertStringNotContainsString('poster=', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('type=', $html);
    }

    /**
     * @dataProvider disallowedSelfHostedSources
     */
    public function testSelfHostedOmittedForHostNotInAllowlist(string $url): void
    {
        // Host self-hosted KHÔNG nằm trong setting video_self_hosted_hosts (=['cdn.zmag.vn']) → bỏ.
        // Bao gồm host tùy ý, loopback IP, metadata host, port lạ trên host hợp lệ.
        $html = $this->render($this->vmExtra(['video_source_url' => $url]));

        $this->assertStringNotContainsString('<video', $html);
        $this->assertStringNotContainsString('td-post-header__video', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function disallowedSelfHostedSources(): array
    {
        return [
            'host tùy ý' => ['https://evil.example/clip.mp4'],
            'loopback IP' => ['https://127.0.0.1/admin.mp4'],
            'metadata host' => ['https://metadata.google.internal/v.mp4'],
            'port lạ trên host hợp lệ' => ['https://cdn.zmag.vn:8443/clip.mp4'],
        ];
    }

    public function testSelfHostedFailClosedWhenAllowlistEmpty(): void
    {
        // Site CHƯA khai video_self_hosted_hosts → fail-closed, KHÔNG render self-hosted.
        $adapter = new ArrayAdapter(settings: ['show_author_name' => true]);
        $html = $this->render(
            $this->vmExtra(['video_source_url' => 'https://cdn.zmag.vn/v/clip.mp4']),
            $adapter,
        );

        $this->assertStringNotContainsString('<video', $html);
        $this->assertStringContainsString('td-post-header__inner', $html);
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
