<?php
namespace Colorpack\Component\AiSearch\Site\Controller;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Popular;
use Colorpack\Component\AiSearch\Administrator\Lib\SearchService;
use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\Component\Content\Site\Helper\RouteHelper;
use Joomla\Database\DatabaseInterface;

/** JSON for the search module: ?option=com_aisearch&task=suggest.show&format=json&q=... (no q = popular searches) */
class SuggestController extends BaseController
{
    public function show(): void
    {
        $app = Factory::getApplication();
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $settings = Settings::load();
        $q = trim((string) $app->getInput()->getString('q', ''));
        $limit = max(1, min(10, (int) $app->getInput()->getInt('n', 6)));
        $out = ['items' => [], 'popular' => []];
        try {
            if (mb_strlen($q) >= 2) {
                foreach ((new SearchService($db, $settings))->suggest(mb_substr($q, 0, 100), $limit) as $d) {
                    $out['items'][] = [
                        't' => (string) $d->title,
                        'c' => (string) ($d->category ?? ''),
                        'u' => Route::_(RouteHelper::getArticleRoute((int) $d->id, (int) $d->catid)),
                    ];
                }
            } else {
                $out['popular'] = array_slice(Popular::forSettings($db, $settings)['items'], 0, $limit);
            }
        } catch (\Throwable $e) {
            // an empty answer is better than an error box while someone is typing
        }
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->setHeader('Cache-Control', 'public, max-age=30', true);
        $app->sendHeaders();
        echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $app->close();
    }
}
