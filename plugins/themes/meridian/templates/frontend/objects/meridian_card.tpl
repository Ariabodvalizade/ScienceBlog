{**
 * plugins/themes/meridian/templates/frontend/objects/meridian_card.tpl
 *
 * @brief Compact article card (home "Latest articles", rails): section label,
 *  title, authors and publication date.
 *
 * @uses $item array {article, publication, sectionTitle, authors, shortAuthors}
 * @uses $compact bool Rail variant (label + title only)
 *}
{assign var=cardPublication value=$item.publication}
{capture assign="cardUrl"}{url page="article" op="view" path=$cardPublication->getData('urlPath')|default:$item.article->getId()}{/capture}
<article class="m-card{if $compact} m-card--compact{/if}">
	{if $item.sectionTitle}<p class="m-card__type">{$item.sectionTitle|escape}</p>{/if}
	<h3 class="m-card__title"><a href="{$cardUrl}">{$cardPublication->getLocalizedTitle(null, 'html')|strip_unsafe_html}</a></h3>
	{if !$compact}
		{if $item.authors}<p class="m-card__authors">{$item.authors|escape}</p>{/if}
		{if $cardPublication->getData('datePublished')}
			<p class="m-card__meta m-meta">{$cardPublication->getData('datePublished')|date_format:$dateFormatLong}</p>
		{/if}
	{/if}
</article>
