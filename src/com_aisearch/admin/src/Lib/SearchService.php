<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

final class SearchService
{
    private const QUERY_STOP = ['ยัง', 'ไง', 'ทํา', 'ให้', 'ไหม', 'อะไร', 'ดี', 'เมื่อ', 'ทําไง', 'อย่างไร', 'ควร', 'ต้อง'];

    public function __construct(private DatabaseInterface $db, private Settings $settings) {}

    /** "คำ = พ้อง1, พ้อง2" per line */
    private function synonyms(): array
    {
        $map = [];
        foreach (preg_split('/\R/u', $this->settings->synonyms()) ?: [] as $line) {
            if (!str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = array_map('trim', explode('=', $line, 2));
            $alts = array_filter(array_map('trim', explode(',', $v)));
            if ($k !== '' && $alts) {
                $map[$k] = $alts;
            }
        }
        return $map;
    }

    /**
     * Fast suggestions for the search module: titles that contain the typed text first, then the best keyword matches.
     * Uses only the database and the keyword index (never the AI), so it is cheap to call on every keystroke.
     * @return array<int,object> rows of #__aisearch_docs
     */
    public function suggest(string $q, int $n = 6): array
    {
        $q = trim(preg_replace('/\s+/u', ' ', $q));
        if (mb_strlen($q) < 2) {
            return [];
        }
        $db = $this->db;
        $esc = $db->escape(mb_substr($q, 0, 100), true);
        $rows = $db->setQuery($db->getQuery(true)->select('id, catid, title, category')->from($db->quoteName('#__aisearch_docs'))
            ->where($db->quoteName('title') . " LIKE '%" . $esc . "%'")
            ->order("(" . $db->quoteName('title') . " LIKE '" . $esc . "%') DESC, CHAR_LENGTH(" . $db->quoteName('title') . ') ASC'), 0, $n)->loadObjectList('id');
        if (count($rows) < $n) {
            $r = $this->search($q, $n, 0, 20, false);
            foreach ($r['items'] as $d) {
                if (!isset($rows[$d->id]) && count($rows) < $n) {
                    $rows[$d->id] = $d;
                }
            }
        }
        return array_values($rows);
    }

    /** @return array{items:array,total:int,ms:float,indexed:bool,mode:string} mode = hybrid | keyword */
    public function search(string $q, int $limit = 20, int $offset = 0, int $max = 100, bool $semantic = true): array
    {
        $t0 = microtime(true);
        $bm = (new IndexStore($this->db))->load();
        if (!$bm) {
            return ['items' => [], 'total' => 0, 'ms' => 0.0, 'indexed' => false, 'mode' => 'keyword'];
        }
        $tk = new ThaiTokenizer();
        $qn = $tk->normalize($q);
        $tokens = array_values(array_diff($tk->tokenize($q), self::QUERY_STOP));
        $expanded = [];
        foreach ($this->synonyms() as $key => $alts) {
            if (mb_strpos($qn, $tk->normalize($key)) !== false) {
                array_push($expanded, ...$alts);
            }
        }
        $rankings = [$bm->search($tokens, 100)];
        $weights = [1.0];
        if ($expanded) {
            $rankings[] = $bm->search($tk->tokenize(implode(' ', $expanded)), 100);
            $weights[] = 0.5;
        }
        $mode = 'keyword';
        $all = array_slice(array_keys(Rrf::fuse($rankings, 60, $weights)), 0, $max);
        $total = count($all);
        $ids = array_slice($all, max(0, $offset), $limit);
        $items = [];
        if ($ids) {
            $db = $this->db;
            $rows = $db->setQuery($db->getQuery(true)->select('*')->from($db->quoteName('#__aisearch_docs'))
                ->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $ids)) . ')'))->loadObjectList('id');
            foreach ($ids as $id) {
                if (isset($rows[$id])) {
                    $items[] = $rows[$id];
                }
            }
        }
        return ['items' => $items, 'total' => $total, 'ms' => round((microtime(true) - $t0) * 1000, 1), 'indexed' => true, 'mode' => $mode];
    }

    public function log(string $q, int $results, float $ms, string $mode = 'keyword'): void
    {
        $row = (object) ['q' => mb_substr($q, 0, 200), 'results' => $results, 'ms' => $ms, 'mode' => $mode, 'created' => gmdate('Y-m-d H:i:s')];
        $this->db->insertObject('#__aisearch_log', $row);
    }
}
