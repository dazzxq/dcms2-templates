<?php
/**
 * Template view: emagazine (type_id=6, "Bài eMagazine") — KHÔNG có post-header chrome.
 *
 * Quyết định cross-side LOCKED: eMagazine = biên tập viên tự thiết kế header NGAY TRONG
 * content body (qua content.css), engine render header RỖNG. Frontside post.php sẽ bỏ qua
 * chrome header, chỉ render content body của bài.
 *
 * Cố ý KHÔNG output gì → RenderResult.html = ''. Đây là hành vi đúng, không phải bug.
 *
 * @var \Dazzxq\Dcms2Templates\Model\HeaderViewModel  $vm
 * @var \Dazzxq\Dcms2Templates\Contract\HeaderViewAdapter $adapter
 */

declare(strict_types=1);

// (no output) — eMagazine không có header chrome.
