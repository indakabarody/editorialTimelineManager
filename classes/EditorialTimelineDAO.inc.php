<?php

/**
 * @file plugins/generic/editorialTimelineManager/classes/EditorialTimelineDAO.inc.php
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class EditorialTimelineDAO
 * @brief Data access and management operations for Editorial Timeline Manager.
 */

import('lib.pkp.classes.db.DAO');
import('plugins.generic.editorialTimelineManager.classes.EditorialTimelineHelper');

use Illuminate\Database\Capsule\Manager as Capsule;

class EditorialTimelineDAO extends DAO
{
    /**
     * Get comprehensive timeline data for a submission.
     *
     * @param int $submissionId
     * @param int $contextId
     * @return array|null Returns array of timeline records or null if submission not found.
     */
    public function getTimelineData($submissionId, $contextId)
    {
        $submissionId = (int) $submissionId;
        $contextId = (int) $contextId;

        // 1. Fetch submission record
        $submissionRow = Capsule::table('submissions')->where('submission_id', '=', $submissionId)->where('context_id', '=', $contextId)->first();

        if (!$submissionRow) {
            return null;
        }

        $currentLocale = AppLocale::getLocale();
        $primaryLocale = 'en_US';
        $request = Application::get()->getRequest();
        $context = $request ? $request->getContext() : null;
        if ($context) {
            $primaryLocale = $context->getPrimaryLocale();
        }

        // Retrieve submission title from current publication or latest publication in publication_settings
        $submissionTitle = null;
        if (!empty($submissionRow->current_publication_id)) {
            $titleRows = Capsule::table('publication_settings')->where('publication_id', '=', $submissionRow->current_publication_id)->where('setting_name', '=', 'title')->get();
            $titleByLoc = [];
            foreach ($titleRows as $tr) {
                $titleByLoc[$tr->locale] = $tr->setting_value;
            }
            if (isset($titleByLoc[$currentLocale]) && !empty($titleByLoc[$currentLocale])) {
                $submissionTitle = $titleByLoc[$currentLocale];
            } elseif (isset($titleByLoc[$primaryLocale]) && !empty($titleByLoc[$primaryLocale])) {
                $submissionTitle = $titleByLoc[$primaryLocale];
            } elseif (!empty($titleByLoc)) {
                $submissionTitle = reset($titleByLoc);
            }
        }
        if (!$submissionTitle) {
            $latestPub = Capsule::table('publications')->where('submission_id', '=', $submissionId)->orderBy('version', 'desc')->first();
            if ($latestPub) {
                $titleRows = Capsule::table('publication_settings')->where('publication_id', '=', $latestPub->publication_id)->where('setting_name', '=', 'title')->get();
                $titleByLoc = [];
                foreach ($titleRows as $tr) {
                    $titleByLoc[$tr->locale] = $tr->setting_value;
                }
                if (isset($titleByLoc[$currentLocale]) && !empty($titleByLoc[$currentLocale])) {
                    $submissionTitle = $titleByLoc[$currentLocale];
                } elseif (isset($titleByLoc[$primaryLocale]) && !empty($titleByLoc[$primaryLocale])) {
                    $submissionTitle = $titleByLoc[$primaryLocale];
                } elseif (!empty($titleByLoc)) {
                    $submissionTitle = reset($titleByLoc);
                }
            }
        }
        if (!$submissionTitle) {
            $submissionTitle = __('common.untitled') ?: 'Untitled';
        }

        // 2. Fetch publication record(s)
        $publicationRows = Capsule::table('publications')->where('submission_id', '=', $submissionId)->orderBy('version', 'desc')->get();

        $publications = [];
        foreach ($publicationRows as $pub) {
            $pubTitleRows = Capsule::table('publication_settings')->where('publication_id', '=', $pub->publication_id)->where('setting_name', '=', 'title')->get();
            $pubTitleByLoc = [];
            foreach ($pubTitleRows as $ptr) {
                $pubTitleByLoc[$ptr->locale] = $ptr->setting_value;
            }
            $pubTitle = null;
            if (isset($pubTitleByLoc[$currentLocale]) && !empty($pubTitleByLoc[$currentLocale])) {
                $pubTitle = $pubTitleByLoc[$currentLocale];
            } elseif (isset($pubTitleByLoc[$primaryLocale]) && !empty($pubTitleByLoc[$primaryLocale])) {
                $pubTitle = $pubTitleByLoc[$primaryLocale];
            } elseif (!empty($pubTitleByLoc)) {
                $pubTitle = reset($pubTitleByLoc);
            }

            $publications[] = [
                'publication_id' => (int) $pub->publication_id,
                'version' => (int) $pub->version,
                'status' => (int) $pub->status,
                'date_published' => $pub->date_published,
                'last_modified' => $pub->last_modified,
                'title' => $pubTitle ?: $submissionTitle,
            ];
        }

        // 3. Fetch submission files across all stages (No settings joins to prevent row multiplication)
        $fileRows = Capsule::table('submission_files as sf')
            ->leftJoin('users as u', 'sf.uploader_user_id', '=', 'u.user_id')
            ->where('sf.submission_id', '=', $submissionId)
            ->select(['sf.submission_file_id', 'sf.file_id', 'sf.file_stage', 'sf.genre_id', 'sf.created_at', 'sf.updated_at', 'sf.uploader_user_id', 'sf.assoc_type', 'sf.assoc_id', 'u.username as uploader_username'])
            ->orderBy('sf.file_stage', 'asc')
            ->orderBy('sf.created_at', 'asc')
            ->orderBy('sf.submission_file_id', 'asc')
            ->get();

        $fileIds = [];
        $genreIds = [];
        foreach ($fileRows as $f) {
            $fileIds[] = (int) $f->submission_file_id;
            if (!empty($f->genre_id)) {
                $genreIds[] = (int) $f->genre_id;
            }
        }

        // Fetch file names with locale priority
        $fileNames = [];
        if (!empty($fileIds)) {
            $nameRows = Capsule::table('submission_file_settings')->whereIn('submission_file_id', $fileIds)->where('setting_name', '=', 'name')->get();
            $namesByLoc = [];
            foreach ($nameRows as $nr) {
                $namesByLoc[$nr->submission_file_id][$nr->locale] = $nr->setting_value;
            }
            foreach ($fileIds as $fid) {
                if (isset($namesByLoc[$fid])) {
                    if (isset($namesByLoc[$fid][$currentLocale]) && !empty($namesByLoc[$fid][$currentLocale])) {
                        $fileNames[$fid] = $namesByLoc[$fid][$currentLocale];
                    } elseif (isset($namesByLoc[$fid][$primaryLocale]) && !empty($namesByLoc[$fid][$primaryLocale])) {
                        $fileNames[$fid] = $namesByLoc[$fid][$primaryLocale];
                    } elseif (!empty($namesByLoc[$fid])) {
                        $fileNames[$fid] = reset($namesByLoc[$fid]);
                    }
                }
            }
        }

        // Fetch localized genre names with locale priority
        $genreMap = [];
        $genreIds = array_values(array_unique($genreIds));
        if (!empty($genreIds)) {
            try {
                $genreDao = DAORegistry::getDAO('GenreDAO');
                if ($genreDao) {
                    $allGenres = $genreDao->getByContextId($contextId)->toArray();
                    foreach ($allGenres as $g) {
                        $genreMap[$g->getId()] = $g->getLocalizedName();
                    }
                }
            } catch (Exception $e) {
                // Fallback to table lookup
            }

            $missingGenreIds = array_diff($genreIds, array_keys($genreMap));
            if (!empty($missingGenreIds)) {
                $gsRows = Capsule::table('genre_settings')->whereIn('genre_id', $missingGenreIds)->where('setting_name', '=', 'name')->get();
                $genresByLoc = [];
                foreach ($gsRows as $gr) {
                    $genresByLoc[$gr->genre_id][$gr->locale] = $gr->setting_value;
                }
                foreach ($missingGenreIds as $gid) {
                    if (isset($genresByLoc[$gid])) {
                        if (isset($genresByLoc[$gid][$currentLocale]) && !empty($genresByLoc[$gid][$currentLocale])) {
                            $genreMap[$gid] = $genresByLoc[$gid][$currentLocale];
                        } elseif (isset($genresByLoc[$gid][$primaryLocale]) && !empty($genresByLoc[$gid][$primaryLocale])) {
                            $genreMap[$gid] = $genresByLoc[$gid][$primaryLocale];
                        } elseif (!empty($genresByLoc[$gid])) {
                            $genreMap[$gid] = reset($genresByLoc[$gid]);
                        }
                    }
                }
            }
        }

        $files = [];
        $stageCounts = [];
        foreach ($fileRows as $file) {
            $stageName = EditorialTimelineHelper::getFileStageName($file->file_stage);
            $reviewerInfo = null;

            // If reviewer attachment, find associated reviewer
            if ($file->file_stage == SUBMISSION_FILE_REVIEW_ATTACHMENT && $file->assoc_id) {
                $reviewRow = Capsule::table('review_assignments as ra')
                    ->leftJoin('users as u', 'ra.reviewer_id', '=', 'u.user_id')
                    ->where('ra.review_id', '=', $file->assoc_id)
                    ->select(['ra.review_id', 'ra.reviewer_id', 'u.username'])
                    ->first();

                if ($reviewRow) {
                    $reviewerInfo = [
                        'review_id' => (int) $reviewRow->review_id,
                        'reviewer_id' => (int) $reviewRow->reviewer_id,
                        'reviewer_name' => $reviewRow->username,
                    ];
                }
            }

            $rawGenre = isset($genreMap[$file->genre_id]) ? $genreMap[$file->genre_id] : null;
            $genreName = EditorialTimelineHelper::resolveGenreName($rawGenre, $file->genre_id);
            $filename = isset($fileNames[$file->submission_file_id]) ? $fileNames[$file->submission_file_id] : 'File #' . $file->submission_file_id;

            $files[] = [
                'submission_file_id' => (int) $file->submission_file_id,
                'file_id' => (int) $file->file_id,
                'file_stage' => (int) $file->file_stage,
                'file_stage_name' => $stageName,
                'genre_name' => $genreName,
                'filename' => $filename,
                'created_at' => $file->created_at,
                'updated_at' => $file->updated_at,
                'uploader_username' => $file->uploader_username ?: '-',
                'reviewer_info' => $reviewerInfo,
            ];

            if (!isset($stageCounts[$file->file_stage])) {
                $stageCounts[$file->file_stage] = 0;
            }
            $stageCounts[$file->file_stage]++;
        }

        // 4. Fetch review assignments
        $reviewRows = Capsule::table('review_assignments as ra')
            ->leftJoin('users as u', 'ra.reviewer_id', '=', 'u.user_id')
            ->where('ra.submission_id', '=', $submissionId)
            ->select(['ra.review_id', 'ra.reviewer_id', 'ra.review_round_id', 'ra.stage_id', 'ra.recommendation', 'ra.declined', 'ra.cancelled', 'ra.date_assigned', 'ra.date_notified', 'ra.date_confirmed', 'ra.date_due', 'ra.date_completed', 'ra.date_acknowledged', 'ra.last_modified', 'u.username as reviewer_username'])
            ->orderBy('ra.review_round_id', 'asc')
            ->orderBy('ra.review_id', 'asc')
            ->get();

        $reviews = [];
        foreach ($reviewRows as $rev) {
            $revData = [
                'review_id' => (int) $rev->review_id,
                'reviewer_id' => (int) $rev->reviewer_id,
                'reviewer_name' => $rev->reviewer_username ?: 'Reviewer #' . $rev->reviewer_id,
                'review_round_id' => (int) $rev->review_round_id,
                'recommendation' => $rev->recommendation,
                'recommendation_name' => EditorialTimelineHelper::getRecommendationName($rev->recommendation),
                'declined' => (bool) $rev->declined,
                'cancelled' => (bool) $rev->cancelled,
                'date_assigned' => $rev->date_assigned,
                'date_notified' => $rev->date_notified,
                'date_confirmed' => $rev->date_confirmed,
                'date_due' => $rev->date_due,
                'date_completed' => $rev->date_completed,
                'date_acknowledged' => $rev->date_acknowledged,
                'last_modified' => $rev->last_modified,
            ];
            $revData['chronology_warnings'] = EditorialTimelineHelper::checkReviewChronology($revData);
            $reviews[] = $revData;
        }

        // 5. Fetch editorial decisions
        $decisionRows = Capsule::table('edit_decisions as ed')
            ->leftJoin('users as u', 'ed.editor_id', '=', 'u.user_id')
            ->where('ed.submission_id', '=', $submissionId)
            ->select(['ed.edit_decision_id', 'ed.editor_id', 'ed.decision', 'ed.date_decided', 'ed.review_round_id', 'ed.stage_id', 'ed.round', 'u.username as editor_username'])
            ->orderBy('ed.date_decided', 'asc')
            ->get();

        $decisions = [];
        foreach ($decisionRows as $dec) {
            $decisions[] = [
                'edit_decision_id' => (int) $dec->edit_decision_id,
                'editor_id' => (int) $dec->editor_id,
                'editor_name' => $dec->editor_username ?: 'Editor #' . $dec->editor_id,
                'decision' => (int) $dec->decision,
                'decision_name' => EditorialTimelineHelper::getDecisionName($dec->decision),
                'stage_id' => (int) $dec->stage_id,
                'stage_name' => EditorialTimelineHelper::getWorkflowStageName($dec->stage_id),
                'round' => (int) $dec->round,
                'date_decided' => $dec->date_decided,
            ];
        }

        // 6. Fetch stage discussions (queries) and notes
        $queryRows = Capsule::table('queries')
            ->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', '=', $submissionId)
            ->orderBy('stage_id', 'asc')
            ->orderBy('date_posted', 'asc')
            ->get();

        $discussions = [];
        foreach ($queryRows as $q) {
            // Fetch associated notes for this query
            $noteRows = Capsule::table('notes as n')
                ->leftJoin('users as u', 'n.user_id', '=', 'u.user_id')
                ->where('n.assoc_type', '=', ASSOC_TYPE_QUERY)
                ->where('n.assoc_id', '=', $q->query_id)
                ->select(['n.note_id', 'n.user_id', 'n.date_created', 'n.date_modified', 'n.title', 'n.contents', 'u.username as author_username'])
                ->orderBy('n.date_created', 'asc')
                ->get();

            $notes = [];
            $queryTitle = '';
            foreach ($noteRows as $note) {
                if (empty($queryTitle) && !empty($note->title)) {
                    $queryTitle = $note->title;
                }
                $notes[] = [
                    'note_id' => (int) $note->note_id,
                    'user_id' => (int) $note->user_id,
                    'author_name' => $note->author_username ?: '-',
                    'date_created' => $note->date_created,
                    'date_modified' => $note->date_modified,
                    'title' => $note->title,
                    'snippet' => substr(strip_tags($note->contents), 0, 80),
                ];
            }

            $discussions[] = [
                'query_id' => (int) $q->query_id,
                'stage_id' => (int) $q->stage_id,
                'stage_name' => EditorialTimelineHelper::getWorkflowStageName($q->stage_id),
                'title' => $queryTitle ?: 'Discussion #' . $q->query_id,
                'date_posted' => $q->date_posted,
                'date_modified' => $q->date_modified,
                'closed' => (bool) $q->closed,
                'notes' => $notes,
            ];
        }

        return [
            'submission' => [
                'submission_id' => (int) $submissionRow->submission_id,
                'title' => $submissionTitle,
                'context_id' => (int) $submissionRow->context_id,
                'stage_id' => (int) $submissionRow->stage_id,
                'stage_name' => EditorialTimelineHelper::getWorkflowStageName($submissionRow->stage_id),
                'status' => (int) $submissionRow->status,
                'date_submitted' => $submissionRow->date_submitted,
                'date_last_activity' => $submissionRow->date_last_activity,
                'last_modified' => $submissionRow->last_modified,
                'current_publication_id' => (int) $submissionRow->current_publication_id,
            ],
            'publications' => $publications,
            'files' => $files,
            'stage_counts' => $stageCounts,
            'reviews' => $reviews,
            'decisions' => $decisions,
            'discussions' => $discussions,
            'activity_logs' => $this->getActivityLogs($submissionId, $contextId),
            'journal_users' => $this->getJournalUsers($contextId, $submissionId),
        ];
    }

    /**
     * Search submissions in the context for quick selection.
     *
     * @param int $contextId
     * @param string $searchQuery
     * @param int $limit
     * @return array
     */
    public function searchSubmissions($contextId, $searchQuery = '', $limit = 20)
    {
        $contextId = (int) $contextId;
        $currentLocale = AppLocale::getLocale();
        $primaryLocale = 'en_US';
        $request = Application::get()->getRequest();
        $context = $request ? $request->getContext() : null;
        if ($context) {
            $primaryLocale = $context->getPrimaryLocale();
        }

        $q = Capsule::table('submissions as s')
            ->leftJoin('publications as p', 's.current_publication_id', '=', 'p.publication_id')
            ->leftJoin('publication_settings as ps', function ($join) {
                $join->on('p.publication_id', '=', 'ps.publication_id')->where('ps.setting_name', '=', 'title');
            })
            ->where('s.context_id', '=', $contextId);

        if (!empty($searchQuery)) {
            if (is_numeric($searchQuery)) {
                $q->where(function ($sub) use ($searchQuery) {
                    $sub->where('s.submission_id', '=', (int) $searchQuery)->orWhere('ps.setting_value', 'LIKE', '%' . $searchQuery . '%');
                });
            } else {
                $q->where('ps.setting_value', 'LIKE', '%' . $searchQuery . '%');
            }
        }

        $rows = $q
            ->select(['s.submission_id', 's.date_submitted', 's.stage_id', 's.status', 's.current_publication_id'])
            ->groupBy(['s.submission_id', 's.date_submitted', 's.stage_id', 's.status', 's.current_publication_id'])
            ->orderBy('s.submission_id', 'desc')
            ->limit($limit)
            ->get();

        $subIds = [];
        $pubIds = [];
        foreach ($rows as $r) {
            $subIds[] = (int) $r->submission_id;
            if (!empty($r->current_publication_id)) {
                $pubIds[] = (int) $r->current_publication_id;
            }
        }

        $titlesByPubId = [];
        if (!empty($pubIds)) {
            $psRows = Capsule::table('publication_settings')->whereIn('publication_id', $pubIds)->where('setting_name', '=', 'title')->get();
            $grouped = [];
            foreach ($psRows as $psr) {
                $grouped[$psr->publication_id][$psr->locale] = $psr->setting_value;
            }
            foreach ($pubIds as $pid) {
                if (isset($grouped[$pid])) {
                    if (isset($grouped[$pid][$currentLocale]) && !empty($grouped[$pid][$currentLocale])) {
                        $titlesByPubId[$pid] = $grouped[$pid][$currentLocale];
                    } elseif (isset($grouped[$pid][$primaryLocale]) && !empty($grouped[$pid][$primaryLocale])) {
                        $titlesByPubId[$pid] = $grouped[$pid][$primaryLocale];
                    } elseif (!empty($grouped[$pid])) {
                        $titlesByPubId[$pid] = reset($grouped[$pid]);
                    }
                }
            }
        }

        $results = [];
        foreach ($rows as $row) {
            $title = null;
            if (!empty($row->current_publication_id) && isset($titlesByPubId[$row->current_publication_id])) {
                $title = $titlesByPubId[$row->current_publication_id];
            }
            if (empty($title)) {
                $fb = Capsule::table('publications as fb_p')
                    ->join('publication_settings as fb_ps', 'fb_p.publication_id', '=', 'fb_ps.publication_id')
                    ->where('fb_p.submission_id', '=', $row->submission_id)
                    ->where('fb_ps.setting_name', '=', 'title')
                    ->orderBy('fb_p.version', 'desc')
                    ->select(['fb_ps.setting_value', 'fb_ps.locale'])
                    ->get();
                $fbGrouped = [];
                foreach ($fb as $fbr) {
                    $fbGrouped[$fbr->locale] = $fbr->setting_value;
                }
                if (isset($fbGrouped[$currentLocale])) {
                    $title = $fbGrouped[$currentLocale];
                } elseif (isset($fbGrouped[$primaryLocale])) {
                    $title = $fbGrouped[$primaryLocale];
                } elseif (!empty($fbGrouped)) {
                    $title = reset($fbGrouped);
                }
            }
            if (empty($title)) {
                $title = __('common.untitled') ?: 'Untitled';
            }

            $results[] = [
                'submission_id' => (int) $row->submission_id,
                'title' => $title,
                'date_submitted' => $row->date_submitted,
                'stage_name' => EditorialTimelineHelper::getWorkflowStageName($row->stage_id),
            ];
        }
        return $results;
    }

    /**
     * Apply all timeline changes inside an atomic database transaction.
     *
     * @param int $submissionId
     * @param int $contextId
     * @param array $payload
     * @param int $userId
     * @return array Array with success boolean and affected counters.
     */
    public function saveTimelineChanges($submissionId, $contextId, $payload, $userId)
    {
        $submissionId = (int) $submissionId;
        $contextId = (int) $contextId;

        return Capsule::connection()->transaction(function () use ($submissionId, $contextId, $payload, $userId) {
            $affected = [
                'submission' => 0,
                'publications' => 0,
                'files' => 0,
                'reviews' => 0,
                'decisions' => 0,
                'discussions' => 0,
                'activity_logs' => 0,
            ];

            // 1. Verify submission ownership
            $sub = Capsule::table('submissions')->where('submission_id', '=', $submissionId)->where('context_id', '=', $contextId)->first();

            if (!$sub) {
                throw new Exception('Submission not found or permission denied.');
            }

            // 2. Update Submission date
            if (isset($payload['submission']['date_submitted'])) {
                $dateSubmitted = EditorialTimelineHelper::fromHtmlDatetime($payload['submission']['date_submitted']);
                Capsule::table('submissions')
                    ->where('submission_id', '=', $submissionId)
                    ->update(['date_submitted' => $dateSubmitted]);
                $affected['submission']++;
            }

            // 3. Update Publication dates
            if (!empty($payload['publications']) && is_array($payload['publications'])) {
                foreach ($payload['publications'] as $pubId => $pubData) {
                    $pubUpdates = [];
                    if (array_key_exists('date_published', $pubData)) {
                        $pubUpdates['date_published'] = !empty($pubData['date_published']) ? date('Y-m-d', strtotime($pubData['date_published'])) : null;
                    }
                    if (!empty($pubUpdates)) {
                        Capsule::table('publications')->where('publication_id', '=', (int) $pubId)->where('submission_id', '=', $submissionId)->update($pubUpdates);
                        $affected['publications']++;
                    }
                }
            }

            // 4. Update Files
            // A) Bulk stage update if provided
            if (!empty($payload['files_bulk']['target_stage']) && !empty($payload['files_bulk']['created_at'])) {
                $targetStage = $payload['files_bulk']['target_stage'];
                $bulkDate = EditorialTimelineHelper::fromHtmlDatetime($payload['files_bulk']['created_at']);
                $syncUpdated = !empty($payload['files_bulk']['sync_updated_at']);

                if ($bulkDate) {
                    $bulkQuery = Capsule::table('submission_files')->where('submission_id', '=', $submissionId);

                    if ($targetStage !== 'ALL') {
                        $bulkQuery->where('file_stage', '=', (int) $targetStage);
                    }

                    $bulkUpdateData = ['created_at' => $bulkDate];
                    if ($syncUpdated) {
                        $bulkUpdateData['updated_at'] = $bulkDate;
                    }

                    $count = $bulkQuery->update($bulkUpdateData);
                    $affected['files'] += $count;
                }
            }

            // B) Individual file updates
            if (!empty($payload['files']) && is_array($payload['files'])) {
                foreach ($payload['files'] as $fileId => $fileData) {
                    $fileUpdates = [];
                    if (isset($fileData['created_at'])) {
                        $fileUpdates['created_at'] = EditorialTimelineHelper::fromHtmlDatetime($fileData['created_at']);
                    }
                    if (isset($fileData['updated_at']) && !empty($fileData['updated_at'])) {
                        $fileUpdates['updated_at'] = EditorialTimelineHelper::fromHtmlDatetime($fileData['updated_at']);
                    }

                    if (!empty($fileUpdates)) {
                        $c = Capsule::table('submission_files')->where('submission_file_id', '=', (int) $fileId)->where('submission_id', '=', $submissionId)->update($fileUpdates);
                        $affected['files'] += $c;
                    }
                }
            }

            // 5. Update Review Assignments
            if (!empty($payload['reviews']) && is_array($payload['reviews'])) {
                foreach ($payload['reviews'] as $revId => $revData) {
                    $revUpdates = [];
                    $dateFields = ['date_assigned', 'date_notified', 'date_confirmed', 'date_due', 'date_completed', 'date_acknowledged'];

                    foreach ($dateFields as $f) {
                        if (array_key_exists($f, $revData)) {
                            $revUpdates[$f] = EditorialTimelineHelper::fromHtmlDatetime($revData[$f]);
                        }
                    }

                    if (!empty($revUpdates)) {
                        $c = Capsule::table('review_assignments')->where('review_id', '=', (int) $revId)->where('submission_id', '=', $submissionId)->update($revUpdates);
                        $affected['reviews'] += $c;
                    }
                }
            }

            // 6. Update Editorial Decisions
            if (!empty($payload['decisions']) && is_array($payload['decisions'])) {
                foreach ($payload['decisions'] as $decId => $decData) {
                    if (isset($decData['date_decided'])) {
                        $dateDecided = EditorialTimelineHelper::fromHtmlDatetime($decData['date_decided']);
                        $c = Capsule::table('edit_decisions')
                            ->where('edit_decision_id', '=', (int) $decId)
                            ->where('submission_id', '=', $submissionId)
                            ->update(['date_decided' => $dateDecided]);
                        $affected['decisions'] += $c;
                    }
                }
            }

            // 7. Update Discussions & Notes
            if (!empty($payload['discussions']) && is_array($payload['discussions'])) {
                foreach ($payload['discussions'] as $qId => $qData) {
                    if (isset($qData['date_posted'])) {
                        $datePosted = EditorialTimelineHelper::fromHtmlDatetime($qData['date_posted']);
                        $c = Capsule::table('queries')
                            ->where('query_id', '=', (int) $qId)
                            ->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION)
                            ->where('assoc_id', '=', $submissionId)
                            ->update(['date_posted' => $datePosted]);
                        $affected['discussions'] += $c;
                    }

                    if (!empty($qData['notes']) && is_array($qData['notes'])) {
                        foreach ($qData['notes'] as $noteId => $noteData) {
                            $noteUpdates = [];
                            if (isset($noteData['date_created'])) {
                                $noteUpdates['date_created'] = EditorialTimelineHelper::fromHtmlDatetime($noteData['date_created']);
                            }
                            if (!empty($noteUpdates)) {
                                Capsule::table('notes')
                                    ->where('note_id', '=', (int) $noteId)
                                    ->where('assoc_type', '=', ASSOC_TYPE_QUERY)
                                    ->where('assoc_id', '=', (int) $qId)
                                    ->update($noteUpdates);
                            }
                        }
                    }
                }
            }

            // 8. Update Activity Logs
            if (!empty($payload['activity_logs']) && is_array($payload['activity_logs'])) {
                foreach ($payload['activity_logs'] as $logId => $logData) {
                    $logUpdates = [];
                    if (isset($logData['date_logged']) && !empty($logData['date_logged'])) {
                        $logUpdates['date_logged'] = EditorialTimelineHelper::fromHtmlDatetime($logData['date_logged']);
                    }
                    if (isset($logData['user_id'])) {
                        $newUserId = (int) $logData['user_id'];
                        $logUpdates['user_id'] = $newUserId;

                        $userRow = Capsule::table('users')->where('user_id', '=', $newUserId)->first();
                        if ($userRow) {
                            Capsule::table('event_log_settings')->updateOrInsert(['log_id' => (int) $logId, 'setting_name' => 'username'], ['setting_value' => $userRow->username, 'setting_type' => 'string']);

                            $userDao = DAORegistry::getDAO('UserDAO');
                            $user = $userDao ? $userDao->getById($newUserId) : null;
                            $userFullName = $user ? $user->getFullName() : ($userRow->username ?: 'User #' . $newUserId);
                            Capsule::table('event_log_settings')->updateOrInsert(['log_id' => (int) $logId, 'setting_name' => 'userFullName'], ['setting_value' => $userFullName, 'setting_type' => 'string']);
                        }
                    }
                    if (isset($logData['message']) && $logData['message'] !== '') {
                        $cleanMsg = trim($logData['message']);
                        while (preg_match('/^##(.*)##$/s', $cleanMsg, $m)) {
                            $cleanMsg = trim($m[1]);
                        }
                        $logUpdates['message'] = $cleanMsg;
                        $logUpdates['is_translated'] = 1;
                    }

                    if (!empty($logUpdates)) {
                        $c = Capsule::table('event_log')
                            ->where('log_id', '=', (int) $logId)
                            ->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION)
                            ->where('assoc_id', '=', $submissionId)
                            ->update($logUpdates);
                        $affected['activity_logs'] += $c;
                    }
                }
            }

            return [
                'success' => true,
                'affected' => $affected,
            ];
        });
    }

    /**
     * Directly update file timestamps in database for a submission stage or all files.
     *
     * @param int $submissionId
     * @param int $contextId
     * @param string $targetStage 'ALL' or integer file stage ID
     * @param string $dateTime Target datetime string
     * @param bool $syncUpdated
     * @return int Number of affected rows directly in database.
     */
    public function directUpdateFiles($submissionId, $contextId, $targetStage, $dateTime, $syncUpdated = false)
    {
        $submissionId = (int) $submissionId;
        $contextId = (int) $contextId;

        $sub = Capsule::table('submissions')->where('submission_id', '=', $submissionId)->where('context_id', '=', $contextId)->first();

        if (!$sub) {
            throw new Exception('Submission not found or access denied.');
        }

        $dbDate = EditorialTimelineHelper::fromHtmlDatetime($dateTime);
        if (!$dbDate) {
            throw new Exception('Invalid date format provided.');
        }

        $query = Capsule::table('submission_files')->where('submission_id', '=', $submissionId);

        if ($targetStage !== 'ALL') {
            $query->where('file_stage', '=', (int) $targetStage);
        }

        $updateData = ['created_at' => $dbDate];
        if ($syncUpdated) {
            $updateData['updated_at'] = $dbDate;
        }

        return $query->update($updateData);
    }

    /**
     * Directly update a review assignment in database.
     *
     * @param int $submissionId
     * @param int $contextId
     * @param int $reviewId
     * @param array $dates
     * @return int Number of affected rows in database.
     */
    public function directUpdateReview($submissionId, $contextId, $reviewId, $dates)
    {
        $submissionId = (int) $submissionId;
        $contextId = (int) $contextId;
        $reviewId = (int) $reviewId;

        $sub = Capsule::table('submissions')->where('submission_id', '=', $submissionId)->where('context_id', '=', $contextId)->first();

        if (!$sub) {
            throw new Exception('Submission not found or access denied.');
        }

        $dateFields = ['date_assigned', 'date_notified', 'date_confirmed', 'date_due', 'date_completed', 'date_acknowledged'];

        $updateData = [];
        foreach ($dateFields as $f) {
            if (array_key_exists($f, $dates)) {
                $updateData[$f] = EditorialTimelineHelper::fromHtmlDatetime($dates[$f]);
            }
        }

        if (empty($updateData)) {
            return 0;
        }

        return Capsule::table('review_assignments')->where('review_id', '=', $reviewId)->where('submission_id', '=', $submissionId)->update($updateData);
    }

    /**
     * Calculate a shifted timeline preview by applying an offset in days.
     *
     * @param array $timelineData
     * @param int $daysOffset Positive or negative integer
     * @param array $options Options specifying which categories to shift
     * @return array Updated timeline structure with shifted timestamps
     */
    public function shiftTimelineData($timelineData, $daysOffset, $options = [])
    {
        $daysOffset = (int) $daysOffset;
        if ($daysOffset === 0) {
            return $timelineData;
        }

        $shiftDate = function ($dateStr) use ($daysOffset) {
            if (empty($dateStr)) {
                return $dateStr;
            }
            $ts = strtotime($dateStr);
            if ($ts === false) {
                return $dateStr;
            }
            $shiftedTs = strtotime("{$daysOffset} days", $ts);
            return date('Y-m-d H:i:s', $shiftedTs);
        };

        $shiftDateOnly = function ($dateStr) use ($daysOffset) {
            if (empty($dateStr)) {
                return $dateStr;
            }
            $ts = strtotime($dateStr);
            if ($ts === false) {
                return $dateStr;
            }
            $shiftedTs = strtotime("{$daysOffset} days", $ts);
            return date('Y-m-d', $shiftedTs);
        };

        // 1. Shift submission
        if (!empty($options['shift_submission']) && !empty($timelineData['submission']['date_submitted'])) {
            $timelineData['submission']['date_submitted'] = $shiftDate($timelineData['submission']['date_submitted']);
        }

        // 2. Shift publications
        if (!empty($options['shift_publications']) && !empty($timelineData['publications'])) {
            foreach ($timelineData['publications'] as &$pub) {
                if (!empty($pub['date_published'])) {
                    $pub['date_published'] = $shiftDateOnly($pub['date_published']);
                }
            }
        }

        // 3. Shift files
        if (!empty($options['shift_files']) && !empty($timelineData['files'])) {
            foreach ($timelineData['files'] as &$file) {
                if (!empty($file['created_at'])) {
                    $file['created_at'] = $shiftDate($file['created_at']);
                }
                if (!empty($file['updated_at'])) {
                    $file['updated_at'] = $shiftDate($file['updated_at']);
                }
            }
        }

        // 4. Shift reviews
        if (!empty($options['shift_reviews']) && !empty($timelineData['reviews'])) {
            foreach ($timelineData['reviews'] as &$rev) {
                foreach (['date_assigned', 'date_notified', 'date_confirmed', 'date_due', 'date_completed', 'date_acknowledged'] as $f) {
                    if (!empty($rev[$f])) {
                        $rev[$f] = $shiftDate($rev[$f]);
                    }
                }
                $rev['chronology_warnings'] = EditorialTimelineHelper::checkReviewChronology($rev);
            }
        }

        // 5. Shift decisions
        if (!empty($options['shift_decisions']) && !empty($timelineData['decisions'])) {
            foreach ($timelineData['decisions'] as &$dec) {
                if (!empty($dec['date_decided'])) {
                    $dec['date_decided'] = $shiftDate($dec['date_decided']);
                }
            }
        }

        // 6. Shift discussions
        if (!empty($options['shift_discussions']) && !empty($timelineData['discussions'])) {
            foreach ($timelineData['discussions'] as &$disc) {
                if (!empty($disc['date_posted'])) {
                    $disc['date_posted'] = $shiftDate($disc['date_posted']);
                }
                if (!empty($disc['notes'])) {
                    foreach ($disc['notes'] as &$note) {
                        if (!empty($note['date_created'])) {
                            $note['date_created'] = $shiftDate($note['date_created']);
                        }
                    }
                }
            }
        }

        return $timelineData;
    }

    /**
     * Get submission activity log entries with user details.
     *
     * @param int $submissionId
     * @param int $contextId
     * @return array
     */
    public function getActivityLogs($submissionId, $contextId)
    {
        $submissionId = (int) $submissionId;

        import('lib.pkp.classes.log.SubmissionEventLogDAO');
        $submissionEventLogDao = DAORegistry::getDAO('SubmissionEventLogDAO');
        $userDao = DAORegistry::getDAO('UserDAO');

        $logs = [];

        try {
            $eventLogEntries = $submissionEventLogDao ? $submissionEventLogDao->getBySubmissionId($submissionId) : null;
            if ($eventLogEntries) {
                while ($entry = $eventLogEntries->next()) {
                    $userId = (int) $entry->getUserId();
                    $user = $userDao ? $userDao->getById($userId) : null;
                    $username = $user ? $user->getUsername() : '';
                    $userFullName = $user ? $user->getFullName() : ($entry->getUserFullName() ?: '');

                    if (empty($username) && $userId > 0) {
                        $uRow = Capsule::table('users')->where('user_id', '=', $userId)->first();
                        if ($uRow) {
                            $username = $uRow->username;
                        }
                    }

                    $dateLogged = $entry->getDateLogged();
                    $dateLoggedFormatted = '';
                    if (!empty($dateLogged)) {
                        $ts = strtotime($dateLogged);
                        if ($ts) {
                            $dateLoggedFormatted = date('Y-m-d\TH:i', $ts);
                        }
                    }

                    $rawMessage = (string) $entry->getMessage();
                    $isTranslated = (int) $entry->getIsTranslated();
                    $translatedMessage = $entry->getTranslatedMessage();
                    if (empty($translatedMessage)) {
                        $translatedMessage = $rawMessage;
                    }

                    // Clean any untranslated wrapper ##...## if the string is plain text
                    while (preg_match('/^##(.*)##$/s', $translatedMessage, $m)) {
                        $inner = trim($m[1]);
                        if (strpos($inner, ' ') !== false || !preg_match('/^[a-zA-Z0-9_.-]+$/', $inner)) {
                            $translatedMessage = $inner;
                        } else {
                            break;
                        }
                    }
                    while (preg_match('/^##(.*)##$/s', $rawMessage, $m)) {
                        $inner = trim($m[1]);
                        if (strpos($inner, ' ') !== false || !preg_match('/^[a-zA-Z0-9_.-]+$/', $inner)) {
                            $rawMessage = $inner;
                        } else {
                            break;
                        }
                    }

                    // Auto-heal existing records in DB that were saved with is_translated = 0 on literal sentences
                    if ($isTranslated === 0 && (strpos($rawMessage, ' ') !== false || preg_match('/^##.*##$/s', $entry->getMessage()))) {
                        try {
                            Capsule::table('event_log')->where('log_id', '=', (int) $entry->getId())->update([
                                'message' => $translatedMessage,
                                'is_translated' => 1,
                            ]);
                            $isTranslated = 1;
                        } catch (Exception $e) {}
                    }

                    $isPluginLog = strpos($rawMessage, 'plugins.generic.editorialTimelineManager') !== false || strpos($translatedMessage, 'Editorial workflow timeline dates were modified') !== false;

                    $logs[] = [
                        'log_id' => (int) $entry->getId(),
                        'user_id' => $userId,
                        'username' => $username,
                        'user_full_name' => $userFullName ?: ($username ?: 'User #' . $userId),
                        'date_logged' => $dateLogged,
                        'date_logged_formatted' => $dateLoggedFormatted,
                        'event_type' => (int) $entry->getEventType(),
                        'message' => $rawMessage,
                        'display_message' => $translatedMessage,
                        'is_translated' => $isTranslated,
                        'is_plugin_log' => $isPluginLog,
                        'params' => $entry->getParams(),
                    ];
                }
            }
        } catch (Exception $e) {
            // Fallback via Capsule query if DAO throws
            $dbRows = Capsule::table('event_log as el')
                ->leftJoin('users as u', 'el.user_id', '=', 'u.user_id')
                ->where('el.assoc_type', '=', ASSOC_TYPE_SUBMISSION)
                ->where('el.assoc_id', '=', $submissionId)
                ->select(['el.*', 'u.username'])
                ->orderBy('el.date_logged', 'desc')
                ->get();

            foreach ($dbRows as $row) {
                $rawMsg = (string) $row->message;
                $isTrans = (int) $row->is_translated;
                $displayMsg = $isTrans ? $rawMsg : __($rawMsg);
                if (empty($displayMsg)) {
                    $displayMsg = $rawMsg;
                }
                while (preg_match('/^##(.*)##$/s', $displayMsg, $m)) {
                    $inner = trim($m[1]);
                    if (strpos($inner, ' ') !== false || !preg_match('/^[a-zA-Z0-9_.-]+$/', $inner)) {
                        $displayMsg = $inner;
                    } else {
                        break;
                    }
                }
                while (preg_match('/^##(.*)##$/s', $rawMsg, $m)) {
                    $inner = trim($m[1]);
                    if (strpos($inner, ' ') !== false || !preg_match('/^[a-zA-Z0-9_.-]+$/', $inner)) {
                        $rawMsg = $inner;
                    } else {
                        break;
                    }
                }

                if ($isTrans === 0 && (strpos($rawMsg, ' ') !== false || preg_match('/^##.*##$/s', $row->message))) {
                    try {
                        Capsule::table('event_log')->where('log_id', '=', (int) $row->log_id)->update([
                            'message' => $displayMsg,
                            'is_translated' => 1,
                        ]);
                        $isTrans = 1;
                    } catch (Exception $e) {}
                }

                $isPlugin = strpos($rawMsg, 'plugins.generic.editorialTimelineManager') !== false || strpos($displayMsg, 'Editorial workflow timeline dates were modified') !== false;

                $logs[] = [
                    'log_id' => (int) $row->log_id,
                    'user_id' => (int) $row->user_id,
                    'username' => $row->username ?: '',
                    'user_full_name' => $row->username ?: 'User #' . $row->user_id,
                    'date_logged' => $row->date_logged,
                    'date_logged_formatted' => $row->date_logged ? date('Y-m-d\TH:i', strtotime($row->date_logged)) : '',
                    'event_type' => (int) $row->event_type,
                    'message' => $rawMsg,
                    'display_message' => $displayMsg ?: $rawMsg,
                    'is_translated' => $isTrans,
                    'is_plugin_log' => $isPlugin,
                    'params' => [],
                ];
            }
        }

        // Sort descending by date_logged or log_id
        usort($logs, function ($a, $b) {
            $tA = strtotime($a['date_logged'] ?: '1970-01-01');
            $tB = strtotime($b['date_logged'] ?: '1970-01-01');
            if ($tA == $tB) {
                return $b['log_id'] - $a['log_id'];
            }
            return $tB - $tA;
        });

        return $logs;
    }

    /**
     * Get list of journal and submission users for user attribution selection.
     *
     * @param int $contextId
     * @param int $submissionId
     * @return array
     */
    public function getJournalUsers($contextId, $submissionId)
    {
        $contextId = (int) $contextId;
        $submissionId = (int) $submissionId;

        // 1. Users enrolled in this context
        $enrolledUsers = Capsule::table('users as u')
            ->join('user_user_groups as uug', 'u.user_id', '=', 'uug.user_id')
            ->join('user_groups as ug', 'uug.user_group_id', '=', 'ug.user_group_id')
            ->where('ug.context_id', '=', $contextId)
            ->select(['u.user_id', 'u.username', 'u.email'])
            ->distinct()
            ->get();

        // 2. Users in stage_assignments for this submission
        $assignedUsers = Capsule::table('users as u')
            ->join('stage_assignments as sa', 'u.user_id', '=', 'sa.user_id')
            ->where('sa.submission_id', '=', $submissionId)
            ->select(['u.user_id', 'u.username', 'u.email'])
            ->distinct()
            ->get();

        // 3. Users in event_log for this submission
        $logUsers = Capsule::table('users as u')
            ->join('event_log as el', 'u.user_id', '=', 'el.user_id')
            ->where('el.assoc_type', '=', ASSOC_TYPE_SUBMISSION)
            ->where('el.assoc_id', '=', $submissionId)
            ->select(['u.user_id', 'u.username', 'u.email'])
            ->distinct()
            ->get();

        // Merge and deduplicate by user_id
        $usersById = [];
        foreach ([$enrolledUsers, $assignedUsers, $logUsers] as $userSet) {
            foreach ($userSet as $ur) {
                $uid = (int) $ur->user_id;
                if (!isset($usersById[$uid])) {
                    $usersById[$uid] = [
                        'id' => $uid,
                        'username' => $ur->username,
                        'email' => $ur->email,
                    ];
                }
            }
        }

        // Fetch full names from user_settings (givenName, familyName)
        if (!empty($usersById)) {
            $userIds = array_keys($usersById);
            $nameSettings = Capsule::table('user_settings')
                ->whereIn('user_id', $userIds)
                ->whereIn('setting_name', ['givenName', 'familyName'])
                ->get();

            $names = [];
            foreach ($nameSettings as $ns) {
                $names[$ns->user_id][$ns->setting_name] = $ns->setting_value;
            }

            foreach ($usersById as $uid => &$u) {
                $given = isset($names[$uid]['givenName']) ? trim($names[$uid]['givenName']) : '';
                $family = isset($names[$uid]['familyName']) ? trim($names[$uid]['familyName']) : '';
                $fullName = trim($given . ' ' . $family);
                $u['full_name'] = $fullName ?: $u['username'];
            }
            unset($u);
        }

        // Sort alphabetically by username
        uasort($usersById, function ($a, $b) {
            return strcasecmp($a['username'], $b['username']);
        });

        return array_values($usersById);
    }

    /**
     * Directly update a single activity log entry in database immediately.
     *
     * @param int $submissionId
     * @param int $contextId
     * @param int $logId
     * @param array $data
     * @return int
     */
    public function directUpdateActivityLog($submissionId, $contextId, $logId, $data)
    {
        $submissionId = (int) $submissionId;
        $logId = (int) $logId;
        $updates = [];

        if (isset($data['date_logged']) && !empty($data['date_logged'])) {
            $updates['date_logged'] = EditorialTimelineHelper::fromHtmlDatetime($data['date_logged']);
        }
        if (isset($data['user_id'])) {
            $newUserId = (int) $data['user_id'];
            $updates['user_id'] = $newUserId;

            $userRow = Capsule::table('users')->where('user_id', '=', $newUserId)->first();
            if ($userRow) {
                Capsule::table('event_log_settings')->updateOrInsert(['log_id' => $logId, 'setting_name' => 'username'], ['setting_value' => $userRow->username, 'setting_type' => 'string']);

                $userDao = DAORegistry::getDAO('UserDAO');
                $user = $userDao ? $userDao->getById($newUserId) : null;
                $userFullName = $user ? $user->getFullName() : ($userRow->username ?: 'User #' . $newUserId);
                Capsule::table('event_log_settings')->updateOrInsert(['log_id' => $logId, 'setting_name' => 'userFullName'], ['setting_value' => $userFullName, 'setting_type' => 'string']);
            }
        }
        if (isset($data['message']) && $data['message'] !== '') {
            $msg = trim($data['message']);
            while (preg_match('/^##(.*)##$/s', $msg, $m)) {
                $msg = trim($m[1]);
            }
            $updates['message'] = $msg;
            $updates['is_translated'] = 1;
        }

        if (empty($updates)) {
            return 0;
        }

        return Capsule::table('event_log')
            ->where('log_id', '=', $logId)
            ->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', '=', $submissionId)
            ->update($updates);
    }

    /**
     * Delete a single activity log entry.
     *
     * @param int $submissionId
     * @param int $contextId
     * @param int $logId
     * @return int
     */
    public function deleteActivityLog($submissionId, $contextId, $logId)
    {
        $submissionId = (int) $submissionId;
        $logId = (int) $logId;

        // Verify entry belongs to this submission
        $entry = Capsule::table('event_log')
            ->where('log_id', '=', $logId)
            ->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', '=', $submissionId)
            ->first();

        if (!$entry) {
            return 0;
        }

        Capsule::table('event_log_settings')->where('log_id', '=', $logId)->delete();
        return Capsule::table('event_log')->where('log_id', '=', $logId)->delete();
    }

    /**
     * Create a new activity log entry for a submission.
     *
     * @param int $submissionId
     * @param int $contextId
     * @param array $data
     * @return int New log ID
     */
    public function createActivityLog($submissionId, $contextId, $data)
    {
        $submissionId = (int) $submissionId;
        $dateLogged = EditorialTimelineHelper::fromHtmlDatetime($data['date_logged']);
        $userId = (int) ($data['user_id'] ?? 1);
        $message = trim((string) ($data['message'] ?? ''));
        while (preg_match('/^##(.*)##$/s', $message, $m)) {
            $message = trim($m[1]);
        }

        $logId = Capsule::table('event_log')->insertGetId([
            'assoc_type' => ASSOC_TYPE_SUBMISSION,
            'assoc_id' => $submissionId,
            'user_id' => $userId,
            'date_logged' => $dateLogged ?: date('Y-m-d H:i:s'),
            'event_type' => 0,
            'message' => $message,
            'is_translated' => 1,
        ]);

        $userDao = DAORegistry::getDAO('UserDAO');
        $user = $userDao ? $userDao->getById($userId) : null;
        $username = $user ? $user->getUsername() : '';
        $userFullName = $user ? $user->getFullName() : ($username ?: 'User #' . $userId);

        if (empty($username) && $userId > 0) {
            $uRow = Capsule::table('users')->where('user_id', '=', $userId)->first();
            if ($uRow) {
                $username = $uRow->username;
                $userFullName = $username;
            }
        }

        if (!empty($username)) {
            Capsule::table('event_log_settings')->insert([
                'log_id' => (int) $logId,
                'setting_name' => 'username',
                'setting_value' => $username,
                'setting_type' => 'string',
            ]);
            Capsule::table('event_log_settings')->insert([
                'log_id' => (int) $logId,
                'setting_name' => 'userFullName',
                'setting_value' => $userFullName,
                'setting_type' => 'string',
            ]);
        }

        return [
            'log_id' => (int) $logId,
            'user_id' => $userId,
            'username' => $username,
            'user_full_name' => $userFullName,
            'date_logged' => $dateLogged ?: date('Y-m-d H:i:s'),
            'date_logged_formatted' => $data['date_logged'],
            'message' => $message,
            'display_message' => $message,
            'is_translated' => 1,
            'is_plugin_log' => false,
        ];
    }

    /**
     * Delete all plugin-generated activity logs for this submission.
     *
     * @param int $submissionId
     * @param int $contextId
     * @return int Number of deleted logs
     */
    public function cleanPluginActivityLogs($submissionId, $contextId)
    {
        $submissionId = (int) $submissionId;

        $matchingLogs = Capsule::table('event_log')
            ->where('assoc_type', '=', ASSOC_TYPE_SUBMISSION)
            ->where('assoc_id', '=', $submissionId)
            ->where(function ($query) {
                $query->where('message', 'like', '%plugins.generic.editorialTimelineManager%')->orWhere('message', 'like', '%Editorial workflow timeline dates were modified%')->orWhere('message', 'like', '%Tanggal alur kerja editorial%')->orWhere('message', 'like', '%editorialTimeline%')->orWhere('message', 'like', '%timelineUpdated%');
            })
            ->pluck('log_id');

        if ($matchingLogs->isEmpty()) {
            return 0;
        }

        $logIds = $matchingLogs->all();
        Capsule::table('event_log_settings')->whereIn('log_id', $logIds)->delete();
        return Capsule::table('event_log')->whereIn('log_id', $logIds)->delete();
    }
}
