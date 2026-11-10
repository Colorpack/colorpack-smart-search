<?php
namespace Colorpack\Component\AiSearch\Administrator\Controller;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\SynonymRules;
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
        $n = ($q !== '' && $alts) ? SynonymRules::append([[mb_substr($q, 0, 100), array_slice($alts, 0, 8)]]) : 0;
        $this->setRedirect('index.php?option=com_aisearch', $n ? 'เพิ่มคำพ้องแล้ว: ' . $q : 'ไม่ได้เพิ่ม: ใส่คำที่ต้องการให้ค้นเจอแทนอย่างน้อยหนึ่งคำ', $n ? 'message' : 'warning');
    }
}
