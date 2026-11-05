<?php
\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

class Pkg_AisearchInstallerScript
{
    public function postflight($type, $parent): void
    {
        if ($type === 'uninstall') {
            return;
        }
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $db->setQuery("UPDATE " . $db->quoteName('#__extensions') . " SET enabled = 1 WHERE type = 'plugin' AND folder = 'content' AND element = 'aisearch'")->execute();

        // Upgrade path from older versions (idempotent).
        $docCols = $db->getTableColumns('#__aisearch_docs');
        if ($docCols && !isset($docCols['image'])) {
            $db->setQuery("ALTER TABLE `#__aisearch_docs` ADD COLUMN `image` varchar(400) NOT NULL DEFAULT '', ADD COLUMN `image_kind` varchar(4) NOT NULL DEFAULT ''")->execute();
        }
        $cols = $db->getTableColumns('#__aisearch_log');
        if ($cols && !isset($cols['mode'])) {
            $db->setQuery("ALTER TABLE `#__aisearch_log` ADD COLUMN `mode` varchar(10) NOT NULL DEFAULT 'keyword'")->execute();
        }
    }
}
