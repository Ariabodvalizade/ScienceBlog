{**
 * Meridian journal home page.
 *
 * The layout follows the classic OJS journal template used by the client
 * reference: journal content on the left and information blocks on the right.
 *
 * @hook Templates::Index::journal []
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$currentJournal->getLocalizedName()}

<div id="main-content" class="page_index_journal m-reference-home">
	{call_hook name="Templates::Index::journal"}

	<div class="m-reference-layout m-container">
		<div class="m-reference-main">
			{if $homepageImage}
				<div class="m-reference-homepage-image">
					<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}"{if $homepageImage.altText} alt="{$homepageImage.altText|escape}"{else} alt=""{/if} fetchpriority="high">
				</div>
			{else}
				<div class="m-reference-banner">
					{if $displayPageHeaderLogo}<img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:'url'}" alt="" width="72" height="72">{/if}
					<strong>{$currentJournal->getLocalizedName()|escape}</strong>
				</div>
			{/if}

			<section class="m-reference-description" aria-labelledby="journal-description-title">
				<h1 id="journal-description-title" class="m-sr">{$currentJournal->getLocalizedName()|escape}</h1>
				{if $journalDescription}{$journalDescription|strip_unsafe_html}{/if}
				<div class="m-reference-facts">
					{if $currentJournal->getLocalizedName()}<p><strong>Journal Name:</strong> {$currentJournal->getLocalizedName()|escape}</p>{/if}
					<p><strong>Journal initials:</strong> {$currentJournal->getLocalizedData('acronym')|escape|default:'Not announced'}</p>
					<p><strong>Journal Abbreviation:</strong> {$currentJournal->getLocalizedData('abbreviation')|escape|default:'Not announced'}</p>
					<p><strong>ISSN:</strong> {$currentJournal->getData('onlineIssn')|escape|default:'Not announced'}</p>
					{if $currentJournal->getData('publisherInstitution')}<p><strong>Publisher:</strong> {$currentJournal->getData('publisherInstitution')|escape}</p>{/if}
					<p><strong>DOI Prefix:</strong> {$currentJournal->getData('doiPrefix')|escape|default:'Not announced'}</p>
					<p><strong>Frequency:</strong> Not announced</p>
				</div>
			</section>

			{* shortcut: editorial metrics have not been supplied; replace these pending values once verified by the journal. *}
			<section class="m-reference-statistics" aria-labelledby="journal-statistics-title">
				<h2 id="journal-statistics-title">Journal Statistics</h2>
				<p>Average time to preliminary assessment: <span>Not announced</span></p>
				<p>Average time to review: <span>Not announced</span></p>
				<p>Average time to publish: <span>Not announced</span></p>
				<p>Submissions: <span>Not announced</span></p>
				<p>Accepted articles: <span>Not announced</span></p>
				<p>Acceptance rate: <span>Not announced</span></p>
				<p>Accepted articles published for free: <span>Not announced</span></p>
			</section>

			{if $issue && $meridian->isHomeSectionEnabled('currentIssue')}
				{capture assign="issueUrl"}{url page="issue" op="view" path=$issue->getBestIssueId()}{/capture}
				<section class="m-reference-current-issue" aria-labelledby="current-issue-title">
					<header class="m-reference-section-header">
						<h2 id="current-issue-title"><a href="{$issueUrl}">{translate key="plugins.themes.meridian.currentIssue"}</a></h2>
					</header>
					<p class="m-reference-issue-title">{$issue->getIssueIdentification()|escape}</p>
					{if $issue->getDatePublished()}<p class="m-reference-published"><strong>{translate key="submissions.published"}:</strong> {$issue->getDatePublished()|date_format:$dateFormatLong}</p>{/if}

					<div class="m-reference-issue-sections">
						{foreach from=$publishedSubmissions item=section}
							<section class="m-reference-issue-section">
								{if $section.title}<h3>{$section.title|escape}</h3>{/if}
								<div class="m-reference-article-list">
									{foreach from=$section.articles item=article}
									{include file="frontend/objects/article_summary.tpl" heading="h4" hideGalleys=false}
									{/foreach}
								</div>
							</section>
						{/foreach}
					</div>
					<a class="m-reference-button" href="{url page="issue" op="archive"}">{translate key="journal.viewAllIssues"} <span aria-hidden="true">›</span></a>
				</section>
			{/if}
			{if $meridian->isHomeSectionEnabled('announcements') && $numAnnouncementsHomepage && $announcements|@count}
				<section class="m-reference-announcements">
					<h2>{translate key="announcement.announcements"}</h2>
					{foreach name=announcements from=$announcements item=announcement}
						{if $smarty.foreach.announcements.iteration > $numAnnouncementsHomepage}{break}{/if}
						<h3><a href="{url page='announcement' op='view' path=$announcement->id}">{$announcement->getLocalizedData('title')|escape}</a></h3>
						<div>{$announcement->getLocalizedData('descriptionShort')|strip_unsafe_html}</div>
					{/foreach}
				</section>
			{/if}
			{if $additionalHomeContent}<div class="m-prose m-reference-additional">{$additionalHomeContent|strip_unsafe_html}</div>{/if}
		</div>

		<aside class="m-reference-sidebar" id="sidebar" aria-label="Journal information">
			<section class="m-reference-block">
				<h2 class="m-reference-block__title">{translate key="plugins.themes.meridian.footer.explore"}</h2>
				<ul>
					<li><a href="{url page="about"}">{translate key="about.aboutContext"}</a></li>
					<li><a href="{url page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
					<li><a href="{url page="about" op="editorialMasthead"}">{translate key="common.editorialMasthead"}</a></li>
				</ul>
			</section>

			<section class="m-reference-block m-reference-block--action">
				<h2 class="m-reference-block__title m-sr">Make A Submission</h2>
				<a class="m-reference-action" href="{url page="about" op="submissions"}" title="Make a submission">
					<span class="m-reference-action__icon" aria-hidden="true">✎</span>
					<span>Make a Submission</span>
				</a>
			</section>

			<section class="m-reference-block m-reference-block--action">
				<h2 class="m-reference-block__title">Journal Template</h2>
				{* shortcut: the journal has not supplied its manuscript template; enable a download once the approved file is available. *}
				<div class="m-reference-action m-reference-action--pending">
					<span class="m-reference-action__icon m-reference-action__icon--doc" aria-hidden="true">DOCX</span>
					<span>Manuscript Template</span>
				</div>
				<p class="m-reference-block__note">Template not yet available. <a href="{url page='about' op='submissions'}">View author guidelines</a>.</p>
			</section>

			{if $meridian->isHomeSectionEnabled('indexing')}
			<section class="m-reference-block m-reference-block--indexing">
				<h2 class="m-reference-block__title">Indexing and Archiving</h2>
				<p class="m-reference-block__note">Status: Pending verification. The links below are service websites.</p>
				<ul>
					<li><a href="https://www.ebsco.com/" target="_blank" rel="noopener">EBSCO</a></li>
					<li><a href="https://cassi.cas.org/" target="_blank" rel="noopener">CAS</a></li>
					<li><a href="https://search.crossref.org/" target="_blank" rel="noopener">Crossref</a></li>
					<li><a href="https://scholar.google.com/" target="_blank" rel="noopener">Google Scholar</a></li>
					<li><a href="https://pkp.sfu.ca/pkp-pn/" target="_blank" rel="noopener">PKP (LOCKSS)</a></li>
					<li><a href="https://www.portico.org/" target="_blank" rel="noopener">Portico</a></li>
				</ul>
			</section>
			{/if}
		</aside>
	</div>
</div>

{include file="frontend/components/footer.tpl"}
