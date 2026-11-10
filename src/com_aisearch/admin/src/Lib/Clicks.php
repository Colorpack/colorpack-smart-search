<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/**
 * Learns from visitors: when people who searched for the same words keep opening the same article, that article is
 * pushed up for that search. Stores only the search text and the article id with a count (no visitor identity).
 */
final class Clicks
{
    public function __construct(private DatabaseInterface $db, private ThaiTokenizer $tk) {}

    /** Same key for the same words however they are spaced or cased. */
    public function key(string $q): string
    {
        return md5(implode(' ', $this->tk->tokenize($q)));
    }

    public function record(string $q, int $docId): void
    {
        $q = mb_substr(trim(preg_replace('/\s+/u', ' ', $q)), 0, 200);
        if ($q === '' || $docId <= 0) {
            return;
        }
        $db = $this->db;
        $db->setQuery('INSERT INTO ' . $db->quoteName('#__aisearch_clicks') . ' (h, q, doc_id, clicks, updated) VALUES ('
            . $db->quote($this->key($q)) . ', ' . $db->quote($q) . ', ' . $docId . ', 1, ' . $db->quote(gmdate('Y-m-d H:i:s')) . ') '
            . 'ON DUPLICATE KEY UPDATE clicks = clicks + 1, updated = VALUES(updated)')->execute();
        if (random_int(1, 200) === 1) {   // forget what nobody has clicked for a year
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__aisearch_clicks') . ' WHERE updated < ' . $db->quote(gmdate('Y-m-d H:i:s', time() - 365 * 86400)))->execute();
        }
    }

    /** @return array<int,float> doc id => clicks, most clicked first, only articles clicked at least $min times for this search */
    public function learned(string $q, int $min): array
    {
        $db = $this->db;
        try {
            $rows = $db->setQuery($db->getQuery(true)->select('doc_id, clicks')->from($db->quoteName('#__aisearch_clicks'))
                ->where($db->quoteName('h') . ' = ' . $db->quote($this->key($q)))
                ->where($db->quoteName('clicks') . ' >= ' . max(1, $min))
                ->order('clicks DESC'), 0, 20)->loadObjectList();
        } catch (\Throwable $e) {
            return [];   // table not there yet (upgrade not finished)
        }
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->doc_id] = (float) $r->clicks;
        }
        return $out;
    }
}
