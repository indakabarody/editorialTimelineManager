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

    /**
     * Check if the current user is authorized as Site Admin or Journal Manager.
     *
     * @param PKPRequest|null $request
     * @param int|null $contextId
     * @return bool
     */
    public static function isUserAuthorizedManagerOrAdmin($request = null, $contextId = null)
    {
        if (!$request) {
            $request = Application::get()->getRequest();
        }
        $user = $request ? $request->getUser() : null;
        if (!$user) {
            return false;
        }

        // 1. Check Site Administrator (global)
        import('lib.pkp.classes.security.Validation');
        if (Validation::isSiteAdmin()) {
            return true;
        }

        $roleDao = DAORegistry::getDAO('RoleDAO');
        $siteContextId = defined('CONTEXT_SITE') ? CONTEXT_SITE : 0;
        if ($roleDao && $roleDao->userHasRole($siteContextId, $user->getId(), ROLE_ID_SITE_ADMIN)) {
            return true;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole([ROLE_ID_SITE_ADMIN], $siteContextId)) {
            return true;
        }

        // 2. Resolve Context ID for Journal Manager verification
        if ($contextId === null) {
            $context = $request ? $request->getContext() : null;
            $contextId = $context ? (int) $context->getId() : (defined('CONTEXT_ID_NONE') ? CONTEXT_ID_NONE : 0);
        } else {
            $contextId = (int) $contextId;
        }

        // Fallback: If contextId is not available from request context, try getting from submission in request
        if ($contextId <= 0 && $request) {
            $submissionId = (int) ($request->getUserVar('submissionId') ?: $request->getUserVar('articleId'));
            if ($submissionId > 0) {
                $submissionDao = DAORegistry::getDAO('SubmissionDAO');
                $submission = $submissionDao ? $submissionDao->getById($submissionId) : null;
                if ($submission && method_exists($submission, 'getContextId')) {
                    $contextId = (int) $submission->getContextId();
                }
            }
        }

        // 3. Specifically check whether the user is enrolled as Journal Manager (and NOT Journal Editor or Production Editor)
        return self::isUserSpecificallyJournalManager($user, $contextId);
    }

    /**
     * Check whether a user belongs specifically to the Journal Manager user group
     * (and strictly exclude Journal Editor, Production Editor, Section Editor, etc.).
     *
     * @param PKPUser $user
     * @param int $contextId (0 for any context)
     * @return bool
     */
    public static function isUserSpecificallyJournalManager($user, $contextId = 0)
    {
        if (!$user) {
            return false;
        }

        $userId = (int) $user->getId();
        $contextId = (int) $contextId;

        $userGroupDao = DAORegistry::getDAO('UserGroupDAO');
        if ($userGroupDao) {
            $params = [$userId];
            $sql =
                'SELECT ug.user_group_id, ug.role_id, ug.context_id,
                           ugs_key.setting_value AS name_locale_key,
                           ugs_name.setting_value AS group_name,
                           ugs_abbrev.setting_value AS group_abbrev
                    FROM user_groups ug
                    JOIN user_user_groups uug ON (ug.user_group_id = uug.user_group_id)
                    LEFT JOIN user_group_settings ugs_key ON (ugs_key.user_group_id = ug.user_group_id AND ugs_key.setting_name = \'nameLocaleKey\')
                    LEFT JOIN user_group_settings ugs_name ON (ugs_name.user_group_id = ug.user_group_id AND ugs_name.setting_name = \'name\')
                    LEFT JOIN user_group_settings ugs_abbrev ON (ugs_abbrev.user_group_id = ug.user_group_id AND ugs_abbrev.setting_name = \'abbrev\')
                    WHERE uug.user_id = ? AND ug.role_id = ' . (int) ROLE_ID_MANAGER;

            if ($contextId > 0) {
                $sql .= ' AND ug.context_id = ?';
                $params[] = $contextId;
            }

            try {
                $result = $userGroupDao->retrieve($sql, $params);
                if ($result) {
                    foreach ($result as $row) {
                        $key = strtolower(trim((string) ($row->name_locale_key ?? '')));
                        $name = strtolower(trim((string) ($row->group_name ?? '')));
                        $abbrev = strtoupper(trim((string) ($row->group_abbrev ?? '')));

                        // Explicit exclusions: Journal Editor, Production Editor, Section Editor
                        if ($key === 'default.groups.name.editor' || $key === 'default.groups.name.productioneditor' || $key === 'default.groups.name.sectioneditor' || $key === 'default.groups.name.guesteditor' || $abbrev === 'JE' || $abbrev === 'PE' || $abbrev === 'SE' || $abbrev === 'GE') {
                            continue;
                        }

                        // If group name has 'editor' without 'manager'/'manajer', exclude it
                        if (strpos($name, 'editor') !== false && strpos($name, 'manager') === false && strpos($name, 'manajer') === false) {
                            continue;
                        }

                        // Positive matches for Journal Manager
                        if ($key === 'default.groups.name.manager' || $abbrev === 'JM' || strpos($name, 'journal manager') !== false || strpos($name, 'manajer jurnal') !== false || strpos($name, 'pengelola jurnal') !== false || strpos($name, 'manager') !== false || strpos($name, 'manajer') !== false) {
                            return true;
                        }

                        // If key is empty and name doesn't specify editor, check assigned workflow stages
                        if (method_exists($userGroupDao, 'getAssignedStagesByUserGroupId')) {
                            $stages = $userGroupDao->getAssignedStagesByUserGroupId((int) $row->context_id, (int) $row->user_group_id);
                            if (empty($stages)) {
                                return true;
                            }
                        }
                    }
                }
            } catch (Exception $e) {
                // Fallback to ORM
            }
        }

        // ORM Fallback
        if ($userGroupDao && method_exists($userGroupDao, 'getByUserId')) {
            $userGroups = $contextId > 0 ? $userGroupDao->getByUserId($userId, $contextId) : $userGroupDao->getByUserId($userId);
            if ($userGroups) {
                while ($ug = $userGroups->next()) {
                    if ((int) $ug->getRoleId() !== ROLE_ID_MANAGER) {
                        continue;
                    }

                    $key = strtolower(trim((string) $ug->getData('nameLocaleKey')));
                    if (empty($key) && method_exists($userGroupDao, 'getSetting')) {
                        $key = strtolower(trim((string) $userGroupDao->getSetting($ug->getId(), 'nameLocaleKey')));
                    }

                    if ($key === 'default.groups.name.editor' || $key === 'default.groups.name.productioneditor' || $key === 'default.groups.name.sectioneditor' || $key === 'default.groups.name.guesteditor') {
                        continue;
                    }

                    $locName = strtolower(trim((string) $ug->getLocalizedName()));
                    $locAbbrev = strtoupper(trim((string) $ug->getLocalizedAbbrev()));

                    if ($locAbbrev === 'JE' || $locAbbrev === 'PE' || $locAbbrev === 'SE' || $locAbbrev === 'GE') {
                        continue;
                    }

                    if (strpos($locName, 'editor') !== false && strpos($locName, 'manager') === false && strpos($locName, 'manajer') === false) {
                        continue;
                    }

                    if ($key === 'default.groups.name.manager' || $locAbbrev === 'JM' || strpos($locName, 'journal manager') !== false || strpos($locName, 'manajer jurnal') !== false || strpos($locName, 'manager') !== false || strpos($locName, 'manajer') !== false) {
                        return true;
                    }

                    if (method_exists($userGroupDao, 'getAssignedStagesByUserGroupId')) {
                        $stages = $userGroupDao->getAssignedStagesByUserGroupId((int) $ug->getContextId(), (int) $ug->getId());
                        if (empty($stages)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }
}
