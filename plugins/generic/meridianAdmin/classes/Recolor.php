<?php

/**
 * @file plugins/generic/meridianAdmin/classes/Recolor.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class Recolor
 *
 * @brief Swaps the PKP brand colours in the backend stylesheets for the
 *  Meridian Admin colour tokens (--ma-*). Working on a full copy of each
 *  stylesheet, rather than adding overrides, keeps every rule in its original
 *  order, so no layout rule is accidentally overridden. Copies are written to
 *  the public files directory and regenerated whenever the source changes
 *  (e.g. after an OJS upgrade).
 */

namespace APP\plugins\generic\meridianAdmin\classes;

use PKP\core\Core;
use APP\file\PublicFileManager;

class Recolor
{
    /** Bump when MAP or the algorithm changes, to regenerate the copies. */
    public const VERSION = '1';

    /** PKP colour (lower-case, 6-digit hex) => token */
    public const MAP = [
        '006798' => '--ma-accent',        // primary
        '0082bf' => '--ma-accent-hover',  // hover
        '008acb' => '--ma-accent-hover',  // legacy link hover
        '002c40' => '--ma-accent-strong', // header, selected items
        '0c3b66' => '--ma-accent-strong',
        '01354f' => '--ma-ink',           // headings
        'd5e9f2' => '--ma-accent-soft',   // light selection
        'eaf3f8' => '--ma-accent-softer',
        'eaedee' => '--ma-canvas',        // page background
        'ebedee' => '--ma-canvas',
        'd00a6c' => '--ma-negative',      // destructive actions, required marks
        'dddddd' => '--ma-line',
        'bbbbbb' => '--ma-field',
    ];

    /**
     * URL of the recoloured copy of a stylesheet, or null if it can't be written.
     */
    public static function copyUrl(string $name, string $sourceFile, string $baseUrl): ?string
    {
        if (!is_readable($sourceFile)) {
            return null;
        }
        $hash = substr(md5($sourceFile . '|' . filemtime($sourceFile) . '|' . filesize($sourceFile) . '|' . self::VERSION), 0, 12);
        $relDir = (new PublicFileManager())->getSiteFilesPath() . '/meridianAdmin';
        $dir = Core::getBaseDir() . '/' . $relDir;
        $file = "{$name}-{$hash}.css";

        if (!is_file("{$dir}/{$file}")) {
            if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
                return null;
            }
            $css = self::css((string) file_get_contents($sourceFile));
            $tmp = "{$dir}/{$file}." . getmypid() . '.tmp';
            if (@file_put_contents($tmp, $css) === false || !@rename($tmp, "{$dir}/{$file}")) {
                @unlink($tmp);
                return null;
            }
            foreach (glob("{$dir}/{$name}-*.css") ?: [] as $old) {
                if (basename($old) !== $file) {
                    @unlink($old);
                }
            }
        }
        return "{$baseUrl}/{$relDir}/{$file}";
    }

    /**
     * Replace mapped colours inside declaration values (never in selectors or url()s).
     */
    public static function css(string $css): string
    {
        return preg_replace_callback(
            '/([{;]\s*)([-a-zA-Z]+)(\s*:\s*)([^;{}]+)/',
            fn (array $m) => $m[1] . $m[2] . $m[3] . self::value($m[4]),
            $css
        );
    }

    protected static function value(string $value): string
    {
        $parts = preg_split('/(url\((?:[^()]|\([^()]*\))*\))/i', $value, -1, PREG_SPLIT_DELIM_CAPTURE);
        foreach ($parts as $i => &$part) {
            if ($i % 2 === 1) {
                continue;
            }
            $part = preg_replace_callback('/#([0-9a-f]{6}|[0-9a-f]{3})\b/i', function (array $m) {
                $hex = strtolower($m[1]);
                if (strlen($hex) === 3) {
                    $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                }
                return isset(self::MAP[$hex]) ? 'var(' . self::MAP[$hex] . ')' : $m[0];
            }, $part);
            $part = preg_replace_callback(
                '/rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)\s*(?:[\/,]\s*(var\((?:[^()]|\([^()]*\))*\)|[\d.]+%?))?\s*\)/i',
                function (array $m) {
                    $hex = sprintf('%02x%02x%02x', $m[1], $m[2], $m[3]);
                    if (!isset(self::MAP[$hex])) {
                        return $m[0];
                    }
                    $token = 'var(' . self::MAP[$hex] . ')';
                    $alpha = $m[4] ?? '';
                    if ($alpha !== '' && !str_starts_with($alpha, 'var(')) {
                        $pct = str_ends_with($alpha, '%') ? (float) $alpha : (float) $alpha * 100;
                        if ($pct < 100) {
                            return "color-mix(in srgb, {$token} " . round($pct, 1) . '%, transparent)';
                        }
                    }
                    return $token;
                },
                $part
            );
        }
        return implode('', $parts);
    }
}
