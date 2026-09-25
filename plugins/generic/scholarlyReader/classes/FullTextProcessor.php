<?php

/**
 * @file plugins/generic/scholarlyReader/classes/FullTextProcessor.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class FullTextProcessor
 *
 * @brief Turns an HTML galley into safe, inline full text:
 *  - sanitizes it with HTML Purifier (article-level whitelist);
 *  - gives h2/h3 headings stable ids and returns them as an outline;
 *  - removes the galley's own reference list (the landing page renders the
 *    references from the article metadata);
 *  - links in-text citations — numeric ("[1]", "[2, 5]", "[3–6]") and
 *    author–year ("(Smith et al., 2020; Lee and Park, 2019)") — to "#ref-N".
 */

namespace APP\plugins\generic\scholarlyReader\classes;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use DOMXPath;
use HTMLPurifier;
use HTMLPurifier_Config;

class FullTextProcessor
{
    /** Elements allowed in inline full text. */
    public const ALLOWED_HTML = 'h2,h3,h4,h5,h6,p,br,hr,div[class],span[class],'
        . 'a[href|title|class],em,strong,b,i,u,sup,sub,small,cite,code,pre,blockquote,q,abbr[title],'
        . 'ul,ol[start|type],li,dl,dt,dd,'
        . 'table[class|summary],caption,thead,tbody,tfoot,tr,th[colspan|rowspan|scope],td[colspan|rowspan],col[span],colgroup[span],'
        . 'img[src|alt|width|height|title]';

    /** Headings that introduce a galley's own reference list. */
    public const REFERENCE_HEADINGS = '/^\s*(\d+\.?\s*)?(references?|bibliography|literature cited|works cited|citations)\s*$/iu';

    /** @var array<array{level: int, id: string, text: string}> */
    protected array $outline = [];

    /** @var array<string, bool> */
    protected array $usedIds = [];

    /**
     * @param int $referenceCount Number of references on the landing page (ids ref-1 … ref-N)
     * @param array<string, int> $authorYearIndex Map "surname|year" => reference number (1-based)
     * @param string $cachePath Writable directory for HTML Purifier's definition cache
     */
    public function __construct(
        protected int $referenceCount,
        protected array $authorYearIndex = [],
        protected ?string $cachePath = null,
    ) {
    }

    /**
     * Process a full HTML document (or fragment).
     */
    public function process(string $html): string
    {
        $body = $this->extractBody($html);
        $clean = $this->purify($body);

        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="sr-root">' . $clean . '</div>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $doc->getElementById('sr-root');
        if (!$root) {
            return $clean;
        }

        if ($this->referenceCount > 0) {
            $this->removeOwnReferenceList($root);
        }
        $this->addHeadingIds($root);
        if ($this->referenceCount > 0) {
            $this->linkCitations($doc, $root);
        }
        $this->wrapTables($doc, $root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $doc->saveHTML($child);
        }
        return $out;
    }

    /**
     * @return array<array{level: int, id: string, text: string}>
     */
    public function getOutline(): array
    {
        return $this->outline;
    }

    protected function extractBody(string $html): string
    {
        if (preg_match('#<body[^>]*>(.*)</body>#is', $html, $m)) {
            return $m[1];
        }
        return $html;
    }

    protected function purify(string $html): string
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'utf-8');
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.Allowed', self::ALLOWED_HTML);
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        if ($this->cachePath && is_dir($this->cachePath) && is_writable($this->cachePath)) {
            $config->set('Cache.SerializerPath', $this->cachePath);
        } else {
            $config->set('Cache.DefinitionImpl', null);
        }
        return (new HTMLPurifier($config))->purify($html);
    }

    /**
     * Drop a "References" heading and everything after it up to the next
     * heading of the same or a higher level.
     */
    protected function removeOwnReferenceList(DOMElement $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);
        foreach ($xpath->query('.//h2|.//h3', $root) as $heading) {
            if (!preg_match(self::REFERENCE_HEADINGS, trim($heading->textContent))) {
                continue;
            }
            $level = (int) substr($heading->nodeName, 1);
            $node = $heading->nextSibling;
            $heading->parentNode->removeChild($heading);
            while ($node) {
                $next = $node->nextSibling;
                if ($node instanceof DOMElement && preg_match('/^h([1-6])$/', $node->nodeName, $m) && (int) $m[1] <= $level) {
                    break;
                }
                $node->parentNode->removeChild($node);
                $node = $next;
            }
            return;
        }
    }

    protected function addHeadingIds(DOMElement $root): void
    {
        $xpath = new DOMXPath($root->ownerDocument);
        foreach ($xpath->query('.//*[@id]', $root) as $el) {
            $this->usedIds[$el->getAttribute('id')] = true;
        }
        foreach ($xpath->query('.//h2|.//h3', $root) as $heading) {
            /** @var DOMElement $heading */
            $text = trim(preg_replace('/\s+/u', ' ', $heading->textContent));
            if ($text === '') {
                continue;
            }
            $id = $heading->getAttribute('id');
            if (!$id) {
                $id = $this->uniqueId('sec-' . $this->slug($text));
                $heading->setAttribute('id', $id);
            }
            $this->outline[] = ['level' => (int) substr($heading->nodeName, 1), 'id' => $id, 'text' => $text];
        }
    }

    protected function uniqueId(string $base): string
    {
        $id = $base;
        $i = 2;
        while (isset($this->usedIds[$id])) {
            $id = $base . '-' . $i++;
        }
        $this->usedIds[$id] = true;
        return $id;
    }

    protected function slug(string $text): string
    {
        $text = preg_replace('/^\d+(\.\d+)*\.?\s*/', '', $text);
        $slug = strtolower(trim(preg_replace('/[^\p{L}\p{N}]+/u', '-', $text), '-'));
        return substr($slug, 0, 48) ?: 'section';
    }

    /**
     * Replace citation markers in text nodes with links to the reference list.
     */
    protected function linkCitations(DOMDocument $doc, DOMElement $root): void
    {
        $xpath = new DOMXPath($doc);
        $textNodes = $xpath->query('.//text()[not(ancestor::a) and not(ancestor::code) and not(ancestor::pre) and not(ancestor::h1) and not(ancestor::h2) and not(ancestor::h3) and not(ancestor::h4)]', $root);
        $nodes = [];
        foreach ($textNodes as $node) {
            $nodes[] = $node;
        }
        foreach ($nodes as $node) {
            $this->linkTextNode($doc, $node);
        }
    }

    protected function linkTextNode(DOMDocument $doc, DOMText $node): void
    {
        $text = $node->nodeValue;
        $numeric = '\[(\s*\d{1,3}(?:\s*[-–—]\s*\d{1,3})?(?:\s*[,;]\s*\d{1,3}(?:\s*[-–—]\s*\d{1,3})?)*\s*)\]';
        $authorYear = '\(([^()]*?(?:19|20)\d{2}[a-z]?[^()]*?)\)';

        $pattern = '/' . $numeric . ($this->authorYearIndex ? '|' . $authorYear : '') . '/u';
        if (!preg_match_all($pattern, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return;
        }

        $fragment = $doc->createDocumentFragment();
        $cursor = 0;
        $changed = false;
        foreach ($matches as $match) {
            [$whole, $offset] = $match[0];
            $nodesForMatch = isset($match[1]) && $match[1][1] !== -1 && $match[1][0] !== ''
                ? $this->numericNodes($doc, $match[1][0])
                : $this->authorYearNodes($doc, $match[2][0] ?? '');
            if (!$nodesForMatch) {
                continue;
            }
            $fragment->appendChild($doc->createTextNode(substr($text, $cursor, $offset - $cursor)));
            foreach ($nodesForMatch as $n) {
                $fragment->appendChild($n);
            }
            $cursor = $offset + strlen($whole);
            $changed = true;
        }
        if (!$changed) {
            return;
        }
        $fragment->appendChild($doc->createTextNode(substr($text, $cursor)));
        $node->parentNode->replaceChild($fragment, $node);
    }

    /**
     * "[2, 4–6]" => "[", <a>2</a>, ", ", <a>4</a>, "–", <a>6</a>, "]"
     *
     * @return DOMNode[]|null
     */
    protected function numericNodes(DOMDocument $doc, string $inner): ?array
    {
        $parts = preg_split('/(\s*[,;]\s*|\s*[-–—]\s*)/u', trim($inner), -1, PREG_SPLIT_DELIM_CAPTURE);
        $nodes = [$doc->createTextNode('[')];
        foreach ($parts as $part) {
            if (ctype_digit($part)) {
                $n = (int) $part;
                if ($n < 1 || $n > $this->referenceCount) {
                    return null;
                }
                $nodes[] = $this->xref($doc, $n, $part);
            } else {
                $nodes[] = $doc->createTextNode(preg_match('/[-–—]/u', $part) ? '–' : trim($part) . ' ');
            }
        }
        $nodes[] = $doc->createTextNode(']');
        return $nodes;
    }

    /**
     * "(Smith et al., 2020; Lee and Park, 2019)" => linked segments.
     *
     * @return DOMNode[]|null
     */
    protected function authorYearNodes(DOMDocument $doc, string $inner): ?array
    {
        $segments = preg_split('/(;\s*)/u', $inner, -1, PREG_SPLIT_DELIM_CAPTURE);
        $nodes = [$doc->createTextNode('(')];
        $linked = 0;
        foreach ($segments as $segment) {
            $ref = $this->matchAuthorYear($segment);
            if ($ref) {
                $nodes[] = $this->xref($doc, $ref, $segment);
                $linked++;
            } else {
                $nodes[] = $doc->createTextNode($segment);
            }
        }
        $nodes[] = $doc->createTextNode(')');
        return $linked ? $nodes : null;
    }

    protected function matchAuthorYear(string $segment): ?int
    {
        $surname = ReferenceFormatter::SURNAME;
        $re = "/^\\s*(?:e\\.g\\.,?\\s*|see\\s+)?({$surname})(?:\\s+et\\s+al\\.?|\\s+(?:and|&)\\s+{$surname})?,?\\s+((?:19|20)\\d{2})[a-z]?\\s*$/u";
        if (!preg_match($re, $segment, $m)) {
            return null;
        }
        $key = mb_strtolower($m[1]) . '|' . $m[2];
        return $this->authorYearIndex[$key] ?? null;
    }

    protected function xref(DOMDocument $doc, int $n, string $label): DOMElement
    {
        $a = $doc->createElement('a');
        $a->setAttribute('href', '#ref-' . $n);
        $a->setAttribute('class', 'sr-xref');
        $a->setAttribute('data-ref', (string) $n);
        $a->appendChild($doc->createTextNode(trim($label)));
        return $a;
    }

    /**
     * Wrap tables in a horizontally scrollable container.
     */
    protected function wrapTables(DOMDocument $doc, DOMElement $root): void
    {
        $tables = [];
        foreach ($root->getElementsByTagName('table') as $table) {
            $tables[] = $table;
        }
        foreach ($tables as $table) {
            $wrap = $doc->createElement('div');
            $wrap->setAttribute('class', 'sr-table');
            $table->parentNode->replaceChild($wrap, $table);
            $wrap->appendChild($table);
        }
    }
}
