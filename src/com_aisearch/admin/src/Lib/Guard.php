<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseInterface;

/**
 * Keeps one visitor (or one bot) from using up the server or the AI budget.
 * Counters live in a small table, keyed by a hash of the visitor's address (the address itself is never stored) and a time window.
 * Every check fails open: if the table is missing or the database errors, the visitor is let through, so protection can never take search down.
 */
final class Guard
{

    public function __construct(private DatabaseInterface $db, private Settings $s) {}

    /** The visitor's address as the server sees it, or from a proxy header the administrator chose to trust (for example HTTP_CF_CONNECTING_IP). */
    public function address(): string
    {
        $server = $_SERVER;
        $hdr = $this->s->trustIpHeader();
        if ($hdr !== '' && !empty($server[$hdr])) {
            $ip = trim(explode(',', (string) $server[$hdr])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
        return (string) ($server['REMOTE_ADDR'] ?? '');
    }

    public function clientKey(): string
    {
        $secret = (string) Factory::getApplication()->get('secret', 'aisearch');
        return substr(hash('sha256', $this->address() . '|' . $secret), 0, 16);
    }

    /** Adds one to the counter of this visitor for the current window and returns the new count. */
    public function hit(string $scope, int $windowSeconds, bool $global = false): int
    {
        $bucket = intdiv(time(), max(1, $windowSeconds));
        $k = md5($scope . '|' . ($global ? '*' : $this->clientKey()) . '|' . $bucket);
        $db = $this->db;
        try {
            $db->setQuery('INSERT INTO ' . $db->quoteName('#__aisearch_hits') . ' (k, n, expires) VALUES (' . $db->quote($k) . ', 1, ' . ((int) (($bucket + 2) * $windowSeconds)) . ') ON DUPLICATE KEY UPDATE n = n + 1')->execute();
            $n = (int) $db->setQuery('SELECT n FROM ' . $db->quoteName('#__aisearch_hits') . ' WHERE k = ' . $db->quote($k))->loadResult();
            if (random_int(1, 150) === 1) {
                $db->setQuery('DELETE FROM ' . $db->quoteName('#__aisearch_hits') . ' WHERE expires < ' . time())->execute();
            }
            return $n;
        } catch (\Throwable $e) {
            return 0;   // fail open
        }
    }

    /** Is this visitor asking for searches faster than a person would? */
    public function overSearchRate(string $scope = 'search', ?int $perMinute = null): bool
    {
        $limit = $perMinute ?? $this->s->searchPerMinute();
        return $limit > 0 && $this->hit($scope, 60) > $limit;
    }

    // AI-START
    /**
     * May this request call the AI (and so cost money)? Called only when the answer is not already in the cache.
     * Bots never may. A person may a few times a minute and an hour, and the whole site has a ceiling per day,
     * so a flood of invented searches cannot use up the month's budget.
     */
    public function allowAi(): bool
    {
        if (self::isBot((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''))) {
            return false;
        }
        if ($this->s->aiPerMinute() > 0 && $this->hit('aimin', 60) > $this->s->aiPerMinute()) {
            return false;
        }
        if ($this->s->aiPerHour() > 0 && $this->hit('aihour', 3600) > $this->s->aiPerHour()) {
            return false;
        }
        if ($this->s->aiDailyCalls() > 0 && $this->hit('aiday', 86400, true) > $this->s->aiDailyCalls()) {
            return false;
        }
        return true;
    }
    // AI-END
}
