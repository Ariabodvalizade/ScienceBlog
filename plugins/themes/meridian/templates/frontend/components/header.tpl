{**
 * plugins/themes/meridian/templates/frontend/components/header.tpl
 *
 * @brief Meridian site header: menu button + drawer, centered wordmark/logo,
 *  search overlay, user menu and (desktop) a primary navigation row.
 *
 * @uses $isFullWidth bool Should this page be displayed without sidebars?
 *}
<!DOCTYPE html>
<html lang="{$currentLocale|replace:"_":"-"}" xml:lang="{$currentLocale|replace:"_":"-"}">
{if !$pageTitleTranslated}{capture assign="pageTitleTranslated"}{translate key=$pageTitle}{/capture}{/if}
{include file="frontend/components/headerHead.tpl"}
<body class="pkp_page_{$requestedPage|escape|default:"index"} pkp_op_{$requestedOp|escape|default:"index"}{if $displayPageHeaderLogo} has_site_logo{/if} m-body" dir="{$currentLocaleLangDir|escape|default:"ltr"}">
{include file="frontend/components/meridian_icons.tpl"}

	<div class="pkp_structure_page">

		<header class="pkp_structure_head m-header" id="headerNavigationContainer" role="banner">
			{include file="frontend/components/skipLinks.tpl"}

			{capture assign="homeUrl"}{url page="index" router=PKP\core\PKPApplication::ROUTE_PAGE}{/capture}

			<div class="m-header__bar m-container">
				<div class="m-header__start">
					<button type="button" class="m-icon-btn" data-m-open="m-drawer" aria-controls="m-drawer" aria-expanded="false">
						<svg class="m-icon" aria-hidden="true"><use href="#m-i-menu"/></svg>
						<span class="m-sr">{translate key="plugins.themes.meridian.openMenu"}</span>
					</button>
				</div>

				<div class="m-header__brand">
					{if !$requestedPage || $requestedPage === 'index'}
						<h1 class="m-sr">{if $currentContext}{$displayPageHeaderTitle|escape}{else}{$siteTitle|escape}{/if}</h1>
					{/if}
					{if $displayPageHeaderLogo}
						<a href="{$homeUrl}" class="m-brand m-brand--logo">
							<img src="{$publicFilesDir}/{$displayPageHeaderLogo.uploadName|escape:"url"}" width="{$displayPageHeaderLogo.width|escape}" height="{$displayPageHeaderLogo.height|escape}" alt="{$displayPageHeaderLogo.altText|default:$displayPageHeaderTitle|escape}" />
						</a>
					{else}
						<a href="{$homeUrl}" class="m-brand m-brand--text">{$displayPageHeaderTitle|default:$siteTitle|escape}</a>
					{/if}
				</div>

				<div class="m-header__end">
					<div class="m-header__user">
						{load_menu name="user" id="navigationUser" ulClass="pkp_navigation_user m-usernav" liClass="profile"}
					</div>
					{if $currentContext && $requestedPage !== 'search'}
						<button type="button" class="m-icon-btn" data-m-open="m-search" aria-controls="m-search" aria-expanded="false">
							<svg class="m-icon" aria-hidden="true"><use href="#m-i-search"/></svg>
							<span class="m-sr">{translate key="plugins.themes.meridian.openSearch"}</span>
						</button>
					{/if}
				</div>
			</div>

			{capture assign="primaryMenu"}{load_menu name="primary" id="navigationPrimary" ulClass="pkp_navigation_primary m-nav"}{/capture}
			{if $primaryMenu|trim}
				<nav class="m-header__nav" aria-label="{translate|escape key="common.navigation.site"}">
					<a id="siteNav"></a>
					<div class="m-container">{$primaryMenu}</div>
				</nav>
			{/if}

			{* Search overlay *}
			{if $currentContext}
				<div class="m-search" id="m-search" hidden>
					<form class="m-search__form m-container" action="{url page="search" op="search"}" method="get" role="search">
						<label class="m-sr" for="m-search-query">{translate key="common.search"}</label>
						<svg class="m-icon" aria-hidden="true"><use href="#m-i-search"/></svg>
						<input id="m-search-query" class="m-search__input" type="search" name="query" placeholder="{translate|escape key="plugins.themes.meridian.searchPlaceholder"}" autocomplete="off">
						<button type="submit" class="m-btn m-btn--primary">{translate key="common.search"}</button>
						<button type="button" class="m-icon-btn" data-m-close="m-search">
							<svg class="m-icon" aria-hidden="true"><use href="#m-i-close"/></svg>
							<span class="m-sr">{translate key="plugins.themes.meridian.closeSearch"}</span>
						</button>
					</form>
				</div>
			{/if}
		</header>

		{* Navigation drawer *}
		<div class="m-drawer" id="m-drawer" hidden>
			<div class="m-drawer__backdrop" data-m-close="m-drawer"></div>
			<div class="m-drawer__panel" role="dialog" aria-modal="true" aria-label="{translate|escape key="plugins.themes.meridian.menu"}">
				<div class="m-drawer__head">
					<span class="m-meta">{translate key="plugins.themes.meridian.menu"}</span>
					<button type="button" class="m-icon-btn" data-m-close="m-drawer">
						<svg class="m-icon" aria-hidden="true"><use href="#m-i-close"/></svg>
						<span class="m-sr">{translate key="plugins.themes.meridian.closeMenu"}</span>
					</button>
				</div>
				<nav aria-label="{translate|escape key="common.navigation.site"}">
					{load_menu name="primary" id="m-drawer-primary" ulClass="m-drawer__nav"}
				</nav>
				{if $currentContext}
					<form class="m-drawer__search" action="{url page="search" op="search"}" method="get" role="search">
						<label class="m-sr" for="m-drawer-query">{translate key="common.search"}</label>
						<input id="m-drawer-query" type="search" name="query" placeholder="{translate|escape key="plugins.themes.meridian.searchPlaceholder"}">
						<button type="submit" class="m-icon-btn">
							<svg class="m-icon" aria-hidden="true"><use href="#m-i-search"/></svg>
							<span class="m-sr">{translate key="common.search"}</span>
						</button>
					</form>
				{/if}
				<nav class="m-drawer__user" aria-label="{translate|escape key="plugins.themes.meridian.account"}">
					{load_menu name="user" id="m-drawer-user" ulClass="m-drawer__usernav"}
				</nav>
			</div>
		</div>

		{if $isFullWidth}
			{assign var=hasSidebar value=0}
		{/if}
		<div class="pkp_structure_content{if $hasSidebar} has_sidebar{/if}">
			<div class="pkp_structure_main" role="main">
				<a id="pkp_content_main"></a>
