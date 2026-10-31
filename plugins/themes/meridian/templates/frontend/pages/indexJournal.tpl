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
		<main class="m-reference-main" role="main">
			{if $homepageImage}
				<div class="m-reference-homepage-image">
					<img src="{$publicFilesDir}/{$homepageImage.uploadName|escape:"url"}"{if $homepageImage.altText} alt="{$homepageImage.altText|escape}"{else} alt=""{/if} fetchpriority="high">
				</div>
			{else}
				<div class="m-reference-homepage-image">
					<img src="{$baseUrl}/plugins/themes/meridian/images/editorial/journal-hero.webp" alt="" width="1600" height="900" fetchpriority="high">
				</div>
			{/if}

			<section class="m-reference-description" aria-labelledby="journal-description-title">
				<h1 id="journal-description-title" class="m-sr">{$currentJournal->getLocalizedName()|escape}</h1>
				{if $journalDescription}{$journalDescription|strip_unsafe_html}{/if}
				<div class="m-reference-facts">
					{if $currentJournal->getLocalizedName()}<p><strong>Journal Name:</strong> {$currentJournal->getLocalizedName()|escape}</p>{/if}
					{if $currentJournal->getData('initials')}<p><strong>Journal initials:</strong> {$currentJournal->getData('initials')|escape}</p>{/if}
					{if $currentJournal->getData('abbreviation')}<p><strong>Journal Abbreviation:</strong> {$currentJournal->getData('abbreviation')|escape}</p>{/if}
					{if $currentJournal->getData('onlineIssn')}<p><strong>ISSN:</strong> {$currentJournal->getData('onlineIssn')|escape}</p>{/if}
					{if $currentJournal->getData('publisherInstitution')}<p><strong>Publisher:</strong> {$currentJournal->getData('publisherInstitution')|escape}</p>{/if}
					{if $meridianTagline}<p><strong>Frequency:</strong> {$meridianTagline|escape}</p>{/if}
				</div>
			</section>

			{if $issue}
				{capture assign="issueUrl"}{url page="issue" op="view" path=$issue->getBestIssueId()}{/capture}
				<section class="m-reference-current-issue" aria-labelledby="current-issue-title">
					<header class="m-reference-section-header">
						<h2 id="current-issue-title"><a href="{$issueUrl}">{translate key="plugins.themes.meridian.currentIssue"}</a></h2>
					</header>
					<p class="m-reference-issue-title">{$issue->getIssueIdentification()|escape}</p>
					{if $issue->getDatePublished()}<p class="m-reference-published"><strong>{translate key="issue.published"}:</strong> {$issue->getDatePublished()|date_format:$dateFormatLong}</p>{/if}

					<div class="m-reference-issue-sections">
						{foreach from=$publishedSubmissions item=section}
							<section class="m-reference-issue-section">
								{if $section.title}<h3>{$section.title|escape}</h3>{/if}
								<div class="m-reference-article-list">
									{foreach from=$section.articles item=article}
										{include file="frontend/objects/article_summary.tpl" heading="h4" typeLabel=$section.title hideGalleys=false}
									{/foreach}
								</div>
							</section>
						{/foreach}
					</div>
					<a class="m-reference-button" href="{url page="issue" op="archive"}">{translate key="journal.viewAllIssues"} <span aria-hidden="true">›</span></a>
				</section>
			{/if}
		</main>

		<aside class="m-reference-sidebar" id="sidebar" aria-label="{translate|escape key="common.navigation"}">
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
				{* shortcut: the client template file is not in this repository; link to submission guidance until it is uploaded. *}
				<a class="m-reference-action" href="{url page="about" op="submissions"}#authorGuidelines" title="Journal template">
					<span class="m-reference-action__icon m-reference-action__icon--doc" aria-hidden="true">DOCX</span>
					<span>Manuscript Template</span>
				</a>
			</section>

			<section class="m-reference-block m-reference-block--indexing">
				<h2 class="m-reference-block__title">Indexing and Archiving</h2>
				<ul>
					<li><a href="https://www.ebsco.com/" target="_blank" rel="noopener">EBSCO</a></li>
					<li><a href="https://cassi.cas.org/" target="_blank" rel="noopener">CAS</a></li>
					<li><a href="https://search.crossref.org/" target="_blank" rel="noopener">Crossref</a></li>
					<li><a href="https://scholar.google.com/" target="_blank" rel="noopener">Google Scholar</a></li>
					<li><a href="{url page="gateway" op="plugin" path="PKPPreservationPlugin"}" target="_blank" rel="noopener">PKP (LOCKSS)</a></li>
					<li><a href="https://www.portico.org/" target="_blank" rel="noopener">Portico</a></li>
				</ul>
			</section>
		</aside>
	</div>
</div>

{include file="frontend/components/footer.tpl"}
