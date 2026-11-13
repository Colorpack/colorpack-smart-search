<?php
declare(strict_types=1);

namespace Colorpack\Component\AiSearch\Administrator\Lib;

/**
 * Normalises and segments Thai/English text using PHP intl's Thai dictionary.
 */
final class ThaiTokenizer
{
    private static ?\IntlBreakIterator $iterator = null;

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
        $it = self::$iterator ??= \IntlBreakIterator::createWordInstance('th');   // creating it is slow: once per request
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

    /** Joins two neighbouring words into one index term. Used to tell a compound ("เบา" + "หวาน") from the same words far apart. */
    public const JOIN = "\u{00B7}";

    /**
     * The tokens plus one extra term for every pair of neighbours. ICU splits many Thai compounds into pieces
     * (เบาหวาน -> เบา, หวาน), so the pair term is what lets the search see that the pieces stood together.
     * @param string[] $tokens @return string[]
     */
    public static function pairs(array $tokens): array
    {
        $out = [];
        for ($i = 0, $n = count($tokens) - 1; $i < $n; $i++) {
            $out[] = $tokens[$i] . self::JOIN . $tokens[$i + 1];
        }
        return $out;
    }

    /** @param string[] $tokens @return string[] */
    public static function withPairs(array $tokens): array
    {
        $out = $tokens;
        for ($i = 0, $n = count($tokens) - 1; $i < $n; $i++) {
            $out[] = $tokens[$i] . self::JOIN . $tokens[$i + 1];
        }
        return $out;
    }
}
