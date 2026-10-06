{**
 * plugins/generic/reviewDecline/templates/reviewer/review/modal/regretMessage.tpl
 *
 * Distributed under the GNU GPL v3.
 *
 * Replaces the core decline form. The reviewer chooses a reason, can write to
 * the editor and can suggest alternative reviewers. It posts to the core
 * saveDeclineReview operation; the core decline email text travels in the
 * hidden declineReviewMessage field.
 *}
<script type="text/javascript">
	$(function() {ldelim}
		var $form = $('#declineReviewForm');
		$form.pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
		function sync() {ldelim}
			$form.find('.rd-other').prop('hidden', $form.find('input[name=declineReason]:checked').val() !== 'other');
			var suggest = $form.find('input[name=suggestAlternatives]:checked').val() === 'yes';
			$form.find('.rd-suggestions').prop('hidden', !suggest);
			$form.find('.rd-suggestions input[data-required]').prop('required', suggest);
		{rdelim}
		$form.on('change', 'input[type=radio]', sync);
		sync();
	{rdelim});
</script>

<form class="pkp_form rd-form" id="declineReviewForm" method="post" action="{url op="saveDeclineReview" path=$submissionId|escape}">
	{csrf}
	<textarea name="declineReviewMessage" hidden aria-hidden="true" style="display: none">{$declineMessageBody|escape}</textarea>

	<p class="rd-intro">{translate key="plugins.generic.reviewDecline.intro"}</p>

	<fieldset class="rd-group">
		<legend>{translate key="plugins.generic.reviewDecline.reason"} <span class="req" aria-hidden="true">*</span></legend>
		<label class="rd-choice">
			<input type="radio" name="declineReason" value="noTime" required>
			<span>
				{translate key="plugins.generic.reviewDecline.reason.noTime"}
				<small>{translate key="plugins.generic.reviewDecline.reason.noTime.hint"}</small>
			</span>
		</label>
		<label class="rd-choice">
			<input type="radio" name="declineReason" value="conflict">
			<span>{translate key="plugins.generic.reviewDecline.reason.conflict"}</span>
		</label>
		<label class="rd-choice">
			<input type="radio" name="declineReason" value="expertise">
			<span>{translate key="plugins.generic.reviewDecline.reason.expertise"}</span>
		</label>
		<label class="rd-choice">
			<input type="radio" name="declineReason" value="other">
			<span>{translate key="plugins.generic.reviewDecline.reason.other"}</span>
		</label>
		<div class="rd-other" hidden>
			<label for="declineReasonOther" class="-screenReader">{translate key="plugins.generic.reviewDecline.reason.otherLabel"}</label>
			<input type="text" id="declineReasonOther" name="declineReasonOther" maxlength="300" placeholder="{translate key="plugins.generic.reviewDecline.reason.otherLabel"}">
		</div>
	</fieldset>

	<div class="rd-group">
		<label for="declineComments" class="rd-label">{translate key="plugins.generic.reviewDecline.comments"}</label>
		<textarea id="declineComments" name="declineComments" rows="4" maxlength="5000" placeholder="{translate key="plugins.generic.reviewDecline.comments.placeholder"}"></textarea>
	</div>

	<fieldset class="rd-group">
		<legend>{translate key="plugins.generic.reviewDecline.suggest.question"}</legend>
		<p class="rd-help">{translate key="plugins.generic.reviewDecline.suggest.help"}</p>
		<label class="rd-choice rd-choice--inline"><input type="radio" name="suggestAlternatives" value="yes"> <span>{translate key="common.yes"}</span></label>
		<label class="rd-choice rd-choice--inline"><input type="radio" name="suggestAlternatives" value="no" checked> <span>{translate key="common.no"}</span></label>

		<div class="rd-suggestions" hidden>
			{for $i=1 to 2}
				<div class="rd-person">
					<p class="rd-person__title">{translate key="plugins.generic.reviewDecline.suggest.person" number=$i}</p>
					<div class="rd-person__fields">
						<label>
							<span>{translate key="plugins.generic.reviewDecline.suggest.name"}{if $i == 1} <span class="req" aria-hidden="true">*</span>{/if}</span>
							<input type="text" name="suggestName[]" maxlength="150" autocomplete="off"{if $i == 1} data-required="1"{/if}>
						</label>
						<label>
							<span>{translate key="plugins.generic.reviewDecline.suggest.email"}{if $i == 1} <span class="req" aria-hidden="true">*</span>{/if}</span>
							<input type="email" name="suggestEmail[]" maxlength="150" autocomplete="off"{if $i == 1} data-required="1"{/if}>
						</label>
						<label>
							<span>{translate key="plugins.generic.reviewDecline.suggest.affiliation"}</span>
							<input type="text" name="suggestAffiliation[]" maxlength="255" autocomplete="off">
						</label>
					</div>
				</div>
			{/for}
		</div>
	</fieldset>

	{fbvFormButtons submitText="reviewer.submission.declineReview" hideCancel=true}
</form>
