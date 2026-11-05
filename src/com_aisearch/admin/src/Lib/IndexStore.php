<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Persists the BM25 index in #__aisearch_index (gz) with a fast file copy in the Joomla cache folder. */
final class IndexStore
{
    public function __construct(private DatabaseInterface $db) {}

    public function updated(): ?string
    {
        $q = $this->db->getQuery(true)->select($this->db->quoteName('updated'))
            ->from($this->db->quoteName('#__aisearch_index'))->where($this->db->quoteName('id') . ' = 1');
        return $this->db->setQuery($q)->loadResult() ?: null;
    }

    private function cacheFile(): string
    {
        return JPATH_CACHE . '/com_aisearch_index.bin';
    }

    public function load(): ?Bm25
    {
        $updated = $this->updated();
        if ($updated === null) {
            return null;
        }
        $file = $this->cacheFile();
        if (is_file($file) && ($fh = fopen($file, 'rb'))) {
            $head = trim((string) fgets($fh));
            if ($head === $updated) {
                $raw = stream_get_contents($fh);
                fclose($fh);
                $bm = @unserialize($raw, ['allowed_classes' => [Bm25::class]]);
                if ($bm instanceof Bm25) {
                    return $bm;
                }
            } else {
                fclose($fh);
            }
        }
        $q = $this->db->getQuery(true)->select($this->db->quoteName('data'))
            ->from($this->db->quoteName('#__aisearch_index'))->where($this->db->quoteName('id') . ' = 1');
        $blob = $this->db->setQuery($q)->loadResult();
        $raw = $blob ? @gzuncompress($blob) : false;
        if ($raw === false) {
            return null;
        }
        @file_put_contents($file, $updated . "\n" . $raw, LOCK_EX);
        $bm = @unserialize($raw, ['allowed_classes' => [Bm25::class]]);
        return $bm instanceof Bm25 ? $bm : null;
    }

    public function save(Bm25 $bm): void
    {
        $raw = serialize($bm);
        $now = gmdate('Y-m-d H:i:s');
        $o = (object) ['id' => 1, 'data' => gzcompress($raw, 6), 'updated' => $now];
        $this->db->setQuery('DELETE FROM ' . $this->db->quoteName('#__aisearch_index') . ' WHERE id = 1')->execute();
        $this->db->insertObject('#__aisearch_index', $o);
        @file_put_contents($this->cacheFile(), $now . "\n" . $raw, LOCK_EX);
    }
}
