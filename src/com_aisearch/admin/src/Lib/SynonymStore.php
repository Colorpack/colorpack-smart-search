<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;

/**
 * Synonym rules, one row each. Two kinds:
 *  one: when the search contains the phrase (term), also look for the alternatives (alts).
 *  set: every word in alts means the same as every other (look for the others when one is typed).
 * Rules from the old single text box (the "synonyms" option) are moved in once, on the first visit to the Synonyms page;
 * until then the search keeps reading that text, so nothing changes under an upgrade.
 */
final class SynonymStore
{
    private ?array $map = null;
    private ThaiTokenizer $tk;

    public function __construct(private DatabaseInterface $db, private Settings $s)
    {
        $this->tk = new ThaiTokenizer();
    }

    private function t(): string
    {
        return $this->db->quoteName('#__aisearch_synonyms');
    }

    public function migrated(): bool
    {
        return (int) ComponentHelper::getParams('com_aisearch')->get('syn_migrated', 0) === 1;
    }

    /** @return array<string,string[]> phrase => alternatives, the shape the search reads */
    public function map(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }
        if (!$this->migrated()) {
            return $this->map = self::parseText($this->s->synonyms());
        }
        $map = [];
        try {
            $rows = $this->db->setQuery('SELECT kind, term, alts FROM ' . $this->t() . ' WHERE enabled = 1')->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return $this->map = self::parseText($this->s->synonyms());
        }
        foreach ($rows as $r) {
            $alts = self::split((string) $r->alts);
            if ($r->kind === 'set') {
                foreach ($alts as $a) {
                    $others = array_values(array_filter($alts, static fn ($x) => $x !== $a));
                    if ($others) {
                        $map[$a] = array_values(array_unique(array_merge($map[$a] ?? [], $others)));
                    }
                }
            } elseif (trim((string) $r->term) !== '' && $alts) {
                $map[trim((string) $r->term)] = array_values(array_unique(array_merge($map[trim((string) $r->term)] ?? [], $alts)));
            }
        }
        return $this->map = $map;
    }

    /** @return string[] */
    public static function split(string $text): array
    {
        $parts = preg_split('/[,;\x{060C}\r\n]+/u', $text) ?: [];
        return array_values(array_unique(array_filter(array_map(static fn ($p) => trim(preg_replace('/\s+/u', ' ', (string) $p) ?? ''), $parts), static fn ($p) => $p !== '' && mb_strlen($p) <= 100)));
    }

    /** "word = alt1, alt2" lines are one-way rules, "a, b, c" lines are sets. @return array<string,string[]> only the one-way view (legacy reading) */
    public static function parseText(string $text): array
    {
        $map = [];
        foreach (self::parseRules($text) as $r) {
            if ($r['kind'] === 'one') {
                $map[$r['term']] = $r['alts'];
            } else {
                foreach ($r['alts'] as $a) {
                    $others = array_values(array_filter($r['alts'], static fn ($x) => $x !== $a));
                    $map[$a] = array_values(array_unique(array_merge($map[$a] ?? [], $others)));
                }
            }
        }
        return $map;
    }

    /** @return array<int,array{kind:string,term:string,alts:string[]}> */
    public static function parseRules(string $text): array
    {
        $out = [];
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (str_contains($line, '=')) {
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                $alts = self::split($v);
                if ($k !== '' && $alts) {
                    $out[] = ['kind' => 'one', 'term' => mb_substr($k, 0, 200), 'alts' => $alts];
                }
            } else {
                $alts = self::split($line);
                if (count($alts) >= 2) {
                    $out[] = ['kind' => 'set', 'term' => '', 'alts' => $alts];
                }
            }
        }
        return $out;
    }

    private function saveFlag(): void
    {
        $params = ComponentHelper::getParams('com_aisearch');
        $params->set('syn_migrated', 1);
        $params->set('synonyms', '');
        $ext = Table::getInstance('extension');
        $ext->load(ComponentHelper::getComponent('com_aisearch')->id);
        $ext->params = (string) $params;
        $ext->store();
    }

    /** Moves the old text box into the table (once). @return int rules moved */
    public function migrate(): int
    {
        if ($this->migrated()) {
            return 0;
        }
        $n = 0;
        $starter = array_map('trim', preg_split('/\R/u', Settings::DEFAULT_SYNONYMS) ?: []);
        foreach (self::parseRules($this->s->synonyms()) as $r) {
            $line = $r['kind'] === 'one' ? $r['term'] . ' = ' . implode(', ', $r['alts']) : implode(', ', $r['alts']);
            $this->insert($r['kind'], $r['term'], $r['alts'], in_array($line, $starter, true) ? 'starter' : 'manual', '');
            $n++;
        }
        $this->saveFlag();
        $this->map = null;
        return $n;
    }

    private function insert(string $kind, string $term, array $alts, string $source, string $note, int $enabled = 1): int
    {
        $now = gmdate('Y-m-d H:i:s');
        $row = (object) ['kind' => $kind, 'term' => $term, 'alts' => implode(', ', $alts), 'enabled' => $enabled, 'source' => $source, 'note' => mb_substr($note, 0, 255), 'created' => $now, 'updated' => $now];
        $this->db->insertObject('#__aisearch_synonyms', $row);
        return (int) $this->db->insertid();
    }

    /** @return array{rows:array<int,object>,total:int} */
    public function find(string $q, string $source, string $status, int $offset, int $limit): array
    {
        $db = $this->db;
        $w = ['1=1'];
        if ($q !== '') {
            $like = $db->quote('%' . $db->escape($q, true) . '%', false);
            $w[] = '(term LIKE ' . $like . ' OR alts LIKE ' . $like . ' OR note LIKE ' . $like . ')';
        }
        if (in_array($source, ['manual', 'ai', 'starter', 'import', 'dashboard'], true)) {
            $w[] = 'source = ' . $db->quote($source);
        }
        if ($status === 'on' || $status === 'off') {
            $w[] = 'enabled = ' . ($status === 'on' ? 1 : 0);
        }
        $where = ' WHERE ' . implode(' AND ', $w);
        $total = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $this->t() . $where)->loadResult();
        $rows = $db->setQuery('SELECT * FROM ' . $this->t() . $where . ' ORDER BY updated DESC, id DESC', $offset, $limit)->loadObjectList() ?: [];
        return ['rows' => $rows, 'total' => $total];
    }

    public function get(int $id): ?object
    {
        return $this->db->setQuery('SELECT * FROM ' . $this->t() . ' WHERE id = ' . $id)->loadObject() ?: null;
    }

    /** @return array{total:int,on:int,ai:int} */
    public function counts(): array
    {
        $r = $this->db->setQuery('SELECT COUNT(*) n, SUM(enabled = 1) e, SUM(source = ' . $this->db->quote('ai') . ') a FROM ' . $this->t())->loadObject();
        return ['total' => (int) ($r->n ?? 0), 'on' => (int) ($r->e ?? 0), 'ai' => (int) ($r->a ?? 0)];
    }

    /** The same key for the same group of words, whatever the order or the spacing. */
    public static function setKey(ThaiTokenizer $tk, array $words): string
    {
        $n = array_map(static fn ($x) => $tk->normalize((string) $x), $words);
        sort($n);
        return implode('|', $n);
    }

    /**
     * Adds or edits a rule. A one-way rule for a phrase that already has one is merged into it (no duplicates).
     * @return array{ok:bool,id:int,message:string}
     */
    public function save(int $id, string $kind, string $term, string $alts, bool $enabled, string $note, string $source = 'manual'): array
    {
        $kind = $kind === 'set' ? 'set' : 'one';
        $term = $kind === 'set' ? '' : mb_substr(trim(preg_replace('/\s+/u', ' ', $term) ?? ''), 0, 200);
        $list = self::split($alts);
        if ($kind === 'one' && $term === '') {
            return ['ok' => false, 'id' => 0, 'message' => 'ใส่คำค้นที่ต้องการให้มีคำพ้อง'];
        }
        if ($kind === 'one') {
            $list = array_values(array_filter($list, fn ($a) => $this->tk->normalize($a) !== $this->tk->normalize($term)));
        }
        if (($kind === 'one' && !$list) || ($kind === 'set' && count($list) < 2)) {
            return ['ok' => false, 'id' => 0, 'message' => $kind === 'set' ? 'กลุ่มคำต้องมีอย่างน้อย 2 คำ' : 'ใส่คำที่ต้องการให้ค้นเจอแทนอย่างน้อยหนึ่งคำ'];
        }
        if ($id === 0 && $kind === 'one') {
            $same = $this->db->setQuery('SELECT id, alts FROM ' . $this->t() . ' WHERE kind = ' . $this->db->quote('one') . ' AND term = ' . $this->db->quote($term))->loadObject();
            if ($same) {
                $merged = array_values(array_unique(array_merge(self::split((string) $same->alts), $list)));
                $this->db->setQuery('UPDATE ' . $this->t() . ' SET alts = ' . $this->db->quote(implode(', ', $merged)) . ', updated = ' . $this->db->quote(gmdate('Y-m-d H:i:s')) . ' WHERE id = ' . (int) $same->id)->execute();
                return ['ok' => true, 'id' => (int) $same->id, 'message' => 'มีกฎของ "' . $term . '" อยู่แล้ว จึงเพิ่มคำเข้าไปในกฎเดิม'];
            }
        }
        if ($id === 0 && $kind === 'set') {
            $key = fn (array $l) => self::setKey($this->tk, $l);
            foreach ($this->db->setQuery('SELECT id, alts FROM ' . $this->t() . ' WHERE kind = ' . $this->db->quote('set'))->loadObjectList() ?: [] as $r) {
                if ($key(self::split((string) $r->alts)) === $key($list)) {
                    return ['ok' => false, 'id' => (int) $r->id, 'message' => 'มีกลุ่มคำนี้อยู่แล้ว (กฎ #' . (int) $r->id . ')'];
                }
            }
        }
        if ($id > 0) {
            $this->db->setQuery('UPDATE ' . $this->t() . ' SET kind = ' . $this->db->quote($kind) . ', term = ' . $this->db->quote($term) . ', alts = ' . $this->db->quote(implode(', ', $list))
                . ', enabled = ' . ($enabled ? 1 : 0) . ', note = ' . $this->db->quote(mb_substr($note, 0, 255)) . ', updated = ' . $this->db->quote(gmdate('Y-m-d H:i:s')) . ' WHERE id = ' . $id)->execute();
            return ['ok' => true, 'id' => $id, 'message' => 'บันทึกแล้ว'];
        }
        return ['ok' => true, 'id' => $this->insert($kind, $term, $list, $source, $note, $enabled ? 1 : 0), 'message' => 'เพิ่มแล้ว'];
    }

    /** @param int[] $ids */
    public function bulk(array $ids, string $action): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }
        $in = implode(',', $ids);
        $db = $this->db;
        if ($action === 'delete') {
            $db->setQuery('DELETE FROM ' . $this->t() . ' WHERE id IN (' . $in . ')')->execute();
        } elseif ($action === 'enable' || $action === 'disable') {
            $db->setQuery('UPDATE ' . $this->t() . ' SET enabled = ' . ($action === 'enable' ? 1 : 0) . ', updated = ' . $db->quote(gmdate('Y-m-d H:i:s')) . ' WHERE id IN (' . $in . ')')->execute();
        } else {
            return 0;
        }
        return $db->getAffectedRows();
    }

    /** @return array{added:int,skipped:int} */
    public function import(string $text, string $source = 'import'): array
    {
        $added = 0; $skipped = 0;
        foreach (self::parseRules($text) as $r) {
            $res = $this->save(0, $r['kind'], $r['term'], implode(', ', $r['alts']), true, '', $source);
            $res['ok'] ? $added++ : $skipped++;
        }
        return ['added' => $added, 'skipped' => $skipped];
    }

    public function export(): string
    {
        $out = [];
        foreach ($this->db->setQuery('SELECT kind, term, alts, enabled FROM ' . $this->t() . ' ORDER BY id')->loadObjectList() ?: [] as $r) {
            $line = $r->kind === 'set' ? (string) $r->alts : $r->term . ' = ' . $r->alts;
            $out[] = ((int) $r->enabled === 1 ? '' : '# (ปิดอยู่) ') . $line;
        }
        return implode("\n", $out) . "\n";
    }

    /** Which rules fire for this search, and the words they add. @return array{rules:array<int,array{id:int,kind:string,term:string,alts:string,enabled:bool}>,words:string[]} */
    public function test(string $q): array
    {
        $qn = $this->tk->normalize($q);
        $rules = []; $words = [];
        foreach ($this->db->setQuery('SELECT id, kind, term, alts, enabled FROM ' . $this->t() . ' ORDER BY id')->loadObjectList() ?: [] as $r) {
            $alts = self::split((string) $r->alts);
            $keys = $r->kind === 'set' ? $alts : [(string) $r->term];
            foreach ($keys as $k) {
                $kn = $this->tk->normalize($k);
                if ($kn !== '' && mb_strpos($qn, $kn) !== false) {
                    $add = $r->kind === 'set' ? array_values(array_filter($alts, static fn ($x) => $x !== $k)) : $alts;
                    $rules[] = ['id' => (int) $r->id, 'kind' => (string) $r->kind, 'term' => $r->kind === 'set' ? $k : (string) $r->term, 'alts' => implode(', ', $add), 'enabled' => (int) $r->enabled === 1];
                    if ((int) $r->enabled === 1) {
                        $words = array_merge($words, $add);
                    }
                    break;
                }
            }
        }
        return ['rules' => $rules, 'words' => array_values(array_unique($words))];
    }

    /**
     * How many searches of the last 30 days contained each rule's words.
     * @param object[] $rows @return array<int,int> rule id => searches
     */
    public function usage(array $rows): array
    {
        if (!$rows) {
            return [];
        }
        try {
            $logs = $this->db->setQuery('SELECT q, COUNT(*) n FROM ' . $this->db->quoteName('#__aisearch_log') . ' WHERE created >= ' . $this->db->quote(gmdate('Y-m-d H:i:s', time() - 30 * 86400)) . ' GROUP BY q ORDER BY n DESC LIMIT 4000')->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
        $norm = [];
        foreach ($logs as $l) {
            $norm[] = [$this->tk->normalize((string) $l->q), (int) $l->n];
        }
        $out = [];
        foreach ($rows as $r) {
            $keys = $r->kind === 'set' ? self::split((string) $r->alts) : [(string) $r->term];
            $keys = array_filter(array_map(fn ($k) => $this->tk->normalize($k), $keys));
            $n = 0;
            foreach ($norm as [$q, $c]) {
                foreach ($keys as $k) {
                    if (mb_strpos($q, $k) !== false) { $n += $c; break; }
                }
            }
            $out[(int) $r->id] = $n;
        }
        return $out;
    }
}
