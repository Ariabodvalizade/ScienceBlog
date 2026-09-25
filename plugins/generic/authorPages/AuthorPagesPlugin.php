<?php

/**
 * @file plugins/generic/authorPages/AuthorPagesPlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class AuthorPagesPlugin
 *
 * @brief Public author pages: a directory at /authors and one profile per
 *  author (photo, affiliation, ORCID, biography, website, published articles).
 *  Authors manage their photo, bio and website themselves in their OJS
 *  profile (Profile › Public); the page matches them by e-mail address.
 *
 * Theme integration: on article pages it assigns $authorPageUrls and
 * $authorPagePhotos (both keyed by contributor id) so the theme can link
 * author names and show photos. Meridian uses them.
 */

namespace APP\plugins\generic\authorPages;

use APP\core\Application;
use APP\plugins\generic\authorPages\classes\AuthorDirectory;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use Throwable;

class AuthorPagesPlugin extends GenericPlugin
{
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
            Hook::add('TemplateManager::display', $this->assignArticleAuthorLinks(...));
            foreach (['Publication::publish', 'Publication::unpublish', 'Publication::edit'] as $hook) {
                Hook::add($hook, $this->invalidateDirectory(...));
            }
        }
        return $success;
    }

    public function loadHandler(string $hookName, array $args): bool
    {
        $page = &$args[0];
        $handler = &$args[3];
        if ($page === 'authors') {
            $handler = new AuthorPagesHandler($this);
            return true;
        }
        return false;
    }

    /**
     * Profile links and photos for the contributors of the article being viewed.
     */
    public function assignArticleAuthorLinks(string $hookName, array $args): bool
    {
        [$templateMgr, $template] = $args;
        if ($template !== 'frontend/pages/article.tpl') {
            return Hook::CONTINUE;
        }
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        $publication = $templateMgr->getTemplateVars('publication');
        if (!$context || !$publication) {
            return Hook::CONTINUE;
        }

        try {
            $directory = new AuthorDirectory($context->getId());
            $urls = [];
            $photos = [];
            foreach ($publication->getData('authors') ?? [] as $author) {
                $key = AuthorDirectory::keyFor($author->getData('orcid'), $author->getData('email'), $author->getFullName(false));
                $person = $directory->get($key);
                if (!$person) {
                    continue;
                }
                $urls[$author->getId()] = $request->url(null, 'authors', 'view', [$person['key'], $person['slug']]);
                if ($photo = $directory->photoUrl($author->getData('email'), $request->getBaseUrl())) {
                    $photos[$author->getId()] = $photo;
                }
            }
            $templateMgr->assign(['authorPageUrls' => $urls, 'authorPagePhotos' => $photos]);
        } catch (Throwable $e) {
            error_log('[authorPages] ' . $e->getMessage());
        }
        return Hook::CONTINUE;
    }

    public function invalidateDirectory(string $hookName, array $args): bool
    {
        $context = Application::get()->getRequest()->getContext();
        if ($context) {
            AuthorDirectory::invalidate($context->getId());
        }
        return Hook::CONTINUE;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.authorPages.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.authorPages.description');
    }
}
