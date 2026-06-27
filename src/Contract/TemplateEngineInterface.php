<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Contract;

use Dazzxq\Dcms2Templates\Exception\TemplateFallbackException;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Model\RenderResult;

/**
 * API engine render template. Cài đặt cụ thể (manifest loader + PHP template
 * renderer) ở \Dazzxq\Dcms2Templates\Engine — F2. DCMS2 + frontsite code theo
 * interface này, version-gate qua getMinEngineVersion/templatesCompatibleWith.
 */
interface TemplateEngineInterface
{
    /**
     * Render template theo slug. Throw nếu slug lạ / min-version cao hơn engine / lỗi render.
     *
     * $adapter là biên framework-agnostic: engine + template CHỈ chạm host qua nó (url/image/
     * setting/date/preview). Truyền per-call (KHÔNG bind vào constructor/global) để engine
     * stateless, 1 instance render được cho nhiều host-context (production vs preview).
     *
     * @throws TemplateFallbackException
     */
    public function render(string $slug, HeaderViewModel $vm, HeaderViewAdapter $adapter): RenderResult;

    /**
     * Render có fallback mềm về template mặc định của content_kind.
     * KHÔNG throw cho slug lạ/incompatible — fallback + ghi fallbackReason.
     *
     * @param string $contentKind 'article' | 'photostory' | 'video' (quyết default template)
     */
    public function renderWithFallback(
        string $slug,
        string $contentKind,
        HeaderViewModel $vm,
        HeaderViewAdapter $adapter,
    ): RenderResult;

    /**
     * Các slug có minEngineVersion <= phiên bản engine frontsite đưa vào.
     * Dùng cho deploy-gate: DCMS2 chỉ cho chọn template frontsite đã đủ version render.
     *
     * @return list<string>
     */
    public function templatesCompatibleWith(string $frontsiteVersion): array;

    /**
     * Toàn bộ slug template đã đăng ký trong manifest.
     *
     * @return list<string>
     */
    public function listTemplates(): array;

    /** Phiên bản engine tối thiểu cần để render slug (semver). */
    public function getMinEngineVersion(string $slug): string;

    /** Phiên bản engine hiện tại của package (semver, khớp composer.json). */
    public function getCurrentEngineVersion(): string;

    /**
     * Class CSS host PHẢI wrap quanh header để stylesheet scope đúng layout
     * (slug KHÔNG 1:1 với class: cover→td-post--cover-story). null nếu template
     * không cần wrapper (vd emagazine no-header). Throw nếu slug lạ.
     *
     * @throws TemplateFallbackException
     */
    public function getWrapperClass(string $slug): ?string;

    /**
     * URL CDN TUYỆT ĐỐI của các file CSS cho slug (host nhúng vào <head>).
     *
     * @return list<string>
     */
    public function getTemplateCss(string $slug): array;
}
