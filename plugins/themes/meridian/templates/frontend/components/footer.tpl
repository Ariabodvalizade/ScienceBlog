{**
 * plugins/themes/meridian/templates/frontend/components/footer.tpl
 *
 * @brief Meridian site footer: journal identity, quick links, open access &
 *  metadata endpoints. The OJS "page footer" setting is shown above the bottom line.
 *
 * @hook Templates::Common::Sidebar []
 * @hook Templates::Common::Footer::PageFooter []
 *}

	</div><!-- pkp_structure_main -->

	{* Sidebars (block plugins) are rendered only when configured *}
	{if empty($isFullWidth)}
		{capture assign="sidebarCode"}{call_hook name="Templates::Common::Sidebar"}{/capture}
		{if $sidebarCode}
			<div class="pkp_structure_sidebar left m-sidebar" role="complementary">
				{$sidebarCode}
			</div>
		{/if}
	{/if}
</div><!-- pkp_structure_content -->

<footer class="pkp_structure_footer_wrapper m-footer" role="contentinfo">
	<a id="pkp_content_footer"></a>
	<div class="m-container">
		<div class="m-footer__grid">
			<section class="m-footer__col m-footer__identity">
				<a class="m-footer__brand" href="{url page="index" router=PKP\core\PKPApplication::ROUTE_PAGE}">{$displayPageHeaderTitle|default:$siteTitle|escape}</a>
				{if $currentContext}
					<ul class="m-footer__facts">
						{if $currentContext->getData('onlineIssn')}<li>{translate key="plugins.themes.meridian.issn.online" issn=$currentContext->getData('onlineIssn')|escape}</li>{/if}
						{if $currentContext->getData('printIssn')}<li>{translate key="plugins.themes.meridian.issn.print" issn=$currentContext->getData('printIssn')|escape}</li>{/if}
						{if $currentContext->getData('publisherInstitution')}<li>{$currentContext->getData('publisherInstitution')|escape}</li>{/if}
					</ul>
					{if $currentContext->getData('contactEmail')}
						<a class="m-footer__contact" href="mailto:{$currentContext->getData('contactEmail')|escape}">{$currentContext->getData('contactEmail')|escape}</a>
					{/if}
				{/if}
			</section>

			{if $currentContext}
				<nav class="m-footer__col" aria-label="{translate|escape key="plugins.themes.meridian.footer.explore"}">
					<h2 class="m-meta">{translate key="plugins.themes.meridian.footer.explore"}</h2>
					<ul class="m-footer__links">
						<li><a href="{url page="issue" op="current"}">{translate key="navigation.current"}</a></li>
						<li><a href="{url page="issue" op="archive"}">{translate key="navigation.archives"}</a></li>
						<li><a href="{url page="about"}">{translate key="about.aboutContext"}</a></li>
						<li><a href="{url page="about" op="editorialMasthead"}">{translate key="common.editorialMasthead"}</a></li>
						<li><a href="{url page="about" op="submissions"}">{translate key="about.submissions"}</a></li>
						<li><a href="{url page="about" op="contact"}">{translate key="about.contact"}</a></li>
					</ul>
				</nav>

				<section class="m-footer__col">
					<h2 class="m-meta">{translate key="plugins.themes.meridian.footer.openAccess"}</h2>
					<p class="m-footer__note">
						<svg class="m-icon m-icon--sm" aria-hidden="true"><use href="#m-i-lock-open"/></svg>
						{translate key="plugins.themes.meridian.footer.openAccessStatement"}
					</p>
					<ul class="m-footer__links">
						<li><a href="{url page="oai"}?verb=Identify">{translate key="plugins.themes.meridian.footer.oai"}</a></li>
						<li><a href="{url page="gateway" op="plugin" path="WebFeedGatewayPlugin"|to_array:"atom"}">{translate key="plugins.themes.meridian.footer.rss"}</a></li>
						<li><a href="{url page="sitemap"}">Sitemap</a></li>
					</ul>
				</section>
			{/if}
		</div>

		{if $pageFooter}
			<div class="m-footer__custom">{$pageFooter}</div>
		{/if}

		<div class="m-footer__bottom">
			<span>&copy; {"now"|date_format:"Y"} {$displayPageHeaderTitle|default:$siteTitle|escape}</span>
			<a href="{url page="about" op="aboutThisPublishingSystem"}">{translate key="about.aboutThisPublishingSystem"}</a>
		</div>
	</div>
</footer>

</div><!-- pkp_structure_page -->

{load_script context="frontend"}

{call_hook name="Templates::Common::Footer::PageFooter"}
</body>
</html>
