<?php

/**
 * @file plugins/generic/scholarlyReader/ScholarlyReaderPlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ScholarlyReaderPlugin
 *
 * @brief Reference linking, inline HTML full text and scholarly metadata for
 *  article landing pages:
 *  - references: DOIs/URLs linked, plus Google Scholar and Crossref look-ups;
 *  - an HTML galley, when present, is rendered inline on the article page with
 *    in-text citations linked to the reference list (#ref-N) and popovers;
 *  - schema.org ScholarlyArticle JSON-LD and OpenGraph/Twitter meta tags.
 *
 * Theme contract: the theme renders the reference list as items with
 * id="ref-1" … id="ref-N" and may use $scholarlyReferences (linked HTML per
 * reference), $scholarlyOutline (full-text headings) and
 * $scholarlyFullTextGalleyId. Meridian does; any theme works without them.
 */

namespace APP\plugins\generic\scholarlyReader;

use APP\core\Application;
use APP\facades\Repo;
use APP\plugins\generic\scholarlyReader\classes\FullTextProcessor;
use APP\plugins\generic\scholarlyReader\classes\MetadataBuilder;
use APP\plugins\generic\scholarlyReader\classes\ReferenceFormatter;
use APP\template\TemplateManager;
use PKP\core\Core;
use PKP\galley\Galley;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\submissionFile\SubmissionFile;
use Throwable;

class ScholarlyReaderPlugin extends GenericPlugin
{
    /** Bump to invalidate cached full-text renderings after processor changes. */
    public const CACHE_VERSION = 1;

    protected ReferenceFormatter $formatter;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            $this->formatter = new ReferenceFormatter();
            Hook::add('TemplateManager::display', $this->prepareArticlePage(...));
            Hook::add('Templates::Article::Main', $this->renderFullText(...));
            Hook::add('Templates::Article::Details::Reference', $this->renderReferenceLinks(...));
        }
        return $success;
    }

    /**
     * Prepare template variables, assets and metadata for the article landing page.
     */
    public function prepareArticlePage(string $hookName, array $args): bool
    {
        [$templateMgr, $template] = $args;
        if ($template !== 'frontend/pages/article.tpl') {
            return Hook::CONTINUE;
        }

        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $article = $templateMgr->getTemplateVars('article');
        $publication = $templateMgr->getTemplateVars('publication');
        if (!$context || !$article || !$publication) {
            return Hook::CONTINUE;
        }

        try {
            // References
            $citations = $publication->getData('citations') ?? [];
            $citations = is_array($citations) ? array_values($citations) : $citations->values()->all();
            $raw = array_map(fn ($citation) => (string) $citation->getRawCitation(), $citations);
            $templateMgr->assign('scholarlyReferences', array_map($this->formatter->toHtml(...), $raw));

            // Inline full text from the first HTML galley
            $galley = $this->findHtmlGalley($publication);
            if ($galley) {
                $rendered = $this->renderGalley($request, $article, $galley, $raw);
                if ($rendered && trim(strip_tags($rendered['html'])) !== '') {
                    $templateMgr->assign([
                        'scholarlyFullText' => $rendered['html'],
                        'scholarlyOutline' => $rendered['outline'],
                        'scholarlyFullTextGalleyId' => $galley->getId(),
                    ]);
                }
            }

            // Metadata
            $section = $templateMgr->getTemplateVars('section');
            $builder = new MetadataBuilder(
                $request,
                $context,
                $article,
                $publication,
                $templateMgr->getTemplateVars('issue'),
                $section?->getLocalizedTitle()
            );
            $templateMgr->addHeader('scholarlyReaderJsonLd', $builder->jsonLdScript());
            $templateMgr->addHeader('scholarlyReaderMeta', $builder->metaTags());
        } catch (Throwable $e) {
            error_log('[scholarlyReader] ' . $e->getMessage());
        }

        $base = $request->getBaseUrl() . '/' . $this->getPluginPath();
        $templateMgr->addStyleSheet('scholarlyReader', $base . '/styles/scholarlyReader.css', ['contexts' => 'frontend']);
        $templateMgr->addJavaScript('scholarlyReader', $base . '/js/citations.js', ['contexts' => 'frontend']);
        $templateMgr->addJavaScript(
            'scholarlyReaderI18n',
            'window.scholarlyReaderI18n = ' . json_encode([
                'goToReference' => __('plugins.generic.scholarlyReader.goToReference'),
                'reference' => __('plugins.generic.scholarlyReader.reference'),
            ]) . ';',
            ['inline' => true, 'contexts' => 'frontend']
        );

        return Hook::CONTINUE;
    }

    /**
     * Output the inline full text (Templates::Article::Main).
     */
    public function renderFullText(string $hookName, array $args): bool
    {
        $smarty = &$args[1];
        $output = &$args[2];
        if ($smarty->getTemplateVars('scholarlyFullText')) {
            $output .= $smarty->fetch($this->getTemplateResource('fulltext.tpl'));
        }
        return Hook::CONTINUE;
    }

    /**
     * Output look-up links after each reference (Templates::Article::Details::Reference).
     */
    public function renderReferenceLinks(string $hookName, array $args): bool
    {
        $params = &$args[0];
        $output = &$args[2];
        $citation = $params['citation'] ?? null;
        if (!$citation) {
            return Hook::CONTINUE;
        }
        $raw = (string) $citation->getRawCitation();
        $output .= '<span class="sr-ref-links">'
            . '<a href="' . htmlspecialchars($this->formatter->googleScholarUrl($raw)) . '" target="_blank" rel="noopener">' . __('plugins.generic.scholarlyReader.googleScholar') . '</a>'
            . '<a href="' . htmlspecialchars($this->formatter->crossrefUrl($raw)) . '" target="_blank" rel="noopener">' . __('plugins.generic.scholarlyReader.crossref') . '</a>'
            . '</span>';
        return Hook::CONTINUE;
    }

    protected function findHtmlGalley($publication): ?Galley
    {
        foreach ($publication->getData('galleys') ?? [] as $galley) {
            if (!$galley->getData('urlRemote') && $galley->getFileType() === 'text/html') {
                return $galley;
            }
        }
        return null;
    }

    /**
     * Read, rewrite, sanitize and link an HTML galley (cached per file version).
     *
     * @param string[] $rawReferences
     *
     * @return ?array{html: string, outline: array}
     */
    protected function renderGalley($request, $article, Galley $galley, array $rawReferences): ?array
    {
        $submissionFile = $galley->getFile();
        if (!$submissionFile) {
            return null;
        }

        $cacheDir = Core::getBaseDir() . '/cache/scholarlyReader';
        $cacheKey = md5(implode('|', [
            self::CACHE_VERSION,
            $submissionFile->getId(),
            $submissionFile->getData('updatedAt'),
            md5(implode("\n", $rawReferences)),
            $request->getBaseUrl(),
        ]));
        $cacheFile = "{$cacheDir}/{$cacheKey}.json";
        if (is_readable($cacheFile) && ($cached = json_decode((string) file_get_contents($cacheFile), true))) {
            return $cached;
        }

        $contents = app()->get('file')->fs->read($submissionFile->getData('path'));
        $contents = $this->rewriteDependentFiles($request, $article, $galley, $submissionFile, (string) $contents);

        $processor = new FullTextProcessor(
            count($rawReferences),
            $this->formatter->authorYearIndex($rawReferences),
            is_dir(Core::getBaseDir() . '/cache') ? Core::getBaseDir() . '/cache' : null
        );
        $result = ['html' => $processor->process($contents), 'outline' => $processor->getOutline()];

        if (is_dir($cacheDir) || @mkdir($cacheDir, 0775, true)) {
            @file_put_contents($cacheFile, json_encode($result));
        }
        return $result;
    }

    /**
     * Point images and other dependent files of the HTML galley to their
     * download URLs (same approach as the bundled HTML galley plugin).
     */
    protected function rewriteDependentFiles($request, $article, Galley $galley, SubmissionFile $submissionFile, string $contents): string
    {
        $dependentFiles = Repo::submissionFile()
            ->getCollector()
            ->filterByAssoc(Application::ASSOC_TYPE_SUBMISSION_FILE, [$submissionFile->getId()])
            ->filterByFileStages([SubmissionFile::SUBMISSION_FILE_DEPENDENT])
            ->includeDependentFiles()
            ->getMany();

        foreach ($dependentFiles as $file) {
            $name = $file->getLocalizedData('name');
            $url = $request->url(null, 'article', 'download', [
                $article->getBestId(), 'version', $galley->getData('publicationId'),
                $galley->getBestGalleyId(), $file->getId(), $name,
            ]);
            $pattern = preg_quote(rawurlencode($name), '/') . '|' . preg_quote($name, '/');
            $contents = preg_replace(
                '/(src|href)\s*=\s*"([^"]*(?:' . $pattern . '))"/i',
                '$1="' . $url . '"',
                $contents
            ) ?? $contents;
        }
        return $contents;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.scholarlyReader.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.scholarlyReader.description');
    }
}
