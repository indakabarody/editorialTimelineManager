/**
 * plugins/generic/editorialTimelineManager/js/editorialTimeline.js
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * Client-side controller for Editorial Timeline Manager.
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
			$alert.find('.etm-alert-icon').empty();
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
		$app.find('#tabFiles_' + subId + ' .etm-filter-chip').on('click', function() {
			$app.find('#tabFiles_' + subId + ' .etm-filter-chip').removeClass('active');
			$(this).addClass('active');

			const filter = $(this).data('filter');
			if (filter === 'all') {
				$app.find('.etm-file-row').show();
			} else {
				$app.find('.etm-file-row').hide();
				$app.find('.etm-file-row.' + filter).show();
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
				url: config.directUpdateFilesUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					csrfToken: $form.find('input[name="csrfToken"]').val(),
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
						showAlert(resp.content || 'Database update failed.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert('Network or server error while updating files in database.', 'error');
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

		// 6. Review Auto-Chronology Alignment
		$app.on('click', '.etm-auto-align-btn', function() {
			const revId = $(this).data('review-id');
			const $card = $('#etmReviewCard_' + revId);
			const $completedInput = $card.find('input[data-field="date_completed"]');

			let baseDate;
			if ($completedInput.val()) {
				baseDate = new Date($completedInput.val());
			} else {
				baseDate = new Date();
			}

			const pad = n => (n < 10 ? '0' + n : n);
			const toDt = d =>
				d.getFullYear() +
				'-' +
				pad(d.getMonth() + 1) +
				'-' +
				pad(d.getDate()) +
				'T' +
				pad(d.getHours()) +
				':' +
				pad(d.getMinutes());

			const dCompleted = new Date(baseDate.getTime());
			const dConfirmed = new Date(baseDate.getTime() - 7 * 86400000);
			const dNotified = new Date(baseDate.getTime() - 8 * 86400000);
			const dAssigned = new Date(baseDate.getTime() - 8 * 86400000 - 300000);
			const dDue = new Date(baseDate.getTime());
			const dAck = new Date(baseDate.getTime() + 1 * 86400000);

			$card.find('input[data-field="date_assigned"]').val(toDt(dAssigned));
			$card.find('input[data-field="date_notified"]').val(toDt(dNotified));
			$card.find('input[data-field="date_confirmed"]').val(toDt(dConfirmed));
			$card.find('input[data-field="date_due"]').val(toDt(dDue));
			$card.find('input[data-field="date_completed"]').val(toDt(dCompleted));
			$card.find('input[data-field="date_acknowledged"]').val(toDt(dAck));

			$('#etmChronoWarning_' + revId).slideUp(150);
			showAlert(t('reviewAlignedSuccess', 'Aligned review assignment chronology in form.'), 'success');
		});

		// 6B. DIRECT ONE-CLICK DATABASE UPDATE FOR SINGLE REVIEW
		$app.on('click', '.etm-direct-review-btn', function() {
			const revId = $(this).data('review-id');
			const $card = $('#etmReviewCard_' + revId);
			const dates = {};

			$card.find('.etm-rev-date').each(function() {
				const field = $(this).data('field');
				dates[field] = $(this).val();
			});

			const $btn = $(this).prop('disabled', true);

			$.ajax({
				url: config.directUpdateReviewUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					csrfToken: $form.find('input[name="csrfToken"]').val(),
					submissionId: subId,
					reviewId: revId,
					dates: dates
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						showAlert(resp.content.message || 'Reviewer schedule directly updated in database!', 'success');
					} else {
						showAlert(resp.content || 'Database update failed.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert('Network error while updating reviewer in database.', 'error');
				}
			});
		});

		// 7. Stepper buttons for Quick Batch Shift
		$app.find('.etm-btn-step').on('click', function() {
			const step = parseInt($(this).data('step'), 10) || 0;
			const $input = $('#etmQuickShiftDays_' + subId);
			const current = parseInt($input.val(), 10) || 0;
			$input.val(current + step);
		});

		// 8. Apply Batch Shift via AJAX
		$('#etmApplyQuickShiftBtn_' + subId).on('click', function() {
			const daysOffset = parseInt($('#etmQuickShiftDays_' + subId).val(), 10) || 0;
			if (daysOffset === 0) {
				alert(t('specifyOffsetPrompt', 'Please specify a positive or negative day offset to shift.'));
				return;
			}

			const options = {
				shift_files: $('#etmShiftOptFiles_' + subId).is(':checked'),
				shift_reviews: $('#etmShiftOptReviews_' + subId).is(':checked'),
				shift_decisions: $('#etmShiftOptDecisions_' + subId).is(':checked'),
				shift_discussions: $('#etmShiftOptDiscussions_' + subId).is(':checked'),
				shift_submission: $('#etmShiftOptSubmission_' + subId).is(':checked'),
				shift_publications: $('#etmShiftOptPublications_' + subId).is(':checked')
			};

			const $btn = $(this).prop('disabled', true);

			$.ajax({
				url: config.shiftUrl,
				type: 'POST',
				dataType: 'json',
				data: {
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
						showAlert(resp.content || 'Failed to shift timeline.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert('Network error while shifting timeline.', 'error');
				}
			});
		});

		/**
		 * Helper to update form fields from a timeline data object
		 */
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

			$form.find('input[name^="decisions["]').each(function() {
				const match = $(this).attr('name').match(/decisions\[(\d+)\]\[date_decided\]/);
				if (match) {
					payload.decisions[match[1]] = { date_decided: $(this).val() };
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

			$btn.prop('disabled', true);
			$btn.find('.etm-spinner-inline').show();
			$btn.find('.etm-btn-text').text(t('savingToDb', 'Saving to Database...'));

			$.ajax({
				url: config.saveUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					csrfToken: $form.find('input[name="csrfToken"]').val(),
					submissionId: subId,
					timelinePayload: JSON.stringify(payload)
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					$btn.find('.etm-spinner-inline').hide();
					$btn.find('.etm-btn-text').text(t('saveToDbDirectly', 'Save Directly to OJS Database'));

					if (resp && resp.status) {
						let detail = t('savedSuccess', 'Data in OJS database updated successfully!');
						if (resp.content && resp.content.message) {
							detail = resp.content.message;
						}
						showAlert(detail, 'success');
					} else {
						showAlert(resp.content || 'Error saving changes to database.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					$btn.find('.etm-spinner-inline').hide();
					$btn.find('.etm-btn-text').text(t('saveToDbDirectly', 'Save Directly to OJS Database'));
					showAlert('Server error while saving directly to database.', 'error');
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
		function getCsrfToken() {
			return $form.find('input[name="csrfToken"]').val() ||
				(window.pkp && pkp.currentUser && pkp.currentUser.csrfToken) || '';
		}

		// A. Real-time Search & Filter in Activity Log
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

				const matchesType = (chip === 'all') || (chip === 'plugin' && isPlugin) || (chip === 'system' && !isPlugin);
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

		// B. Toggle Add Log Form with auto current timestamp
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

		// C. Create New Log Entry (Instant dynamic insert)
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
				url: config.createActivityLogUrl,
				type: 'POST',
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
											'<button type="button" class="pkp_button etm-direct-log-btn" title="' + t('save', 'Save') + '" data-log-id="' + log.log_id + '">' + t('save', 'Save') + '</button>' +
											'<button type="button" class="pkp_button etm-btn-danger etm-delete-log-btn" title="' + t('delete', 'Delete') + '" data-log-id="' + log.log_id + '">' + t('delete', 'Delete') + '</button>' +
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
						showAlert((resp && resp.content) ? resp.content : 'Failed to create activity log entry.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network error occurred.'), 'error');
				}
			});
		});

		// D. Direct Update Single Activity Log Entry
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
				url: config.directUpdateActivityLogUrl,
				type: 'POST',
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
						const cleanMsg = (resp.content && resp.content.clean_message) ? resp.content.clean_message : message.replace(/^##(.*)##$/, '$1');
						$msg.val(cleanMsg).data('orig-message', cleanMsg);
						showAlert((resp.content && resp.content.message) ? resp.content.message : t('activityLogUpdated', 'Activity log entry updated successfully!'), 'success');
					} else {
						showAlert((resp && resp.content) ? resp.content : 'Failed to update activity log entry.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network error occurred.'), 'error');
				}
			});
		});

		// E. Delete Single Activity Log Entry
		$app.off('click.etmDeleteLog', '.etm-delete-log-btn').on('click.etmDeleteLog', '.etm-delete-log-btn', function(e) {
			e.preventDefault();
			const $btn = $(this);
			const $row = $btn.closest('tr');
			const logId = $btn.data('log-id') || $row.data('log-id');
			const confirmMsg = t('confirmDeleteLog', 'Are you sure you want to delete this activity log entry? This action cannot be undone.');
			if (!confirm(confirmMsg)) return;

			$btn.prop('disabled', true);
			$.ajax({
				url: config.deleteActivityLogUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId,
					logId: logId
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						$row.fadeOut(250, function() {
							$(this).remove();
							const count = $app.find('.etm-log-row').length;
							$('#etmActivityLogCount_' + subId).text(count);
							if (count === 0) {
								$('#etmLogTable_' + subId).hide();
								$('#etmNoLogsCard_' + subId).show();
							}
						});
						showAlert((resp.content && resp.content.message) ? resp.content.message : t('activityLogDeleted', 'Activity log entry deleted.'), 'success');
					} else {
						showAlert((resp && resp.content) ? resp.content : 'Failed to delete activity log entry.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network error occurred.'), 'error');
				}
			});
		});

		// F. Clean All Plugin Activity Logs
		$('#etmCleanPluginLogsBtn_' + subId).off('click').on('click', function(e) {
			e.preventDefault();
			e.stopImmediatePropagation();
			const confirmMsg = t('confirmCleanPluginLogs', 'Are you sure you want to delete all Editorial Timeline Manager audit logs for this submission?');
			if (!confirm(confirmMsg)) return;

			const $btn = $(this).prop('disabled', true);
			$.ajax({
				url: config.cleanPluginActivityLogsUrl,
				type: 'POST',
				dataType: 'json',
				data: {
					csrfToken: getCsrfToken(),
					submissionId: subId
				},
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status) {
						$app.find('.etm-log-row.etm-log-is-plugin').fadeOut(250, function() {
							$(this).remove();
							const count = $app.find('.etm-log-row').length;
							$('#etmActivityLogCount_' + subId).text(count);
							if (count === 0) {
								$('#etmLogTable_' + subId).hide();
								$('#etmNoLogsCard_' + subId).show();
							}
						});
						showAlert((resp.content && resp.content.message) ? resp.content.message : 'Plugin logs cleaned successfully!', 'success');
					} else {
						showAlert((resp && resp.content) ? resp.content : 'Failed to clean plugin logs.', 'error');
					}
				},
				error: function() {
					$btn.prop('disabled', false);
					showAlert(t('networkError', 'Network error occurred.'), 'error');
				}
			});
		});
	};

	/**
	 * Initialize Submission Picker
	 */
	window.EditorialTimelineManager.initPicker = function(manageUrl, searchUrl) {
		const $input = $('#etmSubmissionSearchInput');
		const $btn = $('#etmSearchButton');
		const $tbody = $('#etmSubmissionsTbody');

		function doSearch() {
			const query = $.trim($input.val());
			$btn.prop('disabled', true);

			$.ajax({
				url: searchUrl,
				type: 'POST',
				dataType: 'json',
				data: { query: query },
				success: function(resp) {
					$btn.prop('disabled', false);
					if (resp && resp.status && resp.content) {
						renderResults(resp.content);
					}
				},
				error: function() {
					$btn.prop('disabled', false);
				}
			});
		}

		function renderResults(list) {
			$tbody.empty();
			if (!list || list.length === 0) {
				$tbody.append('<tr><td colspan="5" style="text-align:center; padding: 20px; color: #64748b;">' + t('noSubmissionsFound', 'No matching submissions found.') + '</td></tr>');
				return;
			}

			list.forEach(function(item) {
				const tr = $('<tr>').attr('data-submission-id', item.submission_id);
				tr.append('<td><strong>#' + item.submission_id + '</strong></td>');
				tr.append('<td><a href="#" class="etm-submission-link" data-submission-id="' + item.submission_id + '">' + $('<div>').text(item.title).html() + '</a></td>');
				tr.append('<td><span class="etm-stage-badge">' + $('<div>').text(item.stage_name).html() + '</span></td>');
				tr.append('<td>' + (item.date_submitted || '-') + '</td>');
				tr.append('<td style="text-align: right;"><button type="button" class="pkp_button etm-open-btn" data-submission-id="' + item.submission_id + '">' + t('manage', 'Manage') + '</button></td>');
				$tbody.append(tr);
			});
		}

		$btn.on('click', doSearch);
		$input.on('keypress', function(e) {
			if (e.which === 13) {
				e.preventDefault();
				doSearch();
			}
		});

		$(document).off('click.etmInitPicker', '.etm-submission-link, .etm-open-btn');
		$(document).on('click.etmInitPicker', '.etm-submission-link, .etm-open-btn', function(e) {
			e.preventDefault();
			const sId = $(this).data('submission-id') || $(this).closest('tr').data('submission-id');
			if (!sId) return;

			const $wrapper = $('#etmPickerWrapper');
			const $container = $wrapper.closest('.pkp_modal_panel, .ui-dialog-content');
			const $target = $container.length ? $container : $wrapper;

			$target.html(
				'<div style="text-align: center; padding: 70px 20px;">' +
					'<div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #e2e8f0; border-top-color: #2563eb; border-radius: 50%; animation: etmSpin 0.8s linear infinite;"></div>' +
					'<h3 style="margin-top: 18px; color: #1e293b; font-size: 16px; font-weight: 700;">' + t('loadingSubmission', 'Loading editorial timeline #' + sId + '...', { submissionId: sId }) + '</h3>' +
					'<p style="color: #64748b; font-size: 13px; margin-top: 6px;">' + t('loadingDetails', 'Retrieving files, reviews, and editorial decisions...') + '</p>' +
				'</div>'
			);

			$.ajax({
				url: manageUrl,
				type: 'GET',
				dataType: 'json',
				data: { submissionId: sId },
				success: function(resp) {
					if (resp && resp.status && resp.content) {
						$target.html(resp.content);
						const $dialog = $target.closest('.ui-dialog-content');
						if ($dialog.length && $dialog.dialog) {
							try {
								$dialog.dialog('option', 'title', t('displayName', 'Editorial Timeline Manager') + ' — #' + sId);
							} catch(err) {}
						}
					}
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
					'<div style="display: inline-block; width: 44px; height: 44px; border: 4px solid #e2e8f0; border-top-color: #2563eb; border-radius: 50%; animation: etmSpin 0.8s linear infinite;"></div>' +
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
					'<p style="color: #dc2626; font-size: 15px; font-weight: 600;">' + t('errorOccurred', 'An error occurred: ') + $('<div>').text(msg).html() + '</p>' +
					'<button type="button" class="pkp_button pkp_button_primary" id="etmRetryTabBtn" style="margin-top: 14px; cursor: pointer;">' + t('retry', 'Retry') + '</button>' +
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

})(jQuery);
