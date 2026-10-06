<?php

/**
 * @file plugins/generic/readership/ReadershipPlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ReadershipPlugin
 *
 * @brief Who reads the journal, from where, and what they read:
 *   - a Readership page for editors (Statistics › Readership): views,
 *     downloads, unique readers, countries, a world map, the most-read
 *     articles (and what each country reads), a 12-month trend and a live
 *     "online now" counter;
 *   - the figures and map for the theme's public "Readers around the world"
 *     home page section (Meridian), via homeData();
 *   - a Readership shortcut on the admin panel's Home page (Meridian Admin).
 *  It reads the usage statistics OJS already records; country figures need
 *  country statistics switched on (Settings › Distribution › Statistics).
 *  It is a site-wide plugin and is on by default once installed.
 */

namespace APP\plugins\generic\readership;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\readership\classes\ReadershipData;
use APP\plugins\generic\readership\classes\WorldMap;
use APP\submission\Submission;
use APP\template\TemplateManager;
use Illuminate\Support\Facades\Cache;
use PKP\context\Context;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\security\Role;
use Throwable;

class ReadershipPlugin extends GenericPlugin
{
    public const PAGE = 'readership';
    public const ROLES = [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR];

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add('LoadHandler', $this->loadHandler(...));
            Hook::add('TemplateManager::display', $this->display(...));
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.readership.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.readership.description');
    }

    /** One setting for the whole site, managed by the site administrator. */
    public function isSitePlugin()
    {
        return true;
    }

    /** On by default: no setting stored means enabled. */
    public function getEnabled($contextId = null)
    {
        $enabled = parent::getEnabled($contextId);
        return $enabled === null ? true : (bool) $enabled;
    }

    public function loadHandler(string $hookName, array $args): bool
    {
        $page = &$args[0];
        $handler = &$args[3];
        if ($page === self::PAGE) {
            $handler = new ReadershipHandler($this);
            return true;
        }
        return false;
    }

    /**
     * Menu item, Home shortcut and the public home page data.
     */
    public function display(string $hookName, array $args): bool
    {
        [$templateMgr, $template] = $args;
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context || !is_string($template)) {
            return Hook::CONTINUE;
        }

        if ($template === 'frontend/pages/indexJournal.tpl') {
            $templateMgr->assign('readership', $this);
            return Hook::CONTINUE;
        }

        if (!$this->canView($context)) {
            return Hook::CONTINUE;
        }
        $menu = $templateMgr->getState('menu');
        if (is_array($menu) && isset($menu['statistics']['submenu']) && !isset($menu['statistics']['submenu'][self::PAGE])) {
            $menu['statistics']['submenu'] = [self::PAGE => [
                'name' => __('plugins.generic.readership.title'),
                'url' => $request->url(null, self::PAGE),
                'isCurrent' => $request->getRequestedPage() === self::PAGE,
            ]] + $menu['statistics']['submenu'];
            $templateMgr->setState(['menu' => $menu]);
        }

        if (str_ends_with($template, 'workspace.tpl') && is_array($actions = $templateMgr->getTemplateVars('maActions'))) {
            $actions[] = ['icon' => 'Globe', 'label' => __('plugins.generic.readership.shortcut'), 'url' => $request->url(null, self::PAGE)];
            $templateMgr->assign('maActions', $actions);
        }
        return Hook::CONTINUE;
    }

    public function canView(Context $context): bool
    {
        $user = Application::get()->getRequest()->getUser();
        return $user && ($user->hasRole(self::ROLES, $context->getId())
            || $user->hasRole([Role::ROLE_ID_SITE_ADMIN], Application::SITE_CONTEXT_ID));
    }

    public function canManage(Context $context): bool
    {
        $user = Application::get()->getRequest()->getUser();
        return $user && ($user->hasRole([Role::ROLE_ID_MANAGER], $context->getId())
            || $user->hasRole([Role::ROLE_ID_SITE_ADMIN], Application::SITE_CONTEXT_ID));
    }

    /**
     * Whether visitors' countries are recorded for this journal.
     */
    public function geoEnabled(Context $context): bool
    {
        $setting = $context->getEnableGeoUsageStats(Application::get()->getRequest()->getSite());
        return $setting !== null && $setting !== 'disabled';
    }

    /**
     * Figures and map for the public home page section, or null when there is
     * nothing to show yet. Called from the theme template.
     */
    public function homeData(Context $context): ?array
    {
        try {
            $data = new ReadershipData($context->getId());
            $summary = $data->publicSummary();
            if (!$summary['totals']['views'] && !$summary['totals']['downloads']) {
                return null;
            }
            $geo = $this->geoEnabled($context) && $summary['countries'];
            $online = ReadershipData::online($geo);
            $live = array_column($online['countries'], 'count', 'code');
            $countries = $summary['countries'];
            $map = $geo ? WorldMap::render(
                array_column($countries, 'readers', 'code'),
                $live,
                __('plugins.generic.readership.public.mapLabel'),
                fn (string $code, int $value) => __('plugins.generic.readership.tooltip', [
                    'country' => $countries[$code]['name'] ?? $code,
                    'count' => number_format($value),
                ])
            ) : null;

            $top = array_slice(array_values($countries), 0, 5);
            $max = max(1, ...array_column($top, 'readers'));
            foreach ($top as &$row) {
                $row['bar'] = round(100 * $row['readers'] / $max, 1);
            }
            unset($row);

            return ReadershipData::withFormatted([
                'articles' => $this->publishedCount($context->getId()),
                'views' => $summary['totals']['views'],
                'downloads' => $summary['totals']['downloads'],
                'countries' => $summary['totals']['countries'],
                'count' => $online['count'],
                'top' => $top,
                'map' => $map,
            ]);
        } catch (Throwable $e) {
            error_log('readership: ' . $e->getMessage());
            return null;
        }
    }

    protected function publishedCount(int $contextId): int
    {
        return Cache::remember("readership-articles-{$contextId}", 6 * 3600, fn () => Repo::submission()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getCount());
    }
}
