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

    /** Beacon from the results page: a visitor opened an article from a search. POST q, id. Answers 204. */
    public function click(): void
    {
        $app = Factory::getApplication();
        $in = $app->getInput();
        $q = trim((string) $in->post->getString('q', ''));
        $id = (int) $in->post->getInt('id', 0);
        $settings = Settings::load();
        if ($settings->clickLearning() && $q !== '' && $id > 0 && mb_strlen($q) <= 200) {
            $session = $app->getSession();
            $seen = (array) $session->get('com_aisearch.clicks', []);
            $mark = md5(mb_strtolower($q)) . ':' . $id;
            if (!in_array($mark, $seen, true) && count($seen) < 200) {   // one count per visitor session and pair
                try {
                    $db = Factory::getContainer()->get(DatabaseInterface::class);
                    $exists = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__aisearch_docs') . ' WHERE id = ' . $id)->loadResult();
                    if ($exists) {
                        (new \Colorpack\Component\AiSearch\Administrator\Lib\Clicks($db, new \Colorpack\Component\AiSearch\Administrator\Lib\ThaiTokenizer()))->record($q, $id);
                        $seen[] = $mark;
                        $session->set('com_aisearch.clicks', $seen);
                    }
                } catch (\Throwable $e) {
                }
            }
        }
        $app->setHeader('Status', '204', true);
        $app->setHeader('Cache-Control', 'no-store', true);
        $app->sendHeaders();
        $app->close();
    }
}
