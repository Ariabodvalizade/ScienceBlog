{**
 * plugins/themes/meridian/templates/frontend/objects/issue_toc.tpl
 *
 * @brief Issue table of contents: articles grouped by section. The issue
 *  header (cover, description, identifiers) is rendered by pages/issue.tpl.
 *
 * @uses $issue Issue
 * @uses $publishedSubmissions array Articles grouped by section
 * @uses $heading string Heading element for section titles, default h2
 *}
{if !$heading}{assign var="heading" value="h2"}{/if}
{assign var="articleHeading" value="h3"}
{if $heading == "h3"}{assign var="articleHeading" value="h4"}{/if}

<div class="obj_issue_toc m-toc">
	{if !$issue->getPublished()}
		{include file="frontend/components/notification.tpl" type="warning" messageKey="editor.issues.preview"}
	{/if}

	{foreach name=sections from=$publishedSubmissions item=section}
		{if $section.articles}
			<section class="m-toc__section">
				{if $section.title}
					<{$heading} class="m-toc__heading">
						<span>{$section.title|escape}</span>
						<span class="m-toc__count">{$section.articles|@count}</span>
					</{$heading}>
				{/if}
				<ul class="m-list">
					{foreach from=$section.articles item=article}
						<li>{include file="frontend/objects/article_summary.tpl" heading=$articleHeading typeLabel=$section.title}</li>
					{/foreach}
				</ul>
			</section>
		{/if}
	{/foreach}
</div>
