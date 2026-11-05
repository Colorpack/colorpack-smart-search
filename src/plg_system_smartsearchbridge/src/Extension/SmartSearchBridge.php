<?php
namespace Colorpack\Plugin\System\SmartSearchBridge\Extension;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\SearchService;
use Colorpack\Component\AiSearch\Administrator\Lib\Settings;
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
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
    /** the site side needs the plugin's own texts ("Searching...") */
    protected $autoloadLanguage = true;

    public static function getSubscribedEvents(): array
    {
        return ['onAfterRoute' => 'afterRoute', 'onBeforeCompileHead' => 'beforeHead'];
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


    /** Shows that a search is running: a bar at the top of the page and "Searching..." under the search box, from the moment the form is sent. */
    public function beforeHead(Event $event): void
    {
        $app = $this->getApplication();
        if (!$app || !$app->isClient('site') || (int) $this->params->get('progress', 1) !== 1) {
            return;
        }
        $doc = $app->getDocument();
        if (!$doc instanceof HtmlDocument) {
            return;
        }
        $msg = json_encode(Text::_('PLG_SYSTEM_SMARTSEARCHBRIDGE_SEARCHING'), JSON_UNESCAPED_UNICODE);
        $css = '#ssb-bar{position:fixed;left:0;right:0;top:0;height:3px;z-index:2147483000;background:rgba(59,143,224,.18);overflow:hidden;pointer-events:none}'
            . '#ssb-bar i{position:absolute;top:0;bottom:0;width:40%;background:#3b8fe0;border-radius:3px;animation:ssb-slide 1.1s ease-in-out infinite}'
            . '@keyframes ssb-slide{0%{left:-40%}100%{left:100%}}'
            . '.ssb-status{margin-top:10px;font-size:.9em;line-height:1.4;color:#555;text-align:center}'
            . '.ssb-status::before{content:"";display:inline-block;width:.9em;height:.9em;margin-right:.5em;border:2px solid currentColor;border-right-color:transparent;border-radius:50%;vertical-align:-.12em;animation:ssb-spin .8s linear infinite}'
            . '@keyframes ssb-spin{to{transform:rotate(360deg)}}'
            . '.ssb-form-busy{opacity:.8}.ssb-form-busy input{cursor:progress}'
            . '@media (prefers-reduced-motion:reduce){#ssb-bar i{animation:none;width:100%;opacity:.6}.ssb-status::before{animation:none}}';
        $js = '(function(){var MSG=' . $msg . ',SEL=\'form.js-finder-searchform,form[action*="finder"],form[action*="aisearch"],form[role="search"]\';'
            . 'function bar(){if(document.getElementById("ssb-bar"))return;var b=document.createElement("div");b.id="ssb-bar";b.setAttribute("role","progressbar");b.setAttribute("aria-label",MSG);b.appendChild(document.createElement("i"));document.body.appendChild(b)}'
            . 'function busy(f){bar();document.documentElement.classList.add("ssb-busy");f.classList.add("ssb-form-busy");f.setAttribute("aria-busy","true");'
            . 'var n=f.nextElementSibling;if(!n||!n.classList.contains("ssb-status")){var s=document.createElement("div");s.className="ssb-status";s.setAttribute("role","status");s.textContent=MSG;f.parentNode.insertBefore(s,f.nextSibling)}}'
            . 'function reset(){var b=document.getElementById("ssb-bar");if(b)b.remove();document.documentElement.classList.remove("ssb-busy");'
            . 'document.querySelectorAll(".ssb-status").forEach(function(x){x.remove()});document.querySelectorAll(".ssb-form-busy").forEach(function(f){f.classList.remove("ssb-form-busy");f.removeAttribute("aria-busy")})}'
            . 'document.addEventListener("submit",function(ev){var f=ev.target;if(!f||!f.matches||!f.matches(SEL)||(f.closest&&f.closest("[data-ssm],.aisearch")))return;setTimeout(function(){if(!ev.defaultPrevented)busy(f)},0)},false);'
            . 'window.addEventListener("pageshow",function(e){if(e.persisted)reset()});})();';
        $wa = $doc->getWebAssetManager();
        $wa->addInlineStyle($css);
        $wa->addInlineScript($js);
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
