<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Resolver;

/**
 * Maps post taxonomy state (content_kind + layout flags + legacy type_id) to engine slug.
 *
 * v0.2.0 dual-read resolver. Plan: dcms2 .planning/2026-06-29-pr-3-dcms2-templates-v0.2.0-plan.md
 * (codex APPROVE R2/2).
 *
 * Contract (locked from DCMS2 parent plan + PR 1a/1b/2):
 *   - Header slug = function of (content_kind, header_variant) ALONE.
 *   - is_photostory + is_mini_magazine are ADDITIVE body/banner modifiers; they do NOT
 *     change the header slug. Frontside applies photostory-* body classes via content.css
 *     and Mini Magazine banner overlay as orthogonal concerns.
 *   - Legacy type_id fallback fires when content_kind is null (unmigrated row); transition-only
 *     window — PR 5 (v0.3.0) removes the fallback parameter entirely.
 *
 * Pure function. No constructor, no state — adapter pattern matches TemplateEngine.
 */
final class SlugResolver
{
    /**
     * Resolve template slug from taxonomy state.
     *
     * @param  string|null  $contentKind     'article' | 'longform' | 'video' | 'emagazine' | null (legacy)
     * @param  string|null  $headerVariant   'longform' | 'cover_story' | 'split' | null (longform-only field)
     * @param  bool         $isPhotostory    longform-only additive flag (does NOT change slug)
     * @param  bool         $isMiniMagazine  longform-only additive flag (does NOT change slug)
     * @param  int|null     $legacyTypeId    posts.type_id pre-PR2-backfill fallback
     */
    public static function resolve(
        ?string $contentKind,
        ?string $headerVariant = null,
        bool $isPhotostory = false,
        bool $isMiniMagazine = false,
        ?int $legacyTypeId = null,
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

        // Legacy fallback path — content_kind is null OR unknown.
        if ($contentKind === null) {
            return match ($legacyTypeId) {
                2 => 'longform-default',
                3 => 'cover',
                4 => 'split',
                5 => 'longform-default',  // Mini Magazine legacy (count=0 prod, defensive only).
                6 => 'emagazine',
                7 => 'photostory',         // LEGACY transition-only — dedicated photostory header.
                // type_id=1, null, or any other → defensive standard.
                default => 'standard',
            };
        }

        // Unknown content_kind value → defensive standard.
        return 'standard';
    }
}
