{**
 * plugins/themes/meridian/templates/frontend/objects/article_details.tpl
 *
 * @brief Full article view: hero (type, title, authors, DOI) and a three-column
 *  body — left: article info, outline, share; center: affiliations, abstract,
 *  keywords, full text, references, author bios, license; right: downloads,
 *  DOI, how to cite, more from this issue, archive.
 *
 * Same variables as the core template (see templates/frontend/objects/article_details.tpl)
 * plus Meridian's: $meridianAuthors, $meridianAffiliations, $meridianIssueArticles,
 * $meridianArchive; and, when the scholarlyReader plugin is enabled:
 * $scholarlyReferences (linked reference HTML) and $scholarlyOutline (full-text headings).
 *
 * @hook Templates::Article::Main []
 * @hook Templates::Article::Details::Reference []
 * @hook Templates::Article::Details []
 *}
{assign var=doiObject value=$article->getCurrentPublication()->getData('doiObject')}
{capture assign="articleUrl"}{url page="article" op="view" path=$article->getBestId()}{/capture}
{assign var=keywords value=$publication->getLocalizedData('keywords')}
{assign var=abstract value=$publication->getLocalizedData('abstract')}
{assign var=hasReferences value=false}
{if count($parsedCitations) || $publication->getData('citationsRaw')}{assign var=hasReferences value=true}{/if}
{assign var="hasBiographies" value=0}
{foreach from=$publication->getData('authors') item=author}
	{if $author->getLocalizedData('biography')}{assign var="hasBiographies" value=$hasBiographies+1}{/if}
{/foreach}

<article class="obj_article_details m-article">

	{if $publication->getData('status') !== PKP\submission\PKPSubmission::STATUS_PUBLISHED}
		<div class="m-container">
			<div class="cmp_notification notice">
				{capture assign="submissionUrl"}{url page="dashboard" op="editorial" workflowSubmissionId=$article->getId()}{/capture}
				{translate key="submission.viewingPreview" url=$submissionUrl}
			</div>
		</div>
	{elseif $currentPublication->getId() !== $publication->getId()}
		<div class="m-container">
			<div class="cmp_notification notice">
				{capture assign="latestVersionUrl"}{url page="article" op="view" path=$article->getBestId()}{/capture}
				{translate key="submission.outdatedVersion" datePublished=$publication->getData('datePublished')|date_format:$dateFormatShort urlRecentVersion=$latestVersionUrl|escape}
			</div>
		</div>
	{/if}

	{* ---------------------------------------------------------------- Hero *}
	<header class="m-article-hero">
		<div class="m-container m-article-hero__inner">
			<p class="m-article-hero__meta">
				{if $section}<span>{$section->getLocalizedTitle()|escape}</span>{/if}
				{if $publication->getData('datePublished')}<span>{translate key="plugins.themes.meridian.published"} {$firstPublication->getData('datePublished')|date_format:$dateFormatLong}</span>{/if}
				{if $issue}<span><a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|escape}</a></span>{/if}
			</p>

			<h1 class="m-article-hero__title page_title">{$publication->getLocalizedTitle(null, 'html')|strip_unsafe_html}</h1>
			{if $publication->getLocalizedData('subtitle')}
				<p class="m-article-hero__subtitle">{$publication->getLocalizedSubTitle(null, 'html')|strip_unsafe_html}</p>
			{/if}

			{if $meridianAuthors}
				<h2 class="m-sr">{translate key="article.authors"}</h2>
				<ul class="m-article-hero__authors">
					{foreach from=$meridianAuthors item=entry}
						{assign var=author value=$entry.author}
						<li>
							{if $authorPageUrls[$author->getId()]}<a class="m-author-name m-author-link" href="{$authorPageUrls[$author->getId()]|escape}">{$author->getFullName()|escape}</a>{else}<span class="m-author-name">{$author->getFullName()|escape}</span>{/if}{if $entry.marks}<sup>{foreach from=$entry.marks item=mark name=marks}{$mark}{if !$smarty.foreach.marks.last},{/if}{/foreach}</sup>{/if}
							{if $author->getData('orcid')}
								<a class="m-orcid" href="{$author->getData('orcid')|escape}" target="_blank" rel="noopener" title="ORCID {$author->getOrcidDisplayValue()|escape}{if !$author->hasVerifiedOrcid()} (unauthenticated){/if}">
									{if $author->hasVerifiedOrcid()}{$orcidIcon}{else}{$orcidUnauthenticatedIcon}{/if}
									<span class="m-sr">ORCID {$author->getOrcidDisplayValue()|escape}</span>
								</a>
							{/if}
						</li>
					{/foreach}
				</ul>
			{/if}

			{if $doiObject}
				<p class="m-article-hero__doi">
					<a href="{$doiObject->getData('resolvingUrl')|escape}">{$doiObject->getData('resolvingUrl')|escape}</a>
				</p>
			{/if}

			{if $primaryGalleys}
				<div class="m-article-hero__galleys">
					{foreach from=$primaryGalleys item=galley name=heroGalleys}
						{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley galleyClass="m-btn{if $smarty.foreach.heroGalleys.first} m-btn--primary{/if}" purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
					{/foreach}
				</div>
			{/if}
		</div>
	</header>

	{* ---------------------------------------------------------------- Body *}
	<div class="m-container m-article-grid">

		{* Left rail: article info, outline, share *}
		<aside class="m-article-left" aria-label="{translate|escape key="plugins.themes.meridian.articleInfo"}">
			<section class="m-rail-block m-article-info">
				<h2 class="m-rail-block__title">{translate key="plugins.themes.meridian.articleInfo"}</h2>
				{if $issue && $issue->getLocalizedCoverImageUrl()}
					<a class="m-article-info__cover" href="{url page="issue" op="view" path=$issue->getBestIssueId()}">
						<img src="{$issue->getLocalizedCoverImageUrl()|escape}" alt="{$issue->getLocalizedCoverImageAltText()|escape|default:$issue->getIssueIdentification()|escape}">
					</a>
				{/if}
				<dl class="m-facts">
					{if $article->getData('dateSubmitted')}
						<div><dt>{translate key="submissions.submitted"}</dt><dd>{$article->getData('dateSubmitted')|date_format:$dateFormatLong}</dd></div>
					{/if}
					{if $publication->getData('datePublished')}
						<div><dt>{translate key="submissions.published"}</dt><dd>{$firstPublication->getData('datePublished')|date_format:$dateFormatLong}</dd></div>
						{if $firstPublication->getId() !== $publication->getId()}
							<div><dt>{translate key="common.updated"}</dt><dd>{$publication->getData('datePublished')|date_format:$dateFormatLong}</dd></div>
						{/if}
					{/if}
					{if $issue}
						<div><dt>{translate key="issue.issue"}</dt><dd><a href="{url page="issue" op="view" path=$issue->getBestIssueId()}">{$issue->getIssueIdentification()|escape}</a></dd></div>
					{/if}
					{if $publication->getData('pages')}
						<div><dt>{translate key="plugins.themes.meridian.pagesLabel"}</dt><dd>{$publication->getData('pages')|escape}</dd></div>
					{/if}
					{if $section}
						<div><dt>{translate key="section.section"}</dt><dd>{$section->getLocalizedTitle()|escape}</dd></div>
					{/if}
				</dl>
				{if count($article->getPublishedPublications()) > 1}
					<p class="m-rail-block__label">{translate key="submission.versions"}</p>
					<ul class="m-rail-list">
						{foreach from=array_reverse($article->getPublishedPublications()) item=iPublication}
							{capture assign="name"}{translate key="submission.versionIdentity" datePublished=$iPublication->getData('datePublished')|date_format:$dateFormatShort version=$iPublication->getData('version')}{/capture}
							<li>
								{if $iPublication->getId() === $publication->getId()}
									<span aria-current="true">{$name}</span>
								{elseif $iPublication->getId() === $currentPublication->getId()}
									<a href="{url page="article" op="view" path=$article->getBestId()}">{$name}</a>
								{else}
									<a href="{url page="article" op="view" path=$article->getBestId()|to_array:"version":$iPublication->getId()}">{$name}</a>
								{/if}
							</li>
						{/foreach}
					</ul>
				{/if}
			</section>

			<section class="m-rail-block m-share">
				<h2 class="m-rail-block__title">{translate key="plugins.themes.meridian.share"}</h2>
				{capture assign="shareTitle"}{$publication->getLocalizedTitle()|strip_tags}{/capture}
				<ul class="m-share__list">
					<li><a href="mailto:?subject={$shareTitle|escape:"url"}&amp;body={$articleUrl|trim|escape:"url"}" title="{translate|escape key="plugins.themes.meridian.shareBy" network="email"}"><svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-mail"/></svg><span class="m-sr">{translate key="plugins.themes.meridian.shareBy" network="email"}</span></a></li>
					<li><a href="https://www.linkedin.com/sharing/share-offsite/?url={$articleUrl|trim|escape:"url"}" target="_blank" rel="noopener" title="{translate|escape key="plugins.themes.meridian.shareBy" network="LinkedIn"}"><svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-linkedin"/></svg><span class="m-sr">{translate key="plugins.themes.meridian.shareBy" network="LinkedIn"}</span></a></li>
					<li><a href="https://x.com/intent/post?url={$articleUrl|trim|escape:"url"}&amp;text={$shareTitle|escape:"url"}" target="_blank" rel="noopener" title="{translate|escape key="plugins.themes.meridian.shareBy" network="X"}"><svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-x"/></svg><span class="m-sr">{translate key="plugins.themes.meridian.shareBy" network="X"}</span></a></li>
					<li><a href="https://www.facebook.com/sharer/sharer.php?u={$articleUrl|trim|escape:"url"}" target="_blank" rel="noopener" title="{translate|escape key="plugins.themes.meridian.shareBy" network="Facebook"}"><svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-facebook"/></svg><span class="m-sr">{translate key="plugins.themes.meridian.shareBy" network="Facebook"}</span></a></li>
				</ul>
			</section>

			<nav class="m-rail-block m-outline" aria-label="{translate|escape key="plugins.themes.meridian.onThisPage"}" data-m-outline>
				<h2 class="m-rail-block__title">{translate key="plugins.themes.meridian.onThisPage"}</h2>
				<ol class="m-outline__list">
					{if $abstract}<li><a href="#abstract">{translate key="article.abstract"}</a></li>{/if}
					{if $keywords}<li><a href="#keywords">{translate key="plugins.themes.meridian.keywords"}</a></li>{/if}
					{if $scholarlyOutline}
						{foreach from=$scholarlyOutline item=heading}
							<li class="m-outline__level-{$heading.level|escape}"><a href="#{$heading.id|escape}">{$heading.text|escape}</a></li>
						{/foreach}
					{/if}
					{if $hasReferences}<li><a href="#references">{translate key="plugins.themes.meridian.references"}</a></li>{/if}
					{if $hasBiographies}<li><a href="#authors">{translate key="plugins.themes.meridian.aboutAuthors"}</a></li>{/if}
					{if $publication->getData('licenseUrl') || $currentContext->getLocalizedData('licenseTerms')}<li><a href="#license">{translate key="plugins.themes.meridian.license"}</a></li>{/if}
				</ol>
			</nav>

		</aside>

		{* Center: the text *}
		<div class="m-article-main">

			{if $meridianAffiliations}
				<section class="m-affiliations">
					<h2 class="m-sr">{translate key="user.affiliation"}</h2>
					<ol class="m-affiliations__list">
						{foreach from=$meridianAffiliations key=index item=affiliation}
							<li value="{$index}"><sup>{$index}</sup> {$affiliation.name|escape}{if $affiliation.ror} <a class="m-ror" href="{$affiliation.ror|escape}" target="_blank" rel="noopener" title="ROR">{$rorIdIcon}<span class="m-sr">ROR</span></a>{/if}</li>
						{/foreach}
					</ol>
				</section>
			{/if}

			{if $abstract}
				<section class="m-abstract" id="abstract">
					<h2 class="m-section-title">{translate key="article.abstract"}</h2>
					<div class="m-abstract__body">{$abstract|strip_unsafe_html}</div>
				</section>
			{/if}

			{if $keywords}
				<section class="m-keywords" id="keywords">
					<h2 class="m-section-title">{translate key="plugins.themes.meridian.keywords"}</h2>
					<ul class="m-chips">
						{foreach from=$keywords item="keyword"}
							<li><a class="m-chip" href="{url page="search" op="search" query=$keyword.name}">{$keyword.name|escape}</a></li>
						{/foreach}
					</ul>
				</section>
			{/if}

			{call_hook name="Templates::Article::Main"}

			{if $publication->getLocalizedData('dataAvailability')}
				<section class="m-article-section" id="data-availability-statement">
					<h2 class="m-section-title">{translate key="submission.dataAvailability"}</h2>
					<div class="m-prose">{$publication->getLocalizedData('dataAvailability')|strip_unsafe_html}</div>
				</section>
			{/if}

			{if $hasReferences}
				<section class="m-references" id="references">
					<h2 class="m-section-title">{translate key="plugins.themes.meridian.references"}</h2>
					{if count($parsedCitations)}
						<ol class="m-references__list">
							{foreach from=$parsedCitations item="parsedCitation" name="refs"}
								<li id="ref-{$smarty.foreach.refs.iteration}">
									<span class="m-ref__text">{if $scholarlyReferences && $scholarlyReferences[$smarty.foreach.refs.index]}{$scholarlyReferences[$smarty.foreach.refs.index]}{else}{$parsedCitation->getCitationWithLinks()|strip_unsafe_html}{/if}</span>
									{call_hook name="Templates::Article::Details::Reference" citation=$parsedCitation}
								</li>
							{/foreach}
						</ol>
					{else}
						<div class="m-prose">{$publication->getData('citationsRaw')|escape|nl2br}</div>
					{/if}
				</section>
			{/if}

			{if $hasBiographies}
				<section class="m-authors" id="authors">
					<h2 class="m-section-title">{translate key="plugins.themes.meridian.aboutAuthors"}</h2>
					{foreach from=$publication->getData('authors') item=author}
						{if $author->getLocalizedData('biography')}
							<div class="m-author-box">
								{if $authorPagePhotos[$author->getId()]}<img class="m-author-box__avatar" src="{$authorPagePhotos[$author->getId()]|escape}" alt="" width="72" height="72" loading="lazy">{else}<span class="m-author-box__avatar" aria-hidden="true">{$author->getLocalizedGivenName()|truncate:1:""|escape}{$author->getLocalizedFamilyName()|truncate:1:""|escape}</span>{/if}
								<div class="m-author-box__body">
									<p class="m-author-box__name">{if $authorPageUrls[$author->getId()]}<a class="m-author-link" href="{$authorPageUrls[$author->getId()]|escape}">{$author->getFullName()|escape}</a>{else}{$author->getFullName()|escape}{/if}
										{if $author->getData('orcid')}
											<a class="m-orcid" href="{$author->getData('orcid')|escape}" target="_blank" rel="noopener">{if $author->hasVerifiedOrcid()}{$orcidIcon}{else}{$orcidUnauthenticatedIcon}{/if}<span class="m-sr">ORCID</span></a>
										{/if}
									</p>
									{if $author->getLocalizedAffiliationNamesAsString()}
										<p class="m-author-box__affiliation">{$author->getLocalizedAffiliationNamesAsString(null, ', ')|escape}</p>
									{/if}
									<div class="m-author-box__bio">{$author->getLocalizedData('biography')|strip_unsafe_html}</div>
								</div>
							</div>
						{/if}
					{/foreach}
				</section>
			{/if}

			{if $currentContext->getLocalizedData('licenseTerms') || $publication->getData('licenseUrl')}
				<section class="m-license" id="license">
					<h2 class="m-section-title">{translate key="plugins.themes.meridian.license"}</h2>
					<div class="m-license__body">
						{if $publication->getData('licenseUrl')}
							{if $meridianLicense}
								{if $publication->getLocalizedData('copyrightHolder')}
									<p>{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}</p>
								{/if}
								<p class="m-license__badge"><a class="m-cc" href="{$meridianLicense.url|escape}" rel="license noopener" target="_blank">{$meridianLicense.label|escape}</a> {$ccLicenseBadge|strip_tags:false|regex_replace:"/^\s*Creative Commons License\s*/":""}</p>
							{elseif $ccLicenseBadge}
								{if $publication->getLocalizedData('copyrightHolder')}
									<p>{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}</p>
								{/if}
								{$ccLicenseBadge}
							{else}
								<a href="{$publication->getData('licenseUrl')|escape}" class="copyright">
									{if $publication->getLocalizedData('copyrightHolder')}
										{translate key="submission.copyrightStatement" copyrightHolder=$publication->getLocalizedData('copyrightHolder') copyrightYear=$publication->getData('copyrightYear')}
									{else}
										{translate key="submission.license"}
									{/if}
								</a>
							{/if}
						{/if}
						{$currentContext->getLocalizedData('licenseTerms')}
					</div>
				</section>
			{/if}
		</div>

		{* Right rail: tools *}
		<aside class="m-article-right" aria-label="{translate|escape key="submission.downloads"}">
			{if $primaryGalleys || $supplementaryGalleys}
				<section class="m-rail-block m-downloads">
					<h2 class="m-sr">{translate key="submission.downloads"}</h2>
					{foreach from=$primaryGalleys item=galley name=galleys}
						{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley galleyClass="m-btn m-btn--block{if $smarty.foreach.galleys.first} m-btn--primary{/if}" purchaseFee=$currentJournal->getData('purchaseArticleFee') purchaseCurrency=$currentJournal->getData('currency')}
					{/foreach}
					{if $supplementaryGalleys}
						<p class="m-rail-block__label">{translate key="submission.additionalFiles"}</p>
						<ul class="m-rail-list">
							{foreach from=$supplementaryGalleys item=galley}
								<li>{include file="frontend/objects/galley_link.tpl" parent=$article publication=$publication galley=$galley isSupplementary="1"}</li>
							{/foreach}
						</ul>
					{/if}
				</section>
			{/if}

			{if $doiObject}
				<section class="m-rail-block m-doi">
					<h2 class="m-rail-block__label">{translate key="doi.readerDisplayName"}</h2>
					<p class="m-doi__value">
						<a href="{$doiObject->getData('resolvingUrl')|escape}">{$doiObject->getData('doi')|escape}</a>
						<button type="button" class="m-copy" data-m-copy="{$doiObject->getData('resolvingUrl')|escape}" data-m-copied="{translate|escape key="plugins.themes.meridian.copied"}">
							<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-copy"/></svg>
							<span class="m-copy-label">{translate key="plugins.themes.meridian.copy"}</span>
						</button>
					</p>
				</section>
			{/if}

			{foreach from=$pubIdPlugins item=pubIdPlugin}
				{if $pubIdPlugin->getPubIdType() == 'doi'}{continue}{/if}
				{assign var=pubId value=$publication->getStoredPubId($pubIdPlugin->getPubIdType())}
				{if $pubId}
					<section class="m-rail-block">
						<h2 class="m-rail-block__label">{$pubIdPlugin->getPubIdDisplayType()|escape}</h2>
						{if $pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}
							<a id="pub-id::{$pubIdPlugin->getPubIdType()|escape}" href="{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}">{$pubIdPlugin->getResolvingURL($currentJournal->getId(), $pubId)|escape}</a>
						{else}
							{$pubId|escape}
						{/if}
					</section>
				{/if}
			{/foreach}

			{if $categories}
				<section class="m-rail-block">
					<h2 class="m-rail-block__label">{translate key="category.category"}</h2>
					<ul class="m-rail-list">
						{foreach from=$categories item=category}
							<li><a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="catalog" op="category" path=$category->getPath()|escape}">{$category->getLocalizedTitle()|escape}</a></li>
						{/foreach}
					</ul>
				</section>
			{/if}

			<div class="m-rail-hooks">
				{call_hook name="Templates::Article::Details"}
			</div>

			{if $meridianIssueArticles}
				<section class="m-rail-block">
					<h2 class="m-rail-block__title">{translate key="plugins.themes.meridian.moreFromIssue"}</h2>
					{foreach from=$meridianIssueArticles item=item}
						{include file="frontend/objects/meridian_card.tpl" item=$item compact=true}
					{/foreach}
				</section>
			{/if}

			{include file="frontend/components/meridian_archive_rail.tpl"}
		</aside>
	</div>
</article>
