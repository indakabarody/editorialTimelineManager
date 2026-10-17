{**
 * plugins/generic/editorialTimelineManager/templates/timelineModal.tpl
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Comprehensive Editorial Timeline Manager modal and page view.
 * Aligned with PKP OJS 3.3 standards and native design system.
 *}
<link rel="stylesheet" href="{$pluginUrl}/css/editorialTimeline.css" type="text/css" />

<div class="editorial-timeline-app" id="editorialTimelineApp_{$submissionId}" data-submission-id="{$submissionId}">

	<!-- Top Header & Summary Bar -->
	<header class="etm-header">
		<div class="etm-header-info">
			<div class="etm-header-badges">
				<span class="etm-badge etm-badge-primary">
					<i class="fa fa-hashtag"></i> {$submissionId}
				</span>
				<span class="etm-badge etm-badge-info">
					<i class="fa fa-folder-open-o"></i> {$timeline.submission.stage_name|escape}
				</span>
				{if $timeline.submission.status == 3}
					<span class="etm-badge etm-badge-success">
						<i class="fa fa-check-circle"></i> {translate key="plugins.generic.editorialTimelineManager.status.published"}
					</span>
				{elseif $timeline.submission.status == 1}
					<span class="etm-badge etm-badge-warning">
						<i class="fa fa-clock-o"></i> {translate key="plugins.generic.editorialTimelineManager.status.queued"}
					</span>
				{else}
					<span class="etm-badge etm-badge-secondary">
						<i class="fa fa-archive"></i> {translate key="plugins.generic.editorialTimelineManager.status.archived"}
					</span>
				{/if}
			</div>
			<h2 class="etm-title">{$timeline.submission.title|escape}</h2>
		</div>

		<div class="etm-header-actions">
			<button type="button" class="pkp_button etm-btn-back-picker" id="etmBackToPickerBtn_{$submissionId}" title="{translate key="plugins.generic.editorialTimelineManager.backToPicker"}">
				<i class="fa fa-arrow-left"></i>
				<span>{translate key="plugins.generic.editorialTimelineManager.backToPicker"}</span>
			</button>
			<a href="{url router=$smarty.const.ROUTE_PAGE page="workflow" op="access" path=$submissionId}" target="_blank" class="pkp_button" title="{translate key="plugins.generic.editorialTimelineManager.stage.stage"}">
				<i class="fa fa-external-link"></i>
				<span>{translate key="plugins.generic.editorialTimelineManager.stage.stage"}</span>
			</a>
			<button type="button" class="pkp_button etm-btn-shift" id="etmTriggerShiftModalBtn_{$submissionId}">
				<i class="fa fa-calendar"></i>
				<span>{translate key="plugins.generic.editorialTimelineManager.shiftTimeline"}</span>
			</button>

			<button type="button" class="pkp_button pkp_button_primary etm-btn-save-primary" id="etmSaveBtn_{$submissionId}">
				<i class="fa fa-check"></i>
				<span class="etm-btn-text">{translate key="plugins.generic.editorialTimelineManager.saveToDbDirectly"}</span>
				<span class="etm-spinner-inline" style="display:none;"></span>
			</button>
		</div>
	</header>

	<!-- In-Place Alert / Notification Toast -->
	<div class="etm-alert" id="etmAlert_{$submissionId}" style="display:none;">
		<span class="etm-alert-icon"></span>
		<span class="etm-alert-message"></span>
		<button type="button" class="etm-alert-close">&times;</button>
	</div>

	<!-- Navigation Tabs -->
	<nav class="etm-nav-tabs" role="tablist">
		<button type="button" class="etm-tab-link active" data-tab="tabOverview_{$submissionId}">
			<i class="fa fa-info-circle"></i>
			<span>{translate key="plugins.generic.editorialTimelineManager.tab.overview"}</span>
		</button>
		<button type="button" class="etm-tab-link" data-tab="tabFiles_{$submissionId}">
			<i class="fa fa-files-o"></i>
			<span>{translate key="plugins.generic.editorialTimelineManager.tab.files"}</span>
			<span class="etm-tab-count" id="etmFilesCount_{$submissionId}">{count($timeline.files)}</span>
		</button>
		<button type="button" class="etm-tab-link" data-tab="tabReviews_{$submissionId}">
			<i class="fa fa-user-circle-o"></i>
			<span>{translate key="plugins.generic.editorialTimelineManager.tab.reviews"}</span>
			<span class="etm-tab-count">{count($timeline.reviews)}</span>
		</button>
		<button type="button" class="etm-tab-link" data-tab="tabDecisions_{$submissionId}">
			<i class="fa fa-gavel"></i>
			<span>{translate key="plugins.generic.editorialTimelineManager.tab.decisions"}</span>
			<span class="etm-tab-count">{count($timeline.decisions)}</span>
		</button>
		<button type="button" class="etm-tab-link" data-tab="tabDiscussions_{$submissionId}">
			<i class="fa fa-comments-o"></i>
			<span>{translate key="plugins.generic.editorialTimelineManager.tab.discussions"}</span>
			<span class="etm-tab-count">{count($timeline.discussions)}</span>
		</button>
		<button type="button" class="etm-tab-link" data-tab="tabActivityLog_{$submissionId}">
			<i class="fa fa-history"></i>
			<span>{translate key="plugins.generic.editorialTimelineManager.tab.activityLog"}</span>
			<span class="etm-tab-count" id="etmActivityLogCount_{$submissionId}">{count($timeline.activity_logs)}</span>
		</button>
	</nav>

	<!-- Main Form Content -->
	<form id="etmForm_{$submissionId}" class="etm-form">
		<input type="hidden" name="csrfToken" value="{$csrfToken}" />
		<input type="hidden" name="submissionId" value="{$submissionId}" />

		<!-- ============================================================== -->
		<!-- TAB 1: OVERVIEW & CORE DATES                                  -->
		<!-- ============================================================== -->
		<section class="etm-tab-panel active" id="tabOverview_{$submissionId}">
			<div class="etm-grid-2col">

				<!-- Submission Core Timestamps -->
				<div class="etm-card">
					<div class="etm-card-header">
						<h3><i class="fa fa-calendar-check-o"></i> {translate key="plugins.generic.editorialTimelineManager.coreSubmissionDates"}</h3>
					</div>
					<div class="etm-card-body">
						<div class="etm-form-field">
							<label for="etmDateSubmitted_{$submissionId}">
								{translate key="plugins.generic.editorialTimelineManager.submitted"} (<code>submissions.date_submitted</code>)
							</label>
							<input
								type="datetime-local"
								class="etm-input etm-datetime"
								id="etmDateSubmitted_{$submissionId}"
								name="submission[date_submitted]"
								value="{EditorialTimelineHelper::toHtmlDatetime($timeline.submission.date_submitted)}"
							/>
							<small class="etm-help-text">{translate key="plugins.generic.editorialTimelineManager.help.dateSubmitted"}</small>
						</div>

						<div class="etm-form-field">
							<label>{translate key="plugins.generic.editorialTimelineManager.lastModified"}</label>
							<div class="etm-readonly-val">
								<i class="fa fa-clock-o"></i> {EditorialTimelineHelper::formatDisplayDate($timeline.submission.last_modified)}
							</div>
						</div>
					</div>
				</div>

				<!-- Publication Dates -->
				<div class="etm-card">
					<div class="etm-card-header">
						<h3><i class="fa fa-book"></i> {translate key="plugins.generic.editorialTimelineManager.publication"}</h3>
					</div>
					<div class="etm-card-body">
						{if $timeline.publications && count($timeline.publications) > 0}
							{foreach from=$timeline.publications item=pub}
								<div class="etm-pub-entry">
									<div class="etm-pub-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
										<strong>{translate key="plugins.generic.editorialTimelineManager.publicationVersion"} {$pub.version}</strong>
										{if $pub.status == 3}
											<span class="etm-badge etm-badge-success">
												<i class="fa fa-check-circle"></i> {translate key="plugins.generic.editorialTimelineManager.status.published"}
											</span>
										{else}
											<span class="etm-badge etm-badge-warning">
												<i class="fa fa-clock-o"></i> {translate key="plugins.generic.editorialTimelineManager.status.unpublished"}
											</span>
										{/if}
									</div>

									<div class="etm-form-field">
										<label>
											{translate key="plugins.generic.editorialTimelineManager.published"} (<code>publications.date_published</code>)
										</label>
										<input
											type="date"
											class="etm-input etm-date"
											name="publications[{$pub.publication_id}][date_published]"
											value="{$pub.date_published|escape}"
										/>
									</div>
								</div>
							{/foreach}
						{else}
							<p class="etm-empty-card">{translate key="plugins.generic.editorialTimelineManager.noPublications"}</p>
						{/if}
					</div>
				</div>

			</div>

			<!-- Batch Shift Helper Card -->
			<div class="etm-card etm-shift-banner-card">
				<div class="etm-card-header">
					<h3><i class="fa fa-fast-forward"></i> {translate key="plugins.generic.editorialTimelineManager.quickBatchShift"}</h3>
				</div>
				<div class="etm-card-body">
					<p style="margin-top: 0; color: #475569;">{translate key="plugins.generic.editorialTimelineManager.quickBatchShiftDesc"}</p>
					<div class="etm-shift-controls">
						<div class="etm-shift-input-box">
							<label for="etmQuickShiftDays_{$submissionId}">{translate key="plugins.generic.editorialTimelineManager.daysOffset"}:</label>
							<div class="etm-number-stepper">
								<button type="button" class="etm-btn-step" data-step="-30">-30d</button>
								<button type="button" class="etm-btn-step" data-step="-7">-7d</button>
								<button type="button" class="etm-btn-step" data-step="-1">-1d</button>
								<input type="number" id="etmQuickShiftDays_{$submissionId}" class="etm-input etm-num" value="0" />
								<button type="button" class="etm-btn-step" data-step="1">+1d</button>
								<button type="button" class="etm-btn-step" data-step="7">+7d</button>
								<button type="button" class="etm-btn-step" data-step="30">+30d</button>
							</div>
						</div>

						<div class="etm-shift-options">
							<label><input type="checkbox" id="etmShiftOptFiles_{$submissionId}" checked /> {translate key="plugins.generic.editorialTimelineManager.tab.files"}</label>
							<label><input type="checkbox" id="etmShiftOptReviews_{$submissionId}" checked /> {translate key="plugins.generic.editorialTimelineManager.tab.reviews"}</label>
							<label><input type="checkbox" id="etmShiftOptDecisions_{$submissionId}" checked /> {translate key="plugins.generic.editorialTimelineManager.tab.decisions"}</label>
							<label><input type="checkbox" id="etmShiftOptDiscussions_{$submissionId}" checked /> {translate key="plugins.generic.editorialTimelineManager.tab.discussions"}</label>
							<label><input type="checkbox" id="etmShiftOptSubmission_{$submissionId}" checked /> {translate key="plugins.generic.editorialTimelineManager.submitted"}</label>
							<label><input type="checkbox" id="etmShiftOptPublications_{$submissionId}" /> {translate key="plugins.generic.editorialTimelineManager.published"}</label>
						</div>

						<button type="button" class="pkp_button pkp_button_primary etm-btn-apply-shift" id="etmApplyQuickShiftBtn_{$submissionId}">
							<i class="fa fa-magic"></i>
							<span>{translate key="plugins.generic.editorialTimelineManager.applyShiftToForm"}</span>
						</button>
					</div>
				</div>
			</div>
		</section>

		<!-- ============================================================== -->
		<!-- TAB 2: WORKFLOW FILES TIMESTAMPS                              -->
		<!-- ============================================================== -->
		<section class="etm-tab-panel" id="tabFiles_{$submissionId}">

			<!-- Bulk Stage Setter Card -->
			<div class="etm-card etm-bulk-card">
				<div class="etm-card-header">
					<h3><i class="fa fa-sliders"></i> {translate key="plugins.generic.editorialTimelineManager.bulkStageDateSetter"}</h3>
				</div>
				<div class="etm-card-body">
					<p style="margin-top: 0; color: #475569;">{translate key="plugins.generic.editorialTimelineManager.bulkStageDesc"}</p>
					<div class="etm-bulk-bar">
						<div class="etm-field-inline">
							<label for="etmBulkStageSelect_{$submissionId}">{translate key="plugins.generic.editorialTimelineManager.targetStage"}:</label>
							<select id="etmBulkStageSelect_{$submissionId}" class="etm-select">
								<option value="ALL">{translate key="plugins.generic.editorialTimelineManager.allFiles"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_SUBMISSION}">{translate key="plugins.generic.editorialTimelineManager.stage.submissionFiles"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_REVIEW_FILE}">{translate key="plugins.generic.editorialTimelineManager.stage.reviewFiles"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_REVIEW_ATTACHMENT}">{translate key="plugins.generic.editorialTimelineManager.stage.reviewAttachments"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_REVIEW_REVISION}">{translate key="plugins.generic.editorialTimelineManager.stage.revisions"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_FINAL}">{translate key="plugins.generic.editorialTimelineManager.stage.copyeditingDraftFiles"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_COPYEDIT}">{translate key="plugins.generic.editorialTimelineManager.stage.copyeditedFiles"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_PRODUCTION_READY}">{translate key="plugins.generic.editorialTimelineManager.stage.productionReadyFiles"}</option>
								<option value="{$smarty.const.SUBMISSION_FILE_PROOF}">{translate key="plugins.generic.editorialTimelineManager.stage.galleys"}</option>
							</select>
						</div>

						<div class="etm-field-inline">
							<label for="etmBulkDateTime_{$submissionId}">{translate key="plugins.generic.editorialTimelineManager.targetDate"}:</label>
							<div style="display: flex; gap: 4px;">
								<input type="datetime-local" id="etmBulkDateTime_{$submissionId}" class="etm-input etm-datetime" />
								<button type="button" class="pkp_button etm-btn-helper" id="etmSetBulkNow_{$submissionId}">
									<i class="fa fa-clock-o"></i> {translate key="plugins.generic.editorialTimelineManager.now"}
								</button>
							</div>
						</div>

						<div class="etm-field-inline etm-checkbox-inline">
							<label>
								<input type="checkbox" id="etmBulkSyncUpdated_{$submissionId}" />
								{translate key="plugins.generic.editorialTimelineManager.alsoUpdateModified"}
							</label>
						</div>

						<div class="etm-bulk-btn-group">
							<button type="button" class="pkp_button pkp_button_primary etm-btn-direct-update" id="etmDirectUpdateFilesBtn_{$submissionId}">
								<i class="fa fa-bolt"></i> <span>{translate key="plugins.generic.editorialTimelineManager.updateDbDirectly1Click"}</span>
							</button>
							<button type="button" class="pkp_button etm-btn-apply-bulk" id="etmApplyBulkFilesBtn_{$submissionId}">
								<i class="fa fa-pencil-square-o"></i> <span>{translate key="plugins.generic.editorialTimelineManager.applyToFormOnly"}</span>
							</button>
						</div>
					</div>
				</div>
			</div>

			<!-- Filter Bar -->
			<div class="etm-filter-bar">
				<span class="etm-filter-label"><i class="fa fa-filter"></i> {translate key="plugins.generic.editorialTimelineManager.filter"}:</span>
				<button type="button" class="etm-filter-chip active" data-filter="all">{translate key="plugins.generic.editorialTimelineManager.all"} ({count($timeline.files)})</button>
				{foreach from=$timeline.stage_counts key=stageId item=cnt}
					<button type="button" class="etm-filter-chip" data-filter="stage-{$stageId}">
						{EditorialTimelineHelper::getFileStageName($stageId)} ({$cnt})
					</button>
				{/foreach}
			</div>

			<!-- Files Table -->
			<div class="etm-table-responsive">
				<table class="etm-table etm-files-table" id="etmFilesTable_{$submissionId}">
					<thead>
						<tr>
							<th style="width: 80px;">{translate key="plugins.generic.editorialTimelineManager.tableId"}</th>
							<th>{translate key="plugins.generic.editorialTimelineManager.fileName"}</th>
							<th style="width: 140px;">{translate key="plugins.generic.editorialTimelineManager.stage.stage"}</th>
							<th style="width: 130px;">{translate key="plugins.generic.editorialTimelineManager.uploader"}</th>
							<th style="width: 220px;">{translate key="plugins.generic.editorialTimelineManager.uploadDate"} (<code>created_at</code>)</th>
							<th style="width: 220px;">{translate key="plugins.generic.editorialTimelineManager.modifiedDate"} (<code>updated_at</code>)</th>
							<th style="width: 80px; text-align: center;">{translate key="plugins.generic.editorialTimelineManager.action"}</th>
						</tr>
					</thead>
					<tbody id="etmFilesTbody_{$submissionId}">
						{if $timeline.files && count($timeline.files) > 0}
							{foreach from=$timeline.files item=file}
								<tr class="etm-file-row etm-file-stage-{$file.file_stage} stage-{$file.file_stage}" data-file-id="{$file.submission_file_id}" data-stage="{$file.file_stage}">
									<td>
										<strong class="etm-file-id">#{$file.submission_file_id}</strong>
									</td>
									<td>
										<div class="etm-filename-box">
											<span class="etm-filename-title" style="font-weight: 600;">{$file.filename|escape}</span>
											<span class="etm-genre-tag" style="display: block; font-size: 11px; color: #64748b; margin-top: 2px;">
												<i class="fa fa-tag"></i> {$file.genre_name|escape}
											</span>
											{if $file.reviewer_info}
												<span class="etm-reviewer-tag" style="display: inline-block; font-size: 11px; color: #0284c7; margin-top: 2px;">
													<i class="fa fa-user"></i> {$file.reviewer_info.reviewer_name|escape}
												</span>
											{/if}
										</div>
									</td>
									<td>
										<span class="etm-stage-badge etm-badge-stage-{$file.file_stage}">
											{$file.file_stage_name|escape}
										</span>
									</td>
									<td>
										<span class="etm-uploader-name"><i class="fa fa-user-o"></i> {$file.uploader_username|escape}</span>
									</td>
									<td>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-file-created"
											name="files[{$file.submission_file_id}][created_at]"
											value="{EditorialTimelineHelper::toHtmlDatetime($file.created_at)}"
										/>
									</td>
									<td>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-file-updated"
											name="files[{$file.submission_file_id}][updated_at]"
											value="{EditorialTimelineHelper::toHtmlDatetime($file.updated_at)}"
										/>
									</td>
									<td style="text-align: center;">
										<div class="etm-row-btn-group">
											<button
												type="button"
												class="pkp_button etm-direct-file-save-btn"
												title="{translate key="plugins.generic.editorialTimelineManager.save"}"
												data-file-id="{$file.submission_file_id}"
											>
												<i class="fa fa-save"></i>
												<span>{translate key="plugins.generic.editorialTimelineManager.save"}</span>
											</button>
											<button
												type="button"
												class="etm-copy-date-btn"
												title="{translate key="plugins.generic.editorialTimelineManager.copyDateToOthers"}"
												data-created="{EditorialTimelineHelper::toHtmlDatetime($file.created_at)}"
												data-stage="{$file.file_stage}"
											>
												<i class="fa fa-clone"></i>
												<span>{translate key="plugins.generic.editorialTimelineManager.copy"}</span>
											</button>
										</div>
									</td>
								</tr>
							{/foreach}
							<tr class="etm-no-filtered-files" style="display: none;">
								<td colspan="7" class="etm-empty-card" style="text-align: center; padding: 25px; color: #64748b;">
									<i class="fa fa-filter"></i> {translate key="plugins.generic.editorialTimelineManager.noFilesFound"}
								</td>
							</tr>
						{else}
							<tr>
								<td colspan="7" class="etm-empty-card">
									{translate key="plugins.generic.editorialTimelineManager.noFilesFound"}
								</td>
							</tr>
						{/if}
					</tbody>
				</table>
			</div>
		</section>

		<!-- ============================================================== -->
		<!-- TAB 3: PEER REVIEW TIMELINE                                    -->
		<!-- ============================================================== -->
		<section class="etm-tab-panel" id="tabReviews_{$submissionId}">
			{if $timeline.reviews && count($timeline.reviews) > 0}
				<div class="etm-reviews-grid">
					{foreach from=$timeline.reviews item=rev}
						<div class="etm-card etm-review-card" id="etmReviewCard_{$rev.review_id}" data-review-id="{$rev.review_id}">
							<div class="etm-card-header etm-review-header">
								<div class="etm-reviewer-title">
									<h4><i class="fa fa-user"></i> {$rev.reviewer_name|escape}</h4>
									<span class="etm-badge etm-badge-secondary">{translate key="plugins.generic.editorialTimelineManager.round"} {$rev.round|default:1}</span>
									{if $rev.recommendation_name}
										<span class="etm-badge etm-badge-info">{$rev.recommendation_name|escape}</span>
									{/if}
								</div>

								<div class="etm-review-actions">
									<button
										type="button"
										class="pkp_button etm-auto-align-btn"
										data-review-id="{$rev.review_id}"
										title="{translate key="plugins.generic.editorialTimelineManager.autoAlignChronologyDesc"}"
									>
										<i class="fa fa-magic"></i> <span>{translate key="plugins.generic.editorialTimelineManager.autoAlignChronology"}</span>
									</button>
									<button
										type="button"
										class="pkp_button pkp_button_primary etm-direct-review-btn"
										data-review-id="{$rev.review_id}"
									>
										<i class="fa fa-bolt"></i> <span>{translate key="plugins.generic.editorialTimelineManager.saveReviewToDbDirectly"}</span>
									</button>
								</div>
							</div>

							<!-- Chronology Warnings Banner -->
							<div class="etm-chronology-warning" id="etmChronoWarning_{$rev.review_id}" {if !$rev.chronology_warnings || count($rev.chronology_warnings) == 0}style="display:none;"{/if}>
								<span class="etm-warning-icon"><i class="fa fa-exclamation-triangle"></i></span>
								<div class="etm-warning-list">
									{if $rev.chronology_warnings}
										{foreach from=$rev.chronology_warnings item=w}
											<div>{$w}</div>
										{/foreach}
									{/if}
								</div>
							</div>

							<div class="etm-card-body etm-review-fields">
								<div class="etm-grid-2col">
									<div class="etm-form-field">
										<label>{translate key="plugins.generic.editorialTimelineManager.review.assigned"}</label>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-rev-date"
											data-field="date_assigned"
											name="reviews[{$rev.review_id}][date_assigned]"
											value="{EditorialTimelineHelper::toHtmlDatetime($rev.date_assigned)}"
										/>
									</div>
									<div class="etm-form-field">
										<label>{translate key="plugins.generic.editorialTimelineManager.review.notified"}</label>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-rev-date"
											data-field="date_notified"
											name="reviews[{$rev.review_id}][date_notified]"
											value="{EditorialTimelineHelper::toHtmlDatetime($rev.date_notified)}"
										/>
									</div>
									<div class="etm-form-field">
										<label>{translate key="plugins.generic.editorialTimelineManager.review.confirmed"}</label>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-rev-date"
											data-field="date_confirmed"
											name="reviews[{$rev.review_id}][date_confirmed]"
											value="{EditorialTimelineHelper::toHtmlDatetime($rev.date_confirmed)}"
										/>
									</div>
									<div class="etm-form-field">
										<label>{translate key="plugins.generic.editorialTimelineManager.review.due"}</label>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-rev-date"
											data-field="date_due"
											name="reviews[{$rev.review_id}][date_due]"
											value="{EditorialTimelineHelper::toHtmlDatetime($rev.date_due)}"
										/>
									</div>
									<div class="etm-form-field etm-field-highlight">
										<label>
											<strong>{translate key="plugins.generic.editorialTimelineManager.review.completed"}</strong>
											(<code>review_assignments.date_completed</code>)
										</label>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-rev-date etm-date-completed"
											data-field="date_completed"
											name="reviews[{$rev.review_id}][date_completed]"
											value="{EditorialTimelineHelper::toHtmlDatetime($rev.date_completed)}"
										/>
									</div>
									<div class="etm-form-field">
										<label>{translate key="plugins.generic.editorialTimelineManager.review.acknowledged"}</label>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-rev-date"
											data-field="date_acknowledged"
											name="reviews[{$rev.review_id}][date_acknowledged]"
											value="{EditorialTimelineHelper::toHtmlDatetime($rev.date_acknowledged)}"
										/>
									</div>
								</div>
							</div>
						</div>
					{/foreach}
				</div>
			{else}
				<div class="etm-empty-card">
					<p><i class="fa fa-info-circle"></i> {translate key="plugins.generic.editorialTimelineManager.noReviewsFound"}</p>
				</div>
			{/if}
		</section>

		<!-- ============================================================== -->
		<!-- TAB 4: EDITORIAL DECISIONS & NOTIFICATIONS                     -->
		<!-- ============================================================== -->
		<section class="etm-tab-panel" id="tabDecisions_{$submissionId}">
			{if $timeline.decisions && count($timeline.decisions) > 0}
				<div class="etm-table-responsive">
					<table class="etm-table etm-decisions-table">
						<thead>
							<tr>
								<th style="width: 70px;">{translate key="plugins.generic.editorialTimelineManager.tableId"}</th>
								<th style="width: 130px;">{translate key="plugins.generic.editorialTimelineManager.stage.stage"}</th>
								<th style="width: 90px;">{translate key="plugins.generic.editorialTimelineManager.round"}</th>
								<th>{translate key="plugins.generic.editorialTimelineManager.decision.decision"}</th>
								<th style="width: 120px;">{translate key="plugins.generic.editorialTimelineManager.editor"}</th>
								<th style="width: 210px;">{translate key="plugins.generic.editorialTimelineManager.decisionDate"}</th>
								<th style="width: 240px;">{translate key="plugins.generic.editorialTimelineManager.decisionNotificationEmail"} (<code>email_log</code>)</th>
								<th style="width: 80px; text-align: center;">{translate key="plugins.generic.editorialTimelineManager.action"}</th>
							</tr>
						</thead>
						<tbody>
							{foreach from=$timeline.decisions item=dec}
								<tr id="etmDecRow_{$dec.edit_decision_id}" data-dec-id="{$dec.edit_decision_id}">
									<td><strong>#{$dec.edit_decision_id}</strong></td>
									<td><span class="etm-stage-badge">{$dec.stage_name|escape}</span></td>
									<td>
										<span class="etm-badge etm-badge-secondary">
											{translate key="plugins.generic.editorialTimelineManager.round"} {$dec.round|default:1}
										</span>
									</td>
									<td>
										<span class="etm-badge etm-badge-primary">
											<i class="fa fa-check"></i> {$dec.decision_name|escape}
										</span>
									</td>
									<td><i class="fa fa-user-o"></i> {$dec.editor_name|escape}</td>
									<td>
										<input
											type="datetime-local"
											class="etm-input etm-datetime etm-dec-date"
											id="etmDecDate_{$dec.edit_decision_id}"
											data-dec-id="{$dec.edit_decision_id}"
											name="decisions[{$dec.edit_decision_id}][date_decided]"
											value="{EditorialTimelineHelper::toHtmlDatetime($dec.date_decided)}"
										/>
									</td>
									<td>
										{if $dec.email_log}
											<div class="etm-dec-email-box">
												<input type="hidden" name="decisions[{$dec.edit_decision_id}][email_log_id]" value="{$dec.email_log.log_id}" />
												<div style="font-size: 11px; color: #64748b; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 230px;" title="{$dec.email_log.subject|escape}">
													<i class="fa fa-envelope-o"></i> <strong>#{$dec.email_log.log_id}</strong> {$dec.email_log.subject|escape}
												</div>
												<input
													type="datetime-local"
													class="etm-input etm-datetime etm-email-date"
													id="etmEmailDate_{$dec.edit_decision_id}"
													data-dec-id="{$dec.edit_decision_id}"
													data-log-id="{$dec.email_log.log_id}"
													name="decision_emails[{$dec.email_log.log_id}][date_sent]"
													value="{EditorialTimelineHelper::toHtmlDatetime($dec.email_log.date_sent)}"
												/>
												<label style="font-size: 11px; color: #475569; display: flex; align-items: center; gap: 4px; margin-top: 4px; cursor: pointer;">
													<input
														type="checkbox"
														class="etm-sync-dec-email"
														data-dec-id="{$dec.edit_decision_id}"
														data-log-id="{$dec.email_log.log_id}"
														name="decisions[{$dec.edit_decision_id}][sync_email]"
														value="1"
														checked
													/>
													{translate key="plugins.generic.editorialTimelineManager.syncDecisionNotification"}
												</label>
											</div>
										{else}
											<span class="etm-help-text" style="color: #94a3b8; font-size: 12px;">
												<i class="fa fa-info-circle"></i> {translate key="plugins.generic.editorialTimelineManager.noLinkedEmail"}
											</span>
										{/if}
									</td>
									<td style="text-align: center; vertical-align: middle;">
										<button
											type="button"
											class="pkp_button pkp_button_primary etm-direct-decision-btn"
											data-dec-id="{$dec.edit_decision_id}"
											data-email-id="{if $dec.email_log}{$dec.email_log.log_id}{else}0{/if}"
											title="{translate key="plugins.generic.editorialTimelineManager.saveDecisionToDbDirectly"}"
										>
											<i class="fa fa-bolt"></i> <span>{translate key="plugins.generic.editorialTimelineManager.save"}</span>
										</button>
									</td>
								</tr>
							{/foreach}
						</tbody>
					</table>
				</div>

				{if $timeline.decision_emails && count($timeline.decision_emails) > 0}
					<div class="etm-card" style="margin-top: 18px;">
						<div class="etm-card-header">
							<h4><i class="fa fa-envelope-open-o"></i> {translate key="plugins.generic.editorialTimelineManager.allDecisionEmails"} (<code>email_log</code>)</h4>
						</div>
						<div class="etm-card-body" style="padding: 0;">
							<table class="etm-table">
								<thead>
									<tr>
										<th style="width: 70px;">{translate key="plugins.generic.editorialTimelineManager.tableId"}</th>
										<th>{translate key="plugins.generic.editorialTimelineManager.subject"}</th>
										<th>{translate key="plugins.generic.editorialTimelineManager.recipients"}</th>
										<th style="width: 220px;">{translate key="plugins.generic.editorialTimelineManager.dateSent"}</th>
									</tr>
								</thead>
								<tbody>
									{foreach from=$timeline.decision_emails item=em}
										<tr>
											<td><strong>#{$em.log_id}</strong></td>
											<td><i class="fa fa-envelope-o"></i> {$em.subject|escape}</td>
											<td><small style="color: #64748b;">{$em.recipients|escape}</small></td>
											<td>
												<input
													type="datetime-local"
													class="etm-input etm-datetime etm-email-date"
													data-log-id="{$em.log_id}"
													name="decision_emails[{$em.log_id}][date_sent]"
													value="{EditorialTimelineHelper::toHtmlDatetime($em.date_sent)}"
												/>
											</td>
										</tr>
									{/foreach}
								</tbody>
							</table>
						</div>
					</div>
				{/if}
			{else}
				<div class="etm-empty-card">
					<p><i class="fa fa-info-circle"></i> {translate key="plugins.generic.editorialTimelineManager.noDecisionsFound"}</p>
				</div>
			{/if}
		</section>

		<!-- ============================================================== -->
		<!-- TAB 5: STAGE DISCUSSIONS (QUERIES & NOTES)                     -->
		<!-- ============================================================== -->
		<section class="etm-tab-panel" id="tabDiscussions_{$submissionId}">
			{if $timeline.discussions && count($timeline.discussions) > 0}
				<div class="etm-discussions-list">
					{foreach from=$timeline.discussions item=disc}
						<div class="etm-card etm-discussion-card">
							<div class="etm-card-header">
								<div class="etm-disc-title">
									<h4><i class="fa fa-commenting-o"></i> {$disc.title|escape}</h4>
									<span class="etm-stage-badge">{$disc.stage_name|escape}</span>
								</div>

								<div class="etm-disc-posted-box">
									<label>{translate key="plugins.generic.editorialTimelineManager.discussionPosted"}:</label>
									<input
										type="datetime-local"
										class="etm-input etm-datetime"
										name="discussions[{$disc.query_id}][date_posted]"
										value="{EditorialTimelineHelper::toHtmlDatetime($disc.date_posted)}"
									/>
								</div>
							</div>

							<div class="etm-card-body">
								{if $disc.notes && count($disc.notes) > 0}
									<div class="etm-notes-list">
										{foreach from=$disc.notes item=note}
											<div class="etm-note-item">
												<div class="etm-note-header">
													<strong><i class="fa fa-user"></i> {$note.author_name|escape}</strong>
													{if $note.title}<em>({$note.title|escape})</em>{/if}
												</div>
												<div class="etm-note-snippet">
													{$note.snippet|escape}...
												</div>
												<div class="etm-note-date-fields">
													<div class="etm-form-field">
														<label>{translate key="plugins.generic.editorialTimelineManager.dateCreated"}:</label>
														<input
															type="datetime-local"
															class="etm-input etm-datetime"
															name="discussions[{$disc.query_id}][notes][{$note.note_id}][date_created]"
															value="{EditorialTimelineHelper::toHtmlDatetime($note.date_created)}"
														/>
													</div>
												</div>
											</div>
										{/foreach}
									</div>
								{/if}
							</div>
						</div>
					{/foreach}
				</div>
			{else}
				<div class="etm-empty-card">
					<p><i class="fa fa-info-circle"></i> {translate key="plugins.generic.editorialTimelineManager.noDiscussionsFound"}</p>
				</div>
			{/if}
		</section>

		<!-- ============================================================== -->
		<!-- TAB 6: ACTIVITY LOG MANAGER                                   -->
		<!-- ============================================================== -->
		<section class="etm-tab-panel" id="tabActivityLog_{$submissionId}">

			<div class="etm-card">
				<div class="etm-card-header">
					<div>
						<h3><i class="fa fa-history"></i> {translate key="plugins.generic.editorialTimelineManager.activityLog.title"}</h3>
						<small class="etm-help-text">{translate key="plugins.generic.editorialTimelineManager.activityLog.desc"}</small>
					</div>
					<div style="display: flex; gap: 8px; align-items: center;">
						<button type="button" class="pkp_button pkp_button_primary etm-btn-add-log" id="etmToggleAddLogBtn_{$submissionId}">
							<i class="fa fa-plus"></i> <span>{translate key="plugins.generic.editorialTimelineManager.activityLog.addEntry"}</span>
						</button>
						<button type="button" class="pkp_button etm-btn-clean-logs" id="etmCleanPluginLogsBtn_{$submissionId}" title="{translate key="plugins.generic.editorialTimelineManager.activityLog.cleanPluginLogs"}">
							<i class="fa fa-eraser"></i> <span>{translate key="plugins.generic.editorialTimelineManager.activityLog.cleanPluginLogs"}</span>
						</button>
					</div>
				</div>

				<div class="etm-card-body">
					<!-- Filter Controls -->
					<div class="etm-filter-bar" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-bottom: 14px;">
						<div style="flex: 1 1 280px; max-width: 450px;">
							<input
								type="search"
								id="etmLogSearchInput_{$submissionId}"
								class="etm-input"
								placeholder="{translate key="plugins.generic.editorialTimelineManager.activityLog.searchPlaceholder"}"
								style="width: 100%;"
							/>
						</div>
						<div class="etm-filter-chips" id="etmLogFilterChips_{$submissionId}" style="display: flex; gap: 6px; flex-wrap: wrap;">
							<button type="button" class="etm-filter-chip etm-log-filter-chip active" data-filter="all">
								{translate key="plugins.generic.editorialTimelineManager.activityLog.allLogs"}
							</button>
							<button type="button" class="etm-filter-chip etm-log-filter-chip" data-filter="submission">
								{translate key="plugins.generic.editorialTimelineManager.tab.overview"}
							</button>
							<button type="button" class="etm-filter-chip etm-log-filter-chip" data-filter="files">
								{translate key="plugins.generic.editorialTimelineManager.tab.files"}
							</button>
							<button type="button" class="etm-filter-chip etm-log-filter-chip" data-filter="plugin">
								{translate key="plugins.generic.editorialTimelineManager.activityLog.pluginLogs"}
							</button>
							<button type="button" class="etm-filter-chip etm-log-filter-chip" data-filter="system">
								{translate key="plugins.generic.editorialTimelineManager.activityLog.systemLogs"}
							</button>
						</div>
					</div>

					<!-- Simple Add Log Entry Form -->
					<div class="etm-new-log-form" id="etmNewLogForm_{$submissionId}" style="display: none; background: #f8fafc; border: 1px solid var(--etm-border); border-radius: 6px; padding: 14px; margin-bottom: 16px;">
						<h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; color: var(--etm-primary);">
							<i class="fa fa-plus-circle"></i> {translate key="plugins.generic.editorialTimelineManager.activityLog.addEntry"}
						</h4>
						<div class="etm-grid-2col" style="gap: 12px;">
							<div class="etm-form-field">
								<label for="etmNewLogDate_{$submissionId}">{translate key="plugins.generic.editorialTimelineManager.activityLog.dateLogged"}</label>
								<input type="datetime-local" id="etmNewLogDate_{$submissionId}" class="etm-input etm-datetime" />
							</div>
							<div class="etm-form-field">
								<label for="etmNewLogUser_{$submissionId}">{translate key="plugins.generic.editorialTimelineManager.activityLog.user"}</label>
								<select id="etmNewLogUser_{$submissionId}" class="etm-select">
									{foreach from=$timeline.journal_users item=ju}
										<option value="{$ju.id}">{$ju.username|escape} ({$ju.full_name|escape})</option>
									{/foreach}
								</select>
							</div>
						</div>
						<div class="etm-form-field" style="margin-top: 10px;">
							<label for="etmNewLogMessage_{$submissionId}">{translate key="plugins.generic.editorialTimelineManager.activityLog.event"}</label>
							<textarea id="etmNewLogMessage_{$submissionId}" class="etm-input" rows="2" placeholder="{translate key="plugins.generic.editorialTimelineManager.activityLog.event"}..."></textarea>
						</div>
						<div style="margin-top: 12px; display: flex; gap: 8px;">
							<button type="button" class="pkp_button pkp_button_primary etm-btn-create-log" id="etmCreateLogBtn_{$submissionId}">
								<i class="fa fa-check"></i> <span>{translate key="plugins.generic.editorialTimelineManager.activityLog.saveEntry"}</span>
							</button>
							<button type="button" class="pkp_button etm-btn-cancel-log" id="etmCancelAddLogBtn_{$submissionId}">
								<span>{translate key="plugins.generic.editorialTimelineManager.cancel"}</span>
							</button>
						</div>
					</div>

					<!-- Activity Logs Table -->
					<div class="etm-table-responsive" id="etmLogTableWrapper_{$submissionId}">
						<table class="etm-table etm-log-table" id="etmLogTable_{$submissionId}">
							<thead>
								<tr>
									<th style="width: 70px;">{translate key="plugins.generic.editorialTimelineManager.tableId"}</th>
									<th style="width: 200px;">{translate key="plugins.generic.editorialTimelineManager.activityLog.dateLogged"}</th>
									<th style="width: 220px;">{translate key="plugins.generic.editorialTimelineManager.activityLog.user"}</th>
									<th>{translate key="plugins.generic.editorialTimelineManager.activityLog.event"}</th>
									<th style="width: 140px; text-align: center;">{translate key="plugins.generic.editorialTimelineManager.activityLog.actions"}</th>
								</tr>
							</thead>
							<tbody id="etmLogTbody_{$submissionId}">
								{foreach from=$timeline.activity_logs item=log}
									<tr class="etm-log-row {if $log.is_plugin_log}etm-log-is-plugin{/if} {if $log.is_file_log}etm-log-is-file etm-file-stage-{$log.file_stage}{else}etm-log-is-submission{/if}" id="etmLogRow_{$log.log_id}" data-log-id="{$log.log_id}" data-is-file="{if $log.is_file_log}1{else}0{/if}" data-assoc-type="{$log.assoc_type}" data-assoc-id="{$log.assoc_id}">
										<td>
											<strong class="etm-log-id">#{$log.log_id}</strong>
											{if $log.is_plugin_log}
												<span class="etm-badge etm-badge-warning" style="margin-top: 4px; display: inline-block;">
													{translate key="plugins.generic.editorialTimelineManager.activityLog.pluginLogBadge"}
												</span>
											{/if}
											{if $log.is_file_log}
												<span class="etm-stage-badge etm-badge-stage-{$log.file_stage}" style="margin-top: 4px; display: inline-block; font-size: 10px;">
													{$log.file_stage_name|escape}
												</span>
											{/if}
										</td>
										<td>
											<input
												type="datetime-local"
												class="etm-input etm-datetime etm-log-date"
												name="activity_logs[{$log.log_id}][date_logged]"
												value="{$log.date_logged_formatted}"
											/>
										</td>
										<td>
											<select class="etm-select etm-log-user-select" name="activity_logs[{$log.log_id}][user_id]">
												{assign var="userFound" value=false}
												{foreach from=$timeline.journal_users item=ju}
													<option value="{$ju.id}" {if $log.user_id == $ju.id}{assign var="userFound" value=true}selected{/if}>
														{$ju.username|escape} ({$ju.full_name|escape})
													</option>
												{/foreach}
												{if !$userFound && $log.user_id > 0}
													<option value="{$log.user_id}" selected>
														{$log.username|escape} ({$log.user_full_name|escape})
													</option>
												{/if}
											</select>
										</td>
										<td>
											{if $log.is_file_log && $log.file_meta}
												<div class="etm-file-log-tag" style="margin-bottom: 4px; font-size: 11px; color: #0284c7; font-weight: 600;">
													<i class="fa fa-file-text-o"></i> {$log.file_meta.filename|escape} (#{$log.assoc_id})
												</div>
											{/if}
											<textarea
												class="etm-input etm-log-message"
												name="activity_logs[{$log.log_id}][message]"
												data-orig-message="{$log.display_message|escape}"
												rows="2"
											>{$log.display_message|escape}</textarea>
										</td>
										<td style="text-align: center; vertical-align: middle;">
											<div class="etm-row-btn-group">
												<button
													type="button"
													class="pkp_button etm-direct-log-btn"
													title="{translate key="plugins.generic.editorialTimelineManager.save"}"
													data-log-id="{$log.log_id}"
												>
													<i class="fa fa-save"></i> <span>{translate key="plugins.generic.editorialTimelineManager.save"}</span>
												</button>
												<button
													type="button"
													class="pkp_button etm-btn-danger etm-delete-log-btn"
													title="{translate key="plugins.generic.editorialTimelineManager.delete"}"
													data-log-id="{$log.log_id}"
												>
													<i class="fa fa-trash-o"></i> <span>{translate key="plugins.generic.editorialTimelineManager.delete"}</span>
												</button>
											</div>
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
						<div class="etm-empty-card" id="etmNoLogsCard_{$submissionId}" style="{if $timeline.activity_logs && count($timeline.activity_logs) > 0}display: none;{/if}">
							<p><i class="fa fa-info-circle"></i> {translate key="plugins.generic.editorialTimelineManager.activityLog.noLogsFound"}</p>
						</div>
					</div>
				</div>
			</div>
		</section>

	</form>

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
		save: '{translate key="plugins.generic.editorialTimelineManager.save"}',
		delete: '{translate key="plugins.generic.editorialTimelineManager.delete"}',
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
		reviewUpdatedSuccess: '{translate key="plugins.generic.editorialTimelineManager.success.reviewerUpdated"}',
		decisionUpdatedSuccess: '{translate key="plugins.generic.editorialTimelineManager.success.decisionDirectlyUpdated"}',
		updateDecisionError: '{translate key="plugins.generic.editorialTimelineManager.error.updateDecision"}'
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

	var subId = {$submissionId};
	var pickerUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="manage" submissionId=0}';
	var pluginUrl = '{$pluginUrl}';

	window.EditorialTimelineUsers = window.EditorialTimelineUsers || {};
	window.EditorialTimelineUsers[subId] = {$timeline.journal_users|@json_encode};

	var updateLogUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="directUpdateActivityLog" submissionId=$submissionId}';
	var deleteLogUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="deleteActivityLog" submissionId=$submissionId}';
	var createLogUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="createActivityLog" submissionId=$submissionId}';
	var cleanLogsUrl = '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="cleanPluginActivityLogs" submissionId=$submissionId}';

	function startEditor() {ldelim}
		if (window.EditorialTimelineManager && window.EditorialTimelineManager.initEditor) {ldelim}
			EditorialTimelineManager.initEditor({ldelim}
				submissionId: subId,
				saveUrl: '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="saveTimeline" submissionId=$submissionId}',
				directUpdateFilesUrl: '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="directUpdateFiles" submissionId=$submissionId}',
				directUpdateReviewUrl: '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="directUpdateReview" submissionId=$submissionId}',
				directUpdateDecisionUrl: '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="directUpdateDecision" submissionId=$submissionId}',
				directUpdateActivityLogUrl: updateLogUrl,
				deleteActivityLogUrl: deleteLogUrl,
				createActivityLogUrl: createLogUrl,
				cleanPluginActivityLogsUrl: cleanLogsUrl,
				shiftUrl: '{url router=$smarty.const.ROUTE_PAGE page="editorialTimeline" op="shiftTimeline" submissionId=$submissionId}'
			{rdelim});
		{rdelim}
	{rdelim}

	if (window.EditorialTimelineManager && window.EditorialTimelineManager.initEditor) {ldelim}
		startEditor();
	{rdelim} else {ldelim}
		$.getScript(pluginUrl + '/js/editorialTimeline.js?v=' + (new Date().getTime()), function() {ldelim}
			startEditor();
		{rdelim});
	{rdelim}

	// Back to Picker click handler
	$('#etmBackToPickerBtn_' + subId).off('click').on('click', function(e) {ldelim}
		e.preventDefault();
		var $app = $('#editorialTimelineApp_' + subId);
		var $container = $app.closest('.pkp_modal_panel, .ui-dialog-content');
		var $target = $container.length ? $container : $app.parent();

		$target.html(
			'<div style="text-align: center; padding: 60px 20px;">' +
				'<div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #e2e8f0; border-top-color: #006798; border-radius: 50%; animation: etmSpin 0.8s linear infinite;"></div>' +
				'<p style="margin-top: 15px; color: #64748b; font-size: 13px;">' + t('loadingList', 'Loading submissions list...') + '</p>' +
			'</div>'
		);

		$.ajax({ldelim}
			url: pickerUrl,
			type: 'GET',
			dataType: 'json',
			success: function(resp) {ldelim}
				if (resp && resp.status && resp.content) {ldelim}
					$target.html(resp.content);
					var $dialog = $target.closest('.ui-dialog-content');
					if ($dialog.length && $dialog.dialog) {ldelim}
						try {ldelim}
							$dialog.dialog('option', 'title', '{translate key="plugins.generic.editorialTimelineManager.displayName"}');
						{rdelim} catch(e) {ldelim}{rdelim}
					{rdelim}
				{rdelim}
			{rdelim}
		{rdelim});
	{rdelim});
{rdelim})(jQuery);
</script>
