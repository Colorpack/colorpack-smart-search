<?php
namespace Colorpack\Component\AiSearch\Administrator\View\Words;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Lexicon;
use Colorpack\Component\AiSearch\Administrator\Lib\ThaiTokenizer;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** The words of the site: the list the editors keep, the counts of every layer, and a tester that shows how a text is cut. */
class HtmlView extends BaseHtmlView
{
    public const PER_PAGE = 50;
    public array $rows = [];
    public int $total = 0;
    public int $page = 1;
    public string $q = '';
    public string $source = '';
    public string $kind = '';
    public array $snapshot = [];
    public array $live = [];
    public bool $dirty = false;
    public string $testText = '';
    public array $icu = [];
    public array $withLexicon = [];
    public bool $mined = false;
    /** @var array<int,array{word:string,count:int,score:float}> */
    public array $candidates = [];

    public function display($tpl = null): void
    {
        ToolbarHelper::title('Words / คำศัพท์', 'search');
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $in = $app->getInput();
        $this->q = trim((string) $in->getString('q', ''));
        $this->source = (string) $in->getString('source', '');
        $this->kind = (string) $in->getString('kind', '');
        $this->page = max(1, $in->getInt('page', 1));
        $w = ['1=1'];
        if ($this->q !== '') { $w[] = 'word LIKE ' . $db->quote('%' . $db->escape($this->q, true) . '%', false); }
        if (in_array($this->source, ['manual', 'import', 'tag', 'cat', 'mined'], true)) { $w[] = 'source = ' . $db->quote($this->source); }
        if (in_array($this->kind, ['keep', 'split'], true)) { $w[] = 'kind = ' . $db->quote($this->kind); }
        $where = ' WHERE ' . implode(' AND ', $w);
        $t = $db->quoteName('#__aisearch_words');
        $this->total = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $t . $where)->loadResult();
        $this->rows = $db->setQuery('SELECT * FROM ' . $t . $where . ' ORDER BY id DESC', ($this->page - 1) * self::PER_PAGE, self::PER_PAGE)->loadObjectList() ?: [];
        $this->snapshot = Lexicon::snapshotCounts($db) ?? [];
        $by = $db->setQuery('SELECT source, COUNT(*) n FROM ' . $t . ' GROUP BY source')->loadObjectList('source') ?: [];
        $this->live = ['tag' => (int) ($by['tag']->n ?? 0), 'cat' => (int) ($by['cat']->n ?? 0), 'manual' => (int) ($by['manual']->n ?? 0) + (int) ($by['import']->n ?? 0)];
        $this->dirty = Lexicon::isDirty();
        $this->testText = trim((string) $in->getString('test', ''));
        if ($this->testText !== '') {
            $tk = new ThaiTokenizer();
            $keep = Lexicon::$active;
            Lexicon::$active = null;
            $this->icu = $tk->tokenize($this->testText);
            $c = Lexicon::collect($db);   // the words as they are now, even before the index is rebuilt
            Lexicon::$active = new Lexicon($c['words'], $c['split']);
            $this->withLexicon = $tk->tokenize($this->testText);
            Lexicon::$active = $keep;
        }
        if ($in->getInt('mine', 0) === 1) {
            $this->mined = true;
            $this->candidates = Lexicon::candidates($db);
        }
        parent::display($tpl);
    }
}
