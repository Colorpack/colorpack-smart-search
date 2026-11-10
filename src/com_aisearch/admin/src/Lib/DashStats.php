<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\Database\DatabaseInterface;

/** Numbers for the dashboard, all from the search log and Joomla's own tables. Never the AI. */
final class DashStats
{
    public function __construct(private DatabaseInterface $db) {}

    private function q(string $sql): array
    {
        try {
            return $this->db->setQuery($sql)->loadObjectList() ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array{searches:int,zero:int,tracked:int,clicked:int,pos:float} one period [from, to) */
    public function period(int $fromTs, int $toTs): array
    {
        $db = $this->db;
        $w = 'created >= ' . $db->quote(gmdate('Y-m-d H:i:s', $fromTs)) . ' AND created < ' . $db->quote(gmdate('Y-m-d H:i:s', $toTs));
        $r = $this->q('SELECT COUNT(*) n, SUM(results = 0) z, SUM(tracked = 1) t, SUM(tracked = 1 AND clicked = 1) c, AVG(NULLIF(click_pos, 0)) p FROM ' . $db->quoteName('#__aisearch_log') . ' WHERE ' . $w);
        $r = $r[0] ?? (object) [];
        return ['searches' => (int) ($r->n ?? 0), 'zero' => (int) ($r->z ?? 0), 'tracked' => (int) ($r->t ?? 0), 'clicked' => (int) ($r->c ?? 0), 'pos' => (float) ($r->p ?? 0)];
    }

    /** @return array<string,mixed> */
    public function collect(int $days): array
    {
        $db = $this->db;
        $now = time();
        $log = $db->quoteName('#__aisearch_log');
        $since = $db->quote(gmdate('Y-m-d H:i:s', $now - $days * 86400));
        $out = [
            'days' => $days,
            'cur' => $this->period($now - $days * 86400, $now + 60),
            'prev' => $this->period($now - 2 * $days * 86400, $now - $days * 86400),
        ];
        $out['top'] = $this->q('SELECT q, COUNT(*) n, AVG(results) r, SUM(tracked = 1) t, SUM(tracked = 1 AND clicked = 1) c FROM ' . $log . ' WHERE created >= ' . $since . ' GROUP BY q ORDER BY n DESC LIMIT 25');
        $out['zero'] = $this->q('SELECT q, COUNT(*) n FROM ' . $log . ' WHERE results = 0 AND created >= ' . $since . ' GROUP BY q ORDER BY n DESC LIMIT 12');
        $out['noclick'] = $this->q('SELECT q, COUNT(*) n FROM ' . $log . ' WHERE created >= ' . $since . ' AND tracked = 1 AND results > 0 GROUP BY q HAVING n >= 3 AND SUM(clicked) = 0 ORDER BY n DESC LIMIT 10');
        $days_ = [];
        foreach ($this->q('SELECT DATE(created) d, COUNT(*) n FROM ' . $log . ' WHERE created >= ' . $since . ' GROUP BY DATE(created)') as $r) {
            $days_[(string) $r->d] = (int) $r->n;
        }
        $series = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = gmdate('Y-m-d', $now - $i * 86400);
            $series[] = $days_[$d] ?? 0;
        }
        $out['series'] = $series;
        $out['health'] = $this->health();
        return $out;
    }

    /** What the site needs for the search to be live: each item is ['ok'=>bool,'label'=>string,'hint'=>string] */
    public function health(): array
    {
        $db = $this->db;
        $scalar = function (string $sql): int {
            try {
                return (int) $this->db->setQuery($sql)->loadResult();
            } catch (\Throwable $e) {
                return 0;
            }
        };
        $docs = $scalar('SELECT COUNT(*) FROM ' . $db->quoteName('#__aisearch_docs'));
        $pub = $scalar('SELECT COUNT(*) FROM ' . $db->quoteName('#__content') . ' WHERE state = 1');
        $menu = $scalar('SELECT COUNT(*) FROM ' . $db->quoteName('#__menu') . " WHERE published = 1 AND client_id = 0 AND link LIKE 'index.php?option=com_aisearch%'");
        $bridge = $scalar('SELECT COUNT(*) FROM ' . $db->quoteName('#__extensions') . " WHERE type = 'plugin' AND folder = 'system' AND element = 'smartsearchbridge' AND enabled = 1");
        $mod = $scalar('SELECT COUNT(*) FROM ' . $db->quoteName('#__modules') . " WHERE module = 'mod_smartsearch' AND published = 1");
        return [
            'docs' => $docs, 'published' => $pub,
            'items' => [
                ['ok' => $docs > 0, 'key' => 'index', 'label' => 'สร้างดัชนีค้นหาแล้ว', 'hint' => 'กด "สร้างดัชนี" ในแท็บ ระบบ'],
                ['ok' => $menu > 0, 'key' => 'menu', 'label' => 'มีหน้าค้นหาในเมนู', 'hint' => 'สร้างรายการเมนูชนิด Colorpack Smart Search เพื่อให้มีหน้าผลลัพธ์'],
                ['ok' => $bridge > 0, 'key' => 'bridge', 'label' => 'ช่องค้นหาเดิมของเว็บส่งต่อมาหน้าเรา', 'hint' => 'เปิดปลั๊กอิน System - Smart Search Bridge (ถ้าเว็บมีช่องค้นหาของเทมเพลตอยู่แล้ว)'],
                ['ok' => $mod > 0, 'key' => 'module', 'label' => 'มีช่องค้นหาแบบทันสมัยบนหน้าเว็บ', 'hint' => 'เปิดโมดูล Smart Search ในตำแหน่งที่ต้องการ (ไม่บังคับ)', 'optional' => true],
            ],
        ];
    }
}
