{**
 * plugins/themes/meridian/templates/frontend/objects/article_summary.tpl
 *
 * @brief Article list item (issue TOC, search, categories): type label, title,
 *  authors, pages/DOI, galley links and an inline abstract toggle.
 *
 * @uses $article Submission
 * @uses $journal Journal (optional) Journal the article belongs to (site-wide search)
 * @uses $hideGalleys bool Hide the galley links
 * @uses $showDatePublished bool Show the publication date
 * @uses $typeLabel string (optional) Article type / section label shown above the title
 * @uses $heading string Heading element for the title
 *
 * @hook Templates::Issue::Issue::Article []
 *}
{assign var=publication value=$article->getCurrentPublication()}
{assign var=articlePath value=$publication->getData('urlPath')|default:$article->getId()}
{if !$heading}{assign var="heading" value="h3"}{/if}

{if (!$section.hideAuthor && $publication->getData('hideAuthor') == APP\submission\Submission::AUTHOR_TOC_DEFAULT) || $publication->getData('hideAuthor') == APP\submission\Submission::AUTHOR_TOC_SHOW}
	{assign var="showAuthor" value=true}
{/if}
{if $journal}
	{capture assign="articleUrl"}{url journal=$journal->getPath() page="article" op="view" path=$articlePath}{/capture}
{else}
	{capture assign="articleUrl"}{url page="article" op="view" path=$articlePath}{/capture}
{/if}
{assign var=doiObject value=$publication->getData('doiObject')}
{assign var=abstract value=$publication->getLocalizedData('abstract')}

<article class="obj_article_summary m-item">
	{if $publication->getLocalizedData('coverImage')}
		{assign var="coverImage" value=$publication->getLocalizedData('coverImage')}
		<a class="m-item__cover" href="{$articleUrl}" tabindex="-1" aria-hidden="true">
			<img src="{$publication->getLocalizedCoverImageUrl($article->getData('contextId'))|escape}" alt="{$coverImage.altText|escape|default:''}" loading="lazy">
		</a>
	{/if}

	<div class="m-item__body">
		{if $typeLabel}<p class="m-item__type">{$typeLabel|escape}</p>{/if}

		<{$heading} class="m-item__title title">
			<a id="article-{$article->getId()}" href="{$articleUrl}">
				{if $currentContext}
					{$publication->getLocalizedTitle(null, 'html')|strip_unsafe_html}
				{else}
					{$publication->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}
				{/if}
				{assign var=localizedSubtitle value=$publication->getLocalizedSubtitle(null, 'html')|strip_unsafe_html}
				{if $localizedSubtitle}<span class="m-item__subtitle">{$localizedSubtitle}</span>{/if}
			</a>
		</{$heading}>

		{if $showAuthor && $publication->getData('authors')}
			<p class="m-item__authors">{foreach from=$publication->getData('authors') item=author name=authors}{$author->getFullName()|escape}{if !$smarty.foreach.authors.last}, {/if}{/foreach}</p>
		{/if}

		{assign var=submissionPages value=$publication->getData('pages')}
		{assign var=submissionDatePublished value=$publication->getData('datePublished')}
		{if $submissionPages || $doiObject || ($showDatePublished && $submissionDatePublished) || (!$currentContext && $journal)}
			<p class="m-item__meta">
				{if !$currentContext && $journal}<span>{$journal->getLocalizedName()|escape}</span>{/if}
				{if $showDatePublished && $submissionDatePublished}<span>{$submissionDatePublished|date_format:$dateFormatLong}</span>{/if}
				{if $submissionPages}<span>{translate key="plugins.themes.meridian.pages" pages=$submissionPages|escape}</span>{/if}
				{if $doiObject}<span><a href="{$doiObject->getData('resolvingUrl')|escape}">doi:{$doiObject->getData('doi')|escape}</a></span>{/if}
			</p>
		{/if}

		{if !$hideGalleys || $abstract}
			<div class="m-item__actions">
				{if !$hideGalleys}
					{foreach from=$publication->getData('galleys') item=galley}
						{if $primaryGenreIds}
							{assign var="file" value=$galley->getFile()}
							{if !$galley->getData('urlRemote') && !($file && in_array($file->getGenreId(), $primaryGenreIds))}
								{continue}
							{/if}
						{/if}
						{assign var="hasArticleAccess" value=$hasAccess}
						{if $currentContext->getSetting('publishingMode') == APP\journal\Journal::PUBLISHING_MODE_OPEN || $publication->getData('accessStatus') == APP\submission\Submission::ARTICLE_ACCESS_OPEN}
							{assign var="hasArticleAccess" value=1}
						{/if}
						{assign var="id" value="article-{$article->getId()}-galley-{$galley->getId()}"}
						{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication id=$id labelledBy="{$id} article-{$article->getId()}" hasAccess=$hasArticleAccess purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
					{/foreach}
				{/if}
				{if $abstract}
					<button type="button" class="m-toggle" aria-expanded="false" aria-controls="m-abs-{$article->getId()}">
						{translate key="plugins.themes.meridian.abstract.show"}
						<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-chevron"/></svg>
					</button>
				{/if}
			</div>
			{if $abstract}
				<div class="m-item__abstract" id="m-abs-{$article->getId()}" hidden>
					{$abstract|strip_unsafe_html}
				</div>
			{/if}
		{/if}

		{call_hook name="Templates::Issue::Issue::Article"}
	</div>
</article>
