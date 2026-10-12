{**
 * plugins/generic/editorialTimelineManager/templates/submissionPicker.tpl
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Submission selector dialog for Editorial Timeline Manager.
 *}
<link rel="stylesheet" href="{$pluginUrl}/css/editorialTimeline.css" type="text/css" />

<div class="editorial-timeline-picker-wrapper" id="etmPickerWrapper">
	<div class="editorial-timeline-picker-header">
		<div class="editorial-timeline-badge-title">
			<i class="fa fa-history"></i>
			<h3>{translate key="plugins.generic.editorialTimelineManager.displayName"}</h3>
		</div>
		<p class="etm-subtitle">{translate key="plugins.generic.editorialTimelineManager.pickerDescription"}</p>
	</div>

	<!-- Direct ID or Search Box -->
	<div class="etm-search-card">
		<div class="etm-search-form">
			<div class="etm-input-group">
				<label for="etmSubmissionSearchInput">{translate key="plugins.generic.editorialTimelineManager.searchOrEnterId"}</label>
				<div class="etm-search-row">
					<input
						type="text"
						id="etmSubmissionSearchInput"
						class="etm-text-input"
						placeholder="{translate key="plugins.generic.editorialTimelineManager.searchPlaceholder"}"
						autocomplete="off"
					/>
					<button type="button" id="etmSearchButton" class="pkp_button pkp_button_primary">
						<i class="fa fa-search"></i>
						<span>{translate key="plugins.generic.editorialTimelineManager.search"}</span>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Recent Submissions List -->
	<div class="etm-card">
		<div class="etm-card-header">
			<h4><i class="fa fa-list"></i> {translate key="plugins.generic.editorialTimelineManager.recentSubmissions"}</h4>
		</div>
		<div class="etm-table-responsive" style="margin-bottom: 0; border: none; border-radius: 0;">
			<table class="etm-table" id="etmSubmissionsTable">
				<thead>
					<tr>
						<th style="width: 80px;">{translate key="plugins.generic.editorialTimelineManager.tableId"}</th>
						<th>{translate key="plugins.generic.editorialTimelineManager.title"}</th>
						<th style="width: 150px;">{translate key="plugins.generic.editorialTimelineManager.stage.stage"}</th>
						<th style="width: 170px;">{translate key="plugins.generic.editorialTimelineManager.submitted"}</th>
						<th style="width: 120px; text-align: right;">{translate key="plugins.generic.editorialTimelineManager.action"}</th>
					</tr>
				</thead>
				<tbody id="etmSubmissionsTbody">
					{if $submissions && count($submissions) > 0}
						{foreach from=$submissions item=sub}
							<tr data-submission-id="{$sub.submission_id}">
								<td><strong>#{$sub.submission_id}</strong></td>
								<td>
									<a href="javascript:void(0);" class="etm-submission-link" data-submission-id="{$sub.submission_id}">
										{$sub.title|escape}
									</a>
								</td>
								<td><span class="etm-stage-badge">{$sub.stage_name|escape}</span></td>
								<td>{$sub.date_submitted|escape}</td>
								<td style="text-align: right;">
									<button
										type="button"
										class="pkp_button pkp_button_primary etm-open-btn"
										data-submission-id="{$sub.submission_id}"
									>
										<i class="fa fa-pencil"></i>
										<span>{translate key="plugins.generic.editorialTimelineManager.manage"}</span>
									</button>
								</td>
							</tr>
						{/foreach}
					{else}
						<tr>
							<td colspan="5" style="text-align: center; color: #64748b; padding: 25px;">
								{translate key="plugins.generic.editorialTimelineManager.noSubmissionsFound"}
							</td>
						</tr>
					{/if}
				</tbody>
			</table>
		</div>
	</div>
</div>

<script type="text/javascript">
(function($) {ldelim}
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
		networkError: '{translate key="plugins.generic.editorialTimelineManager.error.network"}'
	{rdelim});

	function t(key, fallback, replacements) {ldelim}
		if (window.EditorialTimelineManager && window.EditorialTimelineManager.t) {ldelim}
			return window.EditorialTimelineManager.t(key, fallback, replacements);
		{rdelim}
		var str = (window.EditorialTimelineLocales && window.EditorialTimelineLocales[key]) ? window.EditorialTimelineLocales[key] : fallback;
		if (replacements && typeof replacements === 'object') {ldelim}
			for (var k in replacements) {ldelim}
				var ph1 = '{ldelim}$' + k + '{rdelim}';
				var ph2 = '{ldelim}' + k + '{rdelim}';
				str = str.split(ph1).join(replacements[k]).split(ph2).join(replacements[k]);
			{rdelim}
		{rdelim}
		return str;
	{rdelim}

	var manageUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="manage"}';
	var searchUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="searchSubmissions"}';
	var pluginUrl = '{$pluginUrl}';

	// Preload main script
	if (!window.EditorialTimelineManager) {ldelim}
		$.getScript(pluginUrl + '/js/editorialTimeline.js?v=' + (new Date().getTime()));
	{rdelim}

	// Function to transition the modal and load submission timeline
	function openTimeline(submissionId) {ldelim}
		if (!submissionId) return;

		var $wrapper = $('#etmPickerWrapper');
		var $container = $wrapper.closest('.pkp_modal_panel, .ui-dialog-content');
		var $target = $container.length ? $container : $wrapper;

		$target.html(
			'<div style="text-align: center; padding: 70px 20px;">' +
				'<div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #e2e8f0; border-top-color: #006798; border-radius: 50%; animation: etmSpin 0.8s linear infinite;"></div>' +
				'<h3 style="margin-top: 18px; color: #1e293b; font-size: 16px; font-weight: 700;">' + t('loadingSubmission', 'Loading editorial timeline #' + submissionId + '...', {ldelim}submissionId: submissionId{rdelim}) + '</h3>' +
				'<p style="color: #64748b; font-size: 13px; margin-top: 6px;">' + t('loadingDetails', 'Retrieving files, reviews, and editorial decisions...') + '</p>' +
			'</div>'
		);

		$.ajax({ldelim}
			url: manageUrl,
			type: 'GET',
			dataType: 'json',
			data: {ldelim} submissionId: submissionId {rdelim},
			success: function(resp) {ldelim}
				if (resp && resp.status && resp.content) {ldelim}
					$target.html(resp.content);
					var $dialog = $target.closest('.ui-dialog-content');
					if ($dialog.length && $dialog.dialog) {ldelim}
						try {ldelim}
							$dialog.dialog('option', 'title', '{translate key="plugins.generic.editorialTimelineManager.displayName"} — #' + submissionId);
						{rdelim} catch(e) {ldelim}{rdelim}
					{rdelim}
				{rdelim} else {ldelim}
					var errorMsg = (resp && resp.content) ? resp.content : t('loadError', 'Failed to load timeline.');
					$target.html(
						'<div style="text-align: center; padding: 40px 20px;">' +
							'<p style="color: #dc2626; font-weight: 600;">' + $('<div>').text(errorMsg).html() + '</p>' +
							'<button type="button" class="pkp_button" id="etmBackToPickerBtnErr" style="margin-top: 15px;"><i class="fa fa-arrow-left"></i> ' + t('backToPicker', 'Back to Submissions') + '</button>' +
						'</div>'
					);
					$('#etmBackToPickerBtnErr').on('click', function() {ldelim}
						loadPicker($target);
					{rdelim});
				{rdelim}
			{rdelim},
			error: function(xhr, status, err) {ldelim}
				$target.html(
					'<div style="text-align: center; padding: 40px 20px;">' +
						'<p style="color: #dc2626; font-weight: 600;">' + t('errorOccurred', 'An error occurred: ') + err + '</p>' +
						'<button type="button" class="pkp_button" id="etmBackToPickerBtnErr" style="margin-top: 15px;"><i class="fa fa-arrow-left"></i> ' + t('backToPicker', 'Back to Submissions') + '</button>' +
					'</div>'
				);
				$('#etmBackToPickerBtnErr').on('click', function() {ldelim}
					loadPicker($target);
				{rdelim});
			{rdelim}
		{rdelim});
	{rdelim}

	function loadPicker($target) {ldelim}
		$target.html('<div style="text-align: center; padding: 40px;"><p>' + t('loadingList', 'Loading submissions list...') + '</p></div>');
		$.ajax({ldelim}
			url: manageUrl,
			type: 'GET',
			dataType: 'json',
			data: {ldelim} submissionId: 0 {rdelim},
			success: function(resp) {ldelim}
				if (resp && resp.status && resp.content) {ldelim}
					$target.html(resp.content);
				{rdelim}
			{rdelim}
		{rdelim});
	{rdelim}

	// Delegated click event for Manage buttons and Title links
	$(document).off('click.etmPicker', '.etm-open-btn, .etm-submission-link');
	$(document).on('click.etmPicker', '.etm-open-btn, .etm-submission-link', function(e) {ldelim}
		e.preventDefault();
		var sId = $(this).data('submission-id') || $(this).closest('tr').data('submission-id');
		if (sId) {ldelim}
			openTimeline(sId);
		{rdelim}
	{rdelim});

	// Search handler
	function doSearch() {ldelim}
		var query = $.trim($('#etmSubmissionSearchInput').val());
		var $btn = $('#etmSearchButton');
		var $tbody = $('#etmSubmissionsTbody');

		$btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> <span>' + t('searching', 'Searching...') + '</span>');

		$.ajax({ldelim}
			url: searchUrl,
			type: 'POST',
			dataType: 'json',
			data: {ldelim} query: query {rdelim},
			success: function(resp) {ldelim}
				$btn.prop('disabled', false).html('<i class="fa fa-search"></i> <span>' + t('search', 'Search') + '</span>');
				if (resp && resp.status && resp.content) {ldelim}
					renderResults(resp.content);
				{rdelim}
			{rdelim},
			error: function() {ldelim}
				$btn.prop('disabled', false).html('<i class="fa fa-search"></i> <span>' + t('search', 'Search') + '</span>');
			{rdelim}
		{rdelim});
	{rdelim}

	function renderResults(list) {ldelim}
		var $tbody = $('#etmSubmissionsTbody');
		$tbody.empty();

		if (!list || list.length === 0) {ldelim}
			$tbody.append('<tr><td colspan="5" style="text-align: center; color: #64748b; padding: 25px;">' + t('noSubmissionsFound', 'No matching submissions found.') + '</td></tr>');
			return;
		{rdelim}

		$.each(list, function(idx, item) {ldelim}
			var tr = $('<tr data-submission-id="' + item.submission_id + '"></tr>');
			tr.append('<td><strong>#' + item.submission_id + '</strong></td>');
			tr.append('<td><a href="javascript:void(0);" class="etm-submission-link" data-submission-id="' + item.submission_id + '">' + $('<div>').text(item.title).html() + '</a></td>');
			tr.append('<td><span class="etm-stage-badge">' + $('<div>').text(item.stage_name).html() + '</span></td>');
			tr.append('<td>' + (item.date_submitted || '-') + '</td>');
			tr.append('<td style="text-align: right;"><button type="button" class="pkp_button pkp_button_primary etm-open-btn" data-submission-id="' + item.submission_id + '"><i class="fa fa-pencil"></i> <span>' + t('manage', 'Manage') + '</span></button></td>');
			$tbody.append(tr);
		{rdelim});
	{rdelim}

	$('#etmSearchButton').on('click', doSearch);
	$('#etmSubmissionSearchInput').on('keypress', function(e) {ldelim}
		if (e.which === 13) {ldelim}
			e.preventDefault();
			doSearch();
		{rdelim}
	{rdelim});

{rdelim})(jQuery);
</script>
