<?php
namespace Colorpack\Component\AiSearch\Administrator\View\Synonyms;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Colorpack\Component\AiSearch\Administrator\Lib\SynonymStore;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** The synonym manager: list with search and filters, add / edit, switch on and off, import and export, and a tester. */
class HtmlView extends BaseHtmlView
{
    public const PER_PAGE = 50;
    public array $rows = [];
    public int $total = 0;
    public array $counts = ['total' => 0, 'on' => 0, 'ai' => 0];
    public array $usage = [];
    public ?object $edit = null;
    public string $q = '';
    public string $source = '';
    public string $status = '';
    public int $page = 1;
    public string $testQ = '';
    public array $test = ['rules' => [], 'words' => []];
    public bool $moved = false;

    public function display($tpl = null): void
    {
        ToolbarHelper::title('Synonyms / คำพ้อง', 'search');
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $store = new SynonymStore($db, Settings::load());
        $this->moved = $store->migrate() > 0;
        $in = $app->getInput();
        $this->q = trim((string) $in->getString('q', ''));
        $this->source = (string) $in->getString('source', '');
        $this->status = (string) $in->getString('status', '');
        $this->page = max(1, $in->getInt('page', 1));
        $res = $store->find($this->q, $this->source, $this->status, ($this->page - 1) * self::PER_PAGE, self::PER_PAGE);
        $this->rows = $res['rows'];
        $this->total = $res['total'];
        $this->counts = $store->counts();
        $this->usage = $store->usage($this->rows);
        if ($in->getInt('id', 0) > 0) {
            $this->edit = $store->get($in->getInt('id', 0));
        }
        $this->testQ = trim((string) $in->getString('test', ''));
        if ($this->testQ !== '') {
            $this->test = $store->test($this->testQ);
        }
        parent::display($tpl);
    }
}
