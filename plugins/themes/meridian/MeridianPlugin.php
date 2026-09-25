<?php

/**
 * @file plugins/themes/meridian/MeridianPlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class MeridianPlugin
 *
 * @brief Meridian — a minimal editorial theme for academic journals.
 *
 * Child theme of the OJS Default theme: every template that Meridian does not
 * override falls back to the parent theme and then to OJS core, so all OJS
 * pages keep working. Meridian adds its own design tokens, fonts, layouts and
 * a few template variables used by the home, issue and article pages.
 */

namespace APP\plugins\themes\meridian;

use APP\core\Application;
use APP\facades\Repo;
use APP\issue\Collector as IssueCollector;
use APP\submission\Submission;
use PKP\plugins\Hook;
use PKP\plugins\ThemePlugin;
use PKP\submission\Collector as SubmissionCollector;

class MeridianPlugin extends ThemePlugin
{
    public const DEFAULT_ACCENT = '#0f5c63';

    /** Home page sections that can be toggled in the theme options. */
    public const HOME_SECTIONS = ['currentIssue', 'latestArticles', 'aims', 'announcements', 'indexing'];

    /**
     * @copydoc ThemePlugin::init()
     */
    public function init()
    {
        $this->setParent('defaultthemeplugin');

        $this->addOption('accentColour', 'FieldColor', [
            'label' => __('plugins.themes.meridian.option.accent.label'),
            'description' => __('plugins.themes.meridian.option.accent.description'),
            'default' => self::DEFAULT_ACCENT,
        ]);

        $this->addOption('homeSections', 'FieldOptions', [
            'label' => __('plugins.themes.meridian.option.homeSections.label'),
            'description' => __('plugins.themes.meridian.option.homeSections.description'),
            'options' => array_map(fn (string $section) => [
                'value' => $section,
                'label' => __('plugins.themes.meridian.option.homeSections.' . $section),
            ], self::HOME_SECTIONS),
            'default' => self::HOME_SECTIONS,
        ]);

        $this->addOption('tagline', 'FieldText', [
            'label' => __('plugins.themes.meridian.option.tagline.label'),
            'description' => __('plugins.themes.meridian.option.tagline.description'),
            'isMultilingual' => true,
        ]);

        $this->addOption('indexingLogos', 'FieldTextarea', [
            'label' => __('plugins.themes.meridian.option.indexingLogos.label'),
            'description' => __('plugins.themes.meridian.option.indexingLogos.description'),
            'default' => '',
        ]);

        // The parent's font choices are replaced by Meridian's own type system.
        $this->removeOption('typography');
        $this->removeStyle('font');

        $this->addStyle('meridianFonts', 'styles/fonts.less');

        // Compile Meridian's LESS together with the parent stylesheet so parent
        // variables can be reused and overridden.
        $accent = $this->getAccentColour();
        $this->modifyStyle('stylesheet', [
            'addLess' => ['styles/meridian.less'],
            'addLessVariables' => "@m-accent: {$accent};",
        ]);

        $this->addScript('meridian', 'js/meridian.js');

        Hook::add('TemplateManager::display', $this->loadTemplateData(...));
    }

    /**
     * @copydoc ThemePlugin::saveOption()
     */
    public function saveOption($name, $value, $contextId = null)
    {
        if ($name === 'accentColour' && !$this->isAccessibleAccent((string) $value)) {
            $value = null;
        }
        parent::saveOption($name, $value, $contextId);
    }

    /**
     * Accent colour from the theme options, falling back to the default when
     * missing, malformed or too light to be readable on white.
     */
    public function getAccentColour(): string
    {
        $accent = (string) $this->getOption('accentColour');
        return $this->isAccessibleAccent($accent) ? $accent : self::DEFAULT_ACCENT;
    }

    /**
     * A valid #rrggbb colour with a contrast ratio of at least 4.5:1 on white.
     */
    public function isAccessibleAccent(string $colour): bool
    {
        if (!preg_match('/^#([0-9a-f]{6})$/i', $colour, $m)) {
            return false;
        }
        $channels = array_map(function (string $hex) {
            $c = hexdec($hex) / 255;
            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($m[1], 2));
        $luminance = 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
        return (1.05 / ($luminance + 0.05)) >= 4.5;
    }

    /**
     * Whether a home page section is enabled in the theme options.
     */
    public function isHomeSectionEnabled(string $section): bool
    {
        $enabled = $this->getOption('homeSections');
        return is_array($enabled) && in_array($section, $enabled);
    }

    /**
     * Parse the "indexing logos" option: one "Name | Logo URL | Link URL" per line.
     *
     * @return array<array{name: string, logo: string, url: string}>
     */
    public function getIndexingLogos(): array
    {
        $logos = [];
        foreach (preg_split('/\R/', (string) $this->getOption('indexingLogos')) as $line) {
            $parts = array_map('trim', explode('|', $line));
            if ($parts[0] === '') {
                continue;
            }
            $logos[] = [
                'name' => $parts[0],
                'logo' => $this->safeUrl($parts[1] ?? ''),
                'url' => $this->safeUrl($parts[2] ?? ''),
            ];
        }
        return $logos;
    }

    /**
     * Only allow http(s) and site-relative URLs.
     */
    protected function safeUrl(string $url): string
    {
        return preg_match('#^(https?://|/)#i', $url) ? $url : '';
    }

    /**
     * Assign extra variables used by Meridian's templates.
     *
     * @param string $hookName
     * @param array $args [TemplateManager, string template, ...]
     */
    public function loadTemplateData($hookName, $args): bool
    {
        [$templateMgr, $template] = $args;
        $request = Application::get()->getRequest();
        $context = $request->getContext();

        $templateMgr->assign([
            'meridian' => $this,
            'meridianTagline' => $this->getLocalizedOption('tagline'),
            'meridianIndexingLogos' => $this->getIndexingLogos(),
        ]);

        if (!$context) {
            return Hook::CONTINUE;
        }

        switch ($template) {
            case 'frontend/pages/indexJournal.tpl':
                if ($this->isHomeSectionEnabled('latestArticles')) {
                    $templateMgr->assign('meridianLatestArticles', $this->getLatestArticles($context->getId(), 6));
                }
                break;

            case 'frontend/pages/article.tpl':
                $issue = $templateMgr->getTemplateVars('issue');
                $article = $templateMgr->getTemplateVars('article');
                if ($issue && $article) {
                    $templateMgr->assign('meridianIssueArticles', $this->getIssueArticles($context->getId(), $issue->getId(), $article->getId(), 5));
                }
                $templateMgr->assign('meridianArchive', $this->getArchiveSummary($context->getId()));
                break;

            case 'frontend/pages/issue.tpl':
            case 'frontend/pages/issueArchive.tpl':
                $templateMgr->assign('meridianArchive', $this->getArchiveSummary($context->getId()));
                break;
        }

        return Hook::CONTINUE;
    }

    /**
     * Most recently published articles in the journal.
     *
     * @return Submission[]
     */
    public function getLatestArticles(int $contextId, int $count): array
    {
        return Repo::submission()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(SubmissionCollector::ORDERBY_DATE_PUBLISHED)
            ->limit($count)
            ->getMany()
            ->values()
            ->all();
    }

    /**
     * Other published articles in the same issue ("More from this issue").
     *
     * @return Submission[]
     */
    public function getIssueArticles(int $contextId, int $issueId, int $excludeId, int $count): array
    {
        return Repo::submission()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByIssueIds([$issueId])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->orderBy(SubmissionCollector::ORDERBY_SEQUENCE, SubmissionCollector::ORDER_DIR_ASC)
            ->getMany()
            ->filter(fn (Submission $submission) => $submission->getId() !== $excludeId)
            ->take($count)
            ->values()
            ->all();
    }

    /**
     * Published issues grouped by year, for the archive rail and archive page.
     *
     * @return array<int|string, array{year: string, issues: \APP\issue\Issue[]}>
     */
    public function getArchiveSummary(int $contextId): array
    {
        $issues = Repo::issue()->getCollector()
            ->filterByContextIds([$contextId])
            ->filterByPublished(true)
            ->orderBy(IssueCollector::ORDERBY_PUBLISHED_ISSUES)
            ->getMany();

        $years = [];
        foreach ($issues as $issue) {
            $year = $issue->getYear() ?: date('Y', strtotime((string) $issue->getDatePublished()));
            $years[$year]['year'] = (string) $year;
            $years[$year]['issues'][] = $issue;
        }
        return array_values($years);
    }

    /**
     * @copydoc Plugin::getDisplayName()
     */
    public function getDisplayName()
    {
        return __('plugins.themes.meridian.name');
    }

    /**
     * @copydoc Plugin::getDescription()
     */
    public function getDescription()
    {
        return __('plugins.themes.meridian.description');
    }
}
