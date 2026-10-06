{**
 * plugins/generic/reviewDecline/templates/workflow/reviewHistory.tpl
 *
 * Distributed under the GNU GPL v3.
 *
 * Core review history (dates), plus why the reviewer declined, their comments
 * and the reviewers they suggested instead.
 *}
<div class="pkp_review_history">
	{foreach from=$dates key="localeKey" item="date"}
		{if $date}
		<div>
			<strong>{$date|date_format:$datetimeFormatShort}</strong>
			{translate key=$localeKey}
		</div>
		{/if}
	{/foreach}
</div>

{if $reviewDecline}
	<div class="rd-history">
		<h3 class="rd-history__title">{translate key="plugins.generic.reviewDecline.history.title"}</h3>
		<dl>
			<dt>{translate key="plugins.generic.reviewDecline.reason"}</dt>
			<dd>{$reviewDecline.reason|escape}</dd>
			{if $reviewDecline.comments}
				<dt>{translate key="plugins.generic.reviewDecline.comments"}</dt>
				<dd>{$reviewDecline.comments|escape|nl2br}</dd>
			{/if}
			{if $reviewDecline.suggestions}
				<dt>{translate key="plugins.generic.reviewDecline.suggestions"}</dt>
				<dd>
					<ul>
						{foreach from=$reviewDecline.suggestions item=person}
							<li>{$person.name|escape} &lt;{$person.email|escape}&gt;{if $person.affiliation}, {$person.affiliation|escape}{/if}</li>
						{/foreach}
					</ul>
					<p class="rd-history__hint">{translate key="plugins.generic.reviewDecline.history.inviteHint"}</p>
				</dd>
			{/if}
		</dl>
	</div>
{/if}
