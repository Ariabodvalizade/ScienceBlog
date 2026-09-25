<?php

/**
 * @file plugins/generic/scholarlyReader/classes/ReferenceFormatter.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ReferenceFormatter
 *
 * @brief Reference-list helpers: link DOIs and URLs inside raw reference text,
 *  build Google Scholar / Crossref look-up URLs, and index references by
 *  first-author surname + year for author–year citation linking.
 */

namespace APP\plugins\generic\scholarlyReader\classes;

use PKP\core\PKPString;

class ReferenceFormatter
{
    public const DOI_PATTERN = '#\b(10\.\d{4,9}/[^\s"<>]+)#u';

    /** A surname: capitalised word(s), optionally with particles ("Percie du Sert", "van der Berg"). */
    public const SURNAME = "(?:(?:van|von|de|der|den|du|da|di|dos|del|le|la)\\s+)*[\\p{Lu}][\\p{L}'’\\-]+(?:\\s+(?:van|von|de|der|den|du|da|di|dos|del|le|la))*(?:\\s+[\\p{Lu}]\\p{Ll}[\\p{L}'’\\-]+)?";

    /**
     * Reference text as safe HTML with DOIs and URLs linked.
     */
    public function toHtml(string $raw): string
    {
        $raw = trim($raw);
        // Editors sometimes paste references that already contain links.
        if (stripos($raw, '<a ') !== false) {
            return PKPString::stripUnsafeHtml($raw);
        }

        $html = htmlspecialchars($raw, ENT_QUOTES, 'UTF-8');
        $tokens = [];

        // 1. Explicit URLs (including https://doi.org/…)
        $html = preg_replace_callback(
            '#\bhttps?://[^\s<>"]+#iu',
            function (array $m) use (&$tokens): string {
                $url = $this->trimTrailing($m[0]);
                $trail = substr($m[0], strlen($url));
                $tokens[] = '<a href="' . $url . '" rel="noopener">' . $url . '</a>';
                return "\u{E000}" . (count($tokens) - 1) . "\u{E001}" . $trail;
            },
            $html
        );

        // 2. Bare DOIs ("doi:10.1234/abc" or "10.1234/abc")
        $html = preg_replace_callback(
            '#(?:\bdoi:\s*)?' . substr(self::DOI_PATTERN, 1, -2) . '#iu',
            function (array $m) use (&$tokens): string {
                $doi = $this->trimTrailing($m[1]);
                $trail = substr($m[1], strlen($doi));
                $tokens[] = '<a href="https://doi.org/' . $doi . '" rel="noopener">' . ($m[0] === $m[1] ? $doi : 'doi:' . $doi) . '</a>';
                return "\u{E000}" . (count($tokens) - 1) . "\u{E001}" . $trail;
            },
            $html
        );

        return preg_replace_callback("#\u{E000}(\d+)\u{E001}#u", fn ($m) => $tokens[(int) $m[1]], $html);
    }

    /**
     * The DOI contained in a reference, if any.
     */
    public function extractDoi(string $raw): ?string
    {
        if (preg_match(self::DOI_PATTERN, $raw, $m)) {
            return $this->trimTrailing($m[1]);
        }
        return null;
    }

    /**
     * Strip sentence punctuation from the end of a URL/DOI, keeping balanced
     * parentheses (e.g. 10.1016/S0140-6736(86)90837-8).
     */
    protected function trimTrailing(string $value): string
    {
        while ($value !== '') {
            $last = substr($value, -1);
            if (in_array($last, ['.', ',', ';', ':'], true)) {
                $value = substr($value, 0, -1);
            } elseif ($last === ')' && substr_count($value, '(') < substr_count($value, ')')) {
                $value = substr($value, 0, -1);
            } else {
                break;
            }
        }
        return $value;
    }

    /**
     * Plain-text query for search engines (no URLs/DOIs, max 300 chars).
     */
    public function searchQuery(string $raw): string
    {
        $text = strip_tags($raw);
        $text = preg_replace('#https?://\S+#i', '', $text);
        $text = preg_replace('#(doi:\s*)?10\.\d{4,9}/\S+#i', '', $text);
        $text = trim(preg_replace('/\s+/u', ' ', $text));
        return mb_substr($text, 0, 300);
    }

    public function googleScholarUrl(string $raw): string
    {
        return 'https://scholar.google.com/scholar?q=' . rawurlencode($this->searchQuery($raw));
    }

    public function crossrefUrl(string $raw): string
    {
        $doi = $this->extractDoi($raw);
        return 'https://search.crossref.org/search/works?from_ui=yes&q=' . rawurlencode($doi ?: $this->searchQuery($raw));
    }

    /**
     * Map "surname|year" (first author, lower-case) => reference number (1-based).
     * Ambiguous keys (two references by the same first author and year) are dropped.
     *
     * @param string[] $rawReferences
     *
     * @return array<string, int>
     */
    public function authorYearIndex(array $rawReferences): array
    {
        $index = [];
        $ambiguous = [];
        foreach (array_values($rawReferences) as $i => $raw) {
            $text = trim(strip_tags($raw));
            if (!preg_match('/\b((?:19|20)\d{2})[a-z]?\b/u', $text, $year)) {
                continue;
            }
            // First author surname: leading capitalised word(s) before a comma,
            // initials, or "et al." (Vancouver "Kilkenny C, …" and APA "Kilkenny, C., …").
            if (!preg_match('/^\s*(' . self::SURNAME . ')(?=[\s,])/u', $text, $surname)) {
                continue;
            }
            $key = mb_strtolower($surname[1]) . '|' . $year[1];
            if (isset($index[$key])) {
                $ambiguous[$key] = true;
            }
            $index[$key] = $i + 1;
            // Also index the last word of multi-word surnames ("Percie du Sert" → "sert").
            $parts = preg_split('/\s+/u', $surname[1]);
            if (count($parts) > 1) {
                $index[mb_strtolower(end($parts)) . '|' . $year[1]] ??= $i + 1;
            }
        }
        return array_diff_key($index, $ambiguous);
    }
}
