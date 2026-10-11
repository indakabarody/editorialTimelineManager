{**
 * plugins/generic/editorialTimelineManager/templates/workflowTab.tpl
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Tab injected into workflow.tpl tabs container.
 *}
<tab id="editorialTimelineTab" label="{translate key="plugins.generic.editorialTimelineManager.tabTitle"}">
	<div id="editorialTimelineTabContainer" class="editorial-timeline-tab-container" data-submission-id="{$editorialTimelineSubmissionId}" data-url="{$editorialTimelineUrl}">
		<div class="editorial-timeline-loading">
			<span class="pkp_spinner"></span>
			<span class="editorial-timeline-loading-text">{translate key="common.loading"}</span>
		</div>
	</div>
</tab>

<script type="text/javascript">
(function($) {ldelim}
	window.EditorialTimelineLocales = window.EditorialTimelineLocales || {};
	$.extend(window.EditorialTimelineLocales, {ldelim}
		loadingSubmission: '{translate key="plugins.generic.editorialTimelineManager.loadingSubmission"}',
		loadingDetails: '{translate key="plugins.generic.editorialTimelineManager.loadingDetails"}',
		loadingList: '{translate key="plugins.generic.editorialTimelineManager.loadingList"}',
		loadError: '{translate key="plugins.generic.editorialTimelineManager.loadError"}',
		errorOccurred: '{translate key="plugins.generic.editorialTimelineManager.errorOccurred"}',
		retry: '{translate key="plugins.generic.editorialTimelineManager.retry"}',
		backToPicker: '{translate key="plugins.generic.editorialTimelineManager.backToPicker"}',
		noSubmissionsFound: '{translate key="plugins.generic.editorialTimelineManager.noSubmissionsFound"}',
		searching: '{translate key="plugins.generic.editorialTimelineManager.searching"}',
		search: '{translate key="plugins.generic.editorialTimelineManager.search"}',
		manage: '{translate key="plugins.generic.editorialTimelineManager.manage"}',
		savingToDb: '{translate key="plugins.generic.editorialTimelineManager.savingToDb"}',
		saveToDbDirectly: '{translate key="plugins.generic.editorialTimelineManager.saveToDbDirectly"}',
		savedSuccess: '{translate key="plugins.generic.editorialTimelineManager.savedSuccess"}',
		selectDatePrompt: '{translate key="plugins.generic.editorialTimelineManager.selectDatePrompt"}',
		confirmUpdateFiles: '{translate key="plugins.generic.editorialTimelineManager.confirmUpdateFiles"}',
		confirmCopyDate: '{translate key="plugins.generic.editorialTimelineManager.confirmCopyDate"}',
		dateCopiedSuccess: '{translate key="plugins.generic.editorialTimelineManager.dateCopiedSuccess"}',
		reviewAlignedSuccess: '{translate key="plugins.generic.editorialTimelineManager.reviewAlignedSuccess"}',
		specifyOffsetPrompt: '{translate key="plugins.generic.editorialTimelineManager.specifyOffsetPrompt"}',
		sqlCopied: '{translate key="plugins.generic.editorialTimelineManager.sqlCopied"}',
		appliedFilesToForm: '{translate key="plugins.generic.editorialTimelineManager.appliedFilesToForm"}',
		shiftedToForm: '{translate key="plugins.generic.editorialTimelineManager.shiftedToForm"}',
		copy: '{translate key="plugins.generic.editorialTimelineManager.copy"}'
	{rdelim});
{rdelim})(jQuery);
</script>
