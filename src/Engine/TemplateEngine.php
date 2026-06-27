<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Engine;

use Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter;
use Dazzxq\Dcms2Templates\Contract\TemplateEngineInterface;
use Dazzxq\Dcms2Templates\Exception\TemplateFallbackException;
use Dazzxq\Dcms2Templates\Model\HeaderViewModel;
use Dazzxq\Dcms2Templates\Model\RenderResult;

/**
 * Engine render template, đọc manifest + thực thi view PHP trong scope cô lập.
 * Stateless re: host — adapter truyền per-call. Chỉ giữ config package (đường dẫn
 * templates, CSS base) ở constructor, KHÔNG giữ state request.
 *
 * @phpstan-type TemplateDef array{minEngineVersion: string, contentKind: string, view: string, css: list<string>, wrapperClass: string|null}
 */
final class TemplateEngine implements TemplateEngineInterface
{
    private string $templatesDir;
    private string $cssBaseUrl;
    private string $engineVersion;

    /** @var array<string, string> content_kind => default slug */
    private array $contentKindDefaults;

    /** @var array<string, TemplateDef> */
    private array $templates;

    /**
     * @param string|null $templatesDir Thư mục templates (mặc định = templates/ trong package)
     * @param string      $cssBaseUrl   Base URL tuyệt đối để resolve file CSS (F10 quyết chiến lược; '' = relative)
     */
    public function __construct(?string $templatesDir = null, string $cssBaseUrl = '')
    {
        $this->templatesDir = rtrim($templatesDir ?? \dirname(__DIR__, 2) . '/templates', '/');
        $this->cssBaseUrl = rtrim($cssBaseUrl, '/');

        $manifest = $this->loadManifest($this->templatesDir . '/manifest.php');
        $this->engineVersion = $manifest['engineVersion'];
        $this->contentKindDefaults = $manifest['contentKindDefaults'];
        $this->templates = $manifest['templates'];
    }

    public function render(string $slug, HeaderViewModel $vm, HeaderViewAdapter $adapter): RenderResult
    {
        $def = $this->templates[$slug] ?? null;
        if ($def === null) {
            throw new TemplateFallbackException($slug, 'unknown_slug');
        }
        if (version_compare($def['minEngineVersion'], $this->engineVersion, '>')) {
            throw new TemplateFallbackException($slug, 'min_version_too_high');
        }

        $viewFile = $this->templatesDir . '/' . $def['view'];
        if (!is_file($viewFile)) {
            throw new TemplateFallbackException($slug, 'view_missing');
        }

        $html = $this->renderView($viewFile, $vm, $adapter);

        return new RenderResult($slug, $slug, $html);
    }

    public function renderWithFallback(
        string $slug,
        string $contentKind,
        HeaderViewModel $vm,
        HeaderViewAdapter $adapter,
    ): RenderResult {
        try {
            return $this->render($slug, $vm, $adapter);
        } catch (\Throwable $e) {
            $reason = $e instanceof TemplateFallbackException ? $e->reason : 'render_exception';
            // Kind lạ → lùi về 'article' (manifest validate đảm bảo 'article' tồn tại + slug hợp lệ).
            $defaultSlug = $this->contentKindDefaults[$contentKind] ?? $this->contentKindDefaults['article'];

            // Default chính là slug vừa fail → không có đường lùi an toàn, ném tiếp.
            if ($defaultSlug === $slug) {
                throw $e;
            }

            // Render default; nếu default cũng lỗi = lỗi đóng gói → để propagate (lộ ở test/Sentry).
            $fallback = $this->render($defaultSlug, $vm, $adapter);

            return new RenderResult($slug, $defaultSlug, $fallback->html, $reason);
        }
    }

    public function templatesCompatibleWith(string $frontsiteVersion): array
    {
        $compatible = [];
        foreach ($this->templates as $slug => $def) {
            if (version_compare($def['minEngineVersion'], $frontsiteVersion, '<=')) {
                $compatible[] = $slug;
            }
        }

        return $compatible;
    }

    public function listTemplates(): array
    {
        return array_keys($this->templates);
    }

    public function getMinEngineVersion(string $slug): string
    {
        $def = $this->templates[$slug] ?? null;
        if ($def === null) {
            throw new TemplateFallbackException($slug, 'unknown_slug');
        }

        return $def['minEngineVersion'];
    }

    public function getCurrentEngineVersion(): string
    {
        return $this->engineVersion;
    }

    public function getWrapperClass(string $slug): ?string
    {
        $def = $this->templates[$slug] ?? null;
        if ($def === null) {
            throw new TemplateFallbackException($slug, 'unknown_slug');
        }

        return $def['wrapperClass'];
    }

    public function getTemplateCss(string $slug): array
    {
        $def = $this->templates[$slug] ?? null;
        if ($def === null) {
            throw new TemplateFallbackException($slug, 'unknown_slug');
        }

        $base = $this->cssBaseUrl === '' ? '' : $this->cssBaseUrl . '/';

        return array_map(
            static fn (string $file): string => $base . ltrim($file, '/'),
            $def['css'],
        );
    }

    /**
     * Thực thi view trong closure static (KHÔNG $this) — view chỉ thấy $vm + $adapter,
     * không chạm được internal engine. Output buffering thu HTML.
     */
    private function renderView(string $viewFile, HeaderViewModel $vm, HeaderViewAdapter $adapter): string
    {
        $renderer = static function (string $__viewFile, HeaderViewModel $vm, HeaderViewAdapter $adapter): string {
            $__level = ob_get_level();
            ob_start();
            try {
                include $__viewFile;
            } catch (\Throwable $e) {
                // Dọn mọi buffer (kể cả buffer template lỡ mở chồng) về đúng mức ban đầu.
                while (ob_get_level() > $__level) {
                    ob_end_clean();
                }
                throw $e;
            }

            // Template balance buffer của nó; nếu lỡ để hở buffer lồng → bỏ, chỉ giữ buffer engine.
            while (ob_get_level() > $__level + 1) {
                ob_end_clean();
            }

            return (string) ob_get_clean();
        };

        return $renderer($viewFile, $vm, $adapter);
    }

    /**
     * Load + validate manifest. Mọi sai cấu trúc → RuntimeException NGAY lúc construct
     * (fail-fast, lỗi đóng gói lộ rõ thay vì warning/TypeError/unknown_slug lúc runtime).
     *
     * @return array{engineVersion: string, contentKindDefaults: array<string, string>, templates: array<string, TemplateDef>}
     */
    private function loadManifest(string $manifestFile): array
    {
        if (!is_file($manifestFile)) {
            throw new \RuntimeException(sprintf('Manifest template không tồn tại: %s', $manifestFile));
        }

        /** @var mixed $raw */
        $raw = require $manifestFile;
        if (!is_array($raw)) {
            throw $this->manifestError($manifestFile, 'không phải array');
        }

        $engineVersion = $raw['engineVersion'] ?? null;
        if (!is_string($engineVersion) || $engineVersion === '') {
            throw $this->manifestError($manifestFile, "'engineVersion' phải là string không rỗng");
        }

        $templatesRaw = $raw['templates'] ?? null;
        if (!is_array($templatesRaw) || $templatesRaw === []) {
            throw $this->manifestError($manifestFile, "'templates' phải là array không rỗng");
        }

        $templates = [];
        foreach ($templatesRaw as $slug => $def) {
            if (!is_string($slug) || $slug === '' || !is_array($def)) {
                throw $this->manifestError($manifestFile, sprintf("template entry '%s' sai", (string) $slug));
            }

            $min = $def['minEngineVersion'] ?? null;
            $kind = $def['contentKind'] ?? null;
            $view = $def['view'] ?? null;
            $cssRaw = $def['css'] ?? null;

            if (!is_string($min) || $min === ''
                || !is_string($kind) || $kind === ''
                || !is_string($view) || $view === ''
                || !is_array($cssRaw)
            ) {
                throw $this->manifestError($manifestFile, sprintf("template '%s' thiếu/sai field (minEngineVersion/contentKind/view/css)", $slug));
            }

            // View path PHẢI tương đối, không traversal (chặn đọc file ngoài thư mục templates).
            if (str_starts_with($view, '/') || str_contains($view, '..')) {
                throw $this->manifestError($manifestFile, sprintf("template '%s' view path không an toàn: %s", $slug, $view));
            }

            $css = [];
            foreach ($cssRaw as $cssFile) {
                if (!is_string($cssFile) || $cssFile === '') {
                    throw $this->manifestError($manifestFile, sprintf("template '%s' có css entry không phải string", $slug));
                }
                $css[] = $cssFile;
            }

            // wrapperClass: string không rỗng HOẶC null (template no-header). Mặc định null nếu vắng.
            $wrapperClass = $def['wrapperClass'] ?? null;
            if ($wrapperClass !== null && (!is_string($wrapperClass) || $wrapperClass === '')) {
                throw $this->manifestError($manifestFile, sprintf("template '%s' wrapperClass phải là string không rỗng hoặc null", $slug));
            }

            $templates[$slug] = [
                'minEngineVersion' => $min,
                'contentKind' => $kind,
                'view' => $view,
                'css' => $css,
                'wrapperClass' => $wrapperClass,
            ];
        }

        $defaultsRaw = $raw['contentKindDefaults'] ?? null;
        if (!is_array($defaultsRaw)) {
            throw $this->manifestError($manifestFile, "'contentKindDefaults' phải là array");
        }

        $defaults = [];
        foreach ($defaultsRaw as $kind => $slug) {
            if (!is_string($kind) || $kind === '' || !is_string($slug)) {
                throw $this->manifestError($manifestFile, 'contentKindDefaults có entry sai kiểu');
            }
            if (!isset($templates[$slug])) {
                throw $this->manifestError($manifestFile, sprintf("contentKindDefaults['%s'] => '%s' không có trong templates", $kind, $slug));
            }
            $defaults[$kind] = $slug;
        }

        // 'article' bắt buộc — base fallback khi gặp content_kind lạ (renderWithFallback dựa vào).
        if (!isset($defaults['article'])) {
            throw $this->manifestError($manifestFile, "contentKindDefaults phải có 'article' (base fallback)");
        }

        return [
            'engineVersion' => $engineVersion,
            'contentKindDefaults' => $defaults,
            'templates' => $templates,
        ];
    }

    private function manifestError(string $manifestFile, string $detail): \RuntimeException
    {
        return new \RuntimeException(sprintf('Manifest template sai (%s): %s', $manifestFile, $detail));
    }
}
