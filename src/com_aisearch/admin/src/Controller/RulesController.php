<?php
namespace Colorpack\Component\AiSearch\Administrator\Controller;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Colorpack\Component\AiSearch\Administrator\Lib\SynonymStore;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;

/** Quick "add a synonym" from the dashboard (works in both editions). */
class RulesController extends BaseController
{
    public function add(): void
    {
        $this->checkToken();
        $user = $this->app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $in = $this->app->getInput();
        $q = trim((string) $in->post->getString('q', ''));
        $alts = array_filter(array_map('trim', explode(',', str_replace(['،', '、', "\n"], ',', (string) $in->post->getString('alts', '')))));
        $store = new SynonymStore(Factory::getContainer()->get(DatabaseInterface::class), Settings::load());
        $store->migrate();
        $n = ($q !== '' && $alts && $store->save(0, 'one', mb_substr($q, 0, 100), implode(', ', array_slice($alts, 0, 8)), true, '', 'dashboard')['ok']) ? 1 : 0;
        $this->setRedirect('index.php?option=com_aisearch', $n ? 'เพิ่มคำพ้องแล้ว: ' . $q : 'ไม่ได้เพิ่ม: ใส่คำที่ต้องการให้ค้นเจอแทนอย่างน้อยหนึ่งคำ', $n ? 'message' : 'warning');
    }
}
