{**
 * plugins/generic/authorPages/templates/authors.tpl
 *
 * @brief Author directory, grouped by the first letter of the family name.
 *
 * @uses $authorGroups array letter => [author, …]
 * @uses $authorCount int
 *}
{capture assign="pageTitle"}{translate key="plugins.generic.authorPages.title"}{/capture}
{include file="frontend/components/header.tpl" pageTitleTranslated=$pageTitle}

<div class="page_authors m-page">
	<header class="m-hero">
		<div class="m-container m-hero__center">
			{include file="frontend/components/breadcrumbs.tpl" currentTitle=$pageTitle}
			<h1 class="m-hero__title">{$pageTitle|escape}</h1>
			<p class="m-hero__lead">{translate key="plugins.generic.authorPages.lead"}</p>
		</div>
	</header>

	<div class="m-container ap-directory">
		{if !$authorGroups}
			<p class="m-empty">{translate key="plugins.generic.authorPages.empty"}</p>
		{elseif $authorCount <= 40}
			{* Small journals: one continuous A–Z grid *}
			<ul class="ap-grid">
				{foreach from=$authorGroups item=people}
					{foreach from=$people item=person}
						<li>
							<a class="ap-card" href="{$person.url|escape}">
								{if $person.photo}
									<img class="ap-avatar" src="{$person.photo|escape}" alt="" loading="lazy" width="64" height="64">
								{else}
									<span class="ap-avatar ap-avatar--initials" aria-hidden="true">{$person.initials|escape}</span>
								{/if}
								<span class="ap-card__body">
									<span class="ap-card__name">{$person.name|escape}</span>
									{if $person.affiliation}<span class="ap-card__affiliation">{$person.affiliation|escape}</span>{/if}
									<span class="ap-card__count">{translate key="plugins.generic.authorPages.articles" count=$person.articleCount number=$person.articleCount}</span>
								</span>
							</a>
						</li>
					{/foreach}
				{/foreach}
			</ul>
		{else}
			<nav class="ap-letters" aria-label="{translate|escape key="plugins.generic.authorPages.jumpTo"}">
				{foreach from=$authorGroups key=letter item=people}
					<a href="#letter-{$letter|escape:"url"}">{$letter|escape}</a>
				{/foreach}
			</nav>

			{foreach from=$authorGroups key=letter item=people}
				<section class="ap-group" id="letter-{$letter|escape:"url"}">
					<h2 class="ap-group__letter">{$letter|escape}</h2>
					<ul class="ap-grid">
						{foreach from=$people item=person}
							<li>
								<a class="ap-card" href="{$person.url|escape}">
									{if $person.photo}
										<img class="ap-avatar" src="{$person.photo|escape}" alt="" loading="lazy" width="64" height="64">
									{else}
										<span class="ap-avatar ap-avatar--initials" aria-hidden="true">{$person.initials|escape}</span>
									{/if}
									<span class="ap-card__body">
										<span class="ap-card__name">{$person.name|escape}</span>
										{if $person.affiliation}<span class="ap-card__affiliation">{$person.affiliation|escape}</span>{/if}
										<span class="ap-card__count">{translate key="plugins.generic.authorPages.articles" count=$person.articleCount number=$person.articleCount}</span>
									</span>
								</a>
							</li>
						{/foreach}
					</ul>
				</section>
			{/foreach}
		{/if}
	</div>
</div>

{include file="frontend/components/footer.tpl"}
