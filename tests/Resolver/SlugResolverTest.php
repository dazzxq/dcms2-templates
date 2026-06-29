<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests\Resolver;

use Dazzxq\Dcms2Templates\Resolver\SlugResolver;
use PHPUnit\Framework\TestCase;

/**
 * v0.2.0 SlugResolver tests. Plan (codex APPROVE R2/2):
 * dcms2 .planning/2026-06-29-pr-3-dcms2-templates-v0.2.0-plan.md
 *
 * 23 cases covering:
 *   - Primary new-contract path (content_kind + header_variant)
 *   - Photostory + Mini Magazine flags ADDITIVE (don't change slug)
 *   - Legacy type_id fallback (transition window)
 *   - Defensive defaults (unknown content_kind / header_variant / legacyTypeId)
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

    // Section 2 — Legacy fallback (content_kind=null).

    public function test_11_null_kind_with_legacy_type_1_resolves_to_standard(): void
    {
        $this->assertSame('standard', SlugResolver::resolve(null, null, false, false, 1));
    }

    public function test_12_null_kind_with_legacy_type_2_resolves_to_longform_default(): void
    {
        $this->assertSame('longform-default', SlugResolver::resolve(null, null, false, false, 2));
    }

    public function test_13_null_kind_with_legacy_type_3_resolves_to_cover(): void
    {
        $this->assertSame('cover', SlugResolver::resolve(null, null, false, false, 3));
    }

    public function test_14_null_kind_with_legacy_type_4_resolves_to_split(): void
    {
        $this->assertSame('split', SlugResolver::resolve(null, null, false, false, 4));
    }

    public function test_15_null_kind_with_legacy_type_5_resolves_to_longform_default(): void
    {
        // Codex R1 ISSUE-3: Mini Magazine legacy (count=0 prod, defensive).
        $this->assertSame('longform-default', SlugResolver::resolve(null, null, false, false, 5));
    }

    public function test_16_null_kind_with_legacy_type_6_resolves_to_emagazine(): void
    {
        $this->assertSame('emagazine', SlugResolver::resolve(null, null, false, false, 6));
    }

    public function test_17_null_kind_with_legacy_type_7_resolves_to_photostory(): void
    {
        // LEGACY transition-only — dedicated photostory header. Removed in PR 5.
        $this->assertSame('photostory', SlugResolver::resolve(null, null, false, false, 7));
    }

    public function test_18_null_kind_null_type_resolves_to_standard(): void
    {
        $this->assertSame('standard', SlugResolver::resolve(null));
    }

    public function test_19_null_kind_unknown_type_resolves_to_standard(): void
    {
        $this->assertSame('standard', SlugResolver::resolve(null, null, false, false, 999));
    }

    // Section 3 — Defensive defaults.

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
