{**
 * plugins/themes/meridian/templates/frontend/components/meridian_archive_rail.tpl
 *
 * @brief Rail block listing published issues by year ("Archive" in the reference design).
 *
 * @uses $meridianArchive array [{year, issues[]}]
 * @uses $issue Issue (optional) highlight the current one
 *}
{if $meridianArchive}
	<section class="m-rail-block">
		<h2 class="m-rail-block__title">{translate key="plugins.themes.meridian.archive"}</h2>
		{foreach from=$meridianArchive item=year}
			<p class="m-rail-block__label">{$year.year|escape}</p>
			<ul class="m-rail-list">
				{foreach from=$year.issues item=archiveIssue}
					<li>
						<a href="{url page="issue" op="view" path=$archiveIssue->getBestIssueId()}"{if $issue && $issue->getId() == $archiveIssue->getId()} aria-current="page"{/if}>
							{$archiveIssue->getIssueIdentification(['showYear' => false, 'showTitle' => false])|escape}
						</a>
					</li>
				{/foreach}
			</ul>
		{/foreach}
		<a class="m-more" href="{url page="issue" op="archive"}">
			{translate key="journal.viewAllIssues"}
			<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-arrow"/></svg>
		</a>
	</section>
{/if}
