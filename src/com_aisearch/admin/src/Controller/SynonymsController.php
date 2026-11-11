<?php
namespace Colorpack\Component\AiSearch\Administrator\Controller;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Colorpack\Component\AiSearch\Administrator\Lib\SynonymStore;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;

/** Add, edit, switch on/off, delete, import and export synonym rules. */
class SynonymsController extends BaseController
{
    private function store(): SynonymStore
    {
        $this->checkToken('request');
        $user = $this->app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $store = new SynonymStore(Factory::getContainer()->get(DatabaseInterface::class), Settings::load());
        $store->migrate();
        return $store;
    }

    private function back(string $msg, string $type = 'message', string $extra = ''): void
    {
        $this->setRedirect('index.php?option=com_aisearch&view=synonyms' . $extra, $msg, $type);
    }

    public function save(): void
    {
        $store = $this->store();
        $in = $this->app->getInput()->post;
        $r = $store->save($in->getInt('id', 0), (string) $in->getString('kind', 'one'), (string) $in->getString('term', ''), (string) $in->getString('alts', ''), $in->getInt('enabled', 1) === 1, (string) $in->getString('note', ''));
        $this->back($r['message'], $r['ok'] ? 'message' : 'warning', $r['ok'] ? '' : ($in->getInt('id', 0) ? '&id=' . $in->getInt('id', 0) : ''));
    }

    /** Checked rows: enable, disable or delete. */
    public function bulk(): void
    {
        $store = $this->store();
        $in = $this->app->getInput()->post;
        $action = (string) $in->getString('action', '');
        $ids = array_map('intval', (array) $in->get('cid', [], 'array'));
        if (!$ids || !in_array($action, ['enable', 'disable', 'delete'], true)) {
            $this->back('เลือกรายการและการกระทำก่อน', 'notice');
            return;
        }
        $n = $store->bulk($ids, $action);
        $this->back(['enable' => 'เปิดใช้แล้ว', 'disable' => 'ปิดใช้แล้ว', 'delete' => 'ลบแล้ว'][$action] . ' ' . $n . ' กฎ');
    }

    public function import(): void
    {
        $store = $this->store();
        $text = (string) $this->app->getInput()->post->getString('text', '');
        if (mb_strlen($text) > 500000) {
            $this->back('ข้อความยาวเกินไป', 'warning');
            return;
        }
        $r = $store->import($text);
        $this->back(sprintf('นำเข้า %d กฎ' . ($r['skipped'] ? ' (ข้าม %d บรรทัดที่ไม่ถูกรูปแบบ)' : ''), $r['added'], $r['skipped']), $r['added'] ? 'message' : 'warning');
    }

    public function export(): void
    {
        $store = $this->store();
        $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="synonyms-' . gmdate('Y-m-d') . '.txt"', true);
        $this->app->sendHeaders();
        echo $store->export();
        $this->app->close();
    }
}
