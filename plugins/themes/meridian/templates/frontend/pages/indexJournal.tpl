{**
 * plugins/themes/meridian/templates/frontend/pages/indexJournal.tpl
 *
 * @brief Journal home page: hero, current issue, latest articles, aims &
 *  scope, announcements and indexing strip (sections toggled in theme options).
 *
 * @uses $currentJournal Journal
 * @uses $journalDescription string
 * @uses $issue Issue Current issue (with $publishedSubmissions)
 * @uses $announcements array, $numAnnouncementsHomepage int
 * @uses $highlights Collection
 * @uses $additionalHomeContent string
 * @uses $meridianLatestArticles array
 * @uses $meridianTagline string
 * @uses $meridianIndexingLogos array
 *
 * @hook Templates::Index::journal []
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div class="page_index_journal m-home">

	{call_hook name="Templates::Index::journal"}

	<section class="m-home-hero">
		<div class="m-container m-home-hero__inner">
			<p class="m-home-hero__eyebrow">
				{if $currentJournal->getData('onlineIssn')}<span>{translate key="plugins.themes.meridian.issn.online" issn=$currentJournal->getData('onlineIssn')|escape}</span>{/if}
				{if $currentJournal->getData('printIssn')}<span>{translate key="plugins.themes.meridian.issn.print" issn=$currentJournal->getData('printIssn')|escape}</span>{/if}
				<span>{translate key="plugins.themes.meridian.openAccess"}</span>
			</p>
			<p class="m-home-hero__title">{$currentJournal->getLocalizedName()|escape}</p>
			{if $meridianTagline}<p class="m-home-hero__tagline">{$meridianTagline|escape}</p>{/if}
			{if $journalDescription}
				<div class="m-home-hero__desc">{$journalDescription|strip_unsafe_html}</div>
			{/if}
			<div class="m-home-hero__actions">
				<a class="m-btn m-btn--primary" href="{url page="submission"}">{translate key="plugins.themes.meridian.submitManuscript"}</a>
				<a class="m-btn m-btn--secondary" href="{url page="about" op="submissions"}">{translate key="plugins.themes.meridian.authorGuidelines"}</a>
			</div>
		</div>
	</section>

	{if $highlights->count()}
		<div class="m-container m-home-highlights">
			{include file="frontend/components/highlights.tpl" highlights=$highlights}
		</div>
	{/if}

	{if $homepageImage}
		<div class="m-container m-home-image">
			<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}"{if $homepageImage.altText} alt="{$homepageImage.altText|escape}"{else} alt=""{/if}>
		</div>
	{/if}

	{* Current issue *}
	{if $issue && $meridian->isHomeSectionEnabled('currentIssue')}
		{assign var=issueCover value=$issue->getLocalizedCoverImageUrl()}
		{capture assign="issueUrl"}{url page="issue" op="view" path=$issue->getBestIssueId()}{/capture}
		<section class="m-home-section m-home-issue">
			<a id="homepageIssue"></a>
			<div class="m-container m-home-issue__grid{if !$issueCover} m-home-issue__grid--nocover{/if}">
				{if $issueCover}
					<a class="m-home-issue__cover" href="{$issueUrl}">
						<img src="{$issueCover|escape}" alt="{$issue->getLocalizedCoverImageAltText()|escape|default:$issue->getIssueIdentification()|escape}">
					</a>
				{/if}
				<div class="m-home-issue__toc">
					<header class="m-section-head">
						<div>
							<p class="m-meta">{$issue->getIssueIdentification(['showTitle' => false])|escape}{if $issue->getDatePublished()} · {$issue->getDatePublished()|date_format:"F Y"}{/if}</p>
							<h2 class="m-section-head__title">{translate key="plugins.themes.meridian.currentIssue"}</h2>
						</div>
						<a class="m-more" href="{$issueUrl}">
							{translate key="plugins.themes.meridian.viewIssue"}
							<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-arrow"/></svg>
						</a>
					</header>
					<ul class="m-list">
						{assign var="shown" value=0}
						{foreach from=$publishedSubmissions item=section}
							{foreach from=$section.articles item=article}
								{if $shown < 4}
									<li>{include file="frontend/objects/article_summary.tpl" heading="h3" typeLabel=$section.title hideGalleys=false}</li>
									{assign var="shown" value=$shown+1}
								{/if}
							{/foreach}
						{/foreach}
					</ul>
				</div>
			</div>
		</section>
	{/if}

	{* Latest articles *}
	{if $meridianLatestArticles}
		<section class="m-home-section m-home-latest">
			<div class="m-container">
				<header class="m-section-head">
					<h2 class="m-section-head__title">{translate key="plugins.themes.meridian.latestArticles"}</h2>
					<a class="m-more" href="{url page="issue" op="archive"}">
						{translate key="journal.viewAllIssues"}
						<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-arrow"/></svg>
					</a>
				</header>
				<ul class="m-cards">
					{foreach from=$meridianLatestArticles item=item}
						<li>{include file="frontend/objects/meridian_card.tpl" item=$item}</li>
					{/foreach}
				</ul>
			</div>
		</section>
	{/if}

	{* Aims & scope *}
	{if $meridian->isHomeSectionEnabled('aims') && $currentJournal->getLocalizedData('about')}
		<section class="m-home-section m-home-aims">
			<div class="m-container m-home-aims__grid">
				<h2 class="m-section-head__title">{translate key="plugins.themes.meridian.aimsScope"}</h2>
				<div class="m-home-aims__text">
					<p>{$currentJournal->getLocalizedData('about')|strip_tags|truncate:520:"…"}</p>
					<a class="m-more" href="{url page="about"}">
						{translate key="plugins.themes.meridian.aboutJournal"}
						<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-arrow"/></svg>
					</a>
				</div>
			</div>
		</section>
	{/if}

	{* Announcements *}
	{if $meridian->isHomeSectionEnabled('announcements') && $numAnnouncementsHomepage && $announcements|@count}
		<section class="m-home-section m-home-news">
			<a id="homepageAnnouncements"></a>
			<div class="m-container">
				<header class="m-section-head">
					<h2 class="m-section-head__title">{translate key="announcement.announcements"}</h2>
					<a class="m-more" href="{url page="announcement"}">
						{translate key="plugins.themes.meridian.allAnnouncements"}
						<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-arrow"/></svg>
					</a>
				</header>
				<ul class="m-news">
					{foreach name=announcements from=$announcements item=announcement}
						{if $smarty.foreach.announcements.iteration > $numAnnouncementsHomepage}{break}{/if}
						<li class="m-news__item">
							<p class="m-meta">{$announcement->datePosted->format($dateFormatLong)}</p>
							<h3 class="m-news__title">
								<a href="{url router=PKP\core\PKPApplication::ROUTE_PAGE page="announcement" op="view" path=$announcement->id}">{$announcement->getLocalizedData('title')|escape}</a>
							</h3>
							<div class="m-news__summary">{$announcement->getLocalizedData('descriptionShort')|strip_unsafe_html}</div>
						</li>
					{/foreach}
				</ul>
			</div>
		</section>
	{/if}

	{* Indexing & archiving *}
	{if $meridian->isHomeSectionEnabled('indexing') && $meridianIndexingLogos}
		<section class="m-home-section m-home-indexing">
			<div class="m-container">
				<h2 class="m-meta m-home-indexing__title">{translate key="plugins.themes.meridian.indexedIn"}</h2>
				<ul class="m-home-indexing__list">
					{foreach from=$meridianIndexingLogos item=logo}
						<li>
							{if $logo.url}<a href="{$logo.url|escape}" rel="noopener">{/if}
							{if $logo.logo}<img src="{$logo.logo|escape}" alt="{$logo.name|escape}" loading="lazy">{else}<span>{$logo.name|escape}</span>{/if}
							{if $logo.url}</a>{/if}
						</li>
					{/foreach}
				</ul>
			</div>
		</section>
	{/if}

	{if $additionalHomeContent}
		<section class="m-home-section m-home-additional">
			<div class="m-container m-prose">{$additionalHomeContent}</div>
		</section>
	{/if}
</div>

{include file="frontend/components/footer.tpl"}
