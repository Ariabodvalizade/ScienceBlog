{**
 * plugins/generic/meridianAdmin/templates/workspace.tpl
 *
 * Distributed under the GNU GPL v3.
 *
 * The admin panel's Home page. Rendered inside the backend's Vue app, so it
 * uses plain markup plus the backend's global <icon> component.
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
<div class="ma-home">

	<header class="ma-hero">
		<div class="ma-hero__text">
			<p class="ma-eyebrow">{$maToday|escape}</p>
			<h1 class="ma-hero__title">{$maGreeting|escape}</h1>
			<p class="ma-hero__lead">
				{if $maIsEditor}
					{translate key="plugins.generic.meridianAdmin.lead.editor" journal=$maJournal|escape}
				{else}
					{translate key="plugins.generic.meridianAdmin.lead.author" journal=$maJournal|escape}
				{/if}
			</p>
		</div>
		<div class="ma-hero__actions">
			{if $maCanSubmit}
				<a class="ma-btn ma-btn--primary" href="{$maSubmitUrl|escape}">
					<icon icon="Add" class="h-5 w-5" aria-hidden="true"></icon>
					<span>{translate key="plugins.generic.meridianAdmin.action.submit"}</span>
				</a>
			{/if}
		</div>
	</header>

	{if $maReviewCount}
		<a class="ma-banner" href="{$maReviewUrl|escape}">
			<icon icon="ReviewAssignments" class="h-6 w-6" aria-hidden="true"></icon>
			<span>{translate key="plugins.generic.meridianAdmin.review.banner" count=$maReviewCount number=$maReviewCount}</span>
			<span class="ma-banner__go">{translate key="plugins.generic.meridianAdmin.review.open"} →</span>
		</a>
	{/if}

	{if $maIsEditor}
		<section class="ma-section" aria-labelledby="ma-attention-title">
			<div class="ma-sectionHead">
				<h2 id="ma-attention-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.attention.title"}</h2>
			</div>
			<div class="ma-attention">
				{foreach from=$maAttention item=item}
					<a class="ma-stat ma-stat--{$item.tone|escape}{if !$item.count} is-zero{/if}" href="{$item.url|escape}">
						<span class="ma-stat__top">
							<span class="ma-stat__icon"><icon icon="{$item.icon|escape}" class="h-5 w-5" aria-hidden="true"></icon></span>
							<span class="ma-stat__count">{$item.count|escape}</span>
						</span>
						<span class="ma-stat__label">{$item.label|escape}</span>
						<span class="ma-stat__hint">{$item.hint|escape}</span>
					</a>
				{/foreach}
			</div>
		</section>

		<section class="ma-section" aria-labelledby="ma-pipeline-title">
			<div class="ma-sectionHead">
				<h2 id="ma-pipeline-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.pipeline.title"}</h2>
				<p class="ma-sectionHead__note">{translate key="plugins.generic.meridianAdmin.pipeline.note"}</p>
			</div>
			<ol class="ma-pipeline">
				{foreach from=$maPipeline item=step name=pipeline}
					<li class="ma-step ma-step--{$step.key|escape}">
						<a class="ma-step__link" href="{$step.url|escape}">
							<span class="ma-step__head">
								<span class="ma-step__num">{$smarty.foreach.pipeline.iteration}</span>
								<span class="ma-step__label">{$step.label|escape}</span>
							</span>
							<span class="ma-step__count">{$step.count|escape}</span>
							<span class="ma-step__hint">{$step.hint|escape}</span>
						</a>
					</li>
				{/foreach}
			</ol>
		</section>

		<div class="ma-grid">
			<section class="ma-panel" aria-labelledby="ma-recent-title">
				<div class="ma-panel__head">
					<h2 id="ma-recent-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.recent.title"}</h2>
					{if $maActiveUrl}
						<a class="ma-more" href="{$maActiveUrl|escape}">{translate key="plugins.generic.meridianAdmin.recent.all"} →</a>
					{/if}
				</div>
				{if $maRecent}
					<ul class="ma-list">
						{foreach from=$maRecent item=row}
							<li class="ma-row">
								<a class="ma-row__link" href="{$row.url|escape}">
									<span class="ma-row__title">{$row.title|escape}</span>
									<span class="ma-row__meta">#{$row.id|escape} · {$row.authors|escape} · {translate key="plugins.generic.meridianAdmin.updated" date=$row.date|escape}</span>
								</a>
								<span class="ma-chip ma-chip--{$row.tone|escape}">{$row.status|escape}</span>
							</li>
						{/foreach}
					</ul>
				{else}
					<p class="ma-empty">{translate key="plugins.generic.meridianAdmin.recent.empty"}</p>
				{/if}
			</section>

			<aside class="ma-side">
				{if $maIssue}
					<section class="ma-panel ma-issue" aria-labelledby="ma-issue-title">
						<h2 id="ma-issue-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.issue.title"}</h2>
						{if $maIssue.label}
							<div class="ma-issue__body">
								{if $maIssue.cover}
									<img class="ma-issue__cover" src="{$maIssue.cover|escape}" alt="" width="72" height="96">
								{/if}
								<div>
									<p class="ma-issue__label">{$maIssue.label|escape}</p>
									{if $maIssue.title}<p class="ma-issue__name">{$maIssue.title|escape}</p>{/if}
									<p class="ma-issue__meta">
										{translate key="plugins.generic.meridianAdmin.issue.articles" count=$maIssue.articles number=$maIssue.articles}
										{if $maIssue.published} · {$maIssue.published|escape}{/if}
									</p>
								</div>
							</div>
						{else}
							<p class="ma-empty">{translate key="plugins.generic.meridianAdmin.issue.none"}</p>
						{/if}
						<p class="ma-issue__future">
							{translate key="plugins.generic.meridianAdmin.issue.future" count=$maIssue.future number=$maIssue.future}
						</p>
						<div class="ma-issue__actions">
							{if $maIssue.viewUrl}
								<a class="ma-btn ma-btn--small" href="{$maIssue.viewUrl|escape}" target="_blank" rel="noopener">{translate key="plugins.generic.meridianAdmin.issue.view"}</a>
							{/if}
							<a class="ma-btn ma-btn--small" href="{$maIssue.manageUrl|escape}">{translate key="plugins.generic.meridianAdmin.issue.manage"}</a>
						</div>
					</section>
				{/if}

				<section class="ma-panel" aria-labelledby="ma-actions-title">
					<h2 id="ma-actions-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.actions.title"}</h2>
					<ul class="ma-actions">
						{foreach from=$maActions item=action}
							<li>
								<a class="ma-action" href="{$action.url|escape}">
									<icon icon="{$action.icon|escape}" class="h-5 w-5" aria-hidden="true"></icon>
									<span>{$action.label|escape}</span>
								</a>
							</li>
						{/foreach}
					</ul>
				</section>
			</aside>
		</div>
	{/if}

	{if isset($maMine)}
		<div class="ma-grid">
			<section class="ma-panel" aria-labelledby="ma-mine-title">
				<div class="ma-panel__head">
					<h2 id="ma-mine-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.mine.title"}</h2>
					{if $maMine}
						<a class="ma-more" href="{$maMineUrl|escape}">{translate key="plugins.generic.meridianAdmin.mine.all"} →</a>
					{/if}
				</div>
				{if $maMine}
					<ul class="ma-list">
						{foreach from=$maMine item=row}
							<li class="ma-row">
								<a class="ma-row__link" href="{$row.url|escape}">
									<span class="ma-row__title">{$row.title|default:"—"|escape}</span>
									<span class="ma-row__meta">#{$row.id|escape} · {translate key="plugins.generic.meridianAdmin.updated" date=$row.date|escape}</span>
								</a>
								<span class="ma-chip ma-chip--{$row.tone|escape}">{$row.status|escape}</span>
							</li>
						{/foreach}
					</ul>
				{else}
					<div class="ma-emptyState">
						<p class="ma-emptyState__title">{translate key="plugins.generic.meridianAdmin.mine.empty"}</p>
						<p>{translate key="plugins.generic.meridianAdmin.mine.emptyHint"}</p>
						{if $maCanSubmit}
							<p class="ma-emptyState__actions">
								<a class="ma-btn ma-btn--primary" href="{$maSubmitUrl|escape}">{translate key="plugins.generic.meridianAdmin.action.submit"}</a>
								<a class="ma-more" href="{$maGuidelinesUrl|escape}">{translate key="plugins.generic.meridianAdmin.mine.guidelines"}</a>
							</p>
						{/if}
					</div>
				{/if}

				<ol class="ma-journey" aria-label="{translate key="plugins.generic.meridianAdmin.journey.title"}">
					<li><strong>{translate key="plugins.generic.meridianAdmin.journey.submit"}</strong><span>{translate key="plugins.generic.meridianAdmin.journey.submit.hint"}</span></li>
					<li><strong>{translate key="plugins.generic.meridianAdmin.journey.review"}</strong><span>{translate key="plugins.generic.meridianAdmin.journey.review.hint"}</span></li>
					<li><strong>{translate key="plugins.generic.meridianAdmin.journey.revise"}</strong><span>{translate key="plugins.generic.meridianAdmin.journey.revise.hint"}</span></li>
					<li><strong>{translate key="plugins.generic.meridianAdmin.journey.publish"}</strong><span>{translate key="plugins.generic.meridianAdmin.journey.publish.hint"}</span></li>
				</ol>
			</section>

			<aside class="ma-panel ma-profile" aria-labelledby="ma-profile-title">
				<div class="ma-profile__head">
					{if $maPhotoUrl}
						<img class="ma-profile__photo" src="{$maPhotoUrl|escape}" alt="" width="64" height="64">
					{else}
						<span class="ma-profile__photo ma-profile__photo--initials" aria-hidden="true">{$maInitials|escape}</span>
					{/if}
					<div>
						<h2 id="ma-profile-title" class="ma-h2">{translate key="plugins.generic.meridianAdmin.profile.title"}</h2>
						<p class="ma-profile__name">{$maFullName|escape}</p>
					</div>
				</div>
				<p class="ma-profile__progress">
					{translate key="plugins.generic.meridianAdmin.profile.progress" done=$maProfileDone total=$maProfileTotal}
				</p>
				<progress class="ma-meter" max="{$maProfileTotal|escape}" value="{$maProfileDone|escape}"></progress>
				<ul class="ma-checks">
					{foreach from=$maProfileChecks item=check}
						<li class="{if $check.done}is-done{/if}">
							<span class="ma-checks__mark" aria-hidden="true">{if $check.done}✓{/if}</span>
							<span>{$check.label|escape}</span>
							<span class="-screenReader">{if $check.done}{translate key="plugins.generic.meridianAdmin.profile.done"}{else}{translate key="plugins.generic.meridianAdmin.profile.missing"}{/if}</span>
						</li>
					{/foreach}
				</ul>
				<p class="ma-profile__note">{translate key="plugins.generic.meridianAdmin.profile.note"}</p>
				<div class="ma-profile__actions">
					<a class="ma-btn{if $maProfileDone < $maProfileTotal} ma-btn--primary{/if}" href="{$maProfileUrl|escape}">
						<icon icon="Photo" class="h-5 w-5" aria-hidden="true"></icon>
						<span>{translate key="plugins.generic.meridianAdmin.profile.edit"}</span>
					</a>
					{if $maAuthorPageUrl}
						<a class="ma-more" href="{$maAuthorPageUrl|escape}" target="_blank" rel="noopener">{translate key="plugins.generic.meridianAdmin.profile.page"} ↗</a>
					{/if}
				</div>
			</aside>
		</div>
	{/if}

</div>
{/block}
