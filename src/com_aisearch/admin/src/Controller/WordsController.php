<?php
namespace Colorpack\Component\AiSearch\Administrator\Controller;

\defined('_JEXEC') or die;

use Colorpack\Component\AiSearch\Administrator\Lib\Lexicon;
use Colorpack\Component\AiSearch\Administrator\Lib\ThaiTokenizer;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\Database\DatabaseInterface;

/** Add, switch off, delete, import and export the words of the site. A change takes effect when the index is rebuilt. */
class WordsController extends BaseController
{
    private function guard(): DatabaseInterface
    {
        $this->checkToken('request');
        $user = $this->app->getIdentity();
        if (!$user || !$user->authorise('core.manage', 'com_aisearch')) {
            throw new \RuntimeException(Text::_('JERROR_ALERTNOAUTHOR'), 403);
        }
        return Factory::getContainer()->get(DatabaseInterface::class);
    }

    private function back(string $msg, string $type = 'message'): void
    {
        $this->setRedirect('index.php?option=com_aisearch&view=words', $msg, $type);
    }

    /** @return array{0:bool,1:string} */
    private function add(DatabaseInterface $db, string $word, string $kind, string $source = 'manual'): array
    {
        $w = (new ThaiTokenizer())->normalize($word);
        $kind = $kind === 'split' ? 'split' : 'keep';
        if ($w === '' || mb_strlen($w) < 2 || mb_strlen($w) > 100 || str_contains($w, ' ')) {
            return [false, 'คำต้องเป็นคำเดียว ไม่มีช่องว่าง ยาว 2–100 ตัวอักษร'];
        }
        $exists = (int) $db->setQuery('SELECT COUNT(*) FROM ' . $db->quoteName('#__aisearch_words') . ' WHERE word = ' . $db->quote($w) . ' AND kind = ' . $db->quote($kind))->loadResult();
        if ($exists) {
            $db->setQuery('UPDATE ' . $db->quoteName('#__aisearch_words') . ' SET enabled = 1 WHERE word = ' . $db->quote($w) . ' AND kind = ' . $db->quote($kind))->execute();
            return [true, 'มีคำนี้อยู่แล้ว (เปิดใช้ให้แล้ว)'];
        }
        $o = (object) ['word' => $w, 'kind' => $kind, 'enabled' => 1, 'source' => $source, 'created' => gmdate('Y-m-d H:i:s')];
        $db->insertObject('#__aisearch_words', $o);
        return [true, 'เพิ่มแล้ว'];
    }

    public function save(): void
    {
        $db = $this->guard();
        $in = $this->app->getInput()->post;
        [$ok, $msg] = $this->add($db, (string) $in->getString('word', ''), (string) $in->getString('kind', 'keep'));
        if ($ok) {
            Lexicon::markDirty();
        }
        $this->back($msg . ($ok ? ' — มีผลเมื่อกด "อัปเดตดัชนี"' : ''), $ok ? 'message' : 'warning');
    }

    public function bulk(): void
    {
        $db = $this->guard();
        $in = $this->app->getInput()->post;
        $action = (string) $in->getString('action', '');
        $ids = array_values(array_filter(array_map('intval', (array) $in->get('cid', [], 'array'))));
        if (!$ids || !in_array($action, ['enable', 'disable', 'delete'], true)) {
            $this->back('เลือกรายการและการกระทำก่อน', 'notice');
            return;
        }
        $list = implode(',', $ids);
        if ($action === 'delete') {
            $db->setQuery('DELETE FROM ' . $db->quoteName('#__aisearch_words') . ' WHERE id IN (' . $list . ')')->execute();
        } else {
            $db->setQuery('UPDATE ' . $db->quoteName('#__aisearch_words') . ' SET enabled = ' . ($action === 'enable' ? 1 : 0) . ' WHERE id IN (' . $list . ')')->execute();
        }
        Lexicon::markDirty();
        $this->back('ทำแล้ว ' . count($ids) . ' คำ — มีผลเมื่อกด "อัปเดตดัชนี"');
    }

    /** One word per line; a word that starts with ! must never be joined. */
    public function import(): void
    {
        $db = $this->guard();
        $text = (string) $this->app->getInput()->post->getString('text', '');
        if (mb_strlen($text) > 500000) {
            $this->back('ข้อความยาวเกินไป', 'warning');
            return;
        }
        $n = 0; $skipped = 0;
        foreach (preg_split('/\R/u', $text) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') { continue; }
            $split = $line[0] === '!';
            [$ok] = $this->add($db, ltrim($line, '!'), $split ? 'split' : 'keep', 'import');
            $ok ? $n++ : $skipped++;
        }
        if ($n) {
            Lexicon::markDirty();
        }
        $this->back(sprintf('นำเข้า %d คำ' . ($skipped ? ' (ข้าม %d บรรทัดที่ไม่ถูกรูปแบบ)' : ''), $n, $skipped), $n ? 'message' : 'warning');
    }

    /** The words ticked in the list of suggestions. */
    public function addMined(): void
    {
        $db = $this->guard();
        $n = 0;
        foreach ((array) $this->app->getInput()->post->get('words', [], 'array') as $w) {
            [$ok] = $this->add($db, (string) $w, 'keep', 'mined');
            $n += $ok ? 1 : 0;
        }
        if ($n) {
            Lexicon::markDirty();
        }
        $this->back($n ? sprintf('เพิ่ม %d คำ — มีผลเมื่อกด "อัปเดตดัชนี"', $n) : 'ยังไม่ได้ติ๊กคำใด', $n ? 'message' : 'notice');
    }

    public function export(): void
    {
        $db = $this->guard();
        $lines = [];
        foreach ($db->setQuery('SELECT word, kind FROM ' . $this->q($db) . ' ORDER BY id')->loadObjectList() ?: [] as $r) {
            $lines[] = ($r->kind === 'split' ? '!' : '') . $r->word;
        }
        $this->app->setHeader('Content-Type', 'text/plain; charset=utf-8', true);
        $this->app->setHeader('Content-Disposition', 'attachment; filename="words-' . gmdate('Y-m-d') . '.txt"', true);
        $this->app->sendHeaders();
        echo implode("\n", $lines) . "\n";
        $this->app->close();
    }

    private function q(DatabaseInterface $db): string
    {
        return $db->quoteName('#__aisearch_words');
    }
}
