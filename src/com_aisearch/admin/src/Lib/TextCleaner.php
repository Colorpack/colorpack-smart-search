<?php
declare(strict_types=1);
namespace Colorpack\Component\AiSearch\Administrator\Lib;

\defined('_JEXEC') or die;

final class TextCleaner
{
    public static function clean(string $h): string
    {
        $h = preg_replace('~<(script|style)\b.*?</\1>~is', ' ', $h) ?? $h;
        $h = preg_replace('~\{[a-z0-9_]+[^{}]*\}~i', ' ', $h) ?? $h;
        $h = preg_replace('~</(p|div|h[1-6]|li|br)>|<br\s*/?>~i', "\n", $h) ?? $h;
        $h = html_entity_decode(strip_tags($h), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $h = str_replace("\xC2\xA0", ' ', $h);
        $h = preg_replace('~https?://\S+~', ' ', $h) ?? $h;
        return trim(preg_replace('/\s+/u', ' ', $h) ?? $h);
    }
}
