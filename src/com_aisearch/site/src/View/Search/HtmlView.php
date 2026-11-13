<?php
namespace Colorpack\Component\AiSearch\Site\View\Search;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Guard;
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
    /** administrators only: ?explain=1 shows why each result is where it is */
    public int $logId = 0;
    /** true when this visitor searched faster than the limit: no search was run */
    public bool $limited = false;
    public array $explain = [];
    public array $ranks = ['bm25' => [], 'ai' => []];
    /** administrators only: what the search did with the typed text (?explain=1) */
    public array $diag = [];
    public int $hidden = 0;
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
        // which page: ?start=<number of results to skip> (Joomla's limitstart is accepted too); always a whole number of pages
        $skip = (int) $app->getInput()->getInt('start', 0) ?: (int) $app->getInput()->getInt('limitstart', 0);
        $this->start = max(0, (int) (floor($skip / max(1, $this->perPage)) * $this->perPage));
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
        if (mb_strlen($this->q) >= 2 && (new Guard(Factory::getContainer()->get(DatabaseInterface::class), $s))->overSearchRate()) {
            $this->limited = true;
            $app->setHeader('Retry-After', '30', true);
            $app->setHeader('status', '429', true);
        } elseif (mb_strlen($this->q) >= 2) {
            $svc = new SearchService(Factory::getContainer()->get(DatabaseInterface::class), $s);
            $wantExplain = $app->getInput()->getInt('explain', 0) === 1 && $app->getIdentity() && $app->getIdentity()->authorise('core.manage', 'com_aisearch');
            $r = $svc->search(mb_substr($this->q, 0, 200), $this->perPage, $this->start, $s->maxResults(), true, null, $wantExplain);
            if ($this->start >= $r['total'] && $r['total'] > 0) {   // page past the end -> last page
                $this->start = (int) (floor(($r['total'] - 1) / $this->perPage) * $this->perPage);
                $r = $svc->search(mb_substr($this->q, 0, 200), $this->perPage, $this->start, $s->maxResults(), true, null, $wantExplain);
            }
            $this->explain = $r['explain'] ?? [];
            $this->ranks = $r['ranks'] ?? $this->ranks;
            $this->diag = $r['diag'] ?? [];
            $this->hidden = (int) ($r['hidden'] ?? 0);
            $this->items = $r['items'];
            $this->total = $r['total'];
            $this->ms = $r['ms'];
            $this->indexed = $r['indexed'];
            $this->mode = $r['mode'];
            if ($r['indexed'] && $this->start === 0) {
                $this->logId = $svc->log($this->q, $this->total, $r['ms'], $r['mode']);
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

    /** Administrators with ?explain=1: one line saying where this result stands and why. */
    /** Why the search put this article here, in Thai (administrators only, ?explain=1). */
    public function explainHtml(object $d): string
    {
        if (!$this->explain && !$this->ranks['bm25'] && !$this->ranks['ai']) {
            return '';
        }
        $id = (int) $d->id;
        $e = $this->explain[$id] ?? null;
        $tiers = [
            1 => 'ระดับ 1 — ตรงที่สุด',
            2 => 'ระดับ 2 — ตรงรองลงมา',
            3 => 'ระดับ 3 — เนื้อหาเกี่ยวข้อง',
            4 => 'ระดับ 4 — แค่เอ่ยถึง',
            9 => 'ไม่ตรงกับคำค้น',
        ];
        $why = [
            'title_close' => 'คำค้นทุกคำอยู่ในชื่อเรื่องและอยู่ใกล้กัน',
            'title_far' => 'คำค้นอยู่ในชื่อเรื่อง แต่อยู่ห่างกันคนละที่ จึงอาจไม่ใช่เรื่องเดียวกัน',
            'meta' => 'คำค้นครบในชื่อเรื่องรวมกับแท็ก/หมวด/คีย์เวิร์ด/คำอธิบาย',
            'body_topical' => 'คำค้นครบ และเนื้อหาพูดถึงซ้ำหรือพูดตั้งแต่ต้นเรื่อง',
            'body_mention' => 'คำค้นครบ แต่เนื้อหาเอ่ยถึงครั้งเดียวและอยู่ท้าย ๆ',
            'most_words' => 'มีคำค้นส่วนใหญ่ (คำถามยาว จึงไม่บังคับให้ครบ)',
            'some_words' => 'มีคำค้นบางส่วนเท่านั้น',
            'missing' => 'ไม่มีคำค้นในบทความ',
            'phrase_title' => 'เจอตัวเลขกับหน่วยตรงกันในชื่อเรื่อง',
            'phrase_title_partial' => 'เจอตัวเลขกับหน่วยในชื่อเรื่อง แต่คำอื่นของคำค้นขาด',
            'phrase_meta' => 'เจอตัวเลขกับหน่วยในแท็ก/หมวด',
            'phrase_meta_partial' => 'เจอตัวเลขกับหน่วยในแท็ก/หมวด แต่คำอื่นขาด',
            'phrase_body' => 'เนื้อหาพูดถึงตัวเลขกับหน่วยนั้นเป็นเรื่องเป็นราว',
            'phrase_body_mention' => 'เนื้อหาเอ่ยถึงตัวเลขกับหน่วยนั้นผ่าน ๆ',
            'phrase_missing' => 'ไม่เจอตัวเลขกับหน่วยที่ค้น',
            'short_title' => 'ค้นคำเดียว: ชื่อเรื่องมีคำนั้น',
            'short_syn' => 'ค้นคำเดียว: ชื่อเรื่องมีคำพ้องของคำนั้น',
            'no_text' => 'ยังไม่มีข้อความของบทความนี้ในดัชนี (ให้กดอัปเดตดัชนี)',
        ];
        $fieldNames = ['title' => 'ชื่อเรื่อง', 'tags' => 'แท็ก/หมวด', 'keys' => 'คีย์เวิร์ด', 'desc' => 'คำอธิบาย', 'intro' => 'ย่อหน้าต้น'];
        $close = [0 => 'คำอยู่ติดกันในชื่อเรื่อง', 1 => 'คำอยู่ในประโยคเดียวกันของชื่อเรื่อง', 2 => 'คำอยู่คนละปลายของชื่อเรื่อง'];
        $esc = static fn ($x) => htmlspecialchars((string) $x, ENT_QUOTES, 'UTF-8');
        $lines = [];
        if ($e) {
            $head = $tiers[(int) $e['tier']] ?? ('ระดับ ' . (int) $e['tier']);
            $lines[] = '<strong>' . $esc($head) . '</strong> — ' . $esc($why[$e['why']] ?? $e['why']);
            if (isset($e['fields'])) {
                $names = array_map(static fn ($f) => $fieldNames[$f] ?? $f, (array) ($e['in'] ?? []));
                $lines[] = 'เจอคำค้นครบใน ' . (int) $e['fields'] . ' จาก 5 ช่อง' . ($names ? ': ' . $esc(implode(', ', $names)) : ' (ไม่มีช่องไหนครบ มีแต่ในเนื้อหา)');
            }
            if (!empty($e['hits'])) {
                $h = [];
                foreach ((array) $e['hits'] as $w => $c) {
                    $h[] = $esc($w) . ' ' . ((int) $c > 0 ? (int) $c . ' ครั้ง' : 'ไม่พบ');
                }
                $lines[] = 'พบแต่ละคำในบทความ: ' . implode(' · ', $h);
            }
            if (($e['why'] ?? '') === 'title_close' && isset($close[(int) ($e['close'] ?? 9)])) {
                $lines[] = $close[(int) $e['close']];   // only when it agrees with the reason above
            }
            if (array_key_exists('context', $e)) {
                $lines[] = empty($e['context']) ? 'คำประกอบ (คำกว้าง ๆ เช่น เด็ก ลูก) ไม่อยู่ในชื่อเรื่องหรือแท็ก' : 'คำประกอบของคำค้นอยู่ในชื่อเรื่องหรือแท็กด้วย';
            }
        }
        $rk = [];
        $rk[] = 'อันดับจากการค้นด้วยคำ: ' . (isset($this->ranks['bm25'][$id]) ? $this->ranks['bm25'][$id] + 1 : 'ไม่ติดอันดับ');
        $lines[] = implode(' · ', $rk);
        return '<div class="ai-explain" style="margin-top:6px;padding:6px 10px;border-radius:6px;background:#fff8e1;color:#5c4400;font-size:12px;line-height:1.6">'
            . implode('<br>', $lines) . '</div>';
    }

    /** One box above the results: what the search understood from what was typed (administrators only, ?explain=1). */
    public function explainHeader(): string
    {
        if (!$this->diag) {
            return '';
        }
        $d = $this->diag;
        $e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $src = ['keyword' => 'คำค้น (BM25)', 'synonyms' => 'คำพ้อง', 'clicks' => 'เรียนรู้จากการคลิก', 'short' => 'โหมดค้นคำเดียว (ชื่อเรื่องเท่านั้น)'];
        $rows = [];
        $rows[] = '<strong>ผู้ใช้พิมพ์:</strong> ' . $e($d['typed']);
        $rows[] = '<strong>ระบบค้นด้วยคำ:</strong> ' . ($d['units'] ? $e(implode('  +  ', $d['units'])) : '— (ไม่เหลือคำที่ค้นได้)');
        if (!empty($d['plan'])) {
            $pl = $d['plan'];
            if (!empty($pl['phrases'])) {
                $rows[] = '<strong>ตัวเลขกับหน่วยที่ต้องตรง:</strong> ' . $e(implode(', ', $pl['phrases']));
            }
            if ($pl['core'] || !$pl['phrases']) {
                $rows[] = '<strong>คำหลักที่บทความต้องมีครบ:</strong> ' . ($pl['core'] ? $e(implode(', ', $pl['core'])) : '— (ไม่เหลือคำหลัก)');
            }
            if (!empty($pl['context'])) {
                $rows[] = '<strong>คำประกอบ</strong> (คำกว้าง ๆ ที่พบในบทความส่วนใหญ่ ใช้ช่วยจัดลำดับเท่านั้น): ' . $e(implode(', ', $pl['context']));
            }
        }
        if (!empty($d['corrected'])) {
            $c = [];
            foreach ($d['corrected'] as $from => $to) { $c[] = $e($from) . ' → ' . $e($to); }
            $rows[] = '<strong>แก้คำพิมพ์ผิด:</strong> ' . implode(', ', $c);
        }
        $dropped = array_values(array_diff($d['typed_words'] ?? [], $d['units'] ?? []));
        if ($dropped) {
            $rows[] = '<strong>ไม่นับเป็นคำค้น</strong> (คำถาม คำเชื่อม หรือถูกกฎคำพ้องแทนที่): ' . $e(implode(', ', $dropped));
        }
        foreach ((array) ($d['rules'] ?? []) as $r) {
            $rows[] = '<strong>กฎคำพ้องที่ทำงาน:</strong> ' . $e($r['key']) . ' → ' . $e(implode(', ', $r['alts']));
        }
        $names = array_map(static fn ($k) => $src[$k] ?? $k, array_unique((array) ($d['sources'] ?? [])));
        $rows[] = '<strong>ใช้วิธีค้น:</strong> ' . ($names ? $e(implode(' + ', $names)) : '—');
        if (!empty($d['precision'])) {
            $rows[] = '<strong>โหมดความแม่นยำ:</strong> เปิด (แสดงถึงระดับ ' . (int) ($d['max_tier'] ?? 3) . ') · ผู้สมัครก่อนกรอง '
                . (int) ($d['before'] ?? 0) . ' เรื่อง → เหลือ ' . (int) ($d['kept'] ?? 0) . ' เรื่อง · ตัดออก ' . (int) ($d['hidden'] ?? 0) . ' เรื่อง';
        } elseif (empty($d['short'])) {
            $rows[] = '<strong>โหมดความแม่นยำ:</strong> ปิด (ไม่ได้ตัดบทความที่ไม่ตรงออก)';
        }
        $rows[] = '<strong>คลังคำ:</strong> ' . ((int) ($d['lexicon'] ?? 0) > 0 ? number_format((int) $d['lexicon']) . ' คำ' : 'ไม่ได้ใช้ (ดัชนีสร้างก่อนมีคลังคำ — กดอัปเดตดัชนี)')
            . ' · ใช้เวลา ' . $this->ms . ' มิลลิวินาที';
        return '<div class="ai-explain-head" style="margin:0 0 14px;padding:10px 14px;border-radius:8px;background:#fff4d6;border:1px solid #f0d27a;color:#5c4400;font-size:13px;line-height:1.7">'
            . '<div style="font-weight:700;margin-bottom:4px">อธิบายการค้นหา (เห็นเฉพาะผู้ดูแล — ตัด &amp;explain=1 ออกเพื่อปิด)</div>'
            . implode('<br>', $rows) . '</div>';
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
