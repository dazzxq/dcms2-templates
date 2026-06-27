<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Tests\Support;

use Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter;

/**
 * Adapter giả định cho test — hành vi deterministic để assert chính xác.
 * urlFor → path '/...'; imageUrl(topic) → src?w=400; formatDate(iso) → raw, (default) → displayDate.
 */
final class ArrayAdapter implements HeaderViewAdapter
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(
        private array $settings = ['show_author_name' => true],
        private bool $previewMode = false,
        private string $displayDate = '06/06/2026 14:30',
    ) {
    }

    public function urlFor(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function imageUrl(?string $source, string $variant = self::VARIANT_ORIGINAL): string
    {
        if ($source === null || $source === '') {
            return '';
        }

        return match ($variant) {
            self::VARIANT_TOPIC => $source . '?w=400',
            default => $source,
        };
    }

    public function setting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    public function formatDate(mixed $value, string $format = 'default'): string
    {
        return $format === 'iso' ? (string) $value : $this->displayDate;
    }

    public function isPreviewMode(): bool
    {
        return $this->previewMode;
    }
}
