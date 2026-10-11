<?php

/**
 * @file plugins/generic/editorialTimelineManager/classes/EditorialTimelineHelper.inc.php
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class EditorialTimelineHelper
 * @brief Utility and formatting helpers for Editorial Timeline Manager.
 */

import('lib.pkp.classes.submission.SubmissionFile');
import('lib.pkp.classes.submission.reviewAssignment.ReviewAssignment');
import('classes.workflow.EditorDecisionActionsManager');

class EditorialTimelineHelper
{
    /**
     * Get the human-readable localized name for a file stage.
     *
     * @param int $fileStage
     * @return string
     */
    public static function getFileStageName($fileStage)
    {
        switch ((int) $fileStage) {
            case SUBMISSION_FILE_SUBMISSION:
                $val = __('plugins.generic.editorialTimelineManager.stage.submissionFiles');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('submission.submissionFiles');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Submission Files' : $val;

            case SUBMISSION_FILE_REVIEW_FILE:
                $val = __('plugins.generic.editorialTimelineManager.stage.reviewFiles');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.reviewFiles');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Review Files' : $val;

            case SUBMISSION_FILE_REVIEW_ATTACHMENT:
                $val = __('plugins.generic.editorialTimelineManager.stage.reviewAttachments');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('grid.reviewAttachments.title');
                }
                return empty($val) || strpos($val, '##') !== false ? "Reviewer's Attachments" : $val;

            case SUBMISSION_FILE_FINAL:
                $val = __('plugins.generic.editorialTimelineManager.stage.copyeditingDraftFiles');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('submission.copyeditingDraftFiles');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Copyediting Draft Files' : $val;

            case SUBMISSION_FILE_COPYEDIT:
                $val = __('plugins.generic.editorialTimelineManager.stage.copyeditedFiles');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('submission.copyeditedFiles');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Copyedited Files' : $val;

            case SUBMISSION_FILE_PROOF:
                $val = __('plugins.generic.editorialTimelineManager.stage.galleys');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('submission.layout.galleys');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Galleys' : $val;

            case SUBMISSION_FILE_PRODUCTION_READY:
                $val = __('plugins.generic.editorialTimelineManager.stage.productionReadyFiles');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('submission.productionReadyFiles');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Production Ready Files' : $val;

            case SUBMISSION_FILE_REVIEW_REVISION:
                $val = __('plugins.generic.editorialTimelineManager.stage.revisions');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.revisions');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Revisions' : $val;

            case SUBMISSION_FILE_QUERY:
                $val = __('plugins.generic.editorialTimelineManager.stage.discussions');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('submission.queries');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Discussions' : $val;

            default:
                $other = __('common.other');
                if (empty($other) || strpos($other, '##') !== false) {
                    $other = 'Stage';
                }
                return $other . ' (' . $fileStage . ')';
        }
    }

    /**
     * Get workflow stage label.
     *
     * @param int $stageId
     * @return string
     */
    public static function getWorkflowStageName($stageId)
    {
        switch ((int) $stageId) {
            case WORKFLOW_STAGE_ID_SUBMISSION:
                $val = __('plugins.generic.editorialTimelineManager.stage.submission');
                return empty($val) || strpos($val, '##') !== false ? 'Submission' : $val;

            case WORKFLOW_STAGE_ID_INTERNAL_REVIEW:
                $val = __('plugins.generic.editorialTimelineManager.stage.internalReview');
                return empty($val) || strpos($val, '##') !== false ? 'Internal Review' : $val;

            case WORKFLOW_STAGE_ID_EXTERNAL_REVIEW:
                $val = __('plugins.generic.editorialTimelineManager.stage.externalReview');
                return empty($val) || strpos($val, '##') !== false ? 'Review' : $val;

            case WORKFLOW_STAGE_ID_EDITING:
                $val = __('plugins.generic.editorialTimelineManager.stage.editing');
                return empty($val) || strpos($val, '##') !== false ? 'Copyediting' : $val;

            case WORKFLOW_STAGE_ID_PRODUCTION:
                $val = __('plugins.generic.editorialTimelineManager.stage.production');
                return empty($val) || strpos($val, '##') !== false ? 'Production' : $val;

            default:
                return 'Stage ' . $stageId;
        }
    }

    /**
     * Get editor decision label.
     *
     * @param int $decision
     * @return string
     */
    public static function getDecisionName($decision)
    {
        switch ((int) $decision) {
            case SUBMISSION_EDITOR_DECISION_ACCEPT:
                $val = __('plugins.generic.editorialTimelineManager.decision.accept');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.accept');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Accept Submission' : $val;

            case SUBMISSION_EDITOR_DECISION_PENDING_REVISIONS:
                $val = __('plugins.generic.editorialTimelineManager.decision.requestRevisions');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.requestRevisions');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Revisions Required' : $val;

            case SUBMISSION_EDITOR_DECISION_RESUBMIT:
                $val = __('plugins.generic.editorialTimelineManager.decision.resubmit');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.resubmit');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Resubmit for Review' : $val;

            case SUBMISSION_EDITOR_DECISION_DECLINE:
                $val = __('plugins.generic.editorialTimelineManager.decision.decline');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.decline');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Decline Submission' : $val;

            case SUBMISSION_EDITOR_DECISION_EXTERNAL_REVIEW:
                $val = __('plugins.generic.editorialTimelineManager.decision.sendExternalReview');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.sendExternalReview');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Send to Review' : $val;

            case SUBMISSION_EDITOR_DECISION_NEW_ROUND:
                $val = __('plugins.generic.editorialTimelineManager.decision.newRound');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.newRound');
                }
                return empty($val) || strpos($val, '##') !== false ? 'New Review Round' : $val;

            case SUBMISSION_EDITOR_DECISION_SEND_TO_PRODUCTION:
                $val = __('plugins.generic.editorialTimelineManager.decision.sendToProduction');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('editor.submission.decision.sendToProduction');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Send to Production' : $val;

            case SUBMISSION_EDITOR_DECISION_INITIAL_DECLINE:
                $val = __('editor.submission.decision.initialDecline');
                return empty($val) || strpos($val, '##') !== false ? 'Initial Decline' : $val;

            case SUBMISSION_EDITOR_DECISION_REVERT_DECLINE:
                $val = __('editor.submission.decision.revertDecline');
                return empty($val) || strpos($val, '##') !== false ? 'Revert Decline' : $val;

            default:
                return 'Decision (' . $decision . ')';
        }
    }

    /**
     * Get reviewer recommendation label.
     *
     * @param int|null $recommendation
     * @return string
     */
    public static function getRecommendationName($recommendation)
    {
        if ($recommendation === null || $recommendation === '') {
            $val = __('plugins.generic.editorialTimelineManager.none');
            if (empty($val) || strpos($val, '##') !== false) {
                $val = __('common.none');
            }
            return empty($val) || strpos($val, '##') !== false ? 'None' : $val;
        }

        switch ((int) $recommendation) {
            case SUBMISSION_REVIEWER_RECOMMENDATION_ACCEPT:
                $val = __('plugins.generic.editorialTimelineManager.recommendation.accept');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('reviewer.article.decision.accept');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Accept Submission' : $val;

            case SUBMISSION_REVIEWER_RECOMMENDATION_PENDING_REVISIONS:
                $val = __('plugins.generic.editorialTimelineManager.recommendation.revisions');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('reviewer.article.decision.pendingRevisions');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Revisions Required' : $val;

            case SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_HERE:
                $val = __('plugins.generic.editorialTimelineManager.recommendation.resubmit');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('reviewer.article.decision.resubmitHere');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Resubmit for Review' : $val;

            case SUBMISSION_REVIEWER_RECOMMENDATION_RESUBMIT_ELSEWHERE:
                $val = __('plugins.generic.editorialTimelineManager.recommendation.resubmitElsewhere');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('reviewer.article.decision.resubmitElsewhere');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Resubmit Elsewhere' : $val;

            case SUBMISSION_REVIEWER_RECOMMENDATION_DECLINE:
                $val = __('plugins.generic.editorialTimelineManager.recommendation.decline');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('reviewer.article.decision.decline');
                }
                return empty($val) || strpos($val, '##') !== false ? 'Decline Submission' : $val;

            case SUBMISSION_REVIEWER_RECOMMENDATION_SEE_COMMENTS:
                $val = __('plugins.generic.editorialTimelineManager.recommendation.seeComments');
                if (empty($val) || strpos($val, '##') !== false) {
                    $val = __('reviewer.article.decision.seeComments');
                }
                return empty($val) || strpos($val, '##') !== false ? 'See Comments' : $val;

            default:
                return 'Recommendation (' . $recommendation . ')';
        }
    }

    /**
     * Resolve and clean up a genre name, removing any ## or translating standard keys.
     *
     * @param string|null $genreName
     * @param int|null $genreId
     * @return string
     */
    public static function resolveGenreName($genreName, $genreId = null)
    {
        if (empty($genreName) || trim($genreName) === '') {
            $val = __('plugins.generic.editorialTimelineManager.none');
            if (empty($val) || strpos($val, '##') !== false) {
                $val = __('common.none');
            }
            return empty($val) || strpos($val, '##') !== false ? 'None' : $val;
        }

        $genreName = trim($genreName);

        // Check if string contains or is wrapped in ## (e.g. ##default.genres.other##)
        if (preg_match('/^##(.*)##$/', $genreName, $matches)) {
            $key = $matches[1];
            $trans = __($key);
            if (!empty($trans) && strpos($trans, '##') === false) {
                return $trans;
            }
            $genreName = $key;
        }

        // Map known PKP genre keys to plugin translations
        switch ($genreName) {
            case 'default.genres.article':
            case 'SUBMISSION_GENRE_DEFAULT_ARTICLE':
                $val = __('plugins.generic.editorialTimelineManager.genre.article');
                return empty($val) || strpos($val, '##') !== false ? 'Article Text' : $val;

            case 'default.genres.other':
            case 'SUBMISSION_GENRE_DEFAULT_OTHER':
                $val = __('plugins.generic.editorialTimelineManager.genre.other');
                return empty($val) || strpos($val, '##') !== false ? 'Other' : $val;

            case 'default.genres.researchInstrument':
                $val = __('plugins.generic.editorialTimelineManager.genre.researchInstrument');
                return empty($val) || strpos($val, '##') !== false ? 'Research Instrument' : $val;

            case 'default.genres.researchMaterials':
                $val = __('plugins.generic.editorialTimelineManager.genre.researchMaterials');
                return empty($val) || strpos($val, '##') !== false ? 'Research Materials' : $val;

            case 'default.genres.researchResults':
                $val = __('plugins.generic.editorialTimelineManager.genre.researchResults');
                return empty($val) || strpos($val, '##') !== false ? 'Research Results' : $val;

            case 'default.genres.transcripts':
                $val = __('plugins.generic.editorialTimelineManager.genre.transcripts');
                return empty($val) || strpos($val, '##') !== false ? 'Transcripts' : $val;

            case 'default.genres.dataAnalysis':
                $val = __('plugins.generic.editorialTimelineManager.genre.dataAnalysis');
                return empty($val) || strpos($val, '##') !== false ? 'Data Analysis' : $val;

            case 'default.genres.dataSet':
                $val = __('plugins.generic.editorialTimelineManager.genre.dataSet');
                return empty($val) || strpos($val, '##') !== false ? 'Data Set' : $val;

            case 'default.genres.sourceTexts':
                $val = __('plugins.generic.editorialTimelineManager.genre.sourceTexts');
                return empty($val) || strpos($val, '##') !== false ? 'Source Texts' : $val;
        }

        // Strip any residual ## and format
        if (strpos($genreName, '##') !== false) {
            $cleaned = trim($genreName, '#');
            $parts = explode('.', $cleaned);
            $last = end($parts);
            return ucfirst(preg_replace('/(?<!^)[A-Z]/', ' $0', $last));
        }

        return $genreName;
    }

    /**
     * Format date string for HTML5 datetime-local input (YYYY-MM-DDTHH:MM).
     *
     * @param string|null $datetime
     * @return string
     */
    public static function toHtmlDatetime($datetime)
    {
        if (empty($datetime)) {
            return '';
        }
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return '';
        }
        return date('Y-m-d\TH:i', $timestamp);
    }

    /**
     * Parse date string from HTML datetime-local input to standard DB datetime (YYYY-MM-DD HH:MM:SS).
     *
     * @param string|null $datetime
     * @return string|null
     */
    public static function fromHtmlDatetime($datetime)
    {
        if (empty($datetime) || trim($datetime) === '') {
            return null;
        }
        $cleaned = str_replace('T', ' ', trim($datetime));
        if (strlen($cleaned) === 16) {
            $cleaned .= ':00';
        }
        $timestamp = strtotime($cleaned);
        if ($timestamp === false) {
            return null;
        }
        return date('Y-m-d H:i:s', $timestamp);
    }

    /**
     * Format date for display.
     *
     * @param string|null $datetime
     * @param bool $includeTime
     * @return string
     */
    public static function formatDisplayDate($datetime, $includeTime = true)
    {
        if (empty($datetime)) {
            return '-';
        }
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return htmlspecialchars($datetime);
        }
        return date($includeTime ? 'M d, Y H:i' : 'M d, Y', $timestamp);
    }

    /**
     * Check review assignment chronology and return any warning messages.
     *
     * @param array $review
     * @return array
     */
    public static function checkReviewChronology($review)
    {
        $warnings = [];
        $assigned = !empty($review['date_assigned']) ? strtotime($review['date_assigned']) : null;
        $notified = !empty($review['date_notified']) ? strtotime($review['date_notified']) : null;
        $confirmed = !empty($review['date_confirmed']) ? strtotime($review['date_confirmed']) : null;
        $completed = !empty($review['date_completed']) ? strtotime($review['date_completed']) : null;
        $acknowledged = !empty($review['date_acknowledged']) ? strtotime($review['date_acknowledged']) : null;

        if ($assigned && $notified && $notified < $assigned) {
            $warnings[] = __('plugins.generic.editorialTimelineManager.warning.notifiedBeforeAssigned');
        }
        if ($notified && $confirmed && $confirmed < $notified) {
            $warnings[] = __('plugins.generic.editorialTimelineManager.warning.confirmedBeforeNotified');
        }
        if ($assigned && $completed && $completed < $assigned) {
            $warnings[] = __('plugins.generic.editorialTimelineManager.warning.completedBeforeAssigned');
        }
        if ($confirmed && $completed && $completed < $confirmed) {
            $warnings[] = __('plugins.generic.editorialTimelineManager.warning.completedBeforeConfirmed');
        }
        if ($completed && $acknowledged && $acknowledged < $completed) {
            $warnings[] = __('plugins.generic.editorialTimelineManager.warning.acknowledgedBeforeCompleted');
        }

        return $warnings;
    }
}
