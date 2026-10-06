<?php

/**
 * @file plugins/generic/reviewDecline/ReviewDeclinePlugin.php
 *
 * Distributed under the GNU GPL v3.
 *
 * @class ReviewDeclinePlugin
 *
 * @brief When a reviewer declines a review request, they choose a reason
 *  (no time, conflict of interest, outside their expertise, other), can write
 *  a comment to the editor and can suggest up to two alternative reviewers.
 *
 *  - The reason, comment and suggestions are stored with the review
 *    assignment (review_assignment_settings) and added to the decline email
 *    the editors receive.
 *  - Suggested reviewers become core reviewer suggestions, so editors can
 *    invite them from "Add Reviewer" (needs Settings › Workflow › Review ›
 *    Reviewer suggestions turned on).
 *  - Editors see the details in the reviewer's History and on the admin
 *    panel's Home page (Meridian Admin).
 *
 *  Only the decline form is replaced; the core decline flow is unchanged.
 */

namespace APP\plugins\generic\reviewDecline;

use APP\core\Application;
use APP\facades\Repo;
use APP\template\TemplateManager;
use DateTime;
use PKP\plugins\GenericPlugin;
use PKP\plugins\Hook;
use PKP\submission\reviewAssignment\ReviewAssignment;
use PKP\submission\reviewer\suggestion\ReviewerSuggestion;
use Throwable;

class ReviewDeclinePlugin extends GenericPlugin
{
    public const REASONS = ['noTime', 'conflict', 'expertise', 'other'];
    public const MAX_SUGGESTIONS = 2;

    /** The decline details parsed from the current request, if any */
    protected ?array $submitted = null;

    /**
     * @copydoc Plugin::register()
     *
     * @param null|mixed $mainContextId
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);
        if ($success && $this->getEnabled($mainContextId)) {
            Hook::add('Schema::get::reviewAssignment', $this->addSchemaProps(...));
            Hook::add('TemplateResource::getFilename', [$this, '_overridePluginTemplates']);
            Hook::add('ReviewerAction::confirmReview', $this->addToEmail(...));
            Hook::add('ReviewAssignment::edit', $this->saveDecline(...));
            Hook::add('TemplateManager::fetch', $this->assignHistory(...));
            Hook::add('TemplateManager::display', $this->assignWorkspace(...));
            Hook::add('TemplateManager::setupBackendPage', $this->addStyles(...));
        }
        return $success;
    }

    public function getDisplayName()
    {
        return __('plugins.generic.reviewDecline.displayName');
    }

    public function getDescription()
    {
        return __('plugins.generic.reviewDecline.description');
    }

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
     * Store the decline details with the review assignment.
     */
    public function addSchemaProps(string $hookName, array $args): bool
    {
        $schema = $args[0];
        $string = fn (string $description) => (object) ['type' => 'string', 'description' => $description, 'validation' => ['nullable']];
        $schema->properties->declineReason = $string('Why the reviewer declined: noTime, conflict, expertise or other');
        $schema->properties->declineReasonOther = $string('The reviewer\'s own reason when declineReason is other');
        $schema->properties->declineComments = $string('Comments from the declining reviewer to the editor');
        $schema->properties->declineSuggestions = (object) [
            'type' => 'array',
            'description' => 'Alternative reviewers suggested by the declining reviewer',
            'validation' => ['nullable'],
            'items' => (object) [
                'type' => 'object',
                'properties' => (object) [
                    'name' => (object) ['type' => 'string'],
                    'email' => (object) ['type' => 'string'],
                    'affiliation' => (object) ['type' => 'string'],
                ],
            ],
        ];
        return Hook::CONTINUE;
    }

    /**
     * Add the reason, comments and suggestions to the email the editors receive.
     */
    public function addToEmail(string $hookName, array $args): bool
    {
        [$request, $submission, $mailable, $decline] = $args;
        $details = $decline ? $this->submittedDetails() : null;
        if (!$details) {
            return Hook::CONTINUE;
        }

        $e = fn (string $text) => str_replace('{$', '{ $', htmlspecialchars($text, ENT_QUOTES));
        $html = '<p><strong>' . $e(__('plugins.generic.reviewDecline.reason')) . ':</strong> ' . $e($this->reasonLabel($details)) . '</p>';
        if ($details['declineComments'] !== '') {
            $html .= '<p><strong>' . $e(__('plugins.generic.reviewDecline.comments')) . ':</strong><br>' . nl2br($e($details['declineComments'])) . '</p>';
        }
        if ($details['declineSuggestions']) {
            $html .= '<p><strong>' . $e(__('plugins.generic.reviewDecline.suggestions')) . ':</strong></p><ul>';
            foreach ($details['declineSuggestions'] as $s) {
                $html .= '<li>' . $e($s['name']) . ' &lt;' . $e($s['email']) . '&gt;' . ($s['affiliation'] !== '' ? ', ' . $e($s['affiliation']) : '') . '</li>';
            }
            $html .= '</ul>';
        }
        $mailable->body($html . '<hr>' . (string) $mailable->view);
        return Hook::CONTINUE;
    }

    /**
     * Save the details when core marks the assignment as declined, in the same
     * update, so they are not removed by a later save.
     */
    public function saveDecline(string $hookName, array $args): bool
    {
        [$newReviewAssignment, $reviewAssignment, $params] = $args;
        if (empty($params['declined'])) {
            return Hook::CONTINUE;
        }
        $details = $this->submittedDetails();
        if (!$details) {
            return Hook::CONTINUE;
        }
        foreach ($details as $key => $value) {
            $newReviewAssignment->setData($key, $value);
        }
        $this->createSuggestions($reviewAssignment, $details['declineSuggestions']);
        return Hook::CONTINUE;
    }

    /**
     * Turn suggested reviewers into core reviewer suggestions, so editors can
     * invite them from "Add Reviewer".
     */
    protected function createSuggestions(ReviewAssignment $reviewAssignment, array $suggestions): void
    {
        if (!$suggestions) {
            return;
        }
        try {
            $submission = Repo::submission()->get($reviewAssignment->getSubmissionId());
            $locale = $submission?->getData('locale') ?: 'en';
            $reviewer = Repo::user()->get($reviewAssignment->getReviewerId(), true);
            $reason = __('plugins.generic.reviewDecline.suggestionReason', ['reviewer' => $reviewer?->getFullName() ?? '']);
            foreach ($suggestions as $s) {
                $exists = ReviewerSuggestion::query()
                    ->withSubmissionIds($reviewAssignment->getSubmissionId())
                    ->withEmail($s['email'])
                    ->exists();
                if ($exists) {
                    continue;
                }
                $parts = preg_split('/\s+/', trim($s['name']));
                $family = count($parts) > 1 ? array_pop($parts) : $parts[0];
                $given = count($parts) ? implode(' ', $parts) : $family;
                ReviewerSuggestion::create([
                    'submissionId' => $reviewAssignment->getSubmissionId(),
                    'suggestingUserId' => $reviewAssignment->getReviewerId(),
                    'givenName' => [$locale => $given],
                    'familyName' => [$locale => $family],
                    'email' => $s['email'],
                    'affiliation' => [$locale => $s['affiliation'] !== '' ? $s['affiliation'] : '—'],
                    'suggestionReason' => [$locale => $reason],
                ]);
            }
        } catch (Throwable $e) {
            error_log('reviewDecline: could not save reviewer suggestions: ' . $e->getMessage());
        }
    }

    /**
     * The reviewer's History (Reviewers › ⋯ › History) shows the decline details.
     */
    public function assignHistory(string $hookName, array $args): bool
    {
        [$templateMgr, $template] = $args;
        if ($template !== 'workflow/reviewHistory.tpl') {
            return Hook::CONTINUE;
        }
        $id = (int) Application::get()->getRequest()->getUserVar('reviewAssignmentId');
        $reviewAssignment = $id ? Repo::reviewAssignment()->get($id) : null;
        if ($reviewAssignment && $reviewAssignment->getDeclined() && $reviewAssignment->getData('declineReason')) {
            $templateMgr->assign('reviewDecline', $this->present($reviewAssignment));
        }
        return Hook::CONTINUE;
    }

    /**
     * Recent declines on the admin panel's Home page (Meridian Admin).
     */
    public function assignWorkspace(string $hookName, array $args): bool
    {
        [$templateMgr, $template] = $args;
        if (!is_string($template) || !str_ends_with($template, 'workspace.tpl') || !$templateMgr->getTemplateVars('maIsEditor')) {
            return Hook::CONTINUE;
        }
        $request = Application::get()->getRequest();
        $context = $request->getContext();
        if (!$context) {
            return Hook::CONTINUE;
        }
        $since = (new DateTime('-60 days'))->format('Y-m-d');
        $declines = [];
        try {
            $assignments = Repo::reviewAssignment()->getCollector()
                ->filterByContextIds([$context->getId()])
                ->filterByDeclined(true)
                ->getMany()
                ->filter(fn (ReviewAssignment $ra) => $ra->getDateConfirmed() && $ra->getDateConfirmed() >= $since)
                ->sortByDesc(fn (ReviewAssignment $ra) => $ra->getDateConfirmed())
                ->take(5);
            foreach ($assignments as $ra) {
                $item = $this->present($ra);
                $item['url'] = $request->url(null, 'dashboard', 'editorial', null, ['workflowSubmissionId' => $ra->getSubmissionId()]);
                $declines[] = $item;
            }
        } catch (Throwable $e) {
            error_log('reviewDecline: ' . $e->getMessage());
        }
        $templateMgr->assign('rdDeclines', $declines);
        return Hook::CONTINUE;
    }

    public function addStyles(string $hookName, array $args): bool
    {
        $request = Application::get()->getRequest();
        TemplateManager::getManager($request)->addStyleSheet(
            'reviewDecline',
            $request->getBaseUrl() . '/' . $this->getPluginPath() . '/css/reviewDecline.css?v=' . filemtime(__DIR__ . '/css/reviewDecline.css'),
            ['contexts' => ['backend'], 'priority' => TemplateManager::STYLE_SEQUENCE_LAST]
        );
        return Hook::CONTINUE;
    }

    /**
     * Decline details for display.
     */
    protected function present(ReviewAssignment $ra): array
    {
        $reviewer = Repo::user()->get($ra->getReviewerId(), true);
        $submission = Repo::submission()->get($ra->getSubmissionId());
        return [
            'submissionId' => $ra->getSubmissionId(),
            'title' => $submission?->getCurrentPublication()?->getLocalizedFullTitle() ?? '',
            'reviewer' => $reviewer?->getFullName() ?? '',
            'date' => $ra->getDateConfirmed() ? (new DateTime($ra->getDateConfirmed()))->format('j M Y') : '',
            'reason' => $ra->getData('declineReason') ? $this->reasonLabel([
                'declineReason' => $ra->getData('declineReason'),
                'declineReasonOther' => (string) $ra->getData('declineReasonOther'),
            ]) : '',
            'reasonKey' => (string) $ra->getData('declineReason'),
            'comments' => (string) $ra->getData('declineComments'),
            'suggestions' => (array) ($ra->getData('declineSuggestions') ?? []),
        ];
    }

    protected function reasonLabel(array $details): string
    {
        $label = __('plugins.generic.reviewDecline.reason.' . $details['declineReason']);
        if ($details['declineReason'] === 'other' && ($details['declineReasonOther'] ?? '') !== '') {
            $label .= ': ' . $details['declineReasonOther'];
        }
        return $label;
    }

    /**
     * Parse and validate the fields of the decline form, once per request.
     * Returns null when the request is not a decline submitted with this form
     * (e.g. an editor recording a response for the reviewer).
     */
    protected function submittedDetails(): ?array
    {
        if ($this->submitted !== null) {
            return $this->submitted ?: null;
        }
        $request = Application::get()->getRequest();
        $reason = $request->getUserVar('declineReason');
        if (!is_string($reason) || $reason === '') {
            $this->submitted = [];
            return null;
        }

        $clean = fn ($value, int $max) => mb_substr(trim(strip_tags((string) $value)), 0, $max);
        $suggestions = [];
        if ($request->getUserVar('suggestAlternatives') === 'yes') {
            $names = (array) $request->getUserVar('suggestName');
            $emails = (array) $request->getUserVar('suggestEmail');
            $affiliations = (array) $request->getUserVar('suggestAffiliation');
            foreach (array_keys($names) as $i) {
                $name = $clean($names[$i] ?? '', 150);
                $email = $clean($emails[$i] ?? '', 150);
                if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }
                $suggestions[] = ['name' => $name, 'email' => mb_strtolower($email), 'affiliation' => $clean($affiliations[$i] ?? '', 255)];
                if (count($suggestions) === self::MAX_SUGGESTIONS) {
                    break;
                }
            }
        }

        $this->submitted = [
            'declineReason' => in_array($reason, self::REASONS, true) ? $reason : 'other',
            'declineReasonOther' => $clean($request->getUserVar('declineReasonOther'), 300),
            'declineComments' => $clean($request->getUserVar('declineComments'), 5000),
            'declineSuggestions' => $suggestions,
        ];
        return $this->submitted;
    }
}
