<?php

/**
 * @file plugins/generic/meridianAdmin/WorkspaceHandler.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class WorkspaceHandler
 *
 * @brief The admin panel's Home page. What it shows depends on the person's
 *  roles in the journal:
 *   - editors and managers: what needs attention, the publishing pipeline,
 *     recent submissions, the current issue and shortcuts;
 *   - authors: their submissions, a "new submission" call to action, and how
 *     complete their public profile (photo, bio, ORCID) is;
 *   - reviewers: their open review requests.
 *  Counts come from the same dashboard views as the side menu, so the numbers
 *  always match the Submissions lists they link to.
 */

namespace APP\plugins\generic\meridianAdmin;

use APP\core\Application;
use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\authorPages\classes\AuthorDirectory;
use APP\submission\Submission;
use APP\template\TemplateManager;
use DateTime;
use PKP\config\Config;
use PKP\core\PKPRequest;
use APP\file\PublicFileManager;
use PKP\plugins\PluginRegistry;
use PKP\security\authorization\ContextRequiredPolicy;
use PKP\security\authorization\PKPSiteAccessPolicy;
use PKP\security\Role;
use PKP\submission\Collector;
use Throwable;

class WorkspaceHandler extends Handler
{
    /** @copydoc PKPHandler::_isBackendPage */
    public $_isBackendPage = true;

    protected const EDITOR_ROLES = [Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER, Role::ROLE_ID_SUB_EDITOR, Role::ROLE_ID_ASSISTANT];

    public function __construct(protected MeridianAdminPlugin $plugin)
    {
        parent::__construct();
        $this->addRoleAssignment(
            [...self::EDITOR_ROLES, Role::ROLE_ID_REVIEWER, Role::ROLE_ID_AUTHOR, Role::ROLE_ID_READER],
            ['index']
        );
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        $this->addPolicy(new ContextRequiredPolicy($request));
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));
        return parent::authorize($request, $args, $roleAssignments);
    }

    /**
     * The Home page.
     *
     * @param array $args
     * @param PKPRequest $request
     */
    public function index($args, $request)
    {
        $this->setupTemplate($request);
        $context = $request->getContext();
        $user = $request->getUser();
        $roles = (array) $this->getAuthorizedContextObject(Application::ASSOC_TYPE_USER_ROLES);

        $isEditor = (bool) array_intersect(self::EDITOR_ROLES, $roles);
        $isManager = (bool) array_intersect([Role::ROLE_ID_SITE_ADMIN, Role::ROLE_ID_MANAGER], $roles);
        $isAuthor = in_array(Role::ROLE_ID_AUTHOR, $roles);
        $isReviewer = in_array(Role::ROLE_ID_REVIEWER, $roles);

        $templateMgr = TemplateManager::getManager($request);
        $templateMgr->assign([
            'pageTitle' => __('plugins.generic.meridianAdmin.menu.home'),
            'pageWidth' => TemplateManager::PAGE_WIDTH_WIDE,
            'maGreeting' => $this->greeting($user->getLocalizedGivenName() ?: $user->getUsername()),
            'maToday' => (new DateTime())->format('l, j F Y'),
            'maJournal' => $context->getLocalizedName(),
            'maSiteUrl' => $request->url(null, 'index'),
            'maIsEditor' => $isEditor,
            'maIsManager' => $isManager,
            'maCanSubmit' => !$context->getData('disableSubmissions'),
            'maSubmitUrl' => $request->url(null, 'submission'),
            'maGuidelinesUrl' => $request->url(null, 'about', 'submissions'),
        ]);

        if ($isEditor) {
            $templateMgr->assign($this->editorData($request, $context, $user, $isManager));
        }
        if ($isAuthor || !$isEditor) {
            $templateMgr->assign($this->authorData($request, $context, $user));
        }
        if ($isReviewer) {
            $templateMgr->assign($this->reviewerData($request, $context, $user));
        }

        return $templateMgr->display($this->plugin->getTemplateResource('workspace.tpl'));
    }

    protected function editorData(PKPRequest $request, $context, $user, bool $isManager): array
    {
        $views = Repo::submission()->getDashboardViews($context, $user, self::EDITOR_ROLES, true);
        $viewUrl = fn (string $id) => $request->url(null, 'dashboard', 'editorial', null, ['currentViewId' => $id]);
        $card = fn (string $id, string $key, string $icon, string $tone = 'default') => $views->has($id) ? [
            'count' => $views->get($id)->getCount(),
            'label' => __("plugins.generic.meridianAdmin.attention.{$key}"),
            'hint' => __("plugins.generic.meridianAdmin.attention.{$key}.hint"),
            'icon' => $icon,
            'tone' => $tone,
            'url' => $viewUrl($id),
        ] : null;

        $attention = array_values(array_filter([
            $card('needs-editor', 'new', 'New'),
            $card('reviews-submitted', 'decide', 'ReviewSent'),
            $card('revisions-submitted', 'revisions', 'Upload'),
            $card('reviews-overdue', 'overdue', 'Overdue', 'alert'),
        ]));
        if (!$attention) {
            // Section editors without "needs editor": show their own queue
            $attention = array_values(array_filter([$card('assigned-to-me', 'assigned', 'User')]));
        }

        $pipeline = [];
        foreach ([
            'initial-review' => 'submission',
            'external-review' => 'review',
            'copyediting' => 'copyediting',
            'production' => 'production',
            'scheduled' => 'scheduled',
            'published' => 'published',
        ] as $id => $key) {
            if ($views->has($id)) {
                $pipeline[] = [
                    'key' => $key,
                    'count' => $views->get($id)->getCount(),
                    'label' => __("plugins.generic.meridianAdmin.stage.{$key}"),
                    'hint' => __("plugins.generic.meridianAdmin.stage.{$key}.hint"),
                    'url' => $viewUrl($id),
                ];
            }
        }

        $recent = [];
        if ($views->has('active')) {
            $submissions = $views->get('active')->getCollector()
                ->orderBy(Collector::ORDERBY_LAST_MODIFIED, Collector::ORDER_DIR_DESC)
                ->limit(6)
                ->getMany();
            foreach ($submissions as $submission) {
                $recent[] = $this->row($request, $submission, 'editorial');
            }
        }

        $actions = [
            ['icon' => 'Add', 'label' => __('plugins.generic.meridianAdmin.action.submit'), 'url' => $request->url(null, 'submission')],
        ];
        if ($isManager) {
            $actions = array_merge($actions, [
                ['icon' => 'Issues', 'label' => __('plugins.generic.meridianAdmin.action.issues'), 'url' => $request->url(null, 'manageIssues')],
                ['icon' => 'Announcements', 'label' => __('plugins.generic.meridianAdmin.action.announce'), 'url' => $request->url(null, 'management', 'settings', ['announcements'])],
                ['icon' => 'Settings', 'label' => __('plugins.generic.meridianAdmin.action.website'), 'url' => $request->url(null, 'management', 'settings', ['website'])],
                ['icon' => 'User', 'label' => __('plugins.generic.meridianAdmin.action.users'), 'url' => $request->url(null, 'management', 'settings', ['access'])],
                ['icon' => 'Statistics', 'label' => __('plugins.generic.meridianAdmin.action.stats'), 'url' => $request->url(null, 'stats', 'publications', ['publications'])],
            ]);
        }

        return [
            'maAttention' => $attention,
            'maPipeline' => $pipeline,
            'maRecent' => $recent,
            'maActiveUrl' => $views->has('active') ? $viewUrl('active') : null,
            'maActions' => $actions,
            'maIssue' => $isManager ? $this->currentIssue($request, $context) : null,
        ];
    }

    protected function authorData(PKPRequest $request, $context, $user): array
    {
        $mine = [];
        try {
            $submissions = Repo::submission()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->assignedTo([$user->getId()], [Role::ROLE_ID_AUTHOR])
                ->orderBy(Collector::ORDERBY_LAST_MODIFIED, Collector::ORDER_DIR_DESC)
                ->limit(8)
                ->getMany();
            foreach ($submissions as $submission) {
                $mine[] = $this->row($request, $submission, 'mySubmissions');
            }
        } catch (Throwable $e) {
            error_log('meridianAdmin: ' . $e->getMessage());
        }

        $image = $user->getData('profileImage');
        $photoUrl = null;
        if (is_array($image) && !empty($image['uploadName'])) {
            $photoUrl = $request->getBaseUrl() . '/' . (new PublicFileManager())->getSiteFilesPath() . '/' . rawurlencode($image['uploadName']);
        }
        $orcidOn = (bool) Config::getVar('orcid', 'enabled', true) && $context->getData('orcidEnabled') !== false;
        $checks = [
            ['key' => 'photo', 'done' => (bool) $photoUrl],
            ['key' => 'bio', 'done' => trim(strip_tags((string) $user->getLocalizedData('biography'))) !== ''],
            ['key' => 'affiliation', 'done' => trim((string) $user->getLocalizedData('affiliation')) !== ''],
            ['key' => 'orcid', 'done' => (bool) $user->getData('orcid')],
            ['key' => 'url', 'done' => (bool) $user->getData('url')],
        ];
        if (!$orcidOn) {
            $checks = array_values(array_filter($checks, fn ($c) => $c['key'] !== 'orcid'));
        }
        foreach ($checks as &$check) {
            $check['label'] = __("plugins.generic.meridianAdmin.profile.{$check['key']}");
        }
        unset($check);
        $done = count(array_filter($checks, fn ($c) => $c['done']));

        return [
            'maMine' => $mine,
            'maMineUrl' => $request->url(null, 'dashboard', 'mySubmissions', null, ['currentViewId' => 'active']),
            'maPhotoUrl' => $photoUrl,
            'maInitials' => mb_strtoupper(mb_substr((string) $user->getLocalizedGivenName(), 0, 1) . mb_substr((string) $user->getLocalizedFamilyName(), 0, 1)),
            'maFullName' => $user->getFullName(),
            'maProfileChecks' => $checks,
            'maProfileDone' => $done,
            'maProfileTotal' => count($checks),
            'maProfileUrl' => $request->url(null, 'user', 'profile', null, null, 'publicProfile'),
            'maAuthorPageUrl' => $this->authorPageUrl($request, $context, $user),
        ];
    }

    protected function reviewerData(PKPRequest $request, $context, $user): array
    {
        $count = 0;
        try {
            $views = Repo::submission()->getDashboardViews($context, $user, [Role::ROLE_ID_REVIEWER], true);
            $count = $views->has('reviewer-action-required') ? $views->get('reviewer-action-required')->getCount() : 0;
        } catch (Throwable $e) {
            error_log('meridianAdmin: ' . $e->getMessage());
        }
        return [
            'maReviewCount' => $count,
            'maReviewUrl' => $request->url(null, 'dashboard', 'reviewAssignments'),
        ];
    }

    /**
     * One submission row: title, authors, a plain-language status and a link.
     */
    protected function row(PKPRequest $request, Submission $submission, string $dashboard): array
    {
        $publication = $submission->getCurrentPublication();
        $isDraft = (string) $submission->getData('submissionProgress') !== '';
        [$status, $tone] = match (true) {
            $isDraft => ['draft', 'draft'],
            $submission->getData('status') === Submission::STATUS_PUBLISHED => ['published', 'published'],
            $submission->getData('status') === Submission::STATUS_DECLINED => ['declined', 'declined'],
            $submission->getData('status') === Submission::STATUS_SCHEDULED => ['scheduled', 'scheduled'],
            default => match ((int) $submission->getData('stageId')) {
                1 => ['submission', 'submission'],
                2, 3 => ['review', 'review'],
                4 => ['copyediting', 'copyediting'],
                default => ['production', 'production'],
            },
        };
        $modified = $submission->getData('lastModified') ?: $submission->getData('dateSubmitted');

        return [
            'id' => $submission->getId(),
            'title' => $publication ? $publication->getLocalizedFullTitle() : '',
            'authors' => $publication ? $publication->getShortAuthorString() : '',
            'status' => __("plugins.generic.meridianAdmin.status.{$status}"),
            'tone' => $tone,
            'date' => $modified ? (new DateTime($modified))->format('j M Y') : '',
            'url' => $isDraft
                ? $request->url(null, 'submission', null, null, ['id' => $submission->getId()])
                : $request->url(null, 'dashboard', $dashboard, null, ['workflowSubmissionId' => $submission->getId()]),
        ];
    }

    protected function currentIssue(PKPRequest $request, $context): ?array
    {
        $issue = Repo::issue()->getCurrent($context->getId());
        $future = Repo::issue()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByPublished(false)
            ->getCount();
        $manageUrl = $request->url(null, 'manageIssues');
        if (!$issue) {
            return ['label' => null, 'viewUrl' => null, 'manageUrl' => $manageUrl, 'future' => $future];
        }
        $articles = Repo::submission()->getCollector()
            ->filterByContextIds([$context->getId()])
            ->filterByIssueIds([$issue->getId()])
            ->filterByStatus([Submission::STATUS_PUBLISHED])
            ->getCount();
        $published = $issue->getDatePublished();

        return [
            'label' => $issue->getIssueIdentification(),
            'title' => $issue->getLocalizedTitle(),
            'cover' => $issue->getLocalizedCoverImageUrl(),
            'articles' => $articles,
            'published' => $published ? (new DateTime($published))->format('j F Y') : '',
            'viewUrl' => $request->url(null, 'issue', 'view', [$issue->getBestIssueId()]),
            'manageUrl' => $manageUrl,
            'future' => $future,
        ];
    }

    /**
     * The author's public page, when the Author Pages plugin lists them.
     */
    protected function authorPageUrl(PKPRequest $request, $context, $user): ?string
    {
        $plugin = PluginRegistry::getPlugin('generic', 'authorpagesplugin');
        if (!$plugin || !$plugin->getEnabled() || !class_exists(AuthorDirectory::class)) {
            return null;
        }
        try {
            $directory = new AuthorDirectory($context->getId());
            $person = $directory->get(AuthorDirectory::keyFor($user->getData('orcid'), $user->getEmail(), $user->getFullName(false)));
            if (!$person) {
                // Published under an ORCID the account doesn't have yet: match by e-mail
                $email = strtolower((string) $user->getEmail());
                foreach ($directory->all() as $candidate) {
                    if ($email !== '' && strtolower((string) ($candidate['email'] ?? '')) === $email) {
                        $person = $candidate;
                        break;
                    }
                }
            }
            return $person ? $request->url(null, 'authors', 'view', [$person['key'], $person['slug']]) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function greeting(string $name): string
    {
        $hour = (int) (new DateTime())->format('G');
        $key = $hour < 12 ? 'morning' : ($hour < 18 ? 'afternoon' : 'evening');
        return __("plugins.generic.meridianAdmin.greeting.{$key}", ['name' => ucfirst($name)]);
    }
}
