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

    public function __construct(private DatabaseInterface $db, ?Settings $settings = null)
    {
        $this->tk = new ThaiTokenizer();
        $this->store = new IndexStore($db);
    }

    /** @return array{docs:int,seconds:float} */
    public function rebuildAll(): array
    {
        $t0 = microtime(true);
        @set_time_limit(280);
        $bm = new Bm25();
        $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__aisearch_docs'))->execute();
        $this->deleteText(null);
        $tags = $this->tagsFor(null);
        $fc = $this->fcImages(null);
        $n = 0; $offset = 0; $step = 200;
        while (true) {
            $rows = $this->db->setQuery($this->articleQuery(null), $offset, $step)->loadObjectList();
            if (!$rows) {
                break;
            }
            foreach ($rows as $r) {
                $this->addRow($bm, $r, $tags[(int) $r->id] ?? [], $fc);
                $n++;
            }
            $offset += $step;
        }
        $this->store->save($bm);
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
            ->select('a.id, a.title, a.catid, a.created, a.publish_up, a.publish_down, a.access, a.metadesc, a.images, a.introtext, ' . $db->quoteName('a.fulltext') . ', c.title AS category')
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
        $tt = $this->tk->tokenize($r->title);
        $tg = $this->tk->tokenize(implode(' ', $tags));
        $bm->add((int) $r->id, array_merge($tt, $tt, $tt, $tg, $tg,
            $this->tk->tokenize(($r->category ?? '') . ' ' . $r->metadesc),
            $this->tk->tokenize(mb_substr($body, 0, 6000))));
        $snippet = trim((string) $r->metadesc) !== '' ? $r->metadesc : preg_replace('/\s+/u', ' ', $body);
        [$image, $kind] = $this->pickImage($r, $fc);
        $row = (object) [
            'id' => (int) $r->id, 'catid' => (int) $r->catid, 'title' => mb_substr(trim($r->title), 0, 255),
            'category' => mb_substr((string) $r->category, 0, 255), 'tags' => implode(', ', array_slice($tags, 0, 10)),
            'created' => $r->created, 'snippet' => mb_substr((string) $snippet, 0, 200), 'image' => mb_substr($image, 0, 400), 'image_kind' => $kind,
            'publish_up' => self::dateOrNull($r->publish_up ?? null), 'publish_down' => self::dateOrNull($r->publish_down ?? null), 'access' => max(1, (int) ($r->access ?? 1)),
        ];
        $this->db->insertObject('#__aisearch_docs', $row);
        $this->saveText((int) $r->id, $body);
    }

    /** Normalised article text (first 6,000 characters) for the precision mode. Never allowed to break indexing. */
    private function saveText(int $id, string $body): void
    {
        try {
            $row = (object) ['id' => $id, 'body' => $this->tk->normalize(mb_substr($body, 0, 6000))];   // insertObject needs a variable
            $this->db->insertObject('#__aisearch_text', $row);
        } catch (\Throwable $e) {
        }
    }

    private function deleteText(?int $id): void
    {
        try {
            $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__aisearch_text') . ($id === null ? '' : ' WHERE id = ' . (int) $id))->execute();
        } catch (\Throwable $e) {
        }
    }
}
