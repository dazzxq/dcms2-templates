<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * v0.3.0 engine fallback tests post-PR5 cleanup.
 *
 * Codex R1 ISSUE-2 + R3 plan acceptance — verifies:
 *   - T25-26: renderWithFallback honors longform + emagazine defaults (KEPT from v0.2.0)
 *   - T27:    engineVersion 0.3.0
 *   - T28:    all 7 templates still compatible with 0.1.0 frontside (template entries untouched)
 *   - T29:    NEW — 'photostory' template entry STILL EXISTS in manifest (backwards-compat defense)
 *   - T30:    NEW — direct $engine->render('photostory', ...) succeeds (backwards-compat path)
 *   - T31:    NEW — renderWithFallback('bad-slug', 'photostory', ...) falls back to 'standard'
 *             (no 'photostory' default mapping post-v0.3.0 → uses 'article' base fallback)
 */
final class EngineFallbackV03Test extends TestCase
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

    public function test_27_engine_version_is_0_3_0(): void
    {
        $this->assertSame('0.3.0', $this->engine->getCurrentEngineVersion());
    }

    public function test_28_all_seven_templates_still_compatible_with_0_1_0_frontside(): void
    {
        // v0.3.0 keeps all template entries (only dropped 'photostory' from contentKindDefaults).
        // All 7 templates from v0.1.0 must still be selectable.
        $compat = $this->engine->templatesCompatibleWith('0.1.0');
        sort($compat);
        $expected = ['cover', 'emagazine', 'longform-default', 'photostory', 'split', 'standard', 'video'];
        sort($expected);
        $this->assertSame($expected, $compat);
    }

    public function test_29_photostory_template_entry_still_exists_in_manifest(): void
    {
        // Codex R1 ISSUE-2: KEEP 'photostory' template for direct $engine->render('photostory', ...)
        // backwards-compat. Only 'photostory' default mapping in contentKindDefaults was dropped.
        $this->assertContains('photostory', $this->engine->listTemplates());
    }

    public function test_30_direct_render_photostory_slug_succeeds_backwards_compat(): void
    {
        // Codex R1 ISSUE-2: callers using direct $engine->render('photostory', ...) must still work.
        $r = $this->engine->render('photostory', $this->vm, $this->adapter);
        $this->assertSame('photostory', $r->requestedSlug);
        $this->assertSame('photostory', $r->renderedSlug);
        $this->assertNull($r->fallbackReason);
        $this->assertNotSame('', trim($r->html), 'photostory template must render non-empty HTML');
    }

    public function test_31_render_with_fallback_bad_slug_photostory_kind_falls_back_to_standard(): void
    {
        // Post v0.3.0: 'photostory' no longer in contentKindDefaults → no fallback target for
        // bad slug + 'photostory' content_kind. renderWithFallback chain: bad-slug fails →
        // looks up 'photostory' default → MISSING → falls to 'article' base default ('standard').
        $r = $this->engine->renderWithFallback('bad-slug', 'photostory', $this->vm, $this->adapter);
        $this->assertSame('bad-slug', $r->requestedSlug);
        $this->assertSame('standard', $r->renderedSlug, 'Unknown content_kind falls back to article base default (standard)');
        $this->assertNotNull($r->fallbackReason);
    }

    public function test_32_resolver_silently_ignores_legacy_fifth_positional_argument(): void
    {
        // Codex impl R1 ISSUE-1 — document PHP behavior: removing the 5th parameter does
        // NOT make v0.2.0 callers passing `legacyTypeId` positional value fatal. PHP
        // userland functions silently ignore extra positional args. This is the SAFETY
        // INVARIANT enabling PR 6 frontside cleanup with NO race window.
        //
        // Acceptance gate for the silent-ignore behavior: 5-arg positional call returns
        // the same slug as 4-arg call. The 5th arg (formerly legacyTypeId) is dropped.
        // @phpstan-ignore-next-line — intentional too-many-args call to assert PHP behavior
        $fiveArg = \Dazzxq\Dcms2Templates\Resolver\SlugResolver::resolve('longform', 'cover_story', false, false, 7);
        $fourArg = \Dazzxq\Dcms2Templates\Resolver\SlugResolver::resolve('longform', 'cover_story', false, false);
        $this->assertSame($fourArg, $fiveArg, 'PHP must silently drop extra positional 5th arg — no race window');
        $this->assertSame('cover', $fiveArg);
    }
}
