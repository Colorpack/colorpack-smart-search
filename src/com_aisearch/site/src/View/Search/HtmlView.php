<?php
namespace Colorpack\Component\AiSearch\Site\View\Search;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\SearchService;
use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

class HtmlView extends BaseHtmlView
{
    public string $q = '';
    public array $items = [];
    public int $total = 0;
    public int $start = 0;
    public int $perPage = 20;
    public float $ms = 0.0;
    public bool $indexed = true;
    public string $mode = 'keyword';
    public ?Settings $settings = null;
    public bool $yoo = false;
    /** Bootstrap 5 markup (Joomla's own Cassiopeia template and its children) */
    public bool $bs = false;
    public string $layoutName = 'list';
    public bool $canToggle = false;
    public string $viewParam = '';

    /** A custom layout (e.g. "mylayout.php") may reuse the standard parts (default_list.php, default_card.php, default_pagination.php) without copying them. */
    public function loadTemplate($tpl = null)
    {
        try {
            return parent::loadTemplate($tpl);
        } catch (\Throwable $e) {
            $layout = $this->getLayout();
            if ($tpl === null || $layout === 'default') {
                throw $e;
            }
            $this->setLayout('default');
            try {
                return parent::loadTemplate($tpl);
            } finally {
                $this->setLayout($layout);
            }
        }
    }

    public function display($tpl = null): void
    {
        $app = Factory::getApplication();
        $this->settings = $s = Settings::load();
        // "Choose a Layout" on the menu item: the shipped layouts only preset how results are shown;
        // any other layout (a template override or your own) is rendered as its own page file.
        $chosen = $this->getLayout();
        if (in_array($chosen, ['list', 'card', 'both_list', 'both_card'], true)) {
            $s->forceDisplayMode($chosen);
            $this->setLayout('default');
        }
        $this->q = trim((string) $app->getInput()->getString('q', ''));
        $this->perPage = $s->perPage();
        // layout: visitor's choice (?v=card|list, or the remembered cookie) or the default from Options
        $default = $s->layout();
        $this->canToggle = $s->viewToggle();
        $v = (string) $app->getInput()->getCmd('v', '');
        $this->viewParam = in_array($v, ['card', 'list'], true) ? $v : '';
        if ($v === '' && $this->canToggle) {
            $v = (string) $app->getInput()->cookie->getCmd('aisearch_view', '');
        }
        $this->layoutName = ($this->canToggle && in_array($v, ['card', 'list'], true)) ? $v : $default;
        $siteTpl = $app->getTemplate(true);
        $names = '';
        foreach (['template', 'parent'] as $k) {
            $names .= ' ' . (is_string($siteTpl->$k ?? null) ? strtolower($siteTpl->$k) : '');
        }
        $mode = $s->templateMode();
        $this->yoo = $mode === 'yootheme' || ($mode === 'auto' && str_contains($names, 'yootheme'));
        $this->bs = !$this->yoo && ($mode === 'bootstrap' || ($mode === 'auto' && str_contains($names, 'cassiopeia')));
        if (mb_strlen($this->q) >= 2) {
            $svc = new SearchService(Factory::getContainer()->get(DatabaseInterface::class), $s);
            $r = $svc->search(mb_substr($this->q, 0, 200), $this->perPage, $this->start, $s->maxResults());
            if ($this->start >= $r['total'] && $r['total'] > 0) {   // page past the end -> last page
                $this->start = (int) (floor(($r['total'] - 1) / $this->perPage) * $this->perPage);
                $r = $svc->search(mb_substr($this->q, 0, 200), $this->perPage, $this->start, $s->maxResults());
            }
            $this->items = $r['items'];
            $this->total = $r['total'];
            $this->ms = $r['ms'];
            $this->indexed = $r['indexed'];
            $this->mode = $r['mode'];
            if ($r['indexed'] && $this->start === 0) {
                $svc->log($this->q, $this->total, $r['ms'], $r['mode']);
            }
        }
        $font = $this->fontInfo();
        if ($font['url'] !== '') {
            $app->getDocument()->addStyleSheet($font['url']);
        }
        $app->getDocument()->setTitle($this->q !== '' ? $this->q . ' - ค้นหา' : 'ค้นหา');
        parent::display($tpl);
    }

    /** @return array{family:string,url:string,base:string,lh:string} CSS pieces for the chosen font */
    public function fontInfo(): array
    {
        $s = $this->settings;
        $scale = $s->fontScale();
        $gf = static fn (string $q) => 'https://fonts.googleapis.com/css2?family=' . $q . ':wght@400;700&display=swap';
        $mode = $s->fontMode();
        $map = [
            'demo' => ["'Noto Sans Thai', sans-serif", $gf('Noto+Sans+Thai')],
            'sarabun' => ["'Sarabun', sans-serif", $gf('Sarabun')],
            'prompt' => ["'Prompt', sans-serif", $gf('Prompt')],
            'kanit' => ["'Kanit', sans-serif", $gf('Kanit')],
            'ibm' => ["'IBM Plex Sans Thai', sans-serif", $gf('IBM+Plex+Sans+Thai')],
        ];
        if ($mode === 'template') {
            return ['family' => '', 'url' => '', 'base' => 'calc(1rem * ' . $scale . ')', 'lh' => 'inherit'];
        }
        if ($mode === 'custom') {
            $fam = $s->fontCustom();
            return ['family' => $fam, 'url' => '', 'base' => 'calc(16px * ' . $scale . ')', 'lh' => '1.65'];
        }
        return ['family' => $map[$mode][0], 'url' => $map[$mode][1], 'base' => 'calc(16px * ' . $scale . ')', 'lh' => '1.65'];
    }

    /** @return string[] an article's tags (as stored when the index was built) */
    public function tagList(object $d): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $d->tags)), static fn ($t) => $t !== ''));
    }

    /** @return string[] labels for the bottom of a card, per the Options */
    public function footerItems(object $d): array
    {
        $s = $this->settings;
        $cat = $s->showCategory() ? (string) $d->category : '';
        if ($s->showDate($this->layoutName)) {
            $cat = trim($cat . ($cat !== '' ? ' · ' : '') . substr((string) $d->created, 0, 10));
        }
        $mode = $s->cardFooter();
        $items = [];
        if ($mode !== 'tags' && $cat !== '') {
            $items[] = $cat;
        }
        if ($mode !== 'category') {
            foreach (array_slice($this->tagList($d), 0, $s->cardMaxTags()) as $t) {
                $items[] = $t;
            }
        }
        return array_values(array_unique($items));
    }

    public function link(object $d): string
    {
        return Route::_(RouteHelper::getArticleRoute((int) $d->id, (int) $d->catid));
    }

    /** Image URL for a result, or '' when there is none and placeholders are off. */
    public function imageUrl(object $d): string
    {
        $s = $this->settings;
        $path = '';
        if ((string) $d->image !== '') {
            if ($d->image_kind === 'fc') {
                $dir = dirname($d->image);
                $path = 'images/stories/flexicontent/' . $dir . '/' . $s->imagePrefix() . basename($d->image);
            } else {
                $path = (string) $d->image;
            }
        } elseif ($s->showPlaceholder() && $s->placeholder() !== '') {
            $path = $s->placeholder();
        }
        if ($path === '') {
            return '';
        }
        if (preg_match('~^(https?:)?//~i', $path)) {
            return $path;
        }
        $clean = implode('/', array_map('rawurlencode', explode('/', rawurldecode(ltrim($path, '/')))));
        return rtrim(Uri::root(true), '/') . '/' . $clean;
    }

    public function excerpt(object $d): string
    {
        $n = $this->settings->excerptLength();
        $t = trim(preg_replace('/\s+/u', ' ', (string) $d->snippet));
        return mb_strlen($t) > $n ? rtrim(mb_substr($t, 0, $n)) . '…' : $t;
    }

    /**
     * Suggestion buttons under the search box.
     * @return array{label:string,items:string[]}
     */
    public function suggestions(): array
    {
        return \Colorpack\Component\AiSearch\Administrator\Lib\Popular::forSettings(Factory::getContainer()->get(DatabaseInterface::class), $this->settings);
    }

    /** Link for the List / Cards switch (resets to page 1). */
    public function viewUrl(string $v): string
    {
        $base = Uri::getInstance()->toString(['path']);
        $params = ['q' => $this->q, 'v' => $v];
        if (($_GET['option'] ?? '') === 'com_aisearch' && !str_contains($base, '/component/') && !preg_match('~\.html$|/[^/]+$~', $base)) {
            $params = ['option' => 'com_aisearch', 'view' => 'search'] + $params;
        }
        return htmlspecialchars($base . '?' . http_build_query($params), ENT_QUOTES, 'UTF-8');
    }

    public function pageUrl(int $start): string
    {
        $params = ['q' => $this->q];
        if ($this->viewParam !== '') {
            $params['v'] = $this->viewParam;
        }
        if ($start > 0) {
            $params['start'] = $start;
        }
        $base = Uri::getInstance()->toString(['path']);
        $on = $_GET['option'] ?? '';
        if ($on === 'com_aisearch' && !str_contains($base, '/component/') && !preg_match('~\.html$|/[^/]+$~', $base)) {
            $params = ['option' => 'com_aisearch', 'view' => 'search'] + $params;
        }
        return htmlspecialchars($base . '?' . http_build_query($params), ENT_QUOTES, 'UTF-8');
    }

    /** @return array<int,int|string> page numbers with '…' gaps, 1-based */
    public function pageWindow(int $pages, int $current): array
    {
        $out = [];
        foreach (range(1, $pages) as $p) {
            if ($p === 1 || $p === $pages || abs($p - $current) <= 2) {
                $out[] = $p;
            } elseif (end($out) !== '…') {
                $out[] = '…';
            }
        }
        return $out;
    }
}
