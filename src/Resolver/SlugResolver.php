<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Resolver;

/**
 * Maps post taxonomy state (content_kind + layout flags) to engine slug.
 *
 * v0.3.0 cleanup release. Plan: dcms2 .planning/2026-06-30-pr-5-dcms2-templates-v0.3.0-plan.md
 * (codex APPROVE R4/4). v0.2.0 transitional `legacyTypeId` param + legacy fallback branch
 * REMOVED post-R44 frontside flag-flip 3/3 sites verified clean 2026-06-30 + PR 2 backfill
 * 226 rows + 0 errors. Grep audit gate PASS: 0 named-arg callers across 5 repos.
 *
 * Contract (locked from DCMS2 parent plan + PR 1a/1b/2):
 *   - Header slug = function of (content_kind, header_variant) ALONE.
 *   - is_photostory + is_mini_magazine are ADDITIVE body/banner modifiers; they do NOT
 *     change the header slug. Frontside applies photostory-* body classes via content.css
 *     and Mini Magazine banner overlay as orthogonal concerns.
 *
 * Pure function. No constructor, no state — adapter pattern matches TemplateEngine.
 */
final class SlugResolver
{
    /**
     * Resolve template slug from taxonomy state.
     *
     * @param  string|null  $contentKind     'article' | 'longform' | 'video' | 'emagazine' | null (defensive)
     * @param  string|null  $headerVariant   'longform' | 'cover_story' | 'split' | null (longform-only field)
     * @param  bool         $isPhotostory    longform-only additive flag (does NOT change slug)
     * @param  bool         $isMiniMagazine  longform-only additive flag (does NOT change slug)
     */
    public static function resolve(
        ?string $contentKind,
        ?string $headerVariant = null,
        bool $isPhotostory = false,
        bool $isMiniMagazine = false,
    ): string {
        // Primary path — content_kind drives selection.
        if ($contentKind === 'article') {
            return 'standard';
        }
        if ($contentKind === 'longform') {
            // header_variant determines slug; flags are additive (do NOT override).
            return match ($headerVariant) {
                'cover_story' => 'cover',
                'split' => 'split',
                // 'longform', null, or any unknown header_variant value → defensive longform-default.
                default => 'longform-default',
            };
        }
        if ($contentKind === 'video') {
            return 'video';
        }
        if ($contentKind === 'emagazine') {
            return 'emagazine';
        }

        // Defensive default — null content_kind (no published row hits this post-R44 verify
        // 2026-06-30; PR 2 backfill 226 rows 0 errors closed the legacy path) OR unknown
        // content_kind value → 'standard' (manifest guarantees this slug exists).
        return 'standard';
    }
}
