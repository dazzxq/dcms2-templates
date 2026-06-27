<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter;
use Dazzxq\Dcms2Templates\Exception\TemplateFallbackException;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Model\RenderResult;
use PHPUnit\Framework\TestCase;

/**
 * Smoke test cho contract layer (F0/F1). Engine thật + parity test = F2+.
 */
final class ContractTest extends TestCase
{
    public function testHeaderViewModelMinimalConstruct(): void
    {
        $vm = new HeaderViewModel(title: 'Tiêu đề bài');

        $this->assertSame('Tiêu đề bài', $vm->title);
        $this->assertNull($vm->categoryName);
        $this->assertSame([], $vm->authors);
        $this->assertNull($vm->extra('không-có'));
        $this->assertSame('mặc định', $vm->extra('không-có', 'mặc định'));
    }

    public function testHeaderViewModelExtraBag(): void
    {
        $vm = new HeaderViewModel(
            title: 'Bài video',
            extra: ['video_provider' => 'youtube', 'duration' => 120],
        );

        $this->assertSame('youtube', $vm->extra('video_provider'));
        $this->assertSame(120, $vm->extra('duration'));
    }

    public function testExtraDistinguishesExplicitNullFromMissingKey(): void
    {
        $vm = new HeaderViewModel(
            title: 'Bài',
            extra: ['transcript' => null],
        );

        // Key tồn tại với giá trị null cố ý → trả null, KHÔNG trả default.
        $this->assertNull($vm->extra('transcript', 'fallback'));
        // Key vắng hẳn → trả default.
        $this->assertSame('fallback', $vm->extra('missing', 'fallback'));
    }

    public function testRenderResultNonFallback(): void
    {
        $r = new RenderResult(
            requestedSlug: 'standard',
            renderedSlug: 'standard',
            html: '<header></header>',
        );

        $this->assertFalse($r->isFallback());
        $this->assertNull($r->fallbackReason);
    }

    public function testRenderResultFallback(): void
    {
        $r = new RenderResult(
            requestedSlug: 'unknown-slug',
            renderedSlug: 'standard',
            html: '<header></header>',
            fallbackReason: 'unknown_slug',
        );

        $this->assertTrue($r->isFallback());
        $this->assertSame('unknown-slug', $r->requestedSlug);
        $this->assertSame('standard', $r->renderedSlug);
    }

    public function testRenderResultRejectsFallbackWithoutReason(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RenderResult(
            requestedSlug: 'unknown-slug',
            renderedSlug: 'standard',
            html: '<header></header>',
            // thiếu fallbackReason cho fallback → vi phạm invariant
        );
    }

    public function testRenderResultRejectsReasonWithoutFallback(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new RenderResult(
            requestedSlug: 'standard',
            renderedSlug: 'standard',
            html: '<header></header>',
            fallbackReason: 'không hợp lệ khi slug giống nhau',
        );
    }

    public function testTemplateFallbackExceptionCarriesContext(): void
    {
        $e = new TemplateFallbackException('emagazine', 'min_version_too_high');

        $this->assertSame('emagazine', $e->requestedSlug);
        $this->assertSame('min_version_too_high', $e->reason);
        $this->assertStringContainsString('emagazine', $e->getMessage());
    }

    public function testAdapterVariantConstants(): void
    {
        $this->assertSame('original', HeaderViewAdapter::VARIANT_ORIGINAL);
        $this->assertSame('cover', HeaderViewAdapter::VARIANT_COVER);
        $this->assertSame('topic', HeaderViewAdapter::VARIANT_TOPIC);
    }
}
