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
			return $form.find('input[name="csrfToken"]').val() ||
				(window.pkp && pkp.currentUser && pkp.currentUser.csrfToken) || '';
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
				url: config.directUpdateFilesUrl,
				type: 'POST',
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
				url: config.directUpdateReviewUrl,
				type: 'POST',
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
				url: config.shiftUrl,
				type: 'POST',
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

			$btn.addClass('is-loading').prop('disabled', true);
			$btn.find('.etm-spinner-inline').addClass('active').show();
			$btn.find('.etm-btn-text').text(t('savingToDb', 'Saving to Database...'));

			$.ajax({
				url: config.saveUrl,
				type: 'POST',
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

})(jQuery);
