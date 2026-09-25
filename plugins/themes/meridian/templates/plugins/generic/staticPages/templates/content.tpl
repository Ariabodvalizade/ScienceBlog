{**
 * plugins/themes/meridian/templates/plugins/generic/staticPages/templates/content.tpl
 *
 * @brief Static page (Static Pages plugin) in Meridian's layout: hero with
 *  breadcrumbs and title, prose column, and an automatic "On this page"
 *  outline for long pages such as Journal Policies (built by meridian.js).
 *
 * @uses $title string
 * @uses $content string
 *}
{include file="frontend/components/header.tpl" pageTitleTranslated=$title}

<div class="page_static m-page">
	<header class="m-hero">
		<div class="m-container m-hero__center">
			{include file="frontend/components/breadcrumbs.tpl" currentTitle=$title}
			<h1 class="m-hero__title">{$title|escape}</h1>
		</div>
	</header>

	<div class="m-container m-static">
		<nav class="m-static__toc m-rail-block" aria-label="{translate|escape key="plugins.themes.meridian.onThisPage"}" data-m-autotoc hidden>
			<h2 class="m-rail-block__title">{translate key="plugins.themes.meridian.onThisPage"}</h2>
			<ol class="m-outline__list"></ol>
		</nav>
		<div class="m-static__body m-prose">
			{$content}
		</div>
	</div>
</div>

{include file="frontend/components/footer.tpl"}
