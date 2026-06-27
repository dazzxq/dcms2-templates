<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Contract;

/**
 * Cầu nối host → template engine. Mỗi host (frontsite zmag/plcs/banquyen,
 * hoặc DCMS2 preview) cung cấp một implementation; template KHÔNG bao giờ
 * gọi thẳng global của host (url()/resizeImageUrl()/settings()/coverImageForPost()),
 * chỉ đi qua interface này. Giữ engine framework-agnostic + không leak state host.
 *
 * Mọi method PHẢI trả về giá trị an toàn để in ra HTML *attribute/text*
 * (đã escape ở tầng template, KHÔNG escape sẵn ở adapter để tránh double-escape).
 *
 * @see \Dazzxq\Dcms2Templates\Model\HeaderViewModel — dữ liệu thô đi kèm
 */
interface HeaderViewAdapter
{
    /** Variant ảnh: kích thước gốc, không resize. */
    public const VARIANT_ORIGINAL = 'original';

    /** Variant ảnh: cover bài (full width, frontsite = resizeImageUrl(src, 0)). */
    public const VARIANT_COVER = 'cover';

    /** Variant ảnh: avatar nhóm chủ đề (frontsite = resizeImageUrl(src, 400)). */
    public const VARIANT_TOPIC = 'topic';

    /**
     * Resolve một path nội bộ thành URL tuyệt đối/relative của host.
     * Vd: urlFor('tac-gia/nguyen-van-a') → '/tac-gia/nguyen-van-a' (frontsite url()).
     */
    public function urlFor(string $path): string;

    /**
     * Resolve nguồn ảnh + variant thành URL hiển thị (CDN/resize tuỳ host).
     * Trả '' khi $source rỗng/null để template tự bỏ qua block ảnh.
     *
     * @param string|null $source  Nguồn ảnh thô từ HeaderViewModel
     * @param string      $variant Một trong các hằng VARIANT_* (host map → resize riêng)
     */
    public function imageUrl(?string $source, string $variant = self::VARIANT_ORIGINAL): string;

    /**
     * Đọc setting của host (vd 'show_author_name'). Engine KHÔNG giả định kiểu.
     */
    public function setting(string $key, mixed $default = null): mixed;

    /**
     * Format giá trị thời gian theo locale host.
     * $format = 'default' → chuỗi hiển thị (vd "06/06/2026 14:30" kiểu vi_VN);
     * $format = 'iso'     → chuỗi machine-readable cho attribute datetime="".
     */
    public function formatDate(mixed $value, string $format = 'default'): string;

    /**
     * true khi đang render trong ngữ cảnh preview (DCMS2 editor) — template có
     * thể chèn marker/bỏ lazy-load. Frontsite production luôn trả false.
     */
    public function isPreviewMode(): bool;
}
