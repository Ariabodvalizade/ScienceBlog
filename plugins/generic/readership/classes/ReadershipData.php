<?php

/**
 * @file plugins/generic/readership/classes/ReadershipData.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ReadershipData
 *
 * @brief Readership figures for one journal, read from the usage statistics
 *  OJS already records (no extra tracking):
 *   - metrics_submission: article page views and file downloads, per day;
 *   - metrics_context / metrics_issue: journal home and issue page views;
 *   - metrics_counter_submission_*: unique readers per article (COUNTER);
 *   - metrics_submission_geo_*: readers per country (needs country
 *     statistics switched on).
 *  OJS processes visitor logs once a day, so these numbers update daily.
 *  "Online now" is live: recent sessions, with IP addresses resolved to
 *  countries only and never stored or shown.
 */

namespace APP\plugins\generic\readership\classes;

use APP\core\Application;
use APP\facades\Repo;
use DateTime;
use GeoIp2\Database\Reader;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use PKP\core\Core;
use PKP\facades\Locale;
use PKP\statistics\PKPStatisticsHelper;
use Throwable;

class ReadershipData
{
    public const PERIODS = ['all', '12m', '30d'];

    /** Minutes a session counts as "online" after its last request. */
    public const ONLINE_MINUTES = 5;

    protected const VIEW = Application::ASSOC_TYPE_SUBMISSION;
    protected const DOWNLOADS = [Application::ASSOC_TYPE_SUBMISSION_FILE, Application::ASSOC_TYPE_SUBMISSION_FILE_COUNTER_OTHER];

    /** Names for codes that are not in the ISO 3166 list. */
    protected const EXTRA_NAMES = ['XK' => 'Kosovo', 'EU' => 'Europe', 'AP' => 'Asia/Pacific'];

    public function __construct(protected int $contextId)
    {
    }

    /**
     * Everything the Readership page shows for a period, cached for an hour.
     */
    public function report(string $period): array
    {
        $period = in_array($period, self::PERIODS, true) ? $period : '12m';
        return Cache::remember("readership-report-{$this->contextId}-{$period}", 3600, fn () => $this->compute($period));
    }

    /**
     * The public "Readers around the world" figures (all time), cached for six hours.
     */
    public function publicSummary(): array
    {
        return Cache::remember("readership-public-{$this->contextId}", 6 * 3600, function () {
            $countries = $this->countries('all');
            return [
                'totals' => $this->totals('all') + ['countries' => count($countries)],
                'countries' => $countries,
            ];
        });
    }

    protected function compute(string $period): array
    {
        $countries = $this->countries($period);
        $top = array_slice($countries, 0, 20, true);
        $byCountry = $this->countryArticles($period, array_keys($top));
        foreach ($top as $code => &$row) {
            $row['articles'] = $byCountry[$code] ?? [];
        }
        unset($row);

        return [
            'period' => $period,
            'since' => $this->start($period)?->format('Y-m-d'),
            'totals' => $this->totals($period) + ['countries' => count($countries)],
            'countries' => $countries,
            'topCountries' => $top,
            'articles' => $this->topArticles($period, 10),
            'pages' => $this->pages($period),
            'trend' => $this->trend(),
            'generated' => time(),
        ];
    }

    /**
     * Start of the period, or null for all time.
     */
    public function start(string $period): ?DateTime
    {
        return match ($period) {
            '30d' => new DateTime('-29 days midnight'),
            '12m' => new DateTime('first day of -11 months midnight'),
            default => null,
        };
    }

    /**
     * Page views, downloads and unique readers.
     */
    public function totals(string $period): array
    {
        $views = (int) $this->submissionMetrics($period)->where('assoc_type', self::VIEW)->sum('metric');
        $downloads = (int) $this->submissionMetrics($period)->whereIn('assoc_type', self::DOWNLOADS)->sum('metric');
        $home = (int) $this->dated('metrics_context', $period)->sum('metric');
        $issues = (int) $this->dated('metrics_issue', $period)->whereNull('issue_galley_id')->sum('metric');
        $issueDownloads = (int) $this->dated('metrics_issue', $period)->whereNotNull('issue_galley_id')->sum('metric');

        return [
            'views' => $views + $home + $issues,
            'downloads' => $downloads + $issueDownloads,
            'readers' => (int) $this->counter($period)->sum('metric_investigations_unique'),
        ];
    }

    /**
     * Readers per country, most first: [code => [code, name, readers, views]].
     */
    public function countries(string $period): array
    {
        $rows = $this->geo($period)
            ->where('country', '<>', '')
            ->select('country')
            ->selectRaw('SUM(metric_unique) AS readers, SUM(metric) AS views')
            ->groupBy('country')
            ->orderByDesc('readers')
            ->get();

        $total = max(1, (int) $rows->sum('readers'));
        $out = [];
        foreach ($rows as $row) {
            $code = strtoupper($row->country);
            $out[$code] = [
                'code' => $code,
                'name' => self::countryName($code),
                'readers' => (int) $row->readers,
                'views' => (int) $row->views,
                'share' => round(100 * $row->readers / $total, 1),
            ];
        }
        return $out;
    }

    /**
     * The three most-read articles for each of the given countries.
     */
    protected function countryArticles(string $period, array $codes): array
    {
        if (!$codes) {
            return [];
        }
        $rows = $this->geo($period)
            ->whereIn('country', $codes)
            ->select('country', 'submission_id')
            ->selectRaw('SUM(metric_unique) AS readers')
            ->groupBy('country', 'submission_id')
            ->orderByDesc('readers')
            ->get();

        $out = [];
        foreach ($rows as $row) {
            $code = strtoupper($row->country);
            if (count($out[$code] ?? []) < 3 && ($article = $this->article((int) $row->submission_id))) {
                $out[$code][] = $article + ['readers' => (int) $row->readers];
            }
        }
        return $out;
    }

    /**
     * Most-viewed articles with their views and downloads.
     */
    public function topArticles(string $period, int $limit): array
    {
        $rows = $this->submissionMetrics($period)
            ->select('submission_id')
            ->selectRaw('SUM(CASE WHEN assoc_type = ? THEN metric ELSE 0 END) AS views', [self::VIEW])
            ->selectRaw('SUM(CASE WHEN assoc_type IN (?, ?) THEN metric ELSE 0 END) AS downloads', self::DOWNLOADS)
            ->groupBy('submission_id')
            ->orderByDesc('views')
            ->orderByDesc('downloads')
            ->limit($limit * 2)
            ->get();

        $out = [];
        foreach ($rows as $row) {
            if (count($out) < $limit && ($article = $this->article((int) $row->submission_id))) {
                $out[] = $article + ['views' => (int) $row->views, 'downloads' => (int) $row->downloads];
            }
        }
        return $out;
    }

    /**
     * Views by kind of page.
     */
    public function pages(string $period): array
    {
        return [
            'home' => (int) $this->dated('metrics_context', $period)->sum('metric'),
            'issues' => (int) $this->dated('metrics_issue', $period)->whereNull('issue_galley_id')->sum('metric'),
            'articles' => (int) $this->submissionMetrics($period)->where('assoc_type', self::VIEW)->sum('metric'),
            'downloads' => (int) $this->submissionMetrics($period)->whereIn('assoc_type', self::DOWNLOADS)->sum('metric'),
        ];
    }

    /**
     * Views and downloads for each of the last twelve months.
     */
    public function trend(): array
    {
        $start = $this->start('12m');
        $months = [];
        $cursor = clone $start;
        for ($i = 0; $i < 12; $i++) {
            $months[$cursor->format('Ym')] = ['label' => $cursor->format('M'), 'year' => $cursor->format('Y'), 'views' => 0, 'downloads' => 0];
            $cursor->modify('+1 month');
        }

        $month = "DATE_FORMAT(date, '%Y%m')";
        $add = function ($rows, string $key) use (&$months) {
            foreach ($rows as $row) {
                if (isset($months[$row->month])) {
                    $months[$row->month][$key] += (int) $row->total;
                }
            }
        };
        $add($this->submissionMetrics('12m')->where('assoc_type', self::VIEW)->selectRaw("{$month} AS month, SUM(metric) AS total")->groupByRaw($month)->get(), 'views');
        $add($this->submissionMetrics('12m')->whereIn('assoc_type', self::DOWNLOADS)->selectRaw("{$month} AS month, SUM(metric) AS total")->groupByRaw($month)->get(), 'downloads');
        $add($this->dated('metrics_context', '12m')->selectRaw("{$month} AS month, SUM(metric) AS total")->groupByRaw($month)->get(), 'views');
        $add($this->dated('metrics_issue', '12m')->whereNull('issue_galley_id')->selectRaw("{$month} AS month, SUM(metric) AS total")->groupByRaw($month)->get(), 'views');

        $max = max(1, ...array_map(fn ($m) => $m['views'] + $m['downloads'], $months));
        foreach ($months as &$m) {
            $m['height'] = round(100 * ($m['views'] + $m['downloads']) / $max, 1);
            $m['viewsShare'] = $m['views'] + $m['downloads'] ? round(100 * $m['views'] / ($m['views'] + $m['downloads']), 1) : 0;
        }
        unset($m);
        return array_values($months);
    }

    /**
     * People on the website right now (site-wide), and their countries.
     *
     * @param bool $resolveCountries Look up countries (only when country statistics are on)
     */
    public static function online(bool $resolveCountries): array
    {
        $key = 'readership-online-' . (int) $resolveCountries;
        return Cache::remember($key, 30, function () use ($resolveCountries) {
            $rows = DB::table('sessions')
                ->where('last_activity', '>=', time() - self::ONLINE_MINUTES * 60)
                ->select('ip_address', 'user_agent')
                ->get();

            $visitors = [];
            foreach ($rows as $row) {
                $agent = (string) $row->user_agent;
                if ($agent === '' || !$row->ip_address || Core::isUserAgentBot($agent)) {
                    continue;
                }
                $visitors[$row->ip_address . '|' . md5($agent)] = $row->ip_address;
            }

            $countries = [];
            $reader = $resolveCountries ? self::geoReader() : null;
            if ($reader) {
                foreach (array_unique($visitors) as $ip) {
                    try {
                        $code = $reader->city($ip)->country->isoCode;
                    } catch (Throwable $e) {
                        $code = null;
                    }
                    if ($code) {
                        $countries[$code] = ($countries[$code] ?? 0) + 1;
                    }
                }
                arsort($countries);
            }

            $list = [];
            foreach ($countries as $code => $count) {
                $list[] = ['code' => $code, 'name' => self::countryName($code), 'count' => $count];
            }
            return ['count' => count($visitors), 'countries' => $list, 'time' => time()];
        });
    }

    /**
     * Adds a formatted copy ("viewsF" = "12,345") of every count in the report.
     */
    public static function withFormatted(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::withFormatted($value);
            } elseif (is_int($value) && in_array($key, ['views', 'downloads', 'readers', 'countries', 'home', 'issues', 'articles', 'count'], true)) {
                $data["{$key}F"] = number_format($value);
            }
        }
        return $data;
    }

    public static function countryName(string $code): string
    {
        try {
            $country = Locale::getCountries()->getByAlpha2($code);
            if ($country) {
                // "Iran" rather than "Iran, Islamic Republic of" (common names are English)
                $common = str_starts_with(Locale::getLocale(), 'en') ? $country->getCommonName() : null;
                return $common ?: $country->getLocalName();
            }
        } catch (Throwable $e) {
        }
        return self::EXTRA_NAMES[$code] ?? $code;
    }

    protected static function geoReader(): ?Reader
    {
        $path = PKPStatisticsHelper::getGeoDBPath();
        if (!is_file($path)) {
            return null;
        }
        try {
            return new Reader($path);
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Title and link of a published article in this journal, or null.
     */
    protected function article(int $submissionId): ?array
    {
        static $cache = [];
        if (!array_key_exists($submissionId, $cache)) {
            $cache[$submissionId] = null;
            $submission = Repo::submission()->get($submissionId);
            if ($submission && (int) $submission->getData('contextId') === $this->contextId) {
                $publication = $submission->getCurrentPublication();
                $request = Application::get()->getRequest();
                $cache[$submissionId] = [
                    'id' => $submissionId,
                    'title' => $publication ? $publication->getLocalizedFullTitle(null, 'html') : '',
                    'authors' => $publication ? $publication->getShortAuthorString() : '',
                    'url' => $request->getDispatcher()->url($request, Application::ROUTE_PAGE, null, 'article', 'view', [$submission->getBestId()]),
                ];
            }
        }
        return $cache[$submissionId];
    }

    protected function submissionMetrics(string $period): Builder
    {
        return $this->dated('metrics_submission', $period);
    }

    /**
     * A daily metrics table for this journal, limited to the period.
     */
    protected function dated(string $table, string $period): Builder
    {
        $query = DB::table($table)->where('context_id', $this->contextId);
        if ($start = $this->start($period)) {
            $query->where('date', '>=', $start->format('Y-m-d'));
        }
        return $query;
    }

    /**
     * Geo statistics: daily rows for the last 30 days (OJS keeps the current
     * and previous month), monthly rows otherwise.
     */
    protected function geo(string $period): Builder
    {
        if ($period === '30d') {
            return $this->dated('metrics_submission_geo_daily', $period);
        }
        $query = DB::table('metrics_submission_geo_monthly')->where('context_id', $this->contextId);
        if ($start = $this->start($period)) {
            $query->where('month', '>=', (int) $start->format('Ym'));
        }
        return $query;
    }

    /**
     * COUNTER statistics (unique readers), daily or monthly like geo().
     */
    protected function counter(string $period): Builder
    {
        if ($period === '30d') {
            return $this->dated('metrics_counter_submission_daily', $period);
        }
        $query = DB::table('metrics_counter_submission_monthly')->where('context_id', $this->contextId);
        if ($start = $this->start($period)) {
            $query->where('month', '>=', (int) $start->format('Ym'));
        }
        return $query;
    }
}
