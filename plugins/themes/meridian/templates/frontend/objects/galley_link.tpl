{**
 * plugins/themes/meridian/templates/frontend/objects/galley_link.tpl
 *
 * @brief A link to a galley (PDF, HTML, …) with an icon. Same variables as the
 *  core template; see templates/frontend/objects/galley_link.tpl.
 *}
{if $journalOverride}
	{assign var="currentJournal" value=$journalOverride}
{/if}

{if $galley->isPdfGalley()}
	{assign var="type" value="pdf"}
{else}
	{assign var="type" value="file"}
{/if}

{if $parent instanceOf APP\issue\Issue}
	{assign var="page" value="issue"}
	{assign var="parentId" value=$parent->getBestIssueId()}
	{assign var="path" value=$parentId|to_array:$galley->getBestGalleyId()}
{else}
	{assign var="page" value="article"}
	{if $publication}
		{if $publication->getId() !== $parent->getData('currentPublicationId')}
			{assign var="path" value=$parent->getBestId()|to_array:"version":$publication->getId():$galley->getBestGalleyId()}
		{else}
			{assign var="parentId" value=$publication->getData('urlPath')|default:$parent->getId()}
			{assign var="path" value=$parentId|to_array:$galley->getBestGalleyId()}
		{/if}
	{else}
		{assign var="path" value=$parent->getBestId()|to_array:$galley->getBestGalleyId()}
	{/if}
{/if}

{if !$hasAccess}
	{if $restrictOnlyPdf && $type=="pdf"}
		{assign var=restricted value="1"}
	{elseif !$restrictOnlyPdf}
		{assign var=restricted value="1"}
	{/if}
{/if}

<a class="{if $isSupplementary}obj_galley_link_supplementary{else}obj_galley_link{/if} m-galley m-galley--{$type|escape}{if $restricted} restricted{/if}{if $galleyClass} {$galleyClass|escape}{/if}" href="{if $scholarlyFullTextGalleyId && $page == 'article' && $galley->getId() == $scholarlyFullTextGalleyId}#fulltext{else}{url page=$page op="view" path=$path}{/if}"{if $id} id="{$id}"{/if}{if $labelledBy} aria-labelledby="{$labelledBy}"{/if}>
	{if $restricted}
		<span class="pkp_screen_reader">
			{if $purchaseArticleEnabled}{translate key="reader.subscriptionOrFeeAccess"}{else}{translate key="reader.subscriptionAccess"}{/if}
		</span>
	{/if}
	<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#{if $type == 'pdf'}m-i-download{else}m-i-file{/if}"/></svg>
	{$galley->getGalleyLabel()|escape}
	{if $restricted && $purchaseFee && $purchaseCurrency}
		<span class="purchase_cost">{translate key="reader.purchasePrice" price=$purchaseFee currency=$purchaseCurrency}</span>
	{/if}
</a>
