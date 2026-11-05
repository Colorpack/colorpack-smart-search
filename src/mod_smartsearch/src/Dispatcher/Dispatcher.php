<?php
namespace Colorpack\Module\SmartSearch\Site\Dispatcher;

\defined('_JEXEC') or die;

use Joomla\CMS\Dispatcher\AbstractModuleDispatcher;
use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;

/** Prepares the data for tmpl/default.php: where the form posts to and where suggestions come from. */
class Dispatcher extends AbstractModuleDispatcher
{
    protected function getLayoutData(): array
    {
        $data = parent::getLayoutData();
        $params = $data['params'];

        $itemId = (int) $params->get('menu_item', 0);
        if ($itemId < 1) {
            try {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $itemId = (int) $db->setQuery($db->getQuery(true)->select($db->quoteName('id'))->from($db->quoteName('#__menu'))
                    ->where($db->quoteName('link') . ' LIKE ' . $db->quote('index.php?option=com_aisearch&view=search%'))
                    ->where($db->quoteName('published') . ' = 1')->where($db->quoteName('client_id') . ' = 0')
                    ->order($db->quoteName('id') . ' ASC'), 0, 1)->loadResult();
            } catch (\Throwable $e) {
                $itemId = 0;
            }
        }
        $data['itemId'] = $itemId;
        $data['action'] = Route::_($itemId > 0 ? 'index.php?Itemid=' . $itemId : 'index.php?option=com_aisearch&view=search');
        $data['suggestUrl'] = Uri::root(true) . '/index.php?option=com_aisearch&task=suggest.show&format=json';
        return $data;
    }
}
