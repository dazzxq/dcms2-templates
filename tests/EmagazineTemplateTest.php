<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests;

use Dazzxq\Dcms2Templates\Engine\TemplateEngine;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Tests\Support\ArrayAdapter;
use PHPUnit\Framework\TestCase;

/**
 * Template `emagazine` (type_id=6) — KHÔNG header chrome, render rỗng (content-only).
 */
final class EmagazineTemplateTest extends TestCase
{
    public function testRendersEmptyHeader(): void
    {
        $vm = new HeaderViewModel(
            title: 'Bài eMagazine',
            categoryName: 'Chuyên đề',
            categorySlug: 'chuyen-de',
            sapo: 'Có sapo nhưng không render',
        );

        $result = (new TemplateEngine())->render('emagazine', $vm, new ArrayAdapter());

        // Render thành công, KHÔNG fallback, nhưng html rỗng (không chrome).
        $this->assertFalse($result->isFallback());
        $this->assertSame('emagazine', $result->renderedSlug);
        $this->assertSame('', trim($result->html));
        $this->assertStringNotContainsString('td-post-header', $result->html);
    }
}
