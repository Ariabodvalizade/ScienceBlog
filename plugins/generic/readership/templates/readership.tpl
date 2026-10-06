{**
 * plugins/generic/readership/templates/readership.tpl
 *
 * Distributed under the GNU GPL v3.
 *
 * The Readership page. Rendered inside the backend's Vue app, so it is plain
 * markup; js/readership.js refreshes the "online now" figures every minute.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
<div class="rs-page" data-rs-live="{$rsLiveUrl|escape}">

	<header class="rs-head">
		<div class="rs-head__text">
			<p class="rs-eyebrow">{translate key="navigation.tools.statistics"}</p>
			<h1 class="rs-title">{translate key="plugins.generic.readership.title"}</h1>
			<p class="rs-lead">{translate key="plugins.generic.readership.lead"}</p>
		</div>
		<nav class="rs-periods" aria-label="{translate key="plugins.generic.readership.period"}">
			{foreach from=$rsPeriods item=period}
				<a class="rs-periods__item{if $period.current} is-current{/if}" href="{$period.url|escape}"{if $period.current} aria-current="page"{/if}>{$period.label|escape}</a>
			{/foreach}
		</nav>
	</header>

	{if !$rsGeo}
		<div class="rs-notice" role="note">
			<icon icon="Help" class="h-5 w-5" aria-hidden="true"></icon>
			<p>
				{translate key="plugins.generic.readership.geoOff"}
				{if $rsGeoSettingsUrl}<a href="{$rsGeoSettingsUrl|escape}">{translate key="plugins.generic.readership.geoOff.link"}</a>{/if}
			</p>
		</div>
	{/if}

	<section class="rs-tiles" aria-label="{translate key="plugins.generic.readership.totals"}">
		<div class="rs-tile">
			<p class="rs-tile__label">{translate key="plugins.generic.readership.views"}</p>
			<p class="rs-tile__value">{$rs.totals.viewsF}</p>
			<p class="rs-tile__hint">{translate key="plugins.generic.readership.views.hint"}</p>
		</div>
		<div class="rs-tile">
			<p class="rs-tile__label">{translate key="plugins.generic.readership.downloads"}</p>
			<p class="rs-tile__value">{$rs.totals.downloadsF}</p>
			<p class="rs-tile__hint">{translate key="plugins.generic.readership.downloads.hint"}</p>
		</div>
		<div class="rs-tile">
			<p class="rs-tile__label">{translate key="plugins.generic.readership.readers"}</p>
			<p class="rs-tile__value">{$rs.totals.readersF}</p>
			<p class="rs-tile__hint">{translate key="plugins.generic.readership.readers.hint"}</p>
		</div>
		<div class="rs-tile">
			<p class="rs-tile__label">{translate key="plugins.generic.readership.countries"}</p>
			<p class="rs-tile__value">{$rs.totals.countriesF}</p>
			<p class="rs-tile__hint">{translate key="plugins.generic.readership.countries.hint"}</p>
		</div>
		<div class="rs-tile rs-tile--live">
			<p class="rs-tile__label"><span class="rs-pulse" aria-hidden="true"></span>{translate key="plugins.generic.readership.online"}</p>
			<p class="rs-tile__value" data-rs-count>{$rsOnline.countF}</p>
			<p class="rs-tile__hint">{translate key="plugins.generic.readership.online.hint" minutes=5}</p>
		</div>
	</section>

	<section class="rs-panel rs-mapPanel">
		<div class="rs-panel__head">
			<h2 class="rs-h2">{translate key="plugins.generic.readership.map.title"}</h2>
			<p class="rs-note">{$rsPeriodLabel|escape}</p>
		</div>
		<div class="rs-map">{$rsMap.svg}</div>
		<div class="rs-mapFoot">
			{if $rsMap.legend}
				<ul class="rs-legend" aria-label="{translate key="plugins.generic.readership.readers"}">
					<li class="rs-legend__title">{translate key="plugins.generic.readership.readers"}</li>
					{foreach from=$rsMap.legend item=row}
						<li><span class="rs-swatch {$row.class|escape}" aria-hidden="true"></span>{$row.label|escape}</li>
					{/foreach}
					<li><span class="rs-swatch rs-swatch--live" aria-hidden="true"></span>{translate key="plugins.generic.readership.online"}</li>
				</ul>
			{elseif $rsGeo}
				<p class="rs-note">{translate key="plugins.generic.readership.map.empty"}</p>
			{/if}
			<p class="rs-liveList" data-rs-list data-rs-prefix="{translate key="plugins.generic.readership.online.from"}">
				{if $rsOnline.countries}
					{translate key="plugins.generic.readership.online.from"}
					{foreach from=$rsOnline.countries item=country name=live}{$country.name|escape} ({$country.count}){if !$smarty.foreach.live.last}, {/if}{/foreach}
				{/if}
			</p>
		</div>
	</section>

	<div class="rs-grid">
		<section class="rs-panel">
			<div class="rs-panel__head">
				<h2 class="rs-h2">{translate key="plugins.generic.readership.byCountry"}</h2>
				<p class="rs-note">{translate key="plugins.generic.readership.byCountry.hint"}</p>
			</div>
			{if $rs.topCountries}
				<ol class="rs-countries">
					{foreach from=$rs.topCountries item=country}
						<li>
							<details class="rs-country">
								<summary>
									<span class="rs-country__name">{$country.name|escape}</span>
									<span class="rs-country__bar" aria-hidden="true"><span style="width: {$country.bar}%"></span></span>
									<span class="rs-country__value">{$country.readersF}</span>
									<span class="rs-country__share">{$country.share}%</span>
								</summary>
								{if $country.articles}
									<ul class="rs-country__articles">
										{foreach from=$country.articles item=article}
											<li><a href="{$article.url|escape}" target="_blank" rel="noopener">{$article.title|strip_unsafe_html}</a> <span>{$article.readersF}</span></li>
										{/foreach}
									</ul>
								{/if}
							</details>
						</li>
					{/foreach}
				</ol>
				{if $rs.countries|@count > $rs.topCountries|@count}
					<p class="rs-note">{translate key="plugins.generic.readership.byCountry.more" number=$rs.countries|@count-$rs.topCountries|@count}</p>
				{/if}
			{else}
				<p class="rs-empty">{if $rsGeo}{translate key="plugins.generic.readership.empty"}{else}{translate key="plugins.generic.readership.geoOff.short"}{/if}</p>
			{/if}
		</section>

		<section class="rs-panel">
			<div class="rs-panel__head">
				<h2 class="rs-h2">{translate key="plugins.generic.readership.articles"}</h2>
				<p class="rs-note">{translate key="plugins.generic.readership.articles.hint"}</p>
			</div>
			{if $rs.articles}
				<ol class="rs-articles">
					{foreach from=$rs.articles item=article}
						<li class="rs-article">
							<a class="rs-article__title" href="{$article.url|escape}" target="_blank" rel="noopener">{$article.title|strip_unsafe_html}</a>
							<span class="rs-article__meta">{$article.authors|escape}</span>
							<span class="rs-article__nums">
								<span><strong>{$article.viewsF}</strong> {translate key="plugins.generic.readership.views.short"}</span>
								<span><strong>{$article.downloadsF}</strong> {translate key="plugins.generic.readership.downloads.short"}</span>
							</span>
						</li>
					{/foreach}
				</ol>
			{else}
				<p class="rs-empty">{translate key="plugins.generic.readership.empty"}</p>
			{/if}
		</section>
	</div>

	<div class="rs-grid rs-grid--wide">
		<section class="rs-panel">
			<div class="rs-panel__head">
				<h2 class="rs-h2">{translate key="plugins.generic.readership.trend"}</h2>
				<ul class="rs-key">
					<li><span class="rs-swatch rs-swatch--views" aria-hidden="true"></span>{translate key="plugins.generic.readership.views"}</li>
					<li><span class="rs-swatch rs-swatch--downloads" aria-hidden="true"></span>{translate key="plugins.generic.readership.downloads"}</li>
				</ul>
			</div>
			<ol class="rs-trend">
				{foreach from=$rs.trend item=month}
					<li class="rs-trend__month" title="{$month.label|escape} {$month.year|escape}: {$month.viewsF} · {$month.downloadsF}">
						<span class="rs-trend__bar" style="height: {$month.height}%">
							<span class="rs-trend__views" style="height: {$month.viewsShare}%"></span>
						</span>
						<span class="rs-trend__label">{$month.label|escape}</span>
						<span class="-screenReader">{$month.year|escape}: {$month.viewsF} {translate key="plugins.generic.readership.views"}, {$month.downloadsF} {translate key="plugins.generic.readership.downloads"}</span>
					</li>
				{/foreach}
			</ol>
		</section>

		<section class="rs-panel">
			<div class="rs-panel__head">
				<h2 class="rs-h2">{translate key="plugins.generic.readership.pages"}</h2>
				<p class="rs-note">{$rsPeriodLabel|escape}</p>
			</div>
			<dl class="rs-pages">
				<div><dt>{translate key="plugins.generic.readership.pages.home"}</dt><dd>{$rs.pages.homeF}</dd></div>
				<div><dt>{translate key="plugins.generic.readership.pages.issues"}</dt><dd>{$rs.pages.issuesF}</dd></div>
				<div><dt>{translate key="plugins.generic.readership.pages.articles"}</dt><dd>{$rs.pages.articlesF}</dd></div>
				<div><dt>{translate key="plugins.generic.readership.pages.downloads"}</dt><dd>{$rs.pages.downloadsF}</dd></div>
			</dl>
			<a class="rs-link" href="{$rsStatsUrl|escape}">{translate key="plugins.generic.readership.pages.more"} →</a>
		</section>
	</div>

	<p class="rs-foot">
		{translate key="plugins.generic.readership.footer" time=$rsUpdated}
		{if $rsGeo}{translate key="plugins.generic.readership.credit"}.{/if}
	</p>
</div>
{/block}
