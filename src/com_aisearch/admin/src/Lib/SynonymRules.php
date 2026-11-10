<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;

/** Adds synonym rules ("word = alt1, alt2") to the saved list without touching the rest of the options. */
final class SynonymRules
{
    /** @param array<int,array{0:string,1:string[]}> $rules [word, alternatives] @return int rules added */
    public static function append(array $rules): int
    {
        $text = rtrim(Settings::load()->synonyms());
        $added = 0;
        foreach ($rules as [$word, $alts]) {
            $word = trim(preg_replace('/[\r\n=,]+/u', ' ', $word) ?? '');
            $alts = array_values(array_filter(array_map(static fn ($a) => trim(preg_replace('/[\r\n=,]+/u', ' ', (string) $a) ?? ''), $alts)));
            if ($word !== '' && $alts) {
                $text .= "\n" . $word . ' = ' . implode(', ', $alts);
                $added++;
            }
        }
        if ($added) {
            $params = ComponentHelper::getParams('com_aisearch');
            $params->set('synonyms', ltrim($text));
            $ext = Table::getInstance('extension');
            $ext->load(ComponentHelper::getComponent('com_aisearch')->id);
            $ext->params = (string) $params;
            $ext->store();
        }
        return $added;
    }
}
