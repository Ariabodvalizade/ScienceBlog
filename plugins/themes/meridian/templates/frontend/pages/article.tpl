{**
 * plugins/themes/meridian/templates/frontend/pages/article.tpl
 *
 * @brief Article landing page. Layout mirrors the reference design: centered
 *  title hero, then three columns (article info | text | tools).
 *
 * @hook Templates::Article::Footer::PageFooter []
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$article->getCurrentPublication()->getLocalizedFullTitle(null, 'html')|strip_unsafe_html}

<div class="page_article m-page">
	{include file="frontend/objects/article_details.tpl"}

	{capture assign="pageFooterHooks"}{call_hook name="Templates::Article::Footer::PageFooter"}{/capture}
	{if $pageFooterHooks|trim}
		<section class="m-container m-article-related" aria-label="{translate|escape key="plugins.themes.meridian.relatedArticles"}">
			{$pageFooterHooks}
		</section>
	{/if}
</div>

{include file="frontend/components/footer.tpl"}
