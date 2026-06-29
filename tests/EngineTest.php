<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Exception\TemplateFallbackException;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Unit test logic engine: render/fallback/version-gate/manifest queries.
 * Parity HTML template `standard` ở StandardTemplateTest.
 */
final class EngineTest extends TestCase
{
    private function realEngine(string $cssBaseUrl = ''): TemplateEngine
    {
        return new TemplateEngine(null, $cssBaseUrl);
    }

    private function fixtureEngine(): TemplateEngine
    {
        return new TemplateEngine(__DIR__ . '/fixtures/templates');
    }

    private function vm(): HeaderViewModel
    {
        return new HeaderViewModel(title: 'Xin chào');
    }

    public function testRenderStandardReturnsNonFallback(): void
    {
        $r = $this->realEngine()->render('standard', $this->vm(), new ArrayAdapter());

        $this->assertFalse($r->isFallback());
        $this->assertSame('standard', $r->renderedSlug);
        $this->assertStringContainsString('td-post-header', $r->html);
    }

    public function testUnknownSlugThrows(): void
    {
        try {
            $this->realEngine()->render('khong-ton-tai', $this->vm(), new ArrayAdapter());
            $this->fail('Đáng lẽ phải throw TemplateFallbackException');
        } catch (TemplateFallbackException $e) {
            $this->assertSame('khong-ton-tai', $e->requestedSlug);
            $this->assertSame('unknown_slug', $e->reason);
        }
    }

    public function testRenderWithFallbackOnUnknownSlug(): void
    {
        $r = $this->realEngine()->renderWithFallback('khong-ton-tai', 'article', $this->vm(), new ArrayAdapter());

        $this->assertTrue($r->isFallback());
        $this->assertSame('khong-ton-tai', $r->requestedSlug);
        $this->assertSame('standard', $r->renderedSlug);
        $this->assertSame('unknown_slug', $r->fallbackReason);
        $this->assertStringContainsString('td-post-header', $r->html);
    }

    public function testMinVersionTooHighThrows(): void
    {
        try {
            $this->fixtureEngine()->render('future', $this->vm(), new ArrayAdapter());
            $this->fail('Đáng lẽ phải throw');
        } catch (TemplateFallbackException $e) {
            $this->assertSame('min_version_too_high', $e->reason);
        }
    }

    public function testRenderWithFallbackOnMinVersion(): void
    {
        $r = $this->fixtureEngine()->renderWithFallback('future', 'article', $this->vm(), new ArrayAdapter());

        $this->assertTrue($r->isFallback());
        $this->assertSame('future', $r->requestedSlug);
        $this->assertSame('present', $r->renderedSlug);
        $this->assertSame('min_version_too_high', $r->fallbackReason);
    }

    public function testRenderWithFallbackUnknownContentKindUsesArticleDefault(): void
    {
        // content_kind lạ ('audio' — không khai báo default) → lùi về 'article' default ('standard'),
        // KHÔNG được throw unknown_slug (regression ISSUE-1).
        $r = $this->realEngine()->renderWithFallback('khong-ton-tai', 'audio', $this->vm(), new ArrayAdapter());

        $this->assertTrue($r->isFallback());
        $this->assertSame('standard', $r->renderedSlug);
        $this->assertSame('unknown_slug', $r->fallbackReason);
    }

    public function testManifestWithMissingDefaultSlugThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        new TemplateEngine(__DIR__ . '/fixtures/bad-manifest');
    }

    public function testRenderWithFallbackPhotostoryKindUsesPhotostory(): void
    {
        // content_kind 'photostory' giờ có default riêng → fallback về 'photostory' (không phải article).
        $r = $this->realEngine()->renderWithFallback('khong-ton-tai', 'photostory', $this->vm(), new ArrayAdapter());

        $this->assertTrue($r->isFallback());
        $this->assertSame('photostory', $r->renderedSlug);
        $this->assertSame('unknown_slug', $r->fallbackReason);
    }

    public function testRenderWithFallbackVideoKindUsesVideo(): void
    {
        $r = $this->realEngine()->renderWithFallback('khong-ton-tai', 'video', $this->vm(), new ArrayAdapter());

        $this->assertTrue($r->isFallback());
        $this->assertSame('video', $r->renderedSlug);
        $this->assertSame('unknown_slug', $r->fallbackReason);
    }

    public function testAllSevenTemplatesRegistered(): void
    {
        $slugs = $this->realEngine()->listTemplates();

        foreach (['standard', 'longform-default', 'cover', 'split', 'photostory', 'video', 'emagazine'] as $expected) {
            $this->assertContains($expected, $slugs);
        }
        $this->assertCount(7, $slugs);
    }

    public function testTemplatesCompatibleWith(): void
    {
        $engine = $this->fixtureEngine();

        // Frontside version 0.1.0 → 'future' (cần 9.9.9) bị loại.
        $this->assertSame(['present'], $engine->templatesCompatibleWith('0.1.0'));

        // Frontside version 9.9.9 → cả hai.
        $both = $engine->templatesCompatibleWith('9.9.9');
        $this->assertContains('present', $both);
        $this->assertContains('future', $both);
    }

    public function testListTemplatesContainsStandard(): void
    {
        $this->assertContains('standard', $this->realEngine()->listTemplates());
    }

    public function testGetMinEngineVersion(): void
    {
        $this->assertSame('0.1.0', $this->realEngine()->getMinEngineVersion('standard'));
    }

    public function testGetMinEngineVersionUnknownThrows(): void
    {
        $this->expectException(TemplateFallbackException::class);
        $this->realEngine()->getMinEngineVersion('khong-ton-tai');
    }

    public function testGetCurrentEngineVersion(): void
    {
        // Bumped from 0.1.0 → 0.2.0 in v0.2.0 release (post taxonomy refactor PR 3).
        $this->assertSame('0.2.0', $this->realEngine()->getCurrentEngineVersion());
    }

    public function testGetTemplateCssRelativeWhenNoBase(): void
    {
        $this->assertSame(['post-header.css'], $this->realEngine()->getTemplateCss('standard'));
    }

    public function testGetTemplateCssWithAbsoluteBase(): void
    {
        $engine = $this->realEngine('https://cdn.example/t/');

        $this->assertSame(['https://cdn.example/t/post-header.css'], $engine->getTemplateCss('standard'));
    }

    public function testVideoTemplateCssIncludesPlayerStylesheet(): void
    {
        $this->assertSame(['post-header.css', 'video.css'], $this->realEngine()->getTemplateCss('video'));
    }

    public function testEmagazineHasNoCss(): void
    {
        $this->assertSame([], $this->realEngine()->getTemplateCss('emagazine'));
    }

    public function testGetWrapperClass(): void
    {
        $engine = $this->realEngine();

        // Slug KHÔNG 1:1 với class.
        $this->assertSame('td-post--standard', $engine->getWrapperClass('standard'));
        $this->assertSame('td-post--longform', $engine->getWrapperClass('longform-default'));
        $this->assertSame('td-post--cover-story', $engine->getWrapperClass('cover'));
        $this->assertSame('td-post--photostory', $engine->getWrapperClass('photostory'));
        // emagazine no-header → null.
        $this->assertNull($engine->getWrapperClass('emagazine'));
    }

    public function testGetWrapperClassUnknownThrows(): void
    {
        $this->expectException(TemplateFallbackException::class);
        $this->realEngine()->getWrapperClass('khong-ton-tai');
    }
}
