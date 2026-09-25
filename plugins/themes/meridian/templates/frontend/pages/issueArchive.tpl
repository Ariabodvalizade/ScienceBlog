{**
 * plugins/themes/meridian/templates/frontend/pages/issueArchive.tpl
 *
 * @brief Archive of published issues, grouped by year, as cover cards.
 *
 * @uses $issues Array Issues on this page (paginated)
 * @uses $prevPage int|null, $nextPage int|null, $showingStart, $showingEnd, $total
 *}
{capture assign="pageTitle"}
	{if $prevPage}{translate key="archive.archivesPageNumber" pageNumber=$prevPage+1}{else}{translate key="archive.archives"}{/if}
{/capture}
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page_issue_archive m-page">
	<header class="m-hero">
		<div class="m-container m-hero__center">
			{include file="frontend/components/breadcrumbs.tpl" currentTitle=$pageTitle}
			<h1 class="m-hero__title">{$pageTitle|escape}</h1>
			<p class="m-hero__lead">{translate key="plugins.themes.meridian.archive.description"}</p>
		</div>
	</header>

	<div class="m-container m-archive">
		{if empty($issues)}
			<p class="m-empty">{translate key="current.noCurrentIssueDesc"}</p>
		{else}
			{assign var="lastYear" value=""}
			{foreach from=$issues item="issue" name="archive"}
				{assign var="issueYear" value=$issue->getYear()}
				{if !$issueYear}{assign var="issueYear" value=$issue->getDatePublished()|date_format:"Y"}{/if}
				{if $issueYear != $lastYear}
					{if !$smarty.foreach.archive.first}</ul></section>{/if}
					<section class="m-archive__year">
						<h2 class="m-archive__heading">{$issueYear|escape}</h2>
						<ul class="m-archive__grid">
					{assign var="lastYear" value=$issueYear}
				{/if}
				<li>{include file="frontend/objects/issue_summary.tpl"}</li>
				{if $smarty.foreach.archive.last}</ul></section>{/if}
			{/foreach}

			{if $prevPage > 1}
				{capture assign=prevUrl}{url router=PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive" path=$prevPage}{/capture}
			{elseif $prevPage === 1}
				{capture assign=prevUrl}{url router=PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive"}{/capture}
			{/if}
			{if $nextPage}
				{capture assign=nextUrl}{url router=PKP\core\PKPApplication::ROUTE_PAGE page="issue" op="archive" path=$nextPage}{/capture}
			{/if}
			{include file="frontend/components/pagination.tpl" prevUrl=$prevUrl nextUrl=$nextUrl showingStart=$showingStart showingEnd=$showingEnd total=$total}
		{/if}
	</div>
</div>

{include file="frontend/components/footer.tpl"}
