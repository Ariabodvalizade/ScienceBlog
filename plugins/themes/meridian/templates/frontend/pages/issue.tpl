{**
 * plugins/themes/meridian/templates/frontend/pages/issue.tpl
 *
 * @brief Issue page: hero (identification, cover, description, identifiers,
 *  full-issue galleys) + table of contents + archive rail.
 *
 * @uses $issue Issue
 * @uses $issueIdentification string
 * @uses $issueGalleys array
 * @uses $meridianArchive array Published issues grouped by year
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$issueIdentification}

<div class="page_issue m-page">
	{if !$issue}
		<header class="m-hero">
			<div class="m-container m-hero__center">
				<h1 class="m-hero__title">{translate key="current.noCurrentIssue"}</h1>
				<p class="m-hero__lead">{translate key="current.noCurrentIssueDesc"}</p>
			</div>
		</header>
	{else}
		{assign var="articleCount" value=0}
		{foreach from=$publishedSubmissions item=section}{assign var="articleCount" value=$articleCount+$section.articles|@count}{/foreach}
		{assign var=issueCover value=$issue->getLocalizedCoverImageUrl()}
		{assign var=doiObject value=$issue->getData('doiObject')}

		<header class="m-hero m-hero--issue">
			<div class="m-container m-hero__split">
				<div class="m-hero__text">
					{include file="frontend/components/breadcrumbs_issue.tpl" currentTitle=$issueIdentification}
					<h1 class="m-hero__title">
						{if $issue->getShowVolume() && $issue->getShowNumber()}
							{translate key="plugins.themes.meridian.volumeIssue" volume=$issue->getVolume()|escape number=$issue->getNumber()|escape}
						{else}
							{$issueSeries|escape}
						{/if}
					</h1>
					{if $issueTitle}<p class="m-hero__subtitle">{$issueTitle|escape}</p>{/if}
					<p class="m-hero__meta">
						{if $issue->getDatePublished()}<span>{$issue->getDatePublished()|date_format:"F Y"}</span>{/if}
						<span>{translate key="plugins.themes.meridian.articles" count=$articleCount}</span>
						{if $doiObject}<span><a href="{$doiObject->getData('resolvingUrl')|escape}">doi:{$doiObject->getData('doi')|escape}</a></span>{/if}
					</p>
					{if $issue->hasDescription()}
						<div class="m-hero__lead">{$issue->getLocalizedDescription()|strip_unsafe_html}</div>
					{/if}
					{if $issueGalleys}
						<div class="m-hero__actions">
							{foreach from=$issueGalleys item=galley}
								{include file="frontend/objects/galley_link.tpl" parent=$issue galleyClass="m-btn m-btn--primary" purchaseFee=$currentJournal->getData('purchaseIssueFee') purchaseCurrency=$currentJournal->getData('currency')}
							{/foreach}
						</div>
					{/if}
				</div>
				{if $issueCover}
					<div class="m-hero__cover">
						{capture assign="defaultAltText"}{translate key="issue.viewIssueIdentification" identification=$issue->getIssueIdentification()|escape}{/capture}
						<img src="{$issueCover|escape}" alt="{$issue->getLocalizedCoverImageAltText()|escape|default:$defaultAltText}">
					</div>
				{/if}
			</div>
		</header>

		<div class="m-container m-layout">
			<div class="m-layout__main">
				{include file="frontend/objects/issue_toc.tpl"}
			</div>
			<aside class="m-layout__rail" aria-label="{translate|escape key="plugins.themes.meridian.archive"}">
				{include file="frontend/components/meridian_archive_rail.tpl"}
			</aside>
		</div>
	{/if}
</div>

{include file="frontend/components/footer.tpl"}
