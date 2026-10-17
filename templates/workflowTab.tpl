{**
 * plugins/generic/editorialTimelineManager/templates/workflowTab.tpl
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Tab injected into workflow.tpl tabs container.
 *}
{if $editorialTimelineIsAuthorized}
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
	window.EditorialTimelineConfig = window.EditorialTimelineConfig || {};
	$.extend(window.EditorialTimelineConfig, {ldelim}
		submissionId: {$editorialTimelineSubmissionId|json_encode},
		csrfToken: {$editorialTimelineCsrfToken|json_encode},
		timelineUrl: {$editorialTimelineUrl|json_encode},
		updateEventLogDateUrl: {$editorialTimelineUpdateEventLogDateUrl|json_encode},
		getEventLogDetailsUrl: {$editorialTimelineGetEventLogDetailsUrl|json_encode},
		updateFileDateUrl: {$editorialTimelineUpdateFileDateUrl|json_encode},
		updateNoteDateUrl: {$editorialTimelineUpdateNoteDateUrl|json_encode},
		updateActivityLogUrl: {$editorialTimelineUpdateActivityLogUrl|json_encode},
		getFileDetailsUrl: {$editorialTimelineGetFileDetailsUrl|json_encode}
	{rdelim});

	window.EditorialTimelineLocales = window.EditorialTimelineLocales || {};
	$.extend(window.EditorialTimelineLocales, {ldelim}
		displayName: '{translate key="plugins.generic.editorialTimelineManager.displayName"}',
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
		appliedFilesToForm: '{translate key="plugins.generic.editorialTimelineManager.appliedFilesToForm"}',
		shiftedToForm: '{translate key="plugins.generic.editorialTimelineManager.shiftedToForm"}',
		copy: '{translate key="plugins.generic.editorialTimelineManager.copy"}',
		save: '{translate key="plugins.generic.editorialTimelineManager.save"}',
		delete: '{translate key="plugins.generic.editorialTimelineManager.delete"}',
		cancel: '{translate key="plugins.generic.editorialTimelineManager.cancel"}',
		saving: '{translate key="plugins.generic.editorialTimelineManager.saving"}',
		editDateTime: '{translate key="plugins.generic.editorialTimelineManager.editDateTime"}',
		dateUpdatedSuccess: '{translate key="plugins.generic.editorialTimelineManager.dateUpdatedSuccess"}',
		fileUpdatedSuccess: '{translate key="plugins.generic.editorialTimelineManager.fileUpdatedSuccess"}',
		noteUpdatedSuccess: '{translate key="plugins.generic.editorialTimelineManager.noteUpdatedSuccess"}',
		activityLogUpdated: '{translate key="plugins.generic.editorialTimelineManager.activityLog.successUpdated"}',
		activityLogDeleted: '{translate key="plugins.generic.editorialTimelineManager.activityLog.successDeleted"}',
		activityLogCreated: '{translate key="plugins.generic.editorialTimelineManager.activityLog.successCreated"}',
		confirmDeleteLog: '{translate key="plugins.generic.editorialTimelineManager.activityLog.confirmDelete"}',
		confirmCleanPluginLogs: '{translate key="plugins.generic.editorialTimelineManager.activityLog.confirmCleanPluginLogs"}',
		fillRequiredFields: '{translate key="plugins.generic.editorialTimelineManager.activityLog.fillRequiredFields"}',
		networkError: '{translate key="plugins.generic.editorialTimelineManager.error.network"}',
		serverError: '{translate key="plugins.generic.editorialTimelineManager.error.server"}',
		shiftError: '{translate key="plugins.generic.editorialTimelineManager.error.shift"}',
		updateFilesError: '{translate key="plugins.generic.editorialTimelineManager.error.updateFiles"}',
		updateReviewError: '{translate key="plugins.generic.editorialTimelineManager.error.updateReview"}',
		createLogError: '{translate key="plugins.generic.editorialTimelineManager.error.createLog"}',
		updateLogError: '{translate key="plugins.generic.editorialTimelineManager.error.updateLog"}',
		deleteLogError: '{translate key="plugins.generic.editorialTimelineManager.error.deleteLog"}',
		cleanLogsError: '{translate key="plugins.generic.editorialTimelineManager.error.cleanLogs"}',
		logsCleanedSuccess: '{translate key="plugins.generic.editorialTimelineManager.success.pluginLogsCleaned"}',
		reviewUpdatedSuccess: '{translate key="plugins.generic.editorialTimelineManager.success.reviewerUpdated"}'
	{rdelim});
{rdelim})(jQuery);
</script>
{/if}
