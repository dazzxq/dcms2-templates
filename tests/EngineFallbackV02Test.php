<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * v0.2.0 engine fallback tests for new contentKindDefaults (longform + emagazine).
 *
 * Codex R1 ISSUE-4 — verifies renderWithFallback() honors the extended manifest
 * defaults map without regression on the existing article/photostory/video paths.
 */
final class EngineFallbackV02Test extends TestCase
{
    private TemplateEngine $engine;
    private HeaderViewModel $vm;
    private ArrayAdapter $adapter;

    protected function setUp(): void
    {
        $this->engine = new TemplateEngine();
        $this->vm = new HeaderViewModel(title: 'Test');
        $this->adapter = new ArrayAdapter();
    }

    public function test_25_bad_slug_with_longform_kind_falls_back_to_longform_default(): void
    {
        $r = $this->engine->renderWithFallback('bad-slug', 'longform', $this->vm, $this->adapter);
        $this->assertSame('bad-slug', $r->requestedSlug);
        $this->assertSame('longform-default', $r->renderedSlug);
        $this->assertNotNull($r->fallbackReason);
    }

    public function test_26_bad_slug_with_emagazine_kind_falls_back_to_emagazine(): void
    {
        $r = $this->engine->renderWithFallback('bad-slug', 'emagazine', $this->vm, $this->adapter);
        $this->assertSame('bad-slug', $r->requestedSlug);
        $this->assertSame('emagazine', $r->renderedSlug);
        $this->assertNotNull($r->fallbackReason);
    }

    public function test_27_engine_version_is_0_2_0(): void
    {
        $this->assertSame('0.2.0', $this->engine->getCurrentEngineVersion());
    }

    public function test_28_all_seven_templates_still_compatible_with_0_1_0_frontside(): void
    {
        // v0.2.0 is additive — no new templates require >0.1.0 minEngineVersion.
        // All 7 templates from v0.1.0 must still be selectable for a frontside on 0.1.0.
        $compat = $this->engine->templatesCompatibleWith('0.1.0');
        sort($compat);
        $expected = ['cover', 'emagazine', 'longform-default', 'photostory', 'split', 'standard', 'video'];
        sort($expected);
        $this->assertSame($expected, $compat);
    }
}
