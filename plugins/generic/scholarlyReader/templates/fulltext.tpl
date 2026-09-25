{**
 * plugins/generic/scholarlyReader/templates/fulltext.tpl
 *
 * @brief Inline full text of the article (from its HTML galley).
 *
 * @uses $scholarlyFullText string Sanitized, citation-linked HTML
 *}
<section class="sr-fulltext" id="fulltext" aria-labelledby="sr-fulltext-title">
	<h2 class="sr-fulltext__title m-section-title" id="sr-fulltext-title">{translate key="plugins.generic.scholarlyReader.fullText"}</h2>
	<div class="sr-fulltext__body">
		{$scholarlyFullText}
	</div>
</section>
