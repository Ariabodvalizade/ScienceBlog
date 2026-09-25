<?php

/**
 * @file plugins/generic/authorPages/AuthorPagesHandler.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class AuthorPagesHandler
 *
 * @brief Public author directory (/authors) and author profiles
 *  (/authors/view/{key}/{slug}).
 */

namespace APP\plugins\generic\authorPages;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\authorPages\classes\AuthorDirectory;
use APP\submission\Submission;
use APP\template\TemplateManager;
use PKP\core\PKPRequest;
use PKP\security\Role;
use PKP\userGroup\UserGroup;

class AuthorPagesHandler extends Handler
{
    public function __construct(protected AuthorPagesPlugin $plugin)
    {
        parent::__construct();
    }

    /**
     * Directory of all authors, A–Z.
     */
    public function index($args, $request)
    {
        $context = $request->getContext();
        if (!$context) {
            $request->getDispatcher()->handle404();
        }
        $directory = new AuthorDirectory($context->getId());
        $baseUrl = $request->getBaseUrl();

        $groups = [];
        foreach ($directory->all() as $person) {
            $letter = mb_strtoupper(mb_substr($person['familyName'] ?: $person['name'], 0, 1));
            $groups[$letter][] = $this->present($person, $directory, $request, $baseUrl);
        }

        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);
        $templateMgr->assign([
            'authorGroups' => $groups,
            'authorCount' => array_sum(array_map('count', $groups)),
        ]);
        $templateMgr->display($this->plugin->getTemplateResource('authors.tpl'));
    }

    /**
     * One author's profile with photo, bio, identifiers and articles.
     */
    public function view($args, $request)
    {
        $context = $request->getContext();
        $key = (string) array_shift($args);
        $directory = new AuthorDirectory($context?->getId() ?? 0);
        $person = $context ? $directory->get($key) : null;
        if (!$person) {
            $request->getDispatcher()->handle404();
        }

        // Canonical URL includes the name slug
        $slug = (string) array_shift($args);
        if ($slug !== $person['slug']) {
            $request->redirectUrl($request->url(null, 'authors', 'view', [$person['key'], $person['slug']]));
        }

        $articles = Repo::submission()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterBySubmissionIds($person['submissionIds'])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getMany()
            ->sortByDesc(fn (Submission $s) => (string) $s->getCurrentPublication()?->getData('datePublished'))
            ->values()
            ->all();

        $profile = $this->present($person, $directory, $request, $request->getBaseUrl());

        $templateMgr = TemplateManager::getManager($request);
        $this->setupTemplate($request);
        $templateMgr->assign([
            'author' => $profile,
            'authorArticles' => $articles,
            'authorUserGroups' => UserGroup::withRoleIds([Role::ROLE_ID_AUTHOR])->withContextIds([$context->getId()])->get(),
        ]);
        $templateMgr->addHeader('authorPagesJsonLd', $this->jsonLd($profile, $request));
        $templateMgr->display($this->plugin->getTemplateResource('author.tpl'));
    }

    /**
     * Public view of a person: never exposes the e-mail address.
     */
    protected function present(array $person, AuthorDirectory $directory, PKPRequest $request, string $baseUrl): array
    {
        $user = $directory->userFor($person['email'] ?? null);
        $biography = trim(strip_tags((string) $person['biography'])) !== ''
            ? $person['biography']
            : (string) $user?->getLocalizedData('biography');

        return [
            'key' => $person['key'],
            'name' => $person['name'],
            'givenName' => $person['givenName'],
            'familyName' => $person['familyName'],
            'initials' => mb_strtoupper(mb_substr($person['givenName'], 0, 1) . mb_substr($person['familyName'], 0, 1)),
            'affiliation' => $person['affiliation'],
            'country' => $person['country'],
            'orcid' => $person['orcid'],
            'biography' => $biography,
            'website' => $user?->getData('url') ?: null,
            'photo' => $directory->photoUrl($person['email'] ?? null, $baseUrl),
            'articleCount' => count($person['submissionIds']),
            'url' => $request->url(null, 'authors', 'view', [$person['key'], $person['slug']]),
        ];
    }

    protected function jsonLd(array $profile, PKPRequest $request): string
    {
        $data = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $profile['name'],
            'givenName' => $profile['givenName'] ?: null,
            'familyName' => $profile['familyName'] ?: null,
            'url' => $profile['url'],
            'image' => $profile['photo'],
            'affiliation' => $profile['affiliation'] ? ['@type' => 'Organization', 'name' => $profile['affiliation']] : null,
            'sameAs' => array_values(array_filter([$profile['orcid'], $profile['website']])) ?: null,
        ]);
        return '<script type="application/ld+json">' . json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) . '</script>';
    }
}
