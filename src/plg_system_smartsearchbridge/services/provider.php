<?php
\defined('_JEXEC') or die;

use Colorpack\Plugin\System\SmartSearchBridge\Extension\SmartSearchBridge;
use Joomla\CMS\Extension\PluginInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Event\DispatcherInterface;

return new class () implements ServiceProviderInterface {
    public function register(Container $container): void
    {
        $container->set(PluginInterface::class, function (Container $container) {
            $plugin = new SmartSearchBridge(
                $container->get(DispatcherInterface::class),
                (array) PluginHelper::getPlugin('system', 'smartsearchbridge')
            );
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
