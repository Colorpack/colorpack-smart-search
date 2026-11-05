<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Popular searches taken from the search log (shared by the search page and the search module). */
final class Popular
{
    /**
     * @return array{label:string,items:string[]} label is '' when the list is the manual one or empty
     */
    public static function forSettings(DatabaseInterface $db, Settings $s): array
    {
        $manual = $s->examples();
        if ($s->popularMode() === 'off') {
            return ['label' => '', 'items' => []];
        }
        if ($s->popularMode() === 'manual') {
            return ['label' => '', 'items' => $manual];
        }
        $items = self::fromLog($db, $s->popularCount(), $s->popularDays(), $s->popularMin());
        return count($items) >= 3 ? ['label' => 'คำค้นยอดนิยม', 'items' => $items] : ['label' => '', 'items' => $manual];
    }

    /** @return string[] */
    public static function fromLog(DatabaseInterface $db, int $count, int $days, int $min): array
    {
        try {
            $since = $db->quote(gmdate('Y-m-d H:i:s', time() - $days * 86400));
            $rows = $db->setQuery('SELECT LOWER(TRIM(q)) AS qq, COUNT(*) AS n, MAX(created) AS last FROM ' . $db->quoteName('#__aisearch_log')
                . ' WHERE created >= ' . $since . ' AND results > 0 GROUP BY qq HAVING n >= ' . (int) $min . ' ORDER BY n DESC, last DESC', 0, $count * 4)->loadObjectList();
        } catch (\Throwable $e) {
            return [];
        }
        $out = [];
        foreach ($rows as $r) {
            $q = trim((string) $r->qq);
            $len = mb_strlen($q);
            if ($len < 2 || $len > 60 || preg_match('~[@]|https?:|www\.|\d{7,}|[<>{}]~iu', $q)) {
                continue;   // too short/long, or looks like personal data / a link
            }
            $out[] = $q;
            if (count($out) >= $count) {
                break;
            }
        }
        return $out;
    }
}
