<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Exception;

/**
 * Ném khi render() KHÔNG thể dựng template yêu cầu (slug lạ, min-version cao hơn
 * engine, hoặc lỗi render). renderWithFallback() bắt cái này → fallback + ghi reason.
 */
final class TemplateFallbackException extends \RuntimeException
{
    public function __construct(
        public readonly string $requestedSlug,
        public readonly string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            sprintf('Template "%s" could not be rendered: %s', $requestedSlug, $reason),
            0,
            $previous,
        );
    }
}
