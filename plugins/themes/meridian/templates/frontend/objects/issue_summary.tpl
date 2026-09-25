{**
 * plugins/themes/meridian/templates/frontend/objects/issue_summary.tpl
 *
 * @brief Issue card: cover (or typographic placeholder), identification, date.
 *
 * @uses $issue Issue
 *}
{assign var=issueCover value=$issue->getLocalizedCoverImageUrl()}
{capture assign="issueUrl"}{url page="issue" op="view" path=$issue->getBestIssueId()}{/capture}

<article class="obj_issue_summary m-issue-card">
	<a class="m-issue-card__cover" href="{$issueUrl}" tabindex="-1" aria-hidden="true">
		{if $issueCover}
			<img src="{$issueCover|escape}" alt="" loading="lazy">
		{else}
			<span class="m-issue-card__placeholder">
				<span class="m-meta">{$currentContext->getLocalizedAcronym()|default:$currentContext->getLocalizedName()|escape}</span>
				<span class="m-issue-card__placeholder-id">{$issue->getIssueIdentification(['showTitle' => false, 'showYear' => false])|escape}</span>
			</span>
		{/if}
	</a>
	<h3 class="m-issue-card__title">
		<a href="{$issueUrl}">{$issue->getIssueIdentification(['showTitle' => false])|escape}</a>
	</h3>
	{if $issue->getShowTitle() && $issue->getLocalizedTitle()}
		<p class="m-issue-card__subtitle">{$issue->getLocalizedTitle()|escape}</p>
	{/if}
	{if $issue->getDatePublished()}
		<p class="m-issue-card__date m-meta">{$issue->getDatePublished()|date_format:"F Y"}</p>
	{/if}
</article>
