# dcms2-templates

Engine render **post-header template** dùng chung — framework-agnostic PHP — giữa **DCMS2** (CMS, Laravel) và các **frontsite** (zmag.vn / phapluatchinhsach.vn / banquyen.gov.vn, vanilla PHP MVC).

> Direction-E: template là **một nguồn sự thật** sống độc lập trong repo này. DCMS2 lưu slug/composition + tự preview bằng chính engine; frontsite render production cũng bằng engine này. Không duplicate markup → không drift.

## Cài đặt

```bash
composer require dazzxq/dcms2-templates
```

PHP `>= 8.2`. Không phụ thuộc framework (không Laravel, không global của frontsite).

## Kiến trúc

```
Host (DCMS2 preview | frontsite production)
  │  dựng HeaderViewModel (data thô) + cung cấp HeaderViewAdapter (url/image/setting/date)
  ▼
TemplateEngine.render(slug, vm, adapter) ──► RenderResult { html, renderedSlug, fallbackReason }
                  renderWithFallback(slug, contentKind, vm, adapter)  // không throw, fallback mềm
```

`adapter` truyền **per-call** (engine stateless, không bind host) — đó là biên framework-agnostic.

- **`Contract\HeaderViewAdapter`** — host implement: `urlFor` · `imageUrl` · `setting` · `formatDate` · `isPreviewMode`. Template KHÔNG gọi global host, chỉ qua adapter.
- **`Contract\TemplateEngineInterface`** — `render` · `renderWithFallback` · `templatesCompatibleWith` · `listTemplates` · `getMinEngineVersion` · `getCurrentEngineVersion` · `getTemplateCss`.
- **`Model\HeaderViewModel`** — data thô post-header (title/category/topic/author/sapo/cover + `extra` mở rộng).
- **`Model\RenderResult`** — kết quả render + cờ fallback.

## Template (v0.1.0 — đang build)

7 slug: `standard` · `longform-default` · `cover` · `split` · `photostory` · `video` · `emagazine`.

Map type_id (DCMS2) → template:

| type_id | nhãn DCMS2 | template slug |
|---------|------------|---------------|
| 1 | Bài thường (M) | `standard` |
| 2 | Bài L1 | `longform-default` |
| 3 | Bài L2 | `cover` |
| 4 | Bài L3 | `split` |
| 6 | Bài eMagazine | `emagazine` (no-header) |
| 7 | Photostory | `photostory` |

Bài cũ L1/L2/L3 KHÔNG backfill `header_variant` — để NULL, frontsite map theo type_id = render y hệt hiện tại (zero-breakage).

## Versioning

Semver. `extra.dcms2_min_frontsite_version` trong `composer.json` = version frontsite tối thiểu để DCMS2 cho phép bật template mới (deploy-gate, không probe HTTP).

## Trạng thái

🚧 Đang build (Direction-E). Tracker: `zmag.vn/.planning/quick/post-template-architecture/BUILD-TRACKER.md`.
