<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Model;

/**
 * Kết quả render. Tách requested vs rendered slug để host biết có fallback hay không
 * mà KHÔNG cần nuốt exception (renderWithFallback trả về cái này thay vì throw).
 */
final class RenderResult
{
    public function __construct(
        public readonly string $requestedSlug,
        public readonly string $renderedSlug,
        public readonly string $html,
        public readonly ?string $fallbackReason = null,
    ) {
        // Invariant: slug-pair và fallbackReason phải nhất quán — tránh trạng thái
        // "fallback nhưng thiếu reason" hoặc "không fallback nhưng có reason".
        $isFallback = $requestedSlug !== $renderedSlug;
        if ($isFallback && $fallbackReason === null) {
            throw new \InvalidArgumentException(
                sprintf('Render fallback (%s → %s) bắt buộc có fallbackReason.', $requestedSlug, $renderedSlug),
            );
        }
        if (!$isFallback && $fallbackReason !== null) {
            throw new \InvalidArgumentException(
                sprintf('Render không fallback (slug "%s") không được set fallbackReason.', $requestedSlug),
            );
        }
    }

    /** true khi slug render ra KHÁC slug được yêu cầu (đã fallback). Tín hiệu canonical. */
    public function isFallback(): bool
    {
        return $this->requestedSlug !== $this->renderedSlug;
    }
}
