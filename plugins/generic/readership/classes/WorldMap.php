<?php

/**
 * @file plugins/generic/readership/classes/WorldMap.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class WorldMap
 *
 * @brief Renders the world map as inline SVG, each country shaded by how
 *  many readers it has (five classes, from light to the accent colour), with
 *  a tooltip per country and optional "online now" dots. The map shapes are
 *  pre-projected in assets/world.json (see tools/build-world-map.mjs), so no
 *  JavaScript is needed to draw it.
 */

namespace APP\plugins\generic\readership\classes;

class WorldMap
{
    public const CLASSES = 5;

    protected static ?array $shapes = null;

    /**
     * @param array<string, int> $values Readers per ISO alpha-2 country code
     * @param array<string, int> $live People online now per country code
     * @param string $label Accessible description of the map
     * @param callable(string $code, int $value): string $tooltip
     *
     * @return array{svg: string, legend: array}
     */
    public static function render(array $values, array $live, string $label, callable $tooltip): array
    {
        $map = self::shapes();
        $breaks = self::breaks($values);

        $land = '';
        $dots = '';
        foreach ($map['countries'] as $code => $shape) {
            $value = (int) ($values[$code] ?? 0);
            $class = 'rs-q' . self::classFor($value, $breaks);
            $title = $value ? '<title>' . htmlspecialchars($tooltip($code, $value)) . '</title>' : '';
            if (isset($shape['d'])) {
                $land .= '<path class="' . $class . '" data-code="' . $code . '" d="' . $shape['d'] . '">' . $title . '</path>';
            } elseif ($value) {
                // Small states without a shape at this scale: a marker instead
                $dots .= '<circle class="' . $class . ' rs-map__small" cx="' . $shape['c'][0] . '" cy="' . $shape['c'][1] . '" r="3">' . $title . '</circle>';
            }
        }

        $svg = '<svg class="rs-map__svg" viewBox="0 0 ' . $map['width'] . ' ' . $map['height'] . '" role="img" aria-label="' . htmlspecialchars($label) . '" preserveAspectRatio="xMidYMid meet">'
            . '<g class="rs-map__land">' . $land . '</g>'
            . '<g class="rs-map__dots">' . $dots . '</g>'
            . '<g class="rs-map__live">' . self::liveDots($live) . '</g>'
            . '</svg>';

        return ['svg' => $svg, 'legend' => self::legend($values, $breaks)];
    }

    /**
     * Pulsing dots for countries with people online now.
     *
     * @param array<string, int> $live
     */
    public static function liveDots(array $live): string
    {
        $map = self::shapes();
        $out = '';
        foreach ($live as $code => $count) {
            $shape = $map['countries'][$code] ?? null;
            if (!$shape || $count < 1) {
                continue;
            }
            [$x, $y] = $shape['c'];
            $out .= '<g class="rs-live" transform="translate(' . $x . ' ' . $y . ')"><circle class="rs-live__pulse" r="5"/><circle class="rs-live__dot" r="4"/></g>';
        }
        return $out;
    }

    /**
     * Dot positions for the page's live refresh: [code => [x, y]].
     */
    public static function positions(): array
    {
        return array_map(fn ($shape) => $shape['c'], self::shapes()['countries']);
    }

    /**
     * Class (1–5) for each value, by rank among the countries that have
     * readers, so the busiest country is always the darkest.
     *
     * @return array<int, int> value => class
     */
    protected static function breaks(array $values): array
    {
        $sorted = array_values(array_filter(array_map('intval', $values), fn ($v) => $v > 0));
        sort($sorted);
        $n = count($sorted);
        $classes = [];
        foreach ($sorted as $i => $value) {
            // Equal values share the class of the last of them
            $classes[$value] = (int) ceil(self::CLASSES * ($i + 1) / $n);
        }
        return $classes;
    }

    protected static function classFor(int $value, array $classes): int
    {
        return $value < 1 ? 0 : ($classes[$value] ?? self::CLASSES);
    }

    /**
     * Legend rows: the range of values actually in each class.
     */
    protected static function legend(array $values, array $breaks): array
    {
        $ranges = [];
        foreach ($values as $value) {
            $value = (int) $value;
            if ($value < 1) {
                continue;
            }
            $class = self::classFor($value, $breaks);
            $ranges[$class] = [
                'min' => min($ranges[$class]['min'] ?? PHP_INT_MAX, $value),
                'max' => max($ranges[$class]['max'] ?? 0, $value),
            ];
        }
        ksort($ranges);
        $legend = [];
        foreach ($ranges as $class => $range) {
            $legend[] = [
                'class' => 'rs-q' . $class,
                'label' => $range['min'] === $range['max']
                    ? number_format($range['min'])
                    : number_format($range['min']) . '–' . number_format($range['max']),
            ];
        }
        return $legend;
    }

    protected static function shapes(): array
    {
        return self::$shapes ??= json_decode(file_get_contents(dirname(__DIR__) . '/assets/world.json'), true);
    }
}
