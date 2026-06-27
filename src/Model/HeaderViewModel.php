<?php

declare(strict_types=1);

namespace Dazzxq\Dcms2Templates\Model;

/**
 * Dữ liệu THÔ của post-header, độc lập host. DCMS2 (preview) và frontsite
 * (production) cùng dựng VM này rồi đưa cho engine + adapter render.
 *
 * Block vocabulary (rút từ 5 component prod standard/longform/cover/split/photostory):
 *   kicker(category) · topic banner · headline · byline(author+date) · sapo · cover.
 *
 * Quy ước: trường mang giá trị THÔ (chưa escape, chưa resize, chưa format).
 *   - url   → template gọi $adapter->urlFor(slug)
 *   - ảnh   → template gọi $adapter->imageUrl(source, variant)
 *   - ngày  → template gọi $adapter->formatDate(publishedAt, ...)
 *   - setting('show_author_name') → $adapter->setting(...)
 *
 * $extra = escape hatch cho field riêng từng template (video metadata, gallery
 * photostory...) — thêm dần khi extract template, KHÔNG phá chữ ký constructor.
 */
final class HeaderViewModel
{
    /**
     * @param list<array{name?: string, slug?: string}> $authors Danh sách tác giả (investigative/multi-author)
     * @param array<string, mixed>                      $extra   Field mở rộng theo template
     */
    public function __construct(
        public readonly string $title,
        public readonly ?string $categoryName = null,
        public readonly ?string $categorySlug = null,
        public readonly ?string $topicName = null,
        public readonly ?string $topicSlug = null,
        public readonly ?string $topicAvatarSource = null,
        public readonly ?string $authorName = null,
        public readonly ?string $authorSlug = null,
        public readonly array $authors = [],
        public readonly mixed $publishedAt = null,
        public readonly ?string $sapo = null,
        public readonly ?string $siteName = null,
        public readonly ?string $coverImageSource = null,
        public readonly array $extra = [],
    ) {
    }

    /**
     * Đọc field mở rộng an toàn. Phân biệt key VẮNG (trả $default) vs giá trị `null`
     * lưu CỐ Ý (trả về null) — quan trọng vì $extra là escape hatch metadata optional.
     */
    public function extra(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->extra) ? $this->extra[$key] : $default;
    }
}
