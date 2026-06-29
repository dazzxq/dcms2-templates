<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests\Resolver;

use Dazzxq\Dcms2Templates\Resolver\SlugResolver;
use PHPUnit\Framework\TestCase;

/**
 * v0.3.0 SlugResolver tests. Plan (codex APPROVE R4/4):
 * dcms2 .planning/2026-06-30-pr-5-dcms2-templates-v0.3.0-plan.md
 *
 * 16 cases covering:
 *   - Primary new-contract path (content_kind + header_variant)
 *   - Photostory + Mini Magazine flags ADDITIVE (don't change slug)
 *   - Defensive defaults (unknown content_kind / header_variant / null content_kind)
 *   - Signature contract (4 params, legacyTypeId dropped post-R44 verify)
 *
 * v0.2.0 legacy fallback tests (T11-T19) REMOVED — frontside R44 confirmed
 * 0 published row hits the legacy path; PR 2 backfill 226 rows clean.
 */
final class SlugResolverTest extends TestCase
{
    // Section 1 — Primary new contract.

    public function test_1_article_content_kind_resolves_to_standard(): void
    {
        $this->assertSame('standard', SlugResolver::resolve('article'));
    }

    public function test_2_longform_with_header_variant_longform_resolves_to_longform_default(): void
    {
        $this->assertSame('longform-default', SlugResolver::resolve('longform', 'longform'));
    }

    public function test_3_longform_with_header_variant_cover_story_resolves_to_cover(): void
    {
        $this->assertSame('cover', SlugResolver::resolve('longform', 'cover_story'));
    }

    public function test_4_longform_with_header_variant_split_resolves_to_split(): void
    {
        $this->assertSame('split', SlugResolver::resolve('longform', 'split'));
    }

    public function test_5_longform_with_null_header_variant_resolves_to_longform_default(): void
    {
        $this->assertSame('longform-default', SlugResolver::resolve('longform', null));
    }

    public function test_6_is_photostory_flag_does_NOT_override_slug_when_header_variant_null(): void
    {
        // Codex R1 ISSUE-1: photostory is ADDITIVE body modifier, not header override.
        $this->assertSame('longform-default', SlugResolver::resolve('longform', null, true));
    }

    public function test_7_is_photostory_flag_does_NOT_override_cover_story_header_variant(): void
    {
        // Codex R1 ISSUE-1: header_variant wins.
        $this->assertSame('cover', SlugResolver::resolve('longform', 'cover_story', true));
    }

    public function test_8_is_mini_magazine_flag_does_NOT_change_slug(): void
    {
        $this->assertSame('longform-default', SlugResolver::resolve('longform', 'longform', false, true));
    }

    public function test_9_video_content_kind_resolves_to_video(): void
    {
        $this->assertSame('video', SlugResolver::resolve('video'));
    }

    public function test_10_emagazine_content_kind_resolves_to_emagazine(): void
    {
        $this->assertSame('emagazine', SlugResolver::resolve('emagazine'));
    }

    // Section 2 — Defensive defaults.
    // v0.3.0 — legacy fallback tests T11-T19 REMOVED (post-R44 verify clean,
    // 0 published row hits legacy path, PR 2 backfill 226 rows clean).
    // Replaced with single defensive null test + signature-contract test.

    public function test_null_content_kind_resolves_to_standard_defensive_default(): void
    {
        // v0.3.0 — null content_kind no longer triggers legacy type_id fallback (param removed).
        // Defensive: returns 'standard' (manifest guarantees this slug exists).
        $this->assertSame('standard', SlugResolver::resolve(null));
        $this->assertSame('standard', SlugResolver::resolve(null, 'longform'));
        $this->assertSame('standard', SlugResolver::resolve(null, null, true, true));
    }

    public function test_resolver_signature_drops_legacyTypeId_param(): void
    {
        // v0.3.0 codex R1 sig-change coverage — reflection assert method has exactly 4 params.
        $rm = new \ReflectionMethod(SlugResolver::class, 'resolve');
        $this->assertCount(4, $rm->getParameters(), 'SlugResolver::resolve() must have exactly 4 params post-v0.3.0 (legacyTypeId dropped)');
        $paramNames = array_map(fn ($p) => $p->getName(), $rm->getParameters());
        $this->assertSame(['contentKind', 'headerVariant', 'isPhotostory', 'isMiniMagazine'], $paramNames);
        $this->assertNotContains('legacyTypeId', $paramNames);
    }

    // Section 3 — Defensive defaults (unknown values).

    public function test_20_unknown_content_kind_resolves_to_standard(): void
    {
        $this->assertSame('standard', SlugResolver::resolve('unknown'));
    }

    public function test_21_longform_with_unknown_header_variant_resolves_to_longform_default(): void
    {
        // Codex R1 ISSUE-2: longform branch defensive default.
        $this->assertSame('longform-default', SlugResolver::resolve('longform', 'unknown-value'));
    }

    public function test_22_longform_with_both_flags_true_still_resolves_by_header_variant(): void
    {
        // Codex R1 ISSUE-1: BE validates mutex; resolver tolerates + renders header by content_kind + header_variant.
        $this->assertSame('longform-default', SlugResolver::resolve('longform', 'longform', true, true));
    }

    // Section 4 — Pure-function contract (no static state leak).

    public function test_23_resolver_is_pure_function_no_state_leak(): void
    {
        SlugResolver::resolve('longform', 'cover_story');
        // Second call with different input must NOT be influenced by first.
        $this->assertSame('standard', SlugResolver::resolve('article'));
        $this->assertSame('video', SlugResolver::resolve('video'));
    }
}
