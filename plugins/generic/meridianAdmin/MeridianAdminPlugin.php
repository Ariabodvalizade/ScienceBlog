<?php

/**
 * @file plugins/generic/meridianAdmin/MeridianAdminPlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class MeridianAdminPlugin
 *
 * @brief A cleaner admin panel for editors and authors, without changing OJS
 *  core:
 *   - the backend is restyled in the journal's colours (the Meridian accent
 *     colour, if set) with calmer surfaces, navigation and buttons;
 *   - a role-aware Home page (/{journal}/workspace) summarises what needs
 *     attention, the publishing pipeline and the current issue for editors,
 *     and submissions plus profile completeness for authors;
 *   - Home becomes the first menu item and the landing page after login;
 *   - a "View website" button sits in the header.
 *
 *  It is a site-wide plugin (Administration › Site Settings › Plugins) and is
 *  on by default once installed.
 */

namespace APP\plugins\generic\meridianAdmin;

use APP\core\Application;
use APP\plugins\generic\meridianAdmin\classes\Recolor;
use APP\template\TemplateManager;
use PKP\core\Core;
use PKP\db\DAORegistry;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;

class MeridianAdminPlugin extends GenericPlugin
{
    public const DEFAULT_ACCENT = '#0f5c63';
    public const HOME_PAGE = 'workspace';

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
            Hook::add('TemplateManager::setupBackendPage', $this->addAssets(...));
            Hook::add('TemplateManager::display', $this->adjustMenu(...));
            Hook::add('Template::Layout::Backend::HeaderActions', $this->headerActions(...));
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.meridianAdmin.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.meridianAdmin.description');
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

    /**
     * Serve the Home page, and send people there instead of the bare
     * dashboard URLs used after login and by the site's "Dashboard" link.
     */
    public function loadHandler(string $hookName, array $args): bool
    {
        $page = &$args[0];
        $op = &$args[1];
        $handler = &$args[3];

        if ($page === self::HOME_PAGE) {
            $handler = new WorkspaceHandler($this);
            return true;
        }

        $request = Application::get()->getRequest();
        if (!$request->getContext() || !$request->getUser()) {
            return false;
        }
        $isBareDashboard = $page === 'dashboard'
            && in_array($op, ['editorial', 'mySubmissions'], true)
            && empty($request->getQueryArray());
        if ($page === 'submissions' || $isBareDashboard) {
            $request->redirect(null, self::HOME_PAGE);
        }
        return false;
    }

    /**
     * Colour tokens, recoloured copies of the core backend stylesheets, and
     * the Meridian Admin stylesheet.
     */
    public function addAssets(string $hookName, array $args): bool
    {
        $request = Application::get()->getRequest();
        $templateMgr = TemplateManager::getManager($request);
        $baseUrl = $request->getBaseUrl();

        // Same names and priority as core, so these replace the originals
        $core = ['contexts' => ['backend'], 'priority' => TemplateManager::STYLE_SEQUENCE_CORE];
        if ($url = Recolor::copyUrl('build', Core::getBaseDir() . '/styles/build.css', $baseUrl)) {
            $templateMgr->addStyleSheet('build', $url, $core);
        }
        $lessFile = $templateMgr->getCachedLessFilePath('pkp-lib');
        if (!is_file($lessFile)) {
            $templateMgr->cacheLess($lessFile, $templateMgr->compileLess('pkp-lib', 'styles/index.less'));
        }
        if ($url = Recolor::copyUrl('pkp-lib-' . basename($lessFile, '.css'), $lessFile, $baseUrl)) {
            $templateMgr->addStyleSheet('pkpLib', $url, $core);
        }

        $late = ['contexts' => ['backend'], 'priority' => TemplateManager::STYLE_SEQUENCE_LAST];
        $templateMgr->addStyleSheet('meridianAdminTokens', $this->tokensCss($request), $late + ['inline' => true]);
        $templateMgr->addStyleSheet('meridianAdmin', "{$baseUrl}/{$this->getPluginPath()}/css/meridianAdmin.css?v=" . Recolor::VERSION . '.' . filemtime(__DIR__ . '/css/meridianAdmin.css'), $late);
        return Hook::CONTINUE;
    }

    /**
     * Home first; "Editor Dashboard" becomes "Submissions".
     */
    public function adjustMenu(string $hookName, array $args): bool
    {
        [$templateMgr] = $args;
        $menu = $templateMgr->getState('menu');
        $request = Application::get()->getRequest();
        if (!is_array($menu) || !$menu || !$request->getContext() || isset($menu['home'])) {
            return Hook::CONTINUE;
        }

        if (isset($menu['dashboards'])) {
            $menu['dashboards']['name'] = __('plugins.generic.meridianAdmin.menu.submissions');
        }
        $home = [
            'name' => __('plugins.generic.meridianAdmin.menu.home'),
            'url' => $request->getRouter()->url($request, null, self::HOME_PAGE),
            'isCurrent' => $request->getRequestedPage() === self::HOME_PAGE,
            'icon' => 'Dashboard',
        ];
        $templateMgr->setState(['menu' => ['home' => $home] + $menu]);
        return Hook::CONTINUE;
    }

    /**
     * "View website" button in the backend header.
     */
    public function headerActions(string $hookName, array $args): bool
    {
        $output = &$args[2];
        $request = Application::get()->getRequest();
        $url = $request->getContext()
            ? $request->getRouter()->url($request, null, 'index')
            : $request->getBaseUrl();
        $label = htmlspecialchars(__('plugins.generic.meridianAdmin.viewSite'));
        $output .= '<a class="ma-siteLink" href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener">'
            . '<icon icon="Globe" class="h-5 w-5" aria-hidden="true"></icon><span class="ma-siteLink__label">' . $label . '</span></a>';
        return Hook::CONTINUE;
    }

    /**
     * CSS custom properties for the backend, from the journal's accent colour.
     */
    protected function tokensCss($request): string
    {
        $accent = self::DEFAULT_ACCENT;
        $context = $request->getContext();
        if ($context) {
            $saved = DAORegistry::getDAO('PluginSettingsDAO')->getSetting($context->getId(), 'meridianplugin', 'accentColour');
            if (is_string($saved) && preg_match('/^#[0-9a-f]{6}$/i', $saved)) {
                $accent = strtolower($saved);
            }
        }

        $tokens = [
            '--ma-accent' => $accent,
            '--ma-accent-hover' => $this->mix($accent, '#000000', 0.14),
            '--ma-accent-strong' => $this->mix($accent, '#000000', 0.32),
            '--ma-accent-soft' => $this->mix($accent, '#ffffff', 0.9),
            '--ma-accent-softer' => $this->mix($accent, '#ffffff', 0.95),
            '--ma-accent-line' => $this->mix($accent, '#ffffff', 0.7),
            '--ma-canvas' => $this->mix($accent, '#f4f5f5', 0.97),
            '--ma-line' => $this->mix($accent, '#dde2e2', 0.94),
            '--ma-field' => $this->mix($accent, '#a3adac', 0.92),
            '--ma-ink' => $this->mix($accent, '#1b2322', 0.9),
            '--ma-muted' => $this->mix($accent, '#5b6564', 0.9),
            '--ma-negative' => '#b42318',
        ];
        $css = ':root{';
        foreach ($tokens as $name => $value) {
            $css .= "{$name}:{$value};";
        }
        $css .= '}';

        $fonts = $request->getBaseUrl() . '/plugins/themes/meridian/fonts';
        if (is_dir(dirname(__DIR__, 2) . '/themes/meridian/fonts')) {
            $css .= "@font-face{font-family:'Playfair Display';font-style:normal;font-weight:600;font-display:swap;"
                . "src:url('{$fonts}/playfair-display-latin-600-normal.woff2') format('woff2')}";
        }
        return $css;
    }

    /** Mix two #rrggbb colours; $weight is the share of $with. */
    protected function mix(string $color, string $with, float $weight): string
    {
        $a = sscanf($color, '#%02x%02x%02x');
        $b = sscanf($with, '#%02x%02x%02x');
        $out = '#';
        for ($i = 0; $i < 3; $i++) {
            $out .= sprintf('%02x', (int) round($a[$i] * (1 - $weight) + $b[$i] * $weight));
        }
        return $out;
    }
}
