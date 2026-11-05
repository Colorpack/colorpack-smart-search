<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\Registry\Registry;

/** Reads component options. The API key may come from the server environment (preferred) or the Options form. */
final class Settings
{
    /** set by the menu item's layout (list | card | both_list | both_card); null = use the Options value */
    private ?string $modeOverride = null;

    public function __construct(private Registry $p) {}

    public function forceDisplayMode(?string $mode): void
    {
        $this->modeOverride = in_array($mode, ['list', 'card', 'both_list', 'both_card'], true) ? $mode : null;
    }

    public static function load(): self
    {
        return new self(ComponentHelper::getParams('com_aisearch'));
    }

    public const DEFAULT_SYNONYMS = "";

    /** Saved rules, or the built-in starter list when nothing is saved (an empty field also means "use defaults"; put a line with # to disable). */
    public function synonyms(): string
    {
        $v = trim((string) $this->p->get('synonyms', ''));
        return $v !== '' ? $v : self::DEFAULT_SYNONYMS;
    }
    public function maxResults(): int { return max(20, min(300, (int) $this->p->get('max_results', 100))); }
    /** Search box width in px, centred on the page; 0 = full width. */
    public function searchWidth(): int { return max(0, min(1400, (int) $this->p->get('search_width', 760))); }
    public function searchStyle(): string { return (string) $this->p->get('search_style', 'pretty') === 'theme' ? 'theme' : 'pretty'; }
    public function brandColor(): string { $c = trim((string) $this->p->get('brand_color', '#e5365f')); return preg_match('/^#[0-9a-fA-F]{3,8}$/', $c) ? $c : '#e5365f'; }
    public function showHeader(): bool { return (int) $this->p->get('show_header', 1) === 1; }
    public function headerTitle(): string { return trim((string) $this->p->get('header_title', 'ค้นหา')); }
    public function headerText(): string { return trim((string) $this->p->get('header_text', '')); }
    public function placeholderText(): string { $t = trim((string) $this->p->get('placeholder_text', '')); return $t !== '' ? $t : 'พิมพ์คำที่ต้องการค้นหา'; }
    /** @return string[] suggested searches shown as buttons under the box (one per line) */
    public function examples(): array
    {
        $lines = preg_split('/\R/u', (string) $this->p->get('example_queries', '')) ?: [];
        return array_slice(array_values(array_filter(array_map('trim', $lines), static fn ($l) => $l !== '')), 0, 12);
    }
    /** auto | intro | fulltext | text | flexicontent */
    public function imageSource(): string { $v = (string) $this->p->get('image_source', 'auto'); return in_array($v, ['intro', 'fulltext', 'text', 'flexicontent'], true) ? $v : 'auto'; }
    /** when the chosen source has no image for an article, try the others */
    public function imageFallback(): bool { return (int) $this->p->get('image_fallback', 1) === 1; }
    public function panelTint(): bool { return (int) $this->p->get('panel_tint', 1) === 1; }
    public function headerBadge(): string { return trim((string) $this->p->get('header_badge', '')); }
    /** auto = popular searches from the log (manual list as fallback), manual = only the list, off = nothing */
    public function popularMode(): string { $v = (string) $this->p->get('popular_mode', 'auto'); return in_array($v, ['auto', 'manual', 'off'], true) ? $v : 'auto'; }
    public function popularDays(): int { return max(1, min(365, (int) $this->p->get('popular_days', 30))); }
    public function popularCount(): int { return max(1, min(12, (int) $this->p->get('popular_count', 6))); }
    public function popularMin(): int { return max(1, min(100, (int) $this->p->get('popular_min', 3))); }
    public function equalCards(): bool { return (int) $this->p->get('equal_cards', 1) === 1; }
    /** demo (default: Noto Sans Thai, as the demo page) | template (inherit the site font) | sarabun | prompt | kanit | ibm | custom */
    public function fontMode(): string { $v = (string) $this->p->get('font_mode', 'demo'); return in_array($v, ['template', 'sarabun', 'prompt', 'kanit', 'ibm', 'custom'], true) ? $v : 'demo'; }
    public function fontCustom(): string { return preg_replace('/[^A-Za-z0-9 ,\'"_\-]/', '', (string) $this->p->get('font_custom', '')) ?? ''; }
    public function fontScale(): float { return max(80, min(130, (int) $this->p->get('font_scale', 100))) / 100; }
    /** what the pink pills at the bottom of a card show: category (as the old page), tags, or both */
    public function cardFooter(): string { $v = (string) $this->p->get('card_footer', 'category'); return in_array($v, ['tags', 'both'], true) ? $v : 'category'; }
    public function maxTags(): int { $v = $this->p->get('max_tags'); return ($v === null || $v === '') ? 5 : max(0, min(10, (int) $v)); }
    public function cardMaxTags(): int { $v = $this->p->get('card_max_tags'); return ($v === null || $v === '') ? 3 : max(0, min(10, (int) $v)); }
    /**
     * list | card | both_list | both_card. Older installs stored a default layout plus a separate on/off switch,
     * so those values are translated when the new option has not been saved yet.
     */
    public function displayMode(): string
    {
        if ($this->modeOverride !== null) {
            return $this->modeOverride;   // chosen with "Choose a Layout" on the menu item
        }
        $v = (string) $this->p->get('display_mode', '');
        if (in_array($v, ['list', 'card', 'both_list', 'both_card'], true)) {
            return $v;
        }
        $card = (string) $this->p->get('layout', 'list') === 'card';
        $t = $this->p->get('view_toggle');
        $toggle = ($t === null || $t === '') ? true : (int) $t === 1;
        return $toggle ? ($card ? 'both_card' : 'both_list') : ($card ? 'card' : 'list');
    }
    /** the layout shown until a visitor picks one */
    public function layout(): string { return in_array($this->displayMode(), ['card', 'both_card'], true) ? 'card' : 'list'; }
    /** whether visitors get the List / Cards switch */
    public function viewToggle(): bool { return str_starts_with($this->displayMode(), 'both'); }
    /** Show a thumbnail in the List view. Old saved layouts: list_image = yes, demo / list_text = no. */
    public function listShowImage(): bool
    {
        $v = $this->p->get('list_show_image');
        if ($v !== null && $v !== '') {
            return (int) $v === 1;
        }
        return (string) $this->p->get('layout', '') === 'list_image';
    }
    public function templateMode(): string { $v = (string) $this->p->get('template_mode', 'auto'); return in_array($v, ['auto', 'yootheme', 'bootstrap', 'plain'], true) ? $v : 'auto'; }
    public function columns(): int { return max(2, min(4, (int) $this->p->get('columns', 3))); }
    public function imagePrefix(): string { $v = (string) $this->p->get('image_prefix', 'l_'); return in_array($v, ['l_', 'm_', 's_'], true) ? $v : 'l_'; }
    public function cardRatio(): array { return [max(1, (int) $this->p->get('card_w', 1200)), max(1, (int) $this->p->get('card_h', 630))]; }
    public function listImage(): array { return [max(40, min(600, (int) $this->p->get('list_w', 180))), max(30, min(600, (int) $this->p->get('list_h', 120)))]; }
    public function excerptLength(): int { return max(30, min(200, (int) $this->p->get('excerpt_length', 100))); }
    public function showCategory(): bool { return (int) $this->p->get('show_category', 1) === 1; }
    /** An explicit setting wins; otherwise the date shows only in the Demo-style list (as on the demo page). */
    public function showDate(?string $layout = null): bool
    {
        $v = $this->p->get('show_date');
        if ($v !== null && $v !== '') {
            return (int) $v === 1;
        }
        return ($layout ?? $this->layout()) === 'list';
    }
    public function imageFieldId(): int { return max(0, (int) $this->p->get('image_field_id', 0)); }
    public function placeholder(): string { return trim((string) $this->p->get('placeholder_image', '/images/default-main-item.jpg')); }
    public function showPlaceholder(): bool { return (int) $this->p->get('show_placeholder', 1) === 1; }

    public function perPage(): int { return max(5, min(50, (int) $this->p->get('results_per_page', 20))); }
}
