<?php
namespace Colorpack\Component\AiSearch\Administrator\View\Compare;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\SearchService;
use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;
use Joomla\Database\DatabaseInterface;

/** Runs a list of searches twice, with the normal ranking and with the precision mode, and shows the results side by side. */
class HtmlView extends BaseHtmlView
{
    public string $queries = '';
    public bool $withAi = false;
    /** @var array<int,array<string,mixed>> */
    public array $rows = [];
    public bool $precisionOn = false;

    public function display($tpl = null): void
    {
        ToolbarHelper::title('Compare search modes / เปรียบเทียบโหมดการค้น', 'search');
        $app = Factory::getApplication();
        $user = $app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $in = $app->getInput();
        $settings = Settings::load();
        $this->precisionOn = $settings->precisionMode();
        $this->queries = trim((string) $in->get('queries', '', 'string'));
        $this->withAi = $in->getInt('with_ai', 0) === 1;
        if ($this->queries !== '') {
            $svc = new SearchService(Factory::getContainer()->get(DatabaseInterface::class), $settings);
            $lines = array_slice(array_values(array_filter(array_map('trim', preg_split('/\R/u', $this->queries) ?: []), static fn ($l) => mb_strlen($l) >= 2)), 0, 30);
            foreach ($lines as $q) {
                $a = $svc->search($q, 12, 0, 100, $this->withAi, false);
                $b = $svc->search($q, 12, 0, 100, $this->withAi, true, true);
                $this->rows[] = ['q' => $q, 'current' => $a, 'precision' => $b];
            }
        }
        parent::display($tpl);
    }
}
