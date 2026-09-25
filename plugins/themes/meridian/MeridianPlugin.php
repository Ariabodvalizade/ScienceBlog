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
use PKP\security\Role;
use PKP\submission\Collector as SubmissionCollector;
use PKP\userGroup\UserGroup;

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

        // Compile Meridian's fonts and LESS together with the parent stylesheet
        // (one request; parent variables can be reused and overridden).
        $accent = $this->getAccentColour();
        $this->modifyStyle('stylesheet', [
            'addLess' => ['styles/fonts.less', 'styles/meridian.less'],
            'addLessVariables' => "@m-accent: {$accent};",
        ]);

        // The parent loads the Swiper carousel on every page; Meridian only needs
        // it for home page highlights (see loadTemplateData). The parent's main.js
        // always calls `new Swiper(...)`, so provide a no-op until it is loaded.
        $this->removeStyle('swiper');
        $this->removeScript('swiper');
        $this->addScript('meridianSwiperStub', 'window.Swiper = window.Swiper || function () {};', [
            'inline' => true,
            'priority' => \APP\template\TemplateManager::STYLE_SEQUENCE_CORE,
        ]);

        $this->addScript('meridian', 'js/meridian.js');

        // Preload the two faces used above the fold (title + body text)
        $request = Application::get()->getRequest();
        $fontBase = $request->getBaseUrl() . '/' . $this->getPluginPath() . '/fonts/';
        foreach (['playfair-display-latin-400-normal.woff2', 'source-serif-4-latin-400-normal.woff2'] as $font) {
            $this->addHeaderOnce('meridianPreload' . md5($font), '<link rel="preload" href="' . $fontBase . $font . '" as="font" type="font/woff2" crossorigin>');
        }

        Hook::add('TemplateManager::display', $this->loadTemplateData(...));
    }

    /**
     * Queue a <head> element for frontend pages.
     */
    protected function addHeaderOnce(string $name, string $html): void
    {
        $templateMgr = \APP\template\TemplateManager::getManager(Application::get()->getRequest());
        $templateMgr->addHeader($name, $html, ['contexts' => 'frontend']);
    }

    /**
     * Plain-text meta description (max ~160 characters).
     */
    public static function metaDescription(?string $html): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $html), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        if (mb_strlen($text) > 160) {
            $text = rtrim(mb_substr($text, 0, 157), " ,.;:") . '…';
        }
        return $text;
    }

    /**
     * Creative Commons licence badge data from a licence URL (rendered locally,
     * instead of the image OJS loads from an external server).
     *
     * @return ?array{label: string, url: string}
     */
    public static function licenseBadge(?string $url): ?array
    {
        if (!$url || !preg_match('#creativecommons\.org/(licenses|publicdomain)/([a-z-]+)/(\d\.\d)#i', $url, $m)) {
            return null;
        }
        $label = strtolower($m[1]) === 'publicdomain' ? 'CC0 ' . $m[3] : 'CC ' . strtoupper($m[2]) . ' ' . $m[3];
        return ['label' => $label, 'url' => $url];
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

        // Meta description for pages that don't get one from a plugin
        $description = match ($template) {
            'frontend/pages/indexJournal.tpl' => $context->getLocalizedData('description'),
            'frontend/pages/issue.tpl' => ($issue = $templateMgr->getTemplateVars('issue')) && $issue->hasDescription()
                ? $issue->getLocalizedDescription()
                : $context->getLocalizedData('description'),
            'frontend/pages/issueArchive.tpl' => $context->getLocalizedData('description'),
            default => null,
        };
        if ($description && ($text = self::metaDescription($description))) {
            $templateMgr->addHeader('meridianDescription', '<meta name="description" content="' . htmlspecialchars($text, ENT_QUOTES) . '">');
        }

        switch ($template) {
            case 'frontend/pages/indexJournal.tpl':
                $highlights = $templateMgr->getTemplateVars('highlights');
                if ($highlights && $highlights->count()) {
                    $min = \PKP\config\Config::getVar('general', 'enable_minified') ? '.min' : '';
                    $swiperBase = $request->getBaseUrl() . '/plugins/themes/default/js/lib/swiper/swiper-bundle' . $min;
                    $templateMgr->addStyleSheet('swiper', $swiperBase . '.css', ['contexts' => 'frontend']);
                    $templateMgr->addJavaScript('swiper', $swiperBase . '.js', [
                        'contexts' => 'frontend',
                        'priority' => \APP\template\TemplateManager::STYLE_SEQUENCE_CORE,
                    ]);
                }
                if ($this->isHomeSectionEnabled('latestArticles')) {
                    $templateMgr->assign('meridianLatestArticles', $this->summarize($this->getLatestArticles($context->getId(), 6)));
                }
                if (!$templateMgr->getTemplateVars('authorUserGroups')) {
                    $templateMgr->assign('authorUserGroups', UserGroup::withRoleIds([Role::ROLE_ID_AUTHOR])
                        ->withContextIds([$context->getId()])
                        ->get());
                }
                break;

            case 'frontend/pages/article.tpl':
                $issue = $templateMgr->getTemplateVars('issue');
                $article = $templateMgr->getTemplateVars('article');
                if ($issue && $article) {
                    $templateMgr->assign('meridianIssueArticles', $this->summarize($this->getIssueArticles($context->getId(), $issue->getId(), $article->getId(), 5)));
                }
                $templateMgr->assign('meridianArchive', $this->getArchiveSummary($context->getId()));
                $publication = $templateMgr->getTemplateVars('publication');
                if ($publication) {
                    $templateMgr->assign('meridianLicense', self::licenseBadge($publication->getData('licenseUrl')));
                    [$authors, $affiliations] = $this->getAuthorAffiliations($publication);
                    $templateMgr->assign([
                        'meridianAuthors' => $authors,
                        'meridianAffiliations' => $affiliations,
                    ]);
                }
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
     * Authors with numbered affiliation markers, and the numbered affiliation
     * list, as displayed under the article title.
     *
     * @return array{0: array<array{author: \PKP\author\Author, marks: int[]}>, 1: array<int, array{name: string, ror: ?string}>}
     */
    public function getAuthorAffiliations(\APP\publication\Publication $publication): array
    {
        $affiliations = [];
        $authors = [];
        foreach ($publication->getData('authors') ?? [] as $author) {
            $marks = [];
            foreach ($author->getAffiliations() as $affiliation) {
                $name = trim((string) $affiliation->getLocalizedName());
                if ($name === '') {
                    continue;
                }
                $index = array_search($name, array_column($affiliations, 'name'), true);
                if ($index === false) {
                    $affiliations[] = ['name' => $name, 'ror' => $affiliation->getRor()];
                    $index = count($affiliations) - 1;
                }
                $marks[] = $index + 1;
            }
            $authors[] = ['author' => $author, 'marks' => $marks];
        }

        // 1-indexed to match the superscript markers.
        $numbered = [];
        foreach ($affiliations as $i => $affiliation) {
            $numbered[$i + 1] = $affiliation;
        }
        return [$authors, $numbered];
    }

    /**
     * Display data for article lists outside an issue table of contents.
     *
     * @param Submission[] $submissions
     *
     * @return array<array{article: Submission, publication: \APP\publication\Publication, sectionTitle: string, authors: string, shortAuthors: string}>
     */
    public function summarize(array $submissions): array
    {
        $sections = [];
        $items = [];
        foreach ($submissions as $submission) {
            $publication = $submission->getCurrentPublication();
            if (!$publication) {
                continue;
            }
            $sectionId = (int) $publication->getData('sectionId');
            $sections[$sectionId] ??= Repo::section()->get($sectionId);
            $authors = $publication->getData('authors');
            $items[] = [
                'article' => $submission,
                'publication' => $publication,
                'sectionTitle' => $sections[$sectionId]?->getLocalizedTitle() ?? '',
                'authors' => $authors ? $authors->map(fn ($author) => $author->getFullName())->join(', ') : '',
                'shortAuthors' => $publication->getShortAuthorString(),
            ];
        }
        return $items;
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
