<?php
declare(strict_types=1);

namespace Colorpack\Component\AiSearch\Administrator\Lib;

/**
 * Normalises and segments Thai/English text using PHP intl's Thai dictionary.
 */
final class ThaiTokenizer
{
    private const THAI_DIGITS = ['๐' => '0', '๑' => '1', '๒' => '2', '๓' => '3', '๔' => '4',
        '๕' => '5', '๖' => '6', '๗' => '7', '๘' => '8', '๙' => '9'];

    /** @var array<string,true> */
    private array $stop;

    /** @param string[] $stopwords */
    public function __construct(array $stopwords = ['ที่', 'และ', 'หรือ', 'ของ', 'ใน', 'เป็น', 'ได้', 'จะ', 'มี', 'การ', 'ความ', 'ให้', 'กับ', 'แล้ว', 'ก็'])
    {
        $this->stop = array_fill_keys($stopwords, true);
    }

    public function normalize(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        if (class_exists(\Normalizer::class)) {
            $text = \Normalizer::normalize($text, \Normalizer::FORM_KC) ?: $text;
        }
        $text = strtr($text, self::THAI_DIGITS);
        $text = mb_strtolower($text, 'UTF-8');
        // collapse repeated Thai tone marks / vowels (e.g. "ไม่่" -> "ไม่", "สุขขขข" stays)
        $text = preg_replace('/([\x{0E31}\x{0E34}-\x{0E3A}\x{0E47}-\x{0E4E}])\1+/u', '$1', $text) ?? $text;
        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    /** @return string[] */
    public function tokenize(string $text): array
    {
        $text = $this->normalize($text);
        if ($text === '') {
            return [];
        }
        $it = \IntlBreakIterator::createWordInstance('th');
        $it->setText($text);
        $tokens = [];
        $start = 0;
        foreach ($it as $end) {
            $piece = trim(substr($text, $start, $end - $start));
            $start = $end;
            if ($piece === '' || !preg_match('/[\p{L}\p{N}]/u', $piece) || isset($this->stop[$piece])) {
                continue;
            }
            $tokens[] = $piece;
        }
        return $tokens;
    }
}
