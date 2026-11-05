<?php
namespace Colorpack\Plugin\System\SmartSearchBridge\Extension;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\SearchService;
use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

/**
 * Sends Joomla's own Smart Search (com_finder) requests to the Colorpack Smart Search page, so a search box that is
 * hard-wired to com_finder (for example the YOOtheme header search) and old finder links use the new search.
 */
final class SmartSearchBridge extends CMSPlugin implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return ['onAfterRoute' => 'afterRoute'];
    }

    public function afterRoute(Event $event): void
    {
        $app = $this->getApplication();
        if (!$app || !$app->isClient('site') || !class_exists(SearchService::class)) {
            return;
        }
        $in = $app->getInput();
        if ($in->getCmd('option') !== 'com_finder') {
            return;
        }
        $task = (string) $in->getCmd('task', '');

        // finder's search-as-you-type asks this URL; answer it with our titles in the same JSON shape
        if (in_array($task, ['suggestions.suggest', 'suggestions.opensearchsuggest'], true)) {
            if ((int) $this->params->get('bridge_suggest', 1) === 1) {
                $this->answerSuggestions($app, (string) $in->get('q', '', 'string'), $task === 'suggestions.opensearchsuggest');
            }
            return;
        }

        // the search page itself (plain page loads only: not opensearch, json, feeds or form posts)
        $view = (string) $in->getCmd('view', 'search');
        $format = (string) $in->getCmd('format', 'html');
        if ($task !== '' || $view !== 'search' || !in_array($format, ['html', ''], true) || !in_array($app->getInput()->getMethod(), ['GET', 'HEAD'], true)) {
            return;
        }
        $url = $this->target((string) $in->get('q', '', 'string'));
        if ($url !== '') {
            $code = (int) $this->params->get('redirect_code', 302) === 301 ? 301 : 302;
            $app->redirect($url, $code);
        }
    }

    private function target(string $q): string
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $itemId = (int) $this->params->get('menu_item', 0);
        if ($itemId < 1) {
            try {
                $itemId = (int) $db->setQuery($db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_aisearch&view=search%'))
                    ->where($db->quoteName('published') . ' = 1')->where($db->quoteName('client_id') . ' = 0')
                    ->order($db->quoteName('id') . ' ASC'), 0, 1)->loadResult();
            } catch (\Throwable $e) {
                $itemId = 0;
            }
        }
        $base = Route::_($itemId > 0 ? 'index.php?Itemid=' . $itemId : 'index.php?option=com_aisearch&view=search', false);
        $q = trim($q);
        return $q === '' ? $base : $base . (str_contains($base, '?') ? '&' : '?') . 'q=' . rawurlencode($q);
    }

    private function answerSuggestions($app, string $q, bool $opensearch): void
    {
        $titles = [];
        try {
            $db = Factory::getContainer()->get(DatabaseInterface::class);
            foreach ((new SearchService($db, Settings::load()))->suggest(mb_substr(trim($q), 0, 100), 6) as $d) {
                $titles[] = (string) $d->title;
            }
        } catch (\Throwable $e) {
            // an empty list is fine
        }
        $app->allowCache(false);
        $app->setHeader('Content-Type', 'application/json; charset=utf-8', true);
        $app->sendHeaders();
        echo $opensearch
            ? json_encode([$q, $titles], JSON_UNESCAPED_UNICODE)
            : '{ "suggestions": ' . json_encode($titles, JSON_UNESCAPED_UNICODE) . ' }';
        $app->close();
    }
}
