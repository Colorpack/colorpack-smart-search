<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

/**
 * Runs a full rebuild so that a slow server cannot break it. The browser starts the rebuild and then watches this job's state:
 * if the web server or a proxy gives up waiting (HTTP 504) the rebuild itself carries on (ignore_user_abort) and the page keeps asking for its progress.
 * The state is a small file in the Joomla cache folder; a job that has not reported for 2 minutes is treated as dead.
 */
final class RebuildJob
{
    private const STALE = 120;

    private static function file(): string
    {
        return JPATH_CACHE . '/com_aisearch_rebuild.json';
    }

    /** @return array{state:string,done:int,total:int,started:int,beat:int,docs:int,seconds:float,error:string} */
    public static function state(): array
    {
        $s = ['state' => 'idle', 'done' => 0, 'total' => 0, 'started' => 0, 'beat' => 0, 'docs' => 0, 'seconds' => 0.0, 'error' => ''];
        $raw = @file_get_contents(self::file());
        $j = $raw ? json_decode($raw, true) : null;
        if (is_array($j)) {
            $s = array_merge($s, $j);
        }
        if ($s['state'] === 'running' && time() - (int) $s['beat'] > self::STALE) {
            $s['state'] = 'error';
            $s['error'] = 'การสร้างดัชนีหยุดตอบสนองเกิน 2 นาที (เซิร์ฟเวอร์อาจตัดการทำงาน) ดัชนีเดิมยังอยู่ครบ ลองใหม่ หรือเพิ่มเวลา timeout ของเซิร์ฟเวอร์';
        }
        return $s;
    }

    public static function running(): bool
    {
        return self::state()['state'] === 'running';
    }

    private static function write(array $s): void
    {
        @file_put_contents(self::file(), json_encode($s), LOCK_EX);
    }

    /** @return array{docs:int,seconds:float} @throws \Throwable */
    public static function run(Indexer $ix): array
    {
        @ignore_user_abort(true);
        @set_time_limit(0);
        $s = ['state' => 'running', 'done' => 0, 'total' => 0, 'started' => time(), 'beat' => time(), 'docs' => 0, 'seconds' => 0.0, 'error' => ''];
        self::write($s);
        try {
            $r = $ix->rebuildAll(static function (int $done, int $total) use (&$s): void {
                $s['done'] = $done; $s['total'] = $total; $s['beat'] = time();
                self::write($s);
            });
        } catch (\Throwable $e) {
            $s['state'] = 'error'; $s['error'] = $e->getMessage(); $s['beat'] = time();
            self::write($s);
            throw $e;
        }
        $s['state'] = 'done'; $s['docs'] = $r['docs']; $s['seconds'] = $r['seconds']; $s['done'] = $r['docs']; $s['beat'] = time();
        self::write($s);
        return $r;
    }
}
