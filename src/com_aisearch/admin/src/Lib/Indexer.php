<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Builds / updates the keyword index and the #__aisearch_docs display table from published articles. */
final class Indexer
{
    private ThaiTokenizer $tk;
    private IndexStore $store;
    /** tables the rows are written to: the live ones, or the staging copies while the whole index is rebuilt */
    private string $docsTable = '#__aisearch_docs';
    private string $textTable = '#__aisearch_text';
    /** for tests only: stop with an error after this many articles, to prove a failed rebuild leaves the old index alone */
    public int $failAfter = 0;

    public function __construct(private DatabaseInterface $db, ?Settings $settings = null)
    {
        Lexicon::boot($db);
        $this->tk = new ThaiTokenizer();
        $this->store = new IndexStore($db);
    }

    /** @return array{docs:int,seconds:float} */
    /**
     * Rebuilds the whole index. The new rows are written to staging copies of the tables and swapped in only when everything is done,
     * so a rebuild that fails or times out half way leaves the old index exactly as it was.
     * @param ?callable $progress called with (done, total) every few hundred articles
     */
    public function rebuildAll(?callable $progress = null): array
    {
        $t0 = microtime(true);
        @set_time_limit(0);
        $db = $this->db;
        $live = ['#__aisearch_docs', '#__aisearch_text'];
        $stage = ['#__aisearch_docs_new', '#__aisearch_text_new'];
        $quote = static fn (string $t) => $db->quoteName($t);
        foreach ($live as $k => $t) {
            $db->setQuery('DROP TABLE IF EXISTS ' . $quote($stage[$k]))->execute();
            $db->setQuery('CREATE TABLE ' . $quote($stage[$k]) . ' LIKE ' . $quote($t))->execute();
        }
        $this->docsTable = $stage[0];
        $this->textTable = $stage[1];
        // the words of the site as they are now (files, the Words page, tag and category names): this index is built with them and keeps them
        // the names of tags and categories are judged against the general words: a name that is two of them joined is a compound worth keeping
        Lexicon::$active = null;
        $base = Lexicon::collect($db);
        Lexicon::$active = new Lexicon($base['words'], $base['split']);
        try { Lexicon::mineSite($db); } catch (\Throwable $e) { }
        $base = null;
        Lexicon::$active = null;
        $collected = Lexicon::collect($db);
        Lexicon::$active = new Lexicon($collected['words'], $collected['split']);
        try {
            $bm = new Bm25();
            $total = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $quote('#__content') . ' WHERE state = 1')->loadResult();
            $tags = $this->tagsFor(null);
            $fc = $this->fcImages(null);
            $n = 0; $offset = 0; $step = 200;
            while (true) {
                $rows = $db->setQuery($this->articleQuery(null), $offset, $step)->loadObjectList();
                if (!$rows) {
                    break;
                }
                foreach ($rows as $r) {
                    $this->addRow($bm, $r, $tags[(int) $r->id] ?? [], $fc);
                    $n++;
                    if ($this->failAfter > 0 && $n >= $this->failAfter) {
                        throw new \RuntimeException('test failure after ' . $n . ' articles');
                    }
                }
                $offset += $step;
                if ($progress) {
                    $progress($n, $total);
                }
            }
            // swap: both renames in one statement, so a search never sees one new and one old table
            $db->setQuery('RENAME TABLE ' . $quote($live[0]) . ' TO ' . $quote('#__aisearch_docs_old') . ', ' . $quote($stage[0]) . ' TO ' . $quote($live[0])
                . ', ' . $quote($live[1]) . ' TO ' . $quote('#__aisearch_text_old') . ', ' . $quote($stage[1]) . ' TO ' . $quote($live[1]))->execute();
            $db->setQuery('DROP TABLE IF EXISTS ' . $quote('#__aisearch_docs_old') . ', ' . $quote('#__aisearch_text_old'))->execute();
        } catch (\Throwable $e) {
            $this->docsTable = $live[0];
            $this->textTable = $live[1];
            Lexicon::$active = null;
            Lexicon::boot($db);   // back to the lexicon of the index that is still in place
            foreach ($stage as $t) {
                try { $db->setQuery('DROP TABLE IF EXISTS ' . $quote($t))->execute(); } catch (\Throwable $e2) { }
            }
            throw $e;
        }
        $this->docsTable = $live[0];
        $this->textTable = $live[1];
        $this->store->save($bm);
        $bm = null; $tags = null; $fc = null;   // free the index before the word list is saved: the peak of a rebuild is here
        Lexicon::saveSnapshot($db, $collected);
        return ['docs' => $n, 'seconds' => round(microtime(true) - $t0, 1)];
    }

    public function updateArticle(int $id): void
    {
        $bm = $this->store->load() ?? new Bm25();
        $bm->remove($id);
        $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__aisearch_docs') . ' WHERE id = ' . (int) $id)->execute();
        $this->deleteText($id);
        $rows = $this->db->setQuery($this->articleQuery($id))->loadObjectList();
        if ($rows) {
            $this->addRow($bm, $rows[0], $this->tagsFor($id)[$id] ?? [], $this->fcImages($id));
        }
        $this->store->save($bm);
    }

    public function removeArticle(int $id): void
    {
        $bm = $this->store->load();
        if ($bm) {
            $bm->remove($id);
            $this->store->save($bm);
        }
        $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__aisearch_docs') . ' WHERE id = ' . (int) $id)->execute();
        $this->deleteText($id);
    }

    /** @return array<int,string> article id => FlexiContent image file name (only when the option is on and the table exists) */
    private function fcImages(?int $id): array
    {
        $fid = Settings::load()->imageFieldId();
        if ($fid < 1) {
            return [];
        }
        try {
            $db = $this->db;
            $q = $db->getQuery(true)->select('item_id, value')->from($db->quoteName('#__flexicontent_fields_item_relations'))
                ->where('field_id = ' . $fid)->where('valueorder = 1');
            if ($id !== null) {
                $q->where('item_id = ' . (int) $id);
            }
            $out = [];
            foreach ($db->setQuery($q)->loadObjectList() as $r) {
                $v = @unserialize((string) $r->value, ['allowed_classes' => false]);
                $name = is_array($v) ? trim((string) ($v['originalname'] ?? '')) : '';
                if ($name === '' && is_array($v)) {
                    $name = trim((string) ($v['existingname'] ?? ''));
                }
                if ($name !== '' && !str_contains($name, '/') && !str_contains($name, '..')) {
                    $out[(int) $r->item_id] = $name;
                }
            }
            return $out;
        } catch (\Throwable $e) {
            return [];   // FlexiContent not installed
        }
    }

    /** First usable <img> inside the article text (skips data: URIs and tiny icons). */
    private function firstTextImage(string $html): string
    {
        if (!preg_match_all('~<img\b[^>]*>~i', $html, $tags)) {
            return '';
        }
        foreach ($tags[0] as $tag) {
            if (!preg_match('~\bsrc\s*=\s*["\']([^"\']+)["\']~i', $tag, $m)) {
                continue;
            }
            $src = trim(html_entity_decode($m[1]));
            if ($src === '' || str_starts_with($src, 'data:')) {
                continue;
            }
            foreach (['width', 'height'] as $dim) {
                if (preg_match('~\b' . $dim . '\s*=\s*["\']?(\d+)~i', $tag, $d) && (int) $d[1] > 0 && (int) $d[1] < 80) {
                    continue 2;   // icon / spacer
                }
            }
            return $src;
        }
        return '';
    }

    /** @return array{0:string,1:string}|null */
    private function joomlaImage(array $imgs, string $key): ?array
    {
        $u = trim((string) ($imgs[$key] ?? ''));
        return $u === '' ? null : [preg_replace('/#joomlaImage:.*$/', '', $u), 'url'];
    }

    /** @return array{0:string,1:string} [image, kind] kind: fc = FlexiContent folder file (size prefix applies), url = use as is */
    private function pickImage(object $r, array $fc): array
    {
        $id = (int) $r->id;
        $fcImage = isset($fc[$id]) ? ['item_' . $id . '_field_' . Settings::load()->imageFieldId() . '/' . $fc[$id], 'fc'] : null;
        $imgs = json_decode((string) ($r->images ?? ''), true) ?: [];
        $text = $this->firstTextImage($r->introtext . ' ' . $r->fulltext);
        $cand = [
            'intro' => $this->joomlaImage($imgs, 'image_intro'),
            'fulltext' => $this->joomlaImage($imgs, 'image_fulltext'),
            'text' => $text !== '' ? [$text, 'url'] : null,
            'flexicontent' => $fcImage,
        ];
        $st = Settings::load();
        $src = $st->imageSource();
        $auto = ['intro', 'fulltext', 'text', 'flexicontent'];
        $order = $src === 'auto' ? $auto : ($st->imageFallback() ? array_merge([$src], array_diff($auto, [$src])) : [$src]);
        foreach ($order as $k) {
            if (!empty($cand[$k])) {
                return $cand[$k];
            }
        }
        return ['', ''];
    }

    private function articleQuery(?int $id)
    {
        $db = $this->db;
        $q = $db->getQuery(true)
            ->select('a.id, a.title, a.catid, a.created, a.publish_up, a.publish_down, a.access, a.language, a.metadesc, a.metakey, a.images, a.introtext, ' . $db->quoteName('a.fulltext') . ', c.title AS category')
            ->from($db->quoteName('#__content', 'a'))
            ->join('LEFT', $db->quoteName('#__categories', 'c') . ' ON c.id = a.catid')
            ->where('a.state = 1')->order('a.id');
        if ($id !== null) {
            $q->where('a.id = ' . (int) $id);
        }
        return $q;
    }

    /** @return array<int,string[]> */
    private function tagsFor(?int $id): array
    {
        $db = $this->db;
        $q = $db->getQuery(true)->select('m.content_item_id AS id, TRIM(t.title) AS title')
            ->from($db->quoteName('#__contentitem_tag_map', 'm'))
            ->join('INNER', $db->quoteName('#__tags', 't') . ' ON t.id = m.tag_id AND t.published = 1')
            ->where("m.type_alias = 'com_content.article'");
        if ($id !== null) {
            $q->where('m.content_item_id = ' . (int) $id);
        }
        $out = [];
        foreach ($db->setQuery($q)->loadObjectList() as $r) {
            $out[(int) $r->id][] = $r->title;
        }
        return $out;
    }

    private static function dateOrNull($d): ?string
    {
        $d = trim((string) $d);
        return ($d === '' || str_starts_with($d, '0000')) ? null : $d;
    }

    private function addRow(Bm25 $bm, object $r, array $tags, array $fc = []): void
    {
        $tags = array_values(array_unique($tags));
        $body = TextCleaner::clean($r->introtext . ' ' . $r->fulltext);
        // every field also indexes its neighbouring word pairs, so a compound like เบาหวาน (which ICU splits into เบา + หวาน) can be told apart from the two words far apart
        $tt = ThaiTokenizer::withPairs($this->tk->tokenize($r->title));
        $tg = ThaiTokenizer::withPairs($this->tk->tokenize(implode(' ', $tags)));
        $keys = $this->keywords((string) ($r->metakey ?? ''));   // the editor's own Meta Keywords: weighted like tags
        $kt = ThaiTokenizer::withPairs($this->tk->tokenize($keys));
        $bodyTokens = $this->tk->tokenize(mb_substr($body, 0, 6000));
        $bm->add((int) $r->id, array_merge($tt, $tt, $tt, $tg, $tg, $kt, $kt,
            ThaiTokenizer::withPairs($this->tk->tokenize(($r->category ?? '') . ' ' . $r->metadesc)),
            $bodyTokens, ThaiTokenizer::pairs(array_slice($bodyTokens, 0, 150))));   // pairs only for the opening of the article: the pairs of a whole body are most of the index and most of the memory of a rebuild
        $snippet = trim((string) $r->metadesc) !== '' ? $r->metadesc : preg_replace('/\s+/u', ' ', $body);
        [$image, $kind] = $this->pickImage($r, $fc);
        $row = (object) [
            'id' => (int) $r->id, 'catid' => (int) $r->catid, 'title' => mb_substr(trim($r->title), 0, 255),
            'category' => mb_substr((string) $r->category, 0, 255), 'tags' => implode(', ', array_slice($tags, 0, 10)),
            'created' => $r->created, 'snippet' => mb_substr((string) $snippet, 0, 200), 'image' => mb_substr($image, 0, 400), 'image_kind' => $kind,
            'publish_up' => self::dateOrNull($r->publish_up ?? null), 'publish_down' => self::dateOrNull($r->publish_down ?? null), 'access' => max(1, (int) ($r->access ?? 1)), 'lang' => mb_substr((string) ($r->language ?? '*') ?: '*', 0, 7),
        ];
        $this->db->insertObject($this->docsTable, $row);
        $this->saveText((int) $r->id, $body, $keys, (string) ($r->metadesc ?? ''));
    }

    /** Normalised article text (first 6,000 characters) for the precision mode. Never allowed to break indexing. */
    private function saveText(int $id, string $body, string $keys = '', string $desc = ''): void
    {
        // kw holds the Meta Keywords and the Meta Description, told apart by a separator, so the precision mode can count them as two fields
        $row = (object) ['id' => $id, 'body' => $this->tk->normalize(mb_substr($body, 0, 6000)), 'kw' => $this->tk->normalize($keys) . "\u{00A6}" . $this->tk->normalize($desc)];   // insertObject needs a variable
        try {
            $this->db->insertObject($this->textTable, $row);
        } catch (\Throwable $e) {
            try {   // an upgrade that has not added the kw column yet
                unset($row->kw);
                $this->db->insertObject($this->textTable, $row);
            } catch (\Throwable $e2) {
            }
        }
    }

    /** Meta Keywords as one clean string: split on commas, new lines and semicolons, repeats dropped, 30 at most. */
    private function keywords(string $raw): string
    {
        $parts = preg_split('/[,;\r\n\x{060C}]+/u', $raw) ?: [];
        $parts = array_values(array_unique(array_filter(array_map(static fn ($k) => trim((string) $k), $parts), static fn ($k) => $k !== '' && mb_strlen($k) <= 60)));
        return implode(' ', array_slice($parts, 0, 30));
    }

    private function deleteText(?int $id): void
    {
        try {
            $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__aisearch_text') . ($id === null ? '' : ' WHERE id = ' . (int) $id))->execute();
        } catch (\Throwable $e) {
        }
    }
}
