{**
 * plugins/generic/authorPages/templates/author.tpl
 *
 * @brief Author profile: photo, name, affiliation, ORCID, website, biography
 *  and published articles.
 *
 * @uses $author array Public profile (see AuthorPagesHandler::present)
 * @uses $authorArticles Submission[]
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$author.name}

<div class="page_author m-page">
	<header class="m-hero ap-profile">
		<div class="m-container ap-profile__inner">
			{capture assign="authorsTitle"}{translate key="plugins.generic.authorPages.title"}{/capture}
			<nav class="cmp_breadcrumbs" role="navigation">
				<ol>
					<li><a href="{url page="index" router=PKP\core\PKPApplication::ROUTE_PAGE}">{translate key="common.homepageNavigationLabel"}</a><span class="separator">{translate key="navigation.breadcrumbSeparator"}</span></li>
					<li><a href="{url page="authors"}">{$authorsTitle|escape}</a><span class="separator">{translate key="navigation.breadcrumbSeparator"}</span></li>
					<li class="current"><span aria-current="page">{$author.name|escape}</span></li>
				</ol>
			</nav>

			{if $author.photo}
				<img class="ap-avatar ap-avatar--lg" src="{$author.photo|escape}" alt="{translate|escape key="plugins.generic.authorPages.photoOf" name=$author.name}" width="140" height="140">
			{else}
				<span class="ap-avatar ap-avatar--lg ap-avatar--initials" aria-hidden="true">{$author.initials|escape}</span>
			{/if}

			<h1 class="m-hero__title ap-profile__name">{$author.name|escape}</h1>
			{if $author.affiliation}<p class="ap-profile__affiliation">{$author.affiliation|escape}</p>{/if}

			<p class="ap-profile__links">
				{if $author.orcid}
					<a class="ap-orcid" href="{$author.orcid|escape}" target="_blank" rel="noopener">
						<svg viewBox="0 0 256 256" width="16" height="16" aria-hidden="true"><circle cx="128" cy="128" r="128" fill="#a6ce39"/><path fill="#fff" d="M86.3 186.2H70.9V79.1h15.4v107.1zM108.9 79.1h41.6c39.6 0 57 28.3 57 53.6 0 27.5-21.5 53.6-56.8 53.6h-41.8V79.1zm15.4 93.3h24.5c34.9 0 42.9-26.5 42.9-39.7C191.7 111.2 178 93 148 93h-23.7v79.4zM88.7 56.8c0 5.5-4.5 10.1-10.1 10.1s-10.1-4.6-10.1-10.1c0-5.6 4.5-10.1 10.1-10.1s10.1 4.6 10.1 10.1z"/></svg>
						{$author.orcid|replace:"https://orcid.org/":""|escape}
					</a>
				{/if}
				{if $author.website}
					<a href="{$author.website|escape}" target="_blank" rel="noopener nofollow">{translate key="plugins.generic.authorPages.website"}</a>
				{/if}
				<span>{translate key="plugins.generic.authorPages.articles" count=$author.articleCount number=$author.articleCount}</span>
			</p>

			{if $author.biography}
				<div class="ap-profile__bio">{$author.biography|strip_unsafe_html}</div>
			{/if}
		</div>
	</header>

	<div class="m-container ap-articles">
		<h2 class="m-section-head__title ap-articles__title">{translate key="plugins.generic.authorPages.publishedArticles"}</h2>
		<ul class="m-list">
			{foreach from=$authorArticles item=article}
				<li>{include file="frontend/objects/article_summary.tpl" article=$article heading="h3" showDatePublished=true}</li>
			{/foreach}
		</ul>
	</div>
</div>

{include file="frontend/components/footer.tpl"}
