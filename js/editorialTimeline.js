/**
 * plugins/generic/editorialTimelineManager/js/editorialTimeline.js
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Client-side controller for Editorial Timeline Manager in OJS 3.3.
 */

(function($) {
	'use strict';

	window.EditorialTimelineManager = window.EditorialTimelineManager || {};

	/**
	 * Translation helper from window.EditorialTimelineLocales
	 */
	window.EditorialTimelineManager.t = function(key, fallback, replacements) {
		let str = (window.EditorialTimelineLocales && window.EditorialTimelineLocales[key]) ? window.EditorialTimelineLocales[key] : fallback;
		if (replacements && typeof replacements === 'object') {
			for (let k in replacements) {
				str = str.replace(new RegExp('\\{\\$?' + k + '\\}', 'g'), replacements[k]);
			}
		}
		return str;
	};
	const t = window.EditorialTimelineManager.t;

	/**
	 * Helper: Format Date object to YYYY-MM-DDTHH:MM
	 */
	function formatToHtmlDatetime(d) {
		const pad = n => (n < 10 ? '0' + n : n);
		return (
			d.getFullYear() +
			'-' +
			pad(d.getMonth() + 1) +
			'-' +
			pad(d.getDate()) +
			'T' +
			pad(d.getHours()) +
			':' +
			pad(d.getMinutes())
		);
	}

	/**
	 * Initialize the Timeline Editor for a specific submission
	 */
	window.EditorialTimelineManager.initEditor = function(config) {
		const subId = config.submissionId;
		const $app = $('#editorialTimelineApp_' + subId);
		const $form = $('#etmForm_' + subId);
		const $alert = $('#etmAlert_' + subId);

		if (!$app.length) return;
		if ($app.data('etm-initialized')) return;
		$app.data('etm-initialized', true);

		function getCsrfToken() {
			return $form.find('input[name="csrfToken"]').val() || getGlobalCsrfToken();
		}

		// 1. Navigation Tab Switching
		$app.find('.etm-tab-link').on('click', function(e) {
			e.preventDefault();
			const targetPanelId = $(this).data('tab');

			$app.find('.etm-tab-link').removeClass('active');
			$(this).addClass('active');

			$app.find('.etm-tab-panel').removeClass('active');
			$('#' + targetPanelId).addClass('active');

		});

		// 2. Alert helper
		function showAlert(message, type) {
			$alert.removeClass('success error').addClass(type);
			$alert.find('.etm-alert-message').text(message);
			$alert.find('.etm-alert-icon').html(type === 'success' ? '<i class="fa fa-check-circle"></i>' : '<i class="fa fa-exclamation-circle"></i>');
			$alert.slideDown(200);

			if (type === 'success') {
				setTimeout(function() {
					$alert.slideUp(300);
				}, 6000);
			}
		}

		$alert.find('.etm-alert-close').on('click', function() {
			$alert.slideUp(200);
		});

		// 3. Files Filter Chips
		$app.on('click', '#tabFiles_' + subId + ' .etm-filter-chip', function(e) {
			e.preventDefault();
			const $chip = $(this);
			$chip.siblings('.etm-filter-chip').removeClass('active');
			$chip.addClass('active');

			const filter = String($chip.data('filter') || 'all');
			const $tbody = $('#etmFilesTbody_' + subId);
			const $rows = $tbody.find('.etm-file-row');
			const $noFilesRow = $tbody.find('.etm-no-filtered-files');

			let visibleCount = 0;
			if (filter === 'all') {
				$rows.show();
				visibleCount = $rows.length;
			} else {
				const stageId = filter.replace(/^stage-/, '');
				$rows.each(function() {
					const $row = $(this);
					const rowStage = String($row.data('stage'));
					if (rowStage === stageId || $row.hasClass('etm-file-stage-' + stageId) || $row.hasClass('stage-' + stageId) || $row.hasClass(filter)) {
						$row.show();
						visibleCount++;
					} else {
						$row.hide();
					}
				});
			}

			if (visibleCount === 0) {
				$noFilesRow.show();
			} else {
				$noFilesRow.hide();
			}
		});

		// 4. Bulk Stage Setter - "Now" Button
		$('#etmSetBulkNow_' + subId).on('click', function() {
			$('#etmBulkDateTime_' + subId).val(formatToHtmlDatetime(new Date()));
		});

		// 4A. Apply to Form Inputs Only
		$('#etmApplyBulkFilesBtn_' + subId).on('click', function() {
			const targetStage = $('#etmBulkStageSelect_' + subId).val();
			const dt = $('#etmBulkDateTime_' + subId).val();
			const syncUpdated = $('#etmBulkSyncUpdated_' + subId).is(':checked');

			if (!dt) {
				alert(t('selectDatePrompt', 'Please select a target date and time.'));
				return;
			}

			let count = 0;
			$app.find('.etm-file-row').each(function() {
				const rowStage = String($(this).data('stage'));
				if (targetStage === 'ALL' || targetStage === rowStage) {
					$(this).find('.etm-file-created').val(dt);
					if (syncUpdated) {
						$(this).find('.etm-file-updated').val(dt);
					}
					count++;
				}
			});

			const saveBtnText = t('saveToDbDirectly', 'Save Directly to OJS Database');
			showAlert(t('appliedFilesToForm', 'Applied date to {$count} file(s) in form inputs. Click "{$saveBtn}" to persist.', { count: count, saveBtn: saveBtnText }), 'success');
		});

		// 4B. DIRECT ONE-CLICK DATABASE UPDATE FOR FILES
		$('#etmDirectUpdateFilesBtn_' + subId).on('click', function() {
			const targetStage = $('#etmBulkStageSelect_' + subId).val();
			const dt = $('#etmBulkDateTime_' + subId).val();
			const syncUpdated = $('#etmBulkSyncUpdated_' + subId).is(':checked');

			if (!dt) {
				alert(t('selectDatePrompt', 'Please select a target date and time.'));
				return;
			}

			const stageLabel = $('#etmBulkStageSelect_' + subId + ' option:selected').text();
			if (!confirm(t('confirmUpdateFiles', 'Update database NOW for [{$stage}] to {$date}?', { stage: stageLabel, date: dt }))) {
				return;
			}

			const $btn = $(this).prop('disabled', true);

			$.ajax({
				url: config.directUpdateFilesUrl || getApiUrl('directUpdateFiles', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					targetStage: targetStage,
					targetDate: dt,
					syncUpdated: syncUpdated ? 1 : 0
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						// Update table rows immediately with fresh DB values
						$app.find('.etm-file-row').each(function() {
							const rowStage = String($(this).data('stage'));
							if (targetStage === 'ALL' || targetStage === rowStage) {
								$(this).find('.etm-file-created').val(dt);
								if (syncUpdated) {
									$(this).find('.etm-file-updated').val(dt);
								}
							}
						});
						showAlert(resp.content.message || (resp.content.affected + ' file(s) updated directly in database!'), 'success');
					} else {
						showAlert(resp.content || t('updateFilesError', 'Database update failed while updating files.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// 4C. DIRECT ONE-CLICK DATABASE UPDATE FOR SINGLE FILE
		$app.on('click', '.etm-direct-file-save-btn', function() {
			const fileId = $(this).data('file-id');
			const $row = $(this).closest('tr');
			const createdAt = $row.find('.etm-file-created').val();
			const updatedAt = $row.find('.etm-file-updated').val();

			if (!createdAt) {
				alert(t('selectDatePrompt', 'Please select a target date and time.'));
				return;
			}

			const $btn = $(this).prop('disabled', true);
			$.ajax({
				url: config.updateFileDateUrl || (window.EditorialTimelineConfig && window.EditorialTimelineConfig.updateFileDateUrl) || getApiUrl('directUpdateFileDate', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					submissionFileId: fileId,
					created_at: createdAt,
					updated_at: updatedAt || createdAt
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						$row.addClass('etm-flash-success');
						setTimeout(function() { $row.removeClass('etm-flash-success'); }, 1400);
						updateParentWorkflowFileRow(fileId, createdAt, (resp.content && resp.content.display_date));
						showAlert((resp.content && resp.content.message) || t('fileUpdatedSuccess', 'File timestamp and history updated successfully!'), 'success');
					} else {
						showAlert((resp && resp.content) || t('updateFilesError', 'Failed to update file timestamp.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// 5. Copy file date button
		$app.on('click', '.etm-copy-date-btn', function() {
			const dt = $(this).data('created');
			const stage = $(this).data('stage');

			if (!dt) return;

			if (confirm(t('confirmCopyDate', 'Copy this timestamp ({$date}) to all files in this stage?', { date: dt }))) {
				$app.find('.etm-file-row.etm-file-stage-' + stage).find('.etm-file-created').val(dt);
				showAlert(t('dateCopiedSuccess', 'Timestamp replicated to stage files in form.'), 'success');
			}
		});

		// 6. Review Auto-Align Chronology
		$app.on('click', '.etm-auto-align-btn', function() {
			const revId = $(this).data('review-id');
			const $card = $('#etmReviewCard_' + revId);

			let baseDateStr = $card.find('input[data-field="date_assigned"]').val();
			if (!baseDateStr) {
				baseDateStr = $('#etmDateSubmitted_' + subId).val();
			}
			if (!baseDateStr) {
				baseDateStr = formatToHtmlDatetime(new Date());
			}

			const base = new Date(baseDateStr.replace(/-/g, '/'));
			if (isNaN(base.getTime())) return;

			const addDays = (d, days) => new Date(d.getTime() + days * 24 * 60 * 60 * 1000);

			const dAssigned = base;
			const dNotified = addDays(dAssigned, 1);
			const dConfirmed = addDays(dNotified, 3);
			const dDue = addDays(dConfirmed, 21);
			const dCompleted = addDays(dConfirmed, 14);
			const dAcknowledged = addDays(dCompleted, 2);

			$card.find('input[data-field="date_assigned"]').val(formatToHtmlDatetime(dAssigned));
			$card.find('input[data-field="date_notified"]').val(formatToHtmlDatetime(dNotified));
			$card.find('input[data-field="date_confirmed"]').val(formatToHtmlDatetime(dConfirmed));
			$card.find('input[data-field="date_due"]').val(formatToHtmlDatetime(dDue));
			$card.find('input[data-field="date_completed"]').val(formatToHtmlDatetime(dCompleted));
			$card.find('input[data-field="date_acknowledged"]').val(formatToHtmlDatetime(dAcknowledged));

			$card.find('#etmChronoWarning_' + revId).slideUp(200);
			showAlert(t('reviewAlignedSuccess', 'Aligned review assignment chronology in form.'), 'success');
		});

		// 7. Direct Update Review Assignment (1-Click)
		$app.on('click', '.etm-direct-review-btn', function() {
			const revId = $(this).data('review-id');
			const $card = $('#etmReviewCard_' + revId);
			const $btn = $(this);

			const dates = {};
			$card.find('.etm-rev-date').each(function() {
				dates[$(this).data('field')] = $(this).val();
			});

			$btn.prop('disabled', true);

			$.ajax({
				url: config.directUpdateReviewUrl || getApiUrl('directUpdateReview', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					reviewId: revId,
					dates: dates
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						$card.find('#etmChronoWarning_' + revId).slideUp(200);
						showAlert(resp.content.message || t('reviewUpdatedSuccess', 'Reviewer schedule directly updated in database!'), 'success');
					} else {
						showAlert(resp.content || t('updateReviewError', 'Database update failed while updating reviewer.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// 7B. Direct Update Editorial Decision & Author Notification Email (1-Click)
		$app.on('input change', '.etm-dec-date', function() {
			const decId = $(this).data('dec-id');
			const $row = $('#etmDecRow_' + decId);
			const $sync = $row.find('.etm-sync-dec-email');
			if ($sync.length && $sync.is(':checked')) {
				$row.find('.etm-email-date').val($(this).val());
			}
		});

		$app.on('click', '.etm-direct-decision-btn', function() {
			const decId = $(this).data('dec-id');
			const emailLogId = $(this).data('email-id');
			const $row = $('#etmDecRow_' + decId);
			const $btn = $(this);

			const dateDecided = $row.find('.etm-dec-date').val();
			const dateSent = $row.find('.etm-email-date').val();

			if (!dateDecided) {
				alert(t('selectDatePrompt', 'Please select a date and time.'));
				return;
			}

			$btn.prop('disabled', true);

			$.ajax({
				url: config.directUpdateDecisionUrl || getApiUrl('directUpdateDecision', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					decisionId: decId,
					dateDecided: dateDecided,
					emailLogId: emailLogId,
					dateSent: dateSent
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						showAlert((resp.content && resp.content.message) || t('decisionUpdatedSuccess', 'Editorial decision and notification email directly updated in database!'), 'success');
					} else {
						showAlert((resp && resp.content) || t('updateDecisionError', 'Database update failed while updating decision.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// 8. Quick Batch Timeline Offset (Stepper and Form Application)
		$('.etm-btn-step').on('click', function() {
			const step = parseInt($(this).data('step'), 10);
			const $input = $('#etmQuickShiftDays_' + subId);
			const current = parseInt($input.val() || '0', 10);
			$input.val(current + step);
		});

		$('#etmApplyQuickShiftBtn_' + subId).on('click', function() {
			const daysOffset = parseInt($('#etmQuickShiftDays_' + subId).val(), 10);
			if (isNaN(daysOffset) || daysOffset === 0) {
				alert(t('specifyOffsetPrompt', 'Please specify a positive or negative day offset to shift.'));
				return;
			}

			const options = {
				files: $('#etmShiftOptFiles_' + subId).is(':checked'),
				reviews: $('#etmShiftOptReviews_' + subId).is(':checked'),
				decisions: $('#etmShiftOptDecisions_' + subId).is(':checked'),
				discussions: $('#etmShiftOptDiscussions_' + subId).is(':checked'),
				submission: $('#etmShiftOptSubmission_' + subId).is(':checked'),
				publications: $('#etmShiftOptPublications_' + subId).is(':checked')
			};

			const $btn = $(this).prop('disabled', true);

			$.ajax({
				url: config.shiftUrl || getApiUrl('shiftTimeline', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					daysOffset: daysOffset,
					options: options
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status && resp.content) {
						updateFormFromTimelineData(resp.content);
						const saveBtnText = t('saveToDbDirectly', 'Save Directly to OJS Database');
						showAlert(t('shiftedToForm', 'Timeline shifted by {$days} days in form. Click "{$saveBtn}" to commit.', { days: daysOffset, saveBtn: saveBtnText }), 'success');
					} else {
						showAlert(resp.content || t('shiftError', 'Failed to calculate shifted timeline.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		function updateFormFromTimelineData(data) {
			const pad = n => (n < 10 ? '0' + n : n);
			const toDt = str => {
				if (!str) return '';
				const d = new Date(str.replace(/-/g, '/'));
				if (isNaN(d.getTime())) return '';
				return (
					d.getFullYear() +
					'-' +
					pad(d.getMonth() + 1) +
					'-' +
					pad(d.getDate()) +
					'T' +
					pad(d.getHours()) +
					':' +
					pad(d.getMinutes())
				);
			};

			if (data.submission && data.submission.date_submitted) {
				$('#etmDateSubmitted_' + subId).val(toDt(data.submission.date_submitted));
			}

			if (data.publications) {
				data.publications.forEach(function(pub) {
					$form.find('input[name="publications[' + pub.publication_id + '][date_published]"]').val(pub.date_published || '');
				});
			}

			if (data.files) {
				data.files.forEach(function(f) {
					$form.find('input[name="files[' + f.submission_file_id + '][created_at]"]').val(toDt(f.created_at));
					$form.find('input[name="files[' + f.submission_file_id + '][updated_at]"]').val(toDt(f.updated_at));
				});
			}

			if (data.reviews) {
				data.reviews.forEach(function(r) {
					['date_assigned', 'date_notified', 'date_confirmed', 'date_due', 'date_completed', 'date_acknowledged'].forEach(function(f) {
						$form.find('input[name="reviews[' + r.review_id + '][' + f + ']"]').val(toDt(r[f]));
					});
				});
			}

			if (data.decisions) {
				data.decisions.forEach(function(dec) {
					$form.find('input[name="decisions[' + dec.edit_decision_id + '][date_decided]"]').val(toDt(dec.date_decided));
					if (dec.email_log && dec.email_log.date_sent) {
						$form.find('input[name="decision_emails[' + dec.email_log.log_id + '][date_sent]"]').val(toDt(dec.email_log.date_sent));
					}
				});
			}

			if (data.decision_emails) {
				data.decision_emails.forEach(function(em) {
					$form.find('input[name="decision_emails[' + em.log_id + '][date_sent]"]').val(toDt(em.date_sent));
				});
			}

			if (data.discussions) {
				data.discussions.forEach(function(disc) {
					$form.find('input[name="discussions[' + disc.query_id + '][date_posted]"]').val(toDt(disc.date_posted));
					if (disc.notes) {
						disc.notes.forEach(function(n) {
							$form.find('input[name="discussions[' + disc.query_id + '][notes][' + n.note_id + '][date_created]"]').val(toDt(n.date_created));
						});
					}
				});
			}
		}

		/**
		 * Helper to serialize form to JSON structure
		 */
		function getPayloadFromForm() {
			const payload = {
				submission: {},
				publications: {},
				files: {},
				reviews: {},
				decisions: {},
				decision_emails: {},
				discussions: {}
			};

			const subDate = $('#etmDateSubmitted_' + subId).val();
			if (subDate !== undefined) {
				payload.submission.date_submitted = subDate;
			}

			$form.find('input[name^="publications["]').each(function() {
				const match = $(this).attr('name').match(/publications\[(\d+)\]\[date_published\]/);
				if (match) {
					payload.publications[match[1]] = { date_published: $(this).val() };
				}
			});

			$form.find('.etm-file-row').each(function() {
				const fId = $(this).data('file-id');
				const created = $(this).find('.etm-file-created').val();
				const updated = $(this).find('.etm-file-updated').val();
				payload.files[fId] = {
					created_at: created,
					updated_at: updated
				};
			});

			$app.find('.etm-review-card').each(function() {
				const rId = $(this).data('review-id');
				payload.reviews[rId] = {};
				$(this).find('.etm-rev-date').each(function() {
					const field = $(this).data('field');
					payload.reviews[rId][field] = $(this).val();
				});
			});

			$form.find('tr[id^="etmDecRow_"]').each(function() {
				const decId = $(this).data('dec-id');
				if (decId) {
					const dateDecided = $(this).find('.etm-dec-date').val();
					const emailLogId = $(this).find('input[name="decisions[' + decId + '][email_log_id]"]').val();
					const syncEmail = $(this).find('.etm-sync-dec-email').is(':checked');

					payload.decisions[decId] = {
						date_decided: dateDecided,
						email_log_id: emailLogId || null,
						sync_email: syncEmail ? 1 : 0
					};
				}
			});

			if (Object.keys(payload.decisions).length === 0) {
				$form.find('input[name^="decisions["][name$="[date_decided]"]').each(function() {
					const match = $(this).attr('name').match(/decisions\[(\d+)\]\[date_decided\]/);
					if (match) {
						payload.decisions[match[1]] = { date_decided: $(this).val() };
					}
				});
			}

			$form.find('input[name^="decision_emails["]').each(function() {
				const match = $(this).attr('name').match(/decision_emails\[(\d+)\]\[date_sent\]/);
				if (match) {
					payload.decision_emails[match[1]] = { date_sent: $(this).val() };
				}
			});

			$form.find('input[name^="discussions["]').each(function() {
				const matchQ = $(this).attr('name').match(/discussions\[(\d+)\]\[date_posted\]/);
				if (matchQ) {
					if (!payload.discussions[matchQ[1]]) payload.discussions[matchQ[1]] = { notes: {} };
					payload.discussions[matchQ[1]].date_posted = $(this).val();
				}

				const matchN = $(this).attr('name').match(/discussions\[(\d+)\]\[notes\]\[(\d+)\]\[date_created\]/);
				if (matchN) {
					const qId = matchN[1];
					const nId = matchN[2];
					if (!payload.discussions[qId]) payload.discussions[qId] = { notes: {} };
					if (!payload.discussions[qId].notes) payload.discussions[qId].notes = {};
					payload.discussions[qId].notes[nId] = { date_created: $(this).val() };
				}
			});

			payload.activity_logs = {};
			$form.find('.etm-log-row').each(function() {
				const logId = $(this).data('log-id');
				if (logId) {
					const dateLogged = $(this).find('.etm-log-date').val();
					const userId = $(this).find('.etm-log-user-select').val();
					const message = $(this).find('.etm-log-message').val();
					const origMessage = $(this).find('.etm-log-message').data('orig-message');
					const isCustom = (message !== origMessage);

					payload.activity_logs[logId] = {
						date_logged: dateLogged,
						user_id: userId,
						message: message,
						is_custom_message: isCustom ? 1 : 0
					};
				}
			});

			return payload;
		}

		// 9. DIRECT SAVE TO DATABASE
		$('#etmSaveBtn_' + subId).on('click', function() {
			const $btn = $(this);
			const payload = getPayloadFromForm();

			$btn.addClass('is-loading').prop('disabled', true);
			$btn.find('.etm-spinner-inline').addClass('active').show();
			$btn.find('.etm-btn-text').text(t('savingToDb', 'Saving to Database...'));

			$.ajax({
				url: config.saveUrl || (window.EditorialTimelineConfig && window.EditorialTimelineConfig.saveUrl) || getApiUrl('saveTimeline', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					timelinePayload: JSON.stringify(payload)
				},
				success: function(resp) {
					$btn.removeClass('is-loading').prop('disabled', false);
					$btn.find('.etm-spinner-inline').removeClass('active').hide();
					$btn.find('.etm-btn-text').text(t('saveToDbDirectly', 'Save Directly to OJS Database'));

					if (resp && resp.status) {
						let detail = t('savedSuccess', 'Data in OJS database updated successfully!');
						if (resp.content && resp.content.message) {
							detail = resp.content.message;
						}
						showAlert(detail, 'success');
					} else {
						showAlert(resp.content || t('serverError', 'Server error occurred while saving directly to database.'), 'error');
					}
				},
				error: function() {
					$btn.removeClass('is-loading').prop('disabled', false);
					$btn.find('.etm-spinner-inline').removeClass('active').hide();
					$btn.find('.etm-btn-text').text(t('saveToDbDirectly', 'Save Directly to OJS Database'));
					showAlert(t('serverError', 'Server error occurred while saving directly to database.'), 'error');
				}
			});
		});

		$('#etmTriggerShiftModalBtn_' + subId).on('click', function() {
			$app.find('.etm-tab-link[data-tab="tabOverview_' + subId + '"]').trigger('click');
			$('html, body').animate({
				scrollTop: $('.etm-shift-banner-card').offset().top - 80
			}, 300);
		});

		// 10. Activity Log Manager Handlers
		function filterActivityLogs() {
			const q = $.trim($('#etmLogSearchInput_' + subId).val()).toLowerCase();
			const chip = $('#etmLogFilterChips_' + subId + ' .etm-log-filter-chip.active').data('filter') || 'all';
			let count = 0;

			$app.find('#etmLogTbody_' + subId + ' .etm-log-row').each(function() {
				const $r = $(this);
				const logId = String($r.data('log-id') || $r.find('.etm-log-id').text() || '');
				const dateVal = ($r.find('.etm-log-date').val() || '').toLowerCase();
				const userVal = ($r.find('.etm-log-user-select option:selected').text() || '').toLowerCase();
				const msgVal = ($r.find('.etm-log-message').val() || '').toLowerCase();
				const isPlugin = $r.hasClass('etm-log-is-plugin');
				const isFile = $r.hasClass('etm-log-is-file') || $r.data('is-file') == '1' || $r.data('is-file') === 1;

				let matchesType = (chip === 'all');
				if (chip === 'plugin') {
					matchesType = isPlugin;
				} else if (chip === 'system') {
					matchesType = !isPlugin;
				} else if (chip === 'files') {
					matchesType = isFile;
				} else if (chip === 'submission') {
					matchesType = !isFile && !isPlugin;
				}
				let matchesSearch = true;
				if (q) {
					const combined = logId + ' ' + dateVal + ' ' + userVal + ' ' + msgVal;
					matchesSearch = combined.indexOf(q) !== -1;
				}

				const show = matchesType && matchesSearch;
				$r.toggle(show);
				if (show) count++;
			});

			if (count === 0) {
				$('#etmNoLogsCard_' + subId).show();
			} else {
				$('#etmNoLogsCard_' + subId).hide();
			}
		}

		$('#etmLogSearchInput_' + subId).off('input.etmLog keyup.etmLog change.etmLog search.etmLog').on('input.etmLog keyup.etmLog change.etmLog search.etmLog', filterActivityLogs);
		$('#etmLogFilterChips_' + subId + ' .etm-log-filter-chip').off('click.etmLog').on('click.etmLog', function(e) {
			e.preventDefault();
			$(this).addClass('active').siblings().removeClass('active');
			filterActivityLogs();
		});

		// Toggle Add Log Form
		$('#etmToggleAddLogBtn_' + subId).off('click').on('click', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			const $formBox = $('#etmNewLogForm_' + subId);
			if ($formBox.is(':animated')) return;
			if ($formBox.is(':hidden')) {
				const now = new Date();
				const pad = n => (n < 10 ? '0' + n : n);
				const nowStr = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + 'T' + pad(now.getHours()) + ':' + pad(now.getMinutes());
				$('#etmNewLogDate_' + subId).val(nowStr);
				$formBox.slideDown(200, function() {
					$('#etmNewLogMessage_' + subId).focus();
				});
			} else {
				$formBox.slideUp(200);
			}
		});

		$('#etmCancelAddLogBtn_' + subId).off('click').on('click', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			$('#etmNewLogForm_' + subId).slideUp(200);
		});

		// Create New Log Entry
		$('#etmCreateLogBtn_' + subId).off('click').on('click', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			const dateLogged = $('#etmNewLogDate_' + subId).val();
			const userId = $('#etmNewLogUser_' + subId).val();
			const message = $.trim($('#etmNewLogMessage_' + subId).val());

			if (!dateLogged || !message) {
				alert(t('fillRequiredFields', 'Please fill in both Date/Time and Message.'));
				return;
			}

			const $btn = $(this).prop('disabled', true);
			$.ajax({
				url: config.createActivityLogUrl || (window.EditorialTimelineConfig && window.EditorialTimelineConfig.createActivityLogUrl) || getApiUrl('createActivityLog', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					date_logged: dateLogged,
					user_id: userId,
					message: message
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						showAlert((resp.content && resp.content.message) ? resp.content.message : t('activityLogCreated', 'Activity log entry created successfully!'), 'success');
						$('#etmNewLogForm_' + subId).slideUp(200);
						$('#etmNewLogMessage_' + subId).val('');

						if (resp.content && resp.content.log) {
							const log = resp.content.log;
							let optionsHtml = '';
							if (window.EditorialTimelineUsers && window.EditorialTimelineUsers[subId]) {
								window.EditorialTimelineUsers[subId].forEach(function(u) {
									optionsHtml += '<option value="' + u.id + '" ' + (u.id == log.user_id ? 'selected' : '') + '>' + $('<div>').text(u.username + ' (' + u.full_name + ')').html() + '</option>';
								});
							} else {
								optionsHtml = '<option value="' + log.user_id + '" selected>' + $('<div>').text(log.username + ' (' + log.user_full_name + ')').html() + '</option>';
							}

							let rawMsg = (log.display_message || log.message || '');
							if (typeof rawMsg === 'string') {
								rawMsg = rawMsg.replace(/^##(.*)##$/, '$1');
							}
							const safeMsg = $('<div>').text(rawMsg).html();
							const trHtml = 
								'<tr class="etm-log-row" id="etmLogRow_' + log.log_id + '" data-log-id="' + log.log_id + '">' +
									'<td><strong class="etm-log-id">#' + log.log_id + '</strong></td>' +
									'<td><input type="datetime-local" class="etm-input etm-datetime etm-log-date" name="activity_logs[' + log.log_id + '][date_logged]" value="' + (log.date_logged_formatted || '') + '" /></td>' +
									'<td><select class="etm-select etm-log-user-select" name="activity_logs[' + log.log_id + '][user_id]">' + optionsHtml + '</select></td>' +
									'<td><textarea class="etm-input etm-log-message" name="activity_logs[' + log.log_id + '][message]" data-orig-message="' + safeMsg + '" rows="2">' + safeMsg + '</textarea></td>' +
									'<td style="text-align: center; vertical-align: middle;">' +
										'<div class="etm-row-btn-group">' +
											'<button type="button" class="pkp_button etm-direct-log-btn" title="' + t('save', 'Save') + '" data-log-id="' + log.log_id + '"><i class="fa fa-save"></i> ' + t('save', 'Save') + '</button>' +
											'<button type="button" class="pkp_button etm-btn-danger etm-delete-log-btn" title="' + t('delete', 'Delete') + '" data-log-id="' + log.log_id + '"><i class="fa fa-trash-o"></i> ' + t('delete', 'Delete') + '</button>' +
										'</div>' +
									'</td>' +
								'</tr>';

							$('#etmLogTbody_' + subId).prepend(trHtml);
							$('#etmLogTable_' + subId).show();
							$('#etmNoLogsCard_' + subId).hide();
							const count = $('#etmLogTbody_' + subId).find('.etm-log-row').length;
							$('#etmActivityLogCount_' + subId).text(count);
						}
					} else {
						showAlert((resp && resp.content) ? resp.content : t('createLogError', 'Failed to create activity log entry.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// Direct Update Single Activity Log Entry
		$app.off('click.etmDirectLog', '.etm-direct-log-btn').on('click.etmDirectLog', '.etm-direct-log-btn', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const $row = $btn.closest('tr');
			const logId = $btn.data('log-id') || $row.data('log-id');
			const dateLogged = $row.find('.etm-log-date').val();
			const userId = $row.find('.etm-log-user-select').val();
			const $msg = $row.find('.etm-log-message');
			const message = $msg.val();
			const origMessage = $msg.data('orig-message');
			const isCustom = (message !== origMessage);

			$btn.prop('disabled', true);
			$.ajax({
				url: config.directUpdateActivityLogUrl || (window.EditorialTimelineConfig && window.EditorialTimelineConfig.directUpdateActivityLogUrl) || getApiUrl('directUpdateActivityLog', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					logId: logId,
					date_logged: dateLogged,
					user_id: userId,
					message: message,
					is_custom_message: isCustom ? 1 : 0
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						if (resp.content && resp.content.clean_message) {
							$msg.val(resp.content.clean_message);
							$msg.data('orig-message', resp.content.clean_message);
						}
						showAlert((resp.content && resp.content.message) ? resp.content.message : t('activityLogUpdated', 'Activity log entry updated successfully!'), 'success');
					} else {
						showAlert((resp && resp.content) ? resp.content : t('updateLogError', 'Failed to update activity log entry.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// Delete Single Activity Log Entry
		$app.off('click.etmDeleteLog', '.etm-delete-log-btn').on('click.etmDeleteLog', '.etm-delete-log-btn', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const $row = $btn.closest('tr');
			const logId = $btn.data('log-id') || $row.data('log-id');

			if (!confirm(t('confirmDeleteLog', 'Are you sure you want to delete this activity log entry? This action cannot be undone.'))) {
				return;
			}

			$btn.prop('disabled', true);
			$.ajax({
				url: config.deleteActivityLogUrl || (window.EditorialTimelineConfig && window.EditorialTimelineConfig.deleteActivityLogUrl) || getApiUrl('deleteActivityLog', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					logId: logId
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						$row.fadeOut(200, function() {
							$(this).remove();
							const count = $('#etmLogTbody_' + subId).find('.etm-log-row').length;
							$('#etmActivityLogCount_' + subId).text(count);
							if (count === 0) {
								$('#etmNoLogsCard_' + subId).show();
							}
						});
						showAlert((resp.content && resp.content.message) ? resp.content.message : t('activityLogDeleted', 'Activity log entry deleted.'), 'success');
					} else {
						showAlert((resp && resp.content) ? resp.content : t('deleteLogError', 'Failed to delete activity log entry.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

		// Clean All Plugin Logs
		$('#etmCleanPluginLogsBtn_' + subId).off('click').on('click', function(e) {
			e.preventDefault();
			if (!confirm(t('confirmCleanPluginLogs', 'Are you sure you want to delete all Editorial Timeline Manager audit logs for this submission?'))) {
				return;
			}

			const $btn = $(this).prop('disabled', true);
			$.ajax({
				url: config.cleanPluginActivityLogsUrl || (window.EditorialTimelineConfig && window.EditorialTimelineConfig.cleanPluginActivityLogsUrl) || getApiUrl('cleanPluginActivityLogs', { submissionId: subId }),
				type: 'POST',
				headers: { 'X-Csrf-Token': getCsrfToken() },
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						$app.find('.etm-log-is-plugin').fadeOut(200, function() {
							$(this).remove();
							const count = $('#etmLogTbody_' + subId).find('.etm-log-row').length;
							$('#etmActivityLogCount_' + subId).text(count);
							if (count === 0) {
								$('#etmNoLogsCard_' + subId).show();
							}
						});
						showAlert((resp.content && resp.content.message) ? resp.content.message : t('logsCleanedSuccess', 'Plugin logs cleaned successfully!'), 'success');
					} else {
						showAlert((resp && resp.content) ? resp.content : t('cleanLogsError', 'Failed to clean plugin logs.'), 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network or communication error occurred with the server.'), 'error');
				}
			});
		});

	};

	/**
	 * Initialize Tab Injection in Workflow Page
	 */
	$(document).ready(function() {
		const $tabContainer = $('#editorialTimelineTabContainer');
		if (!$tabContainer.length) return;

		const subId = $tabContainer.data('submission-id');
		const url = $tabContainer.data('url');

		function loadWorkflowTabContent() {
			if ($tabContainer.data('loaded') || $tabContainer.data('loading')) return;
			$tabContainer.data('loading', true);

			$tabContainer.html(
				'<div style="text-align: center; padding: 60px 20px;">' +
					'<div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #e2e8f0; border-top-color: #006798; border-radius: 50%; animation: etmSpin 0.8s linear infinite;"></div>' +
					'<h3 style="margin-top: 18px; color: #1e293b; font-size: 16px; font-weight: 700;">' + t('loadingSubmission', 'Loading editorial timeline #' + subId + '...', { submissionId: subId }) + '</h3>' +
					'<p style="color: #64748b; font-size: 13px; margin-top: 6px;">' + t('loadingDetails', 'Retrieving files, reviews, and editorial decisions...') + '</p>' +
				'</div>'
			);

			$.ajax({
				url: url,
				type: 'GET',
				dataType: 'json',
				headers: { 'X-Requested-With': 'XMLHttpRequest' },
				data: { isAjax: 1, submissionId: subId },
				success: function(resp) {
					$tabContainer.data('loading', false);
					if (resp && resp.status && resp.content) {
						$tabContainer.html(resp.content);
						$tabContainer.data('loaded', true);
					} else {
						const msg = (resp && resp.content) ? resp.content : t('loadError', 'Failed to load timeline.');
						showTabError(msg);
					}
				},
				error: function(xhr, status, err) {
					$tabContainer.data('loading', false);
					showTabError(err || 'Server Error (' + xhr.status + ')');
				}
			});
		}

		function showTabError(msg) {
			$tabContainer.html(
				'<div style="text-align: center; padding: 40px 20px;">' +
					'<p style="color: #dc2626; font-size: 14px; font-weight: 600;">' + t('errorOccurred', 'An error occurred: ') + $('<div>').text(msg).html() + '</p>' +
					'<button type="button" class="pkp_button pkp_button_primary" id="etmRetryTabBtn" style="margin-top: 14px; cursor: pointer;"><i class="fa fa-refresh"></i> ' + t('retry', 'Retry') + '</button>' +
				'</div>'
			);
			$('#etmRetryTabBtn').on('click', function() {
				$tabContainer.data('loaded', false);
				loadWorkflowTabContent();
			});
		}

		function checkAndLoadIfActive() {
			const $panel = $('#editorialTimelineTab');
			const $btn = $('#editorialTimelineTab-button');
			const isHash = window.location.hash === '#editorialTimelineTab';
			const isAria = $btn.attr('aria-selected') === 'true' || $btn.attr('aria-selected') === true;
			const isVisible = $panel.is(':visible') || $panel.hasClass('pkpTab--isActive');

			if (isHash || isAria || isVisible) {
				loadWorkflowTabContent();
			}
		}

		// Check on ready, and after brief delays to account for Vue mounting tabs
		checkAndLoadIfActive();
		setTimeout(checkAndLoadIfActive, 150);
		setTimeout(checkAndLoadIfActive, 500);
		setTimeout(checkAndLoadIfActive, 1000);

		// Listen to user clicking tab buttons
		$(document).on('click', '#editorialTimelineTab-button, button[aria-controls="editorialTimelineTab"], .pkpTabs__button', function() {
			const target = $(this).attr('aria-controls') || $(this).data('tab');
			if (target === 'editorialTimelineTab') {
				loadWorkflowTabContent();
			} else {
				setTimeout(checkAndLoadIfActive, 100);
			}
		});

		$(window).on('hashchange', checkAndLoadIfActive);
	});

	// ==============================================================
	// Global Utilities & Workflow Enhancers
	// ==============================================================

	/**
	 * Floating toast feedback notification
	 */
	function showGlobalToast(msg, type) {
		type = type || 'success';
		let $toast = $('#etmGlobalToast');
		if (!$toast.length) {
			$toast = $('<div id="etmGlobalToast" class="etm-global-toast"><i class="fa fa-check-circle"></i> <span class="etm-toast-text"></span></div>').appendTo('body');
		}
		$toast.removeClass('etm-toast-success etm-toast-error').addClass('etm-toast-' + type);
		$toast.find('i.fa').attr('class', type === 'success' ? 'fa fa-check-circle' : 'fa fa-exclamation-circle');
		$toast.find('.etm-toast-text').text(msg);
		$toast.addClass('etm-toast-show');
		clearTimeout($toast.data('timeout'));
		$toast.data('timeout', setTimeout(function() {
			$toast.removeClass('etm-toast-show');
		}, 4000));
	}

	/**
	 * Build dynamic API URL with comprehensive fallbacks for production server paths and configurations
	 */
	function getApiUrl(op, params) {
		let url = '';
		const directOp = op.startsWith('directUpdate') ? op : ('directUpdate' + op.charAt(0).toUpperCase() + op.slice(1));
		const updateOp = op.startsWith('directUpdate') ? ('update' + op.slice(12)) : op;
		const cleanOp = op.replace(/^directUpdate/, '').replace(/^update/, '');
		const shortOp = cleanOp ? (cleanOp.charAt(0).toLowerCase() + cleanOp.slice(1)) : op;

		if (window.EditorialTimelineConfig) {
			const cfg = window.EditorialTimelineConfig;
			if (cfg[op + 'Url']) {
				url = cfg[op + 'Url'];
			} else if (cfg[directOp + 'Url']) {
				url = cfg[directOp + 'Url'];
			} else if (cfg[updateOp + 'Url']) {
				url = cfg[updateOp + 'Url'];
			} else if (cfg[shortOp + 'Url']) {
				url = cfg[shortOp + 'Url'];
			} else if (cfg.timelineUrl) {
				const tUrl = cfg.timelineUrl;
				if (tUrl.indexOf('/manage') !== -1) {
					url = tUrl.replace(/\/manage(?=[\/?#]|$)/, '/' + op);
				} else if (/[?&]op=manage/.test(tUrl)) {
					url = tUrl.replace(/([?&])op=manage/, '$1op=' + op);
				}
			}
		}

		if (!url) {
			const currentHref = window.location.href;
			if (currentHref.indexOf('/workflow/') !== -1) {
				const base = currentHref.split('/workflow/')[0];
				url = base + '/editorialTimeline/' + op;
			} else if (currentHref.indexOf('page=workflow') !== -1) {
				const base = currentHref.split('?')[0];
				const journalMatch = currentHref.match(/[?&]journal=([^&]+)/);
				const journalParam = journalMatch ? ('journal=' + journalMatch[1] + '&') : '';
				url = base + '?' + journalParam + 'page=editorialTimeline&op=' + op;
			} else if (window.EditorialTimelineConfig && window.EditorialTimelineConfig.contextPath && window.EditorialTimelineConfig.baseUrl) {
				url = window.EditorialTimelineConfig.baseUrl + '/index.php/' + window.EditorialTimelineConfig.contextPath + '/editorialTimeline/' + op;
			} else if (window.EditorialTimelineConfig && window.EditorialTimelineConfig.baseUrl) {
				url = window.EditorialTimelineConfig.baseUrl + '/index.php?page=editorialTimeline&op=' + op;
			} else {
				url = window.location.origin + window.location.pathname.replace(/\/workflow.*$/, '') + '/editorialTimeline/' + op;
			}
		}

		if (params && typeof params === 'object') {
			const queryParts = [];
			for (let k in params) {
				if (params[k] !== undefined && params[k] !== null && url.indexOf(k + '=') === -1) {
					queryParts.push(encodeURIComponent(k) + '=' + encodeURIComponent(params[k]));
				}
			}
			if (queryParts.length > 0) {
				url += (url.indexOf('?') === -1 ? '?' : '&') + queryParts.join('&');
			}
		}
		return url;
	}

	/**
	 * Extract submission ID from config, DOM or URL
	 */
	function getGlobalSubmissionId() {
		if (window.EditorialTimelineConfig && window.EditorialTimelineConfig.submissionId && parseInt(window.EditorialTimelineConfig.submissionId, 10) > 0) {
			return parseInt(window.EditorialTimelineConfig.submissionId, 10);
		}
		const $tabContainer = $('#editorialTimelineTabContainer, .pkp_controllers_informationCenter');
		if ($tabContainer.length) {
			const dataId = $tabContainer.data('submission-id') || $tabContainer.attr('data-submission-id');
			if (dataId) return parseInt(dataId, 10);
		}
		// Check modal links and forms (Information Center has links to viewHistory, viewNotes with submissionId)
		const $modalLink = $('.pkp_controllers_informationCenter, .ui-dialog-content').find('a[href*="submissionId="], form[action*="submissionId="]').first();
		if ($modalLink.length) {
			const linkHref = $modalLink.attr('href') || $modalLink.attr('action') || '';
			const m = linkHref.match(/[?&]submissionId=(\d+)/i);
			if (m) return parseInt(m[1], 10);
		}
		const $subInput = $('input[name="submissionId"]');
		if ($subInput.length && $subInput.val()) {
			return parseInt($subInput.val(), 10);
		}
		const search = window.location.search;
		let match = search.match(/[?&]submissionId=(\d+)/i) || search.match(/[?&]articleId=(\d+)/i);
		if (match) return parseInt(match[1], 10);
		const pathMatch = search.match(/[?&]path(?:\[\]|%5B%5D)=(\d+)/i);
		if (pathMatch) return parseInt(pathMatch[1], 10);
		const urlPathMatch = window.location.pathname.match(/\/workflow\/(?:index|access)\/(\d+)/i);
		if (urlPathMatch) return parseInt(urlPathMatch[1], 10);
		const $subLink = $('a[href*="submissionId="]').first();
		if ($subLink.length) {
			const linkMatch = $subLink.attr('href').match(/submissionId=(\d+)/i);
			if (linkMatch) return parseInt(linkMatch[1], 10);
		}
		const $dataSub = $('[data-submission-id]').first();
		if ($dataSub.length && $dataSub.data('submission-id')) {
			return parseInt($dataSub.data('submission-id'), 10);
		}
		return 0;
	}

	/**
	 * Extract CSRF token from config or document
	 */
	function getGlobalCsrfToken() {
		let token = (window.EditorialTimelineConfig && window.EditorialTimelineConfig.csrfToken) ||
			(window.pkp && pkp.currentUser && pkp.currentUser.csrfToken) ||
			$('input[name="csrfToken"]').first().val() ||
			$('meta[name="csrf-token"]').attr('content') || '';

		if (!token) {
			$('.pkp_controllers_grid').each(function() {
				const h = $(this).data('pkpHandler');
				if (h && h.options && h.options.csrfToken) {
					token = h.options.csrfToken;
					return false;
				}
			});
		}

		if (!token) {
			const m = document.body.innerHTML.match(/csrfToken["']?\s*:\s*["']([a-f0-9]{32,64})["']/i) ||
			          document.body.innerHTML.match(/[?&]csrfToken=([a-f0-9]{32,64})/i);
			if (m) token = m[1];
		}

		return token || '';
	}

	/**
	 * Synchronize parent workflow file row and timeline modal inputs
	 */
	function updateParentWorkflowFileRow(fileId, newIsoDt, displayDate) {
		if (!fileId) return;

		const targetDisp = displayDate || (newIsoDt ? newIsoDt.replace('T', ' ') : '');

		// 1. Update any workflow grid rows matching fileId
		const $matchingRows = $('tr[id*="row-' + fileId + '"], tr:has(a[href*="submissionFileId=' + fileId + '"]), tr:has(a[href*="fileId=' + fileId + '"])');
		$matchingRows.each(function() {
			const $row = $(this);
			// In PKP SubmissionFilesGridHandler, the date column is the second cell (td:nth-child(2))
			let $dateCell = $row.find('td:nth-child(2)');
			if (!$dateCell.length || !$dateCell.find('.label').length) {
				$row.find('td').each(function() {
					if ($(this).find('.label').length && /\d/.test($(this).text())) {
						$dateCell = $(this);
						return false;
					}
				});
			}

			if ($dateCell && $dateCell.length) {
				const $lbl = $dateCell.find('.label');
				if ($lbl.length) {
					$lbl.last().text(targetDisp);
				} else {
					$dateCell.text(targetDisp);
				}
				$dateCell.addClass('etm-flash-success');
				setTimeout(() => $dateCell.removeClass('etm-flash-success'), 1500);
			}

			$row.addClass('etm-flash-success');
			setTimeout(() => $row.removeClass('etm-flash-success'), 1500);
		});

		// 2. Update inputs in Timeline modal if loaded
		if (newIsoDt) {
			$('input[name="files[' + fileId + '][created_at]"]').val(newIsoDt.substring(0, 16));
			$('input[name="files[' + fileId + '][updated_at]"]').val(newIsoDt.substring(0, 16));
		}

		// 3. Trigger PKP global grid refresh events
		try {
			$(document).trigger('dataChanged');
			$(document).trigger('refreshRevisionsGrid');
			$(document).trigger('refreshGrid');
		} catch (e) {}
	}

	/**
	 * Enhance Information Center Modal (History and Notes tabs)
	 * Injects date & time edit controls into SubmissionFileEventLogGridHandler and Note rows.
	 */
	function enhanceInformationCenterModal() {
		const $modals = $('.pkp_controllers_informationCenter, div[id^="informationCenter"], .ui-dialog-content:has([id*="eventLog"]), .ui-dialog-content:has([id*="EventLog"])');
		if (!$modals.length) return;

		$modals.each(function() {
			const $modal = $(this);

			// A. ENHANCE HISTORY TAB (EventLog grid rows)
			const $logRows = $modal.find('tr.gridRow, tr[id*="eventLog"], tr[id*="EventLog"]');
			$logRows.each(function() {
				const $row = $(this);
				if ($row.data('etm-log-enhanced')) return;

				const rowId = $row.attr('id') || '';
				const match = rowId.match(/row-(\d+)/i);
				const logId = match ? match[1] : $row.data('log-id');
				if (!logId) return;

				const $dateCell = $row.find('td.first_column, td:first-child');
				if (!$dateCell.length) return;

				if ($dateCell.find('.etm-inline-log-edit-btn').length) return;
				$row.data('etm-log-enhanced', true);

				const $cellContainer = $dateCell.find('.gridCellContainer');
				const $targetElem = $cellContainer.length ? $cellContainer : $dateCell;
				const $label = $dateCell.find('.label').first();

				// Add edit button
				const $editBtn = $(
					'<button type="button" class="pkp_button etm-inline-log-edit-btn" title="' + t('editDateTime', 'Edit Date & Time') + '" data-log-id="' + logId + '">' +
						'<i class="fa fa-pencil"></i>' +
					'</button>'
				);

				$targetElem.append($editBtn);

				$editBtn.on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					if ($dateCell.find('.etm-inline-log-editor').length) return;

					const subId = getGlobalSubmissionId();
					const currentText = $.trim($label.text());

					// Compute default initial datetime string
					let initDt = '';
					if (currentText.indexOf('T') !== -1) {
						initDt = currentText.substring(0, 16);
					} else if (currentText.length === 10 && /^\d{4}-\d{2}-\d{2}$/.test(currentText)) {
						initDt = currentText + 'T12:00';
					} else if (/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}/.test(currentText)) {
						initDt = currentText.replace(' ', 'T').substring(0, 16);
					} else {
						initDt = formatToHtmlDatetime(new Date());
					}

					// Build inline editor container
					const $editor = $(
						'<div class="etm-inline-log-editor" data-log-id="' + logId + '">' +
							'<input type="datetime-local" class="etm-inline-log-input" value="' + initDt + '" step="1" />' +
							'<div class="etm-inline-log-actions">' +
								'<button type="button" class="etm-inline-log-save-btn" title="' + t('save', 'Save') + '"><i class="fa fa-check"></i></button>' +
								'<button type="button" class="etm-inline-log-cancel-btn" title="' + t('cancel', 'Cancel') + '"><i class="fa fa-times"></i></button>' +
							'</div>' +
						'</div>'
					);

					$label.hide();
					$editBtn.hide();
					$targetElem.append($editor);
					$editor.find('.etm-inline-log-input').focus();

					// Asynchronously fetch exact seconds & exact database date_logged
					const detailsUrl = getApiUrl('getEventLogDetails', { submissionId: subId });
					if (detailsUrl) {
						$.ajax({
							url: detailsUrl,
							type: 'GET',
							dataType: 'json',
							data: { submissionId: subId, logId: logId },
							success: function(res) {
								if (res && res.status && res.content && res.content.date_logged_formatted) {
									if ($editor.find('.etm-inline-log-input').val() === initDt) {
										$editor.find('.etm-inline-log-input').val(res.content.date_logged_formatted);
									}
								}
							}
						});
					}

					// Cancel button handler
					$editor.find('.etm-inline-log-cancel-btn').on('click', function(ev) {
						ev.preventDefault();
						ev.stopPropagation();
						$editor.remove();
						$label.show();
						$editBtn.show();
					});

					// Save button handler
					$editor.find('.etm-inline-log-save-btn').on('click', function(ev) {
						ev.preventDefault();
						ev.stopPropagation();

						const newDt = $editor.find('.etm-inline-log-input').val();
						if (!newDt) {
							alert(t('selectDatePrompt', 'Please select a date and time.'));
							return;
						}

						const currentSubId = getGlobalSubmissionId() || subId;
						const updateUrl = getApiUrl('directUpdateEventLogDate', { submissionId: currentSubId });
						const csrf = getGlobalCsrfToken();

						// Detect file ID from modal if available
						const fLink = $modal.find('a[href*="submissionFileId="]').first().attr('href') || '';
						const fMatch = fLink.match(/[?&]submissionFileId=(\d+)/i);
						const modalFileId = fMatch ? parseInt(fMatch[1], 10) : 0;

						const $saveBtn = $(this).prop('disabled', true);
						$saveBtn.html('<i class="fa fa-spinner fa-spin"></i>');
						$editor.find('input').prop('disabled', true);

						$.ajax({
							url: updateUrl,
							type: 'POST',
							headers: { 'X-Csrf-Token': csrf },
							dataType: 'json',
							data: {
								csrfToken: csrf,
								submissionId: currentSubId,
								submissionFileId: modalFileId,
								logId: logId,
								date_logged: newDt,
								sync_file: 1
							},
							success: function(resp) {
								if (resp && resp.status) {
									const disp = (resp.content && resp.content.display_date) ? resp.content.display_date : newDt.replace('T', ' ');
									const dispLong = (resp.content && resp.content.display_date_long) ? resp.content.display_date_long : disp;
									$label.text(disp).attr('title', 'Updated: ' + newDt);
									$editor.remove();
									$label.show();
									$editBtn.show();

									$dateCell.addClass('etm-flash-success');
									setTimeout(function() { $dateCell.removeClass('etm-flash-success'); }, 1500);

									const fileId = (resp.content && resp.content.file_id) || (resp.content && resp.content.result && resp.content.result.file_id) || modalFileId;
									if (fileId) {
										// Synchronize other event log rows in this modal for the same file
										$modal.find('tr.gridRow').each(function() {
											const $otherRow = $(this);
											const otherMatch = ($otherRow.attr('id') || '').match(/row-(\d+)/i);
											if (otherMatch && otherMatch[1] != logId) {
												const $otherCell = $otherRow.find('td.first_column .label, td:first-child .label').first();
												if ($otherCell.length) {
													$otherCell.text(disp).attr('title', 'Updated: ' + newDt);
													$otherRow.addClass('etm-flash-success');
													setTimeout(function() { $otherRow.removeClass('etm-flash-success'); }, 1200);
												}
											}
										});

										updateParentWorkflowFileRow(fileId, newDt, dispLong);
									}

									showGlobalToast((resp.content && resp.content.message) || t('dateUpdatedSuccess', 'Date and time updated successfully!'), 'success');
								} else {
									$saveBtn.prop('disabled', false).html('<i class="fa fa-check"></i>');
									$editor.find('input').prop('disabled', false);
									const errMsg = (resp && resp.content && (resp.content.message || resp.content)) ? (resp.content.message || resp.content) : 'Error saving date.';
									alert(errMsg);
								}
							},
							error: function(xhr, status, error) {
								$saveBtn.prop('disabled', false).html('<i class="fa fa-check"></i>');
								$editor.find('input').prop('disabled', false);
								let errMsg = t('networkError', 'Network or communication error occurred with the server.');
								if (xhr && xhr.responseJSON && xhr.responseJSON.content) {
									errMsg = xhr.responseJSON.content.message || xhr.responseJSON.content;
								}
								alert(errMsg);
							}
						});
					});
				});
			});

			// B. ENHANCE NOTES TAB (Note entries)
			const $notes = $modal.find('.pkp_notes_list .note, .note');
			$notes.each(function() {
				const $note = $(this);
				if ($note.data('etm-note-enhanced')) return;

				const noteIdAttr = $note.attr('id') || '';
				const match = noteIdAttr.match(/note-(\d+)/i);
				const noteId = match ? match[1] : 0;
				if (!noteId) return;

				const $dateSpan = $note.find('.details .date');
				if (!$dateSpan.length) return;
				if ($dateSpan.find('.etm-inline-note-edit-btn').length) return;

				$note.data('etm-note-enhanced', true);

				const $editBtn = $(
					'<button type="button" class="pkp_button etm-inline-note-edit-btn" title="' + t('editDateTime', 'Edit Date & Time') + '" data-note-id="' + noteId + '">' +
						'<i class="fa fa-pencil"></i>' +
					'</button>'
				);

				$dateSpan.append($editBtn);

				$editBtn.on('click', function(e) {
					e.preventDefault();
					e.stopPropagation();

					if ($dateSpan.find('.etm-inline-note-editor').length) return;

					const subId = getGlobalSubmissionId();
					const initDt = formatToHtmlDatetime(new Date());

					const $editor = $(
						'<span class="etm-inline-note-editor" data-note-id="' + noteId + '">' +
							'<input type="datetime-local" class="etm-inline-note-input" value="' + initDt + '" />' +
							'<span class="etm-inline-note-actions">' +
								'<button type="button" class="etm-inline-note-save-btn" title="' + t('save', 'Save') + '"><i class="fa fa-check"></i></button>' +
								'<button type="button" class="etm-inline-note-cancel-btn" title="' + t('cancel', 'Cancel') + '"><i class="fa fa-times"></i></button>' +
							'</span>' +
						'</span>'
					);

					$editBtn.hide();
					$dateSpan.append($editor);

					$editor.find('.etm-inline-note-cancel-btn').on('click', function(ev) {
						ev.preventDefault();
						ev.stopPropagation();
						$editor.remove();
						$editBtn.show();
					});

					$editor.find('.etm-inline-note-save-btn').on('click', function(ev) {
						ev.preventDefault();
						ev.stopPropagation();

						const newDt = $editor.find('.etm-inline-note-input').val();
						if (!newDt) {
							alert(t('selectDatePrompt', 'Please select a date and time.'));
							return;
						}

						const currentSubId = getGlobalSubmissionId() || subId;
						const updateNoteUrl = getApiUrl('directUpdateNoteDate', { submissionId: currentSubId });
						const csrf = getGlobalCsrfToken();

						const $saveBtn = $(this).prop('disabled', true);
						$saveBtn.html('<i class="fa fa-spinner fa-spin"></i>');

						$.ajax({
							url: updateNoteUrl,
							type: 'POST',
							headers: { 'X-Csrf-Token': csrf },
							dataType: 'json',
							data: {
								csrfToken: csrf,
								submissionId: currentSubId,
								noteId: noteId,
								date_created: newDt
							},
							success: function(resp) {
								if (resp && resp.status) {
									const disp = (resp.content && resp.content.display_date) ? resp.content.display_date : newDt.replace('T', ' ');
									$dateSpan.contents().filter(function() { return this.nodeType === 3; }).first().replaceWith(disp + ' ');
									$editor.remove();
									$editBtn.show();

									$dateSpan.addClass('etm-flash-success');
									setTimeout(function() { $dateSpan.removeClass('etm-flash-success'); }, 1500);

									showGlobalToast((resp.content && resp.content.message) || t('noteUpdatedSuccess', 'Note date and time updated successfully!'), 'success');
								} else {
									$saveBtn.prop('disabled', false).html('<i class="fa fa-check"></i>');
									const errMsg = (resp && resp.content && (resp.content.message || resp.content)) ? (resp.content.message || resp.content) : 'Error saving note date.';
									alert(errMsg);
								}
							},
							error: function(xhr) {
								$saveBtn.prop('disabled', false).html('<i class="fa fa-check"></i>');
								let errMsg = t('networkError', 'Network or communication error occurred with the server.');
								if (xhr && xhr.responseJSON && xhr.responseJSON.content) {
									errMsg = xhr.responseJSON.content.message || xhr.responseJSON.content;
								}
								alert(errMsg);
							}
						});
					});
				});
			});
		});
	}

	/**
	 * Enhance Workflow File Grids (Submission, Review, Copyediting, Production)
	 * Injects quick date & time edit buttons directly onto workflow stage file tables.
	 */
	function enhanceWorkflowFileGrids() {
		const $workflowGrids = $('table.pkp_grid:has(tr[id*="submissionFile"]), table.pkp_grid:has(tr[id*="reviewRevision"]), table.pkp_grid:has(tr[id*="copyedit"]), table.pkp_grid:has(tr[id*="finalDraft"]), table.pkp_grid:has(tr[id*="productionReady"])');
		if (!$workflowGrids.length) return;

		$workflowGrids.find('tr.gridRow').each(function() {
			const $row = $(this);
			if ($row.data('etm-grid-enhanced')) return;

			const rowId = $row.attr('id') || '';
			const match = rowId.match(/row-(\d+)/i);
			const fileId = match ? match[1] : 0;
			if (!fileId) return;

			// Locate date cell in row (second cell in SubmissionFilesGridHandler, or cell with date text)
			let $dateCell = $row.find('td:nth-child(2)');
			if (!$dateCell.length || !$dateCell.find('.label').length) {
				$row.find('td').each(function() {
					const text = $.trim($(this).text());
					if (/\d/.test(text) && $(this).find('.label').length) {
						$dateCell = $(this);
						return false;
					}
				});
			}

			if (!$dateCell || !$dateCell.length) return;
			if ($dateCell.find('.etm-workflow-quick-edit-btn').length) return;

			$row.data('etm-grid-enhanced', true);

			const $quickBtn = $(
				'<button type="button" class="pkp_button etm-workflow-quick-edit-btn" title="' + t('quickEditFileDate', 'Quick Edit File Date & Time') + '" data-file-id="' + fileId + '">' +
					'<i class="fa fa-pencil"></i>' +
				'</button>'
			);

			$dateCell.append($quickBtn);

			$quickBtn.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				if ($dateCell.find('.etm-inline-log-editor').length) return;

				const $label = $dateCell.find('.label').first();
				const currentText = $.trim($label.text());

				// Default initial datetime
				let initDt = '';
				if (currentText.indexOf('T') !== -1) {
					initDt = currentText.substring(0, 16);
				} else if (/^\d{4}-\d{2}-\d{2}$/.test(currentText)) {
					initDt = currentText + 'T12:00';
				} else if (/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}/.test(currentText)) {
					initDt = currentText.replace(' ', 'T').substring(0, 16);
				} else {
					initDt = formatToHtmlDatetime(new Date());
				}

				const $editor = $(
					'<div class="etm-inline-log-editor" data-file-id="' + fileId + '">' +
						'<input type="datetime-local" class="etm-inline-log-input" value="' + initDt + '" step="1" />' +
						'<div class="etm-inline-log-actions">' +
							'<button type="button" class="etm-inline-log-save-btn" title="' + t('save', 'Save') + '"><i class="fa fa-check"></i></button>' +
							'<button type="button" class="etm-inline-log-cancel-btn" title="' + t('cancel', 'Cancel') + '"><i class="fa fa-times"></i></button>' +
						'</div>' +
					'</div>'
				);

				$label.hide();
				$quickBtn.hide();
				$dateCell.append($editor);
				$editor.find('.etm-inline-log-input').focus();

				// Cancel button
				$editor.find('.etm-inline-log-cancel-btn').on('click', function(ev) {
					ev.preventDefault();
					ev.stopPropagation();
					$editor.remove();
					$label.show();
					$quickBtn.show();
				});

				// Save button
				$editor.find('.etm-inline-log-save-btn').on('click', function(ev) {
					ev.preventDefault();
					ev.stopPropagation();

					const newDt = $editor.find('.etm-inline-log-input').val();
					if (!newDt) {
						alert(t('selectDatePrompt', 'Please select a date and time.'));
						return;
					}

					const subId = getGlobalSubmissionId();
					const updateUrl = getApiUrl('directUpdateFileDate', { submissionId: subId });
					const csrf = getGlobalCsrfToken();

					const $saveBtn = $(this).prop('disabled', true);
					$saveBtn.html('<i class="fa fa-spinner fa-spin"></i>');
					$editor.find('input').prop('disabled', true);

					$.ajax({
						url: updateUrl,
						type: 'POST',
						headers: { 'X-Csrf-Token': csrf },
						dataType: 'json',
						data: {
							csrfToken: csrf,
							submissionId: subId,
							submissionFileId: fileId,
							fileId: fileId,
							created_at: newDt,
							updated_at: newDt,
							sync_history: 1
						},
						success: function(resp) {
							if (resp && resp.status) {
								const disp = (resp.content && resp.content.display_date_long) ? resp.content.display_date_long : ((resp.content && resp.content.display_date) ? resp.content.display_date : newDt.replace('T', ' '));
								$label.text(disp).attr('title', 'Updated: ' + newDt);
								$editor.remove();
								$label.show();
								$quickBtn.show();

								updateParentWorkflowFileRow(fileId, newDt, disp);
								showGlobalToast((resp.content && resp.content.message) || t('fileUpdatedSuccess', 'File timestamp and history updated successfully!'), 'success');
							} else {
								$saveBtn.prop('disabled', false).html('<i class="fa fa-check"></i>');
								$editor.find('input').prop('disabled', false);
								const errMsg = (resp && resp.content && (resp.content.message || resp.content)) ? (resp.content.message || resp.content) : t('updateFilesError', 'Failed to update file timestamp.');
								alert(errMsg);
							}
						},
						error: function(xhr) {
							$saveBtn.prop('disabled', false).html('<i class="fa fa-check"></i>');
							$editor.find('input').prop('disabled', false);
							let errMsg = t('networkError', 'Network or communication error occurred with the server.');
							if (xhr && xhr.responseJSON && xhr.responseJSON.content) {
								errMsg = xhr.responseJSON.content.message || xhr.responseJSON.content;
							}
							alert(errMsg);
						}
					});
				});
			});
		});
	}

	// ==============================================================
	// Dynamic Enhancement Observers and Lifecycle Hooks
	// ==============================================================

	// 1. Run on document ready and with small delays for asynchronous mounting
	$(document).ready(function() {
		enhanceInformationCenterModal();
		enhanceWorkflowFileGrids();
		setTimeout(enhanceInformationCenterModal, 300);
		setTimeout(enhanceWorkflowFileGrids, 400);
		setTimeout(enhanceInformationCenterModal, 1000);
		setTimeout(enhanceWorkflowFileGrids, 1200);
	});

	// 2. Run whenever an AJAX call finishes (e.g. modals opening, tabs switching, grids reloading)
	$(document).ajaxComplete(function(event, xhr, settings) {
		enhanceInformationCenterModal();
		enhanceWorkflowFileGrids();
		setTimeout(enhanceInformationCenterModal, 200);
		setTimeout(enhanceWorkflowFileGrids, 300);
	});

	// 3. MutationObserver on document body to catch dynamically created dialogs
	if (window.MutationObserver) {
		let observerTimer = null;
		const observer = new MutationObserver(function(mutations) {
			if (observerTimer) clearTimeout(observerTimer);
			observerTimer = setTimeout(function() {
				enhanceInformationCenterModal();
				enhanceWorkflowFileGrids();
			}, 150);
		});
		observer.observe(document.body, { childList: true, subtree: true });
	}

	// 4. Click delegation on links that open Information Center
	$(document).on('click', 'a[href*="viewInformationCenter"], a[href*="FileInfoCenterLinkAction"], a[id*="moreInformation"], .ui-dialog-titlebar-close, .pkp_controllers_tab ul li a', function() {
		setTimeout(enhanceInformationCenterModal, 300);
		setTimeout(enhanceInformationCenterModal, 800);
	});

})(jQuery);
