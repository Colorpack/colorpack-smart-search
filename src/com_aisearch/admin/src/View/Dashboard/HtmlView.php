<?php
namespace Colorpack\Component\AiSearch\Administrator\View\Dashboard;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\IndexStore;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

class HtmlView extends BaseHtmlView
{
    public int $docs = 0;
    public ?string $updated = null;
    public int $searches30 = 0;
    public int $zero30 = 0;
    public array $top = [];
    public array $zero = [];
    public array $imgStats = ['article' => 0, 'fc' => 0, 'none' => 0];
    public bool $intl = true;

    public function display($tpl = null): void
    {
        ToolbarHelper::title(Text::_('COM_AISEARCH'), 'search');
        $user = Factory::getApplication()->getIdentity();
        if ($user->authorise('core.admin', 'com_aisearch')) {
            ToolbarHelper::preferences('com_aisearch');
        }
        $this->intl = class_exists('IntlBreakIterator');
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $since = $db->quote(gmdate('Y-m-d H:i:s', time() - 30 * 86400));
        $this->docs = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__aisearch_docs'))->loadResult();
        $this->updated = (new IndexStore($db))->updated();
        $this->searches30 = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__aisearch_log') . ' WHERE created >= ' . $since)->loadResult();
        $this->zero30 = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__aisearch_log') . ' WHERE results = 0 AND created >= ' . $since)->loadResult();
        $this->top = $db->setQuery('SELECT q, COUNT(*) n FROM ' . $db->quoteName('#__aisearch_log') . ' WHERE created >= ' . $since . ' GROUP BY q ORDER BY n DESC LIMIT 15')->loadObjectList();
        $this->zero = $db->setQuery('SELECT q, COUNT(*) n FROM ' . $db->quoteName('#__aisearch_log') . ' WHERE results = 0 AND created >= ' . $since . ' GROUP BY q ORDER BY n DESC LIMIT 15')->loadObjectList();
        $row = $db->setQuery("SELECT SUM(image_kind = 'url') AS a, SUM(image_kind = 'fc') AS f, SUM(image_kind = '') AS n FROM " . $db->quoteName('#__aisearch_docs'))->loadObject();
        $this->imgStats = ['article' => (int) ($row->a ?? 0), 'fc' => (int) ($row->f ?? 0), 'none' => (int) ($row->n ?? 0)];
        parent::display($tpl);
    }
}
