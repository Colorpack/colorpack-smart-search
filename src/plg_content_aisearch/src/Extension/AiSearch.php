<?php
namespace Colorpack\Plugin\Content\AiSearch\Extension;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Indexer;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

final class AiSearch extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentAfterSave'    => 'afterSave',
            'onContentAfterDelete'  => 'afterDelete',
            'onContentChangeState'  => 'changeState',
        ];
    }

    private function indexer(): ?Indexer
    {
        return class_exists(Indexer::class) ? new Indexer($this->getDatabase()) : null;
    }

    private static function arg(Event $e, string $name, int $pos)
    {
        $a = $e->getArguments();
        return $a[$name] ?? $a[$pos] ?? null;
    }

    public function afterSave(Event $event): void
    {
        $context = self::arg($event, 'context', 0);
        $item = self::arg($event, 'subject', 1);
        if (!in_array($context, ['com_content.article', 'com_content.form'], true) || !is_object($item) || empty($item->id)) {
            return;
        }
        try {
            $this->indexer()?->updateArticle((int) $item->id);
        } catch (\Throwable $e) {
            // never break saving an article because of search indexing
        }
    }

    public function afterDelete(Event $event): void
    {
        $context = self::arg($event, 'context', 0);
        $item = self::arg($event, 'subject', 1);
        if ($context !== 'com_content.article' || !is_object($item) || empty($item->id)) {
            return;
        }
        try {
            $this->indexer()?->removeArticle((int) $item->id);
        } catch (\Throwable $e) {
        }
    }

    public function changeState(Event $event): void
    {
        $context = self::arg($event, 'context', 0);
        $pks = (array) self::arg($event, 'pks', 1);
        if ($context !== 'com_content.article') {
            return;
        }
        try {
            $ix = $this->indexer();
            foreach ($pks as $id) {
                $ix?->updateArticle((int) $id);
            }
        } catch (\Throwable $e) {
        }
    }
}
