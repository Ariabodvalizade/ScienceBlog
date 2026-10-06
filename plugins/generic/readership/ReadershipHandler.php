<?php

/**
 * @file plugins/generic/readership/ReadershipHandler.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ReadershipHandler
 *
 * @brief The Readership page (/{journal}/readership) and its live counter
 *  (/{journal}/readership/live, JSON, polled every minute by the page).
 */

namespace APP\plugins\generic\readership;

use APP\handler\Handler;
use APP\plugins\generic\readership\classes\ReadershipData;
use APP\plugins\generic\readership\classes\WorldMap;
use APP\template\TemplateManager;
use DateTime;
use PKP\core\PKPRequest;
use PKP\security\authorization\ContextAccessPolicy;

class ReadershipHandler extends Handler
{
    /** @copydoc PKPHandler::_isBackendPage */
    public $_isBackendPage = true;

    public function __construct(protected ReadershipPlugin $plugin)
    {
        parent::__construct();
        $this->addRoleAssignment(ReadershipPlugin::ROLES, ['index', 'live']);
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextAccessPolicy($request, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * @param array $args
     * @param PKPRequest $request
     */
    public function index($args, $request)
    {
        $this->setupTemplate($request);
        $context = $request->getContext();
        $period = (string) $request->getUserVar('period');
        $period = in_array($period, ReadershipData::PERIODS, true) ? $period : '12m';

        $report = (new ReadershipData($context->getId()))->report($period);
        $geo = $this->plugin->geoEnabled($context);
        $online = ReadershipData::withFormatted(ReadershipData::online($geo));
        $countries = $report['countries'];

        $map = WorldMap::render(
            array_column($countries, 'readers', 'code'),
            array_column($online['countries'], 'count', 'code'),
            __('plugins.generic.readership.map.label'),
            fn (string $code, int $value) => __('plugins.generic.readership.tooltip', [
                'country' => $countries[$code]['name'] ?? $code,
                'count' => number_format($value),
            ])
        );

        $report = ReadershipData::withFormatted($report);
        $max = max(1, ...array_column($report['topCountries'], 'readers'));
        foreach ($report['topCountries'] as &$row) {
            $row['bar'] = round(100 * $row['readers'] / $max, 1);
        }
        unset($row);

        $periods = [];
        foreach (ReadershipData::PERIODS as $key) {
            $periods[] = [
                'key' => $key,
                'label' => __("plugins.generic.readership.period.{$key}"),
                'url' => $request->url(null, ReadershipPlugin::PAGE, null, null, ['period' => $key]),
                'current' => $key === $period,
            ];
        }

        $baseUrl = $request->getBaseUrl() . '/' . $this->plugin->getPluginPath();
        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->addStyleSheet('readership', $baseUrl . '/css/readership.css?v=' . filemtime(__DIR__ . '/css/readership.css'), [
            'contexts' => ['backend'],
            'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
        ]);
        $templateMgr->addJavaScript('readership', $baseUrl . '/js/readership.js?v=' . filemtime(__DIR__ . '/js/readership.js'), [
            'contexts' => ['backend'],
            'priority' => TemplateManager::STYLE_SEQUENCE_LAST,
        ]);
        $templateMgr->assign([
            'pageTitle' => __('plugins.generic.readership.title'),
            'pageWidth' => TemplateManager::PAGE_WIDTH_WIDE,
            'rs' => $report,
            'rsPeriodLabel' => __("plugins.generic.readership.period.{$period}"),
            'rsPeriods' => $periods,
            'rsMap' => $map,
            'rsOnline' => $online,
            'rsGeo' => $geo,
            'rsGeoSettingsUrl' => $this->plugin->canManage($context) ? $request->url(null, 'management', 'settings', ['distribution'], null, 'statistics') : null,
            'rsLiveUrl' => $request->url(null, ReadershipPlugin::PAGE, 'live'),
            'rsStatsUrl' => $request->url(null, 'stats', 'publications', ['publications']),
            'rsUpdated' => (new DateTime('@' . $report['generated']))->format('j M Y, H:i') . ' UTC',
        ]);

        return $templateMgr->display($this->plugin->getTemplateResource('readership.tpl'));
    }

    /**
     * People online now, as JSON, with map positions for the live dots.
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function live($args, $request)
    {
        $online = ReadershipData::online($this->plugin->geoEnabled($request->getContext()));
        $positions = WorldMap::positions();
        foreach ($online['countries'] as &$country) {
            $country['position'] = $positions[$country['code']] ?? null;
        }
        unset($country);
        $online['countF'] = number_format($online['count']);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($online);
    }
}
