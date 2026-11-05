<?php
namespace Colorpack\Component\AiSearch\Administrator\Controller;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Indexer;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseInterface;

class IndexController extends BaseController
{
    public function rebuild(): void
    {
        $this->checkToken();
        $user = $this->app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        $r = (new Indexer(Factory::getContainer()->get(DatabaseInterface::class)))->rebuildAll();
        $this->setRedirect('index.php?option=com_aisearch', sprintf('Indexed %d articles in %.1f s.', $r['docs'], $r['seconds']));
    }

    private function json(array $data): void
    {
        $this->app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $this->app->sendHeaders();
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->app->close();
    }

    /** AJAX: rebuild the keyword index. */
    public function ajaxRebuild(): void
    {
        $user = $this->app->getIdentity();
        if (!Session::checkToken('request') || !$user || !$user->authorise('core.manage', 'com_aisearch')) {
            $this->json(['ok' => false, 'error' => 'Not allowed or session expired. Reload the page and try again.']);
        }
        try {
            $r = (new Indexer(Factory::getContainer()->get(DatabaseInterface::class)))->rebuildAll();
            $this->json(['ok' => true, 'docs' => $r['docs'], 'seconds' => $r['seconds']]);
        } catch (\Throwable $e) {
            $this->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }
}
