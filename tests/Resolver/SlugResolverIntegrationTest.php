<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests\Resolver;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Resolver\SlugResolver;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * v0.2.0 integration — SlugResolver output flows through TemplateEngine end-to-end.
 *
 * Verifies that every taxonomy state from PR 1a/1b/2 maps to a slug that the engine
 * can render without throwing. Test 24 of the plan.
 */
final class SlugResolverIntegrationTest extends TestCase
{
    private TemplateEngine $engine;
    private HeaderViewModel $vm;
    private ArrayAdapter $adapter;

    protected function setUp(): void
    {
        $this->engine = new TemplateEngine();
        $this->vm = new HeaderViewModel(title: 'Test post');
        $this->adapter = new ArrayAdapter();
    }

    /**
     * v0.3.0 — 9 primary-path cases (legacy type_id datasets removed; resolver signature
     * no longer accepts legacyTypeId param).
     *
     * @return array<string, array{0: ?string, 1: ?string, 2: bool, 3: bool}>
     */
    public static function taxonomyStateProvider(): array
    {
        return [
            'article'                              => ['article',   null,           false, false],
            'longform default'                     => ['longform',  'longform',     false, false],
            'longform cover_story'                 => ['longform',  'cover_story',  false, false],
            'longform split'                       => ['longform',  'split',        false, false],
            'longform null-header'                 => ['longform',  null,           false, false],
            'longform+is_photostory (additive)'    => ['longform',  null,           true,  false],
            'longform+is_mini_magazine (additive)' => ['longform',  'cover_story',  false, true],
            'video'                                => ['video',     null,           false, false],
            'emagazine'                            => ['emagazine', null,           false, false],
        ];
    }

    /**
     * SlugResolver output → TemplateEngine::render() works end-to-end.
     *
     * @dataProvider taxonomyStateProvider
     */
    public function test_resolver_to_engine_pipeline_renders(
        ?string $contentKind,
        ?string $headerVariant,
        bool $isPhotostory,
        bool $isMiniMagazine,
    ): void {
        $slug = SlugResolver::resolve($contentKind, $headerVariant, $isPhotostory, $isMiniMagazine);

        $result = $this->engine->render($slug, $this->vm, $this->adapter);

        $this->assertSame($slug, $result->requestedSlug);
        $this->assertSame($slug, $result->renderedSlug);
        // Emagazine intentionally renders empty (no header chrome per manifest comment).
        if ($slug !== 'emagazine') {
            $this->assertNotSame('', trim($result->html), "Slug '{$slug}' must render non-empty HTML");
        }
    }
}
