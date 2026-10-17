<?php

/**
 * @file plugins/generic/editorialTimelineManager/pages/EditorialTimelineHandler.inc.php
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class EditorialTimelineHandler
 * @brief Handle requests for Editorial Timeline Manager dashboard and API actions.
 */

import('classes.handler.Handler');
import('lib.pkp.classes.core.JSONMessage');
import('plugins.generic.editorialTimelineManager.classes.EditorialTimelineDAO');
import('plugins.generic.editorialTimelineManager.classes.EditorialTimelineHelper');

class EditorialTimelineHandler extends Handler
{
    /** @var EditorialTimelineManagerPlugin */
    protected $_plugin;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->addRoleAssignment([ROLE_ID_MANAGER, ROLE_ID_SITE_ADMIN, ROLE_ID_SUB_EDITOR], ['index', 'manage', 'getTimelineData', 'searchSubmissions', 'saveTimeline', 'directUpdateFiles', 'directUpdateReview', 'directUpdateDecision', 'shiftTimeline', 'directUpdateActivityLog', 'deleteActivityLog', 'createActivityLog', 'cleanPluginActivityLogs', 'directUpdateEventLogDate', 'getEventLogDetails', 'directUpdateFileDate', 'directUpdateNoteDate', 'getFileTimelineDetails']);
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        if (!EditorialTimelineHelper::isUserAuthorizedManagerOrAdmin($request)) {
            return false;
        }

        $this->markRoleAssignmentsChecked();
        return true;
    }

    /**
     * @copydoc PKPHandler::initialize()
     */
    public function initialize($request)
    {
        parent::initialize($request);
        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_EDITOR, LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_GRID, LOCALE_COMPONENT_PKP_DEFAULT, LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_COMMON);
    }

    /**
     * Set the plugin instance.
     *
     * @param EditorialTimelineManagerPlugin $plugin
     */
    public function setPlugin($plugin)
    {
        $this->_plugin = $plugin;
    }

    /**
     * Get the plugin instance.
     *
     * @return EditorialTimelineManagerPlugin
     */
    public function getPlugin()
    {
        if (!$this->_plugin) {
            $plugin = PluginRegistry::getPlugin('generic', 'editorialTimelineManager');
            if (!$plugin) {
                $plugin = PluginRegistry::getPlugin('generic', 'editorialtimelinemanager');
            }
            if (!$plugin) {
                $plugin = PluginRegistry::getPlugin('generic', 'editorialTimelineManagerPlugin');
            }
            if (!$plugin) {
                $plugin = PluginRegistry::getPlugin('generic', 'editorialtimelinemanagerplugin');
            }
            $this->_plugin = $plugin;
        }
        return $this->_plugin;
    }

    /**
     * Check if request is from authorized Site Admin or Journal Manager.
     *
     * @param PKPRequest $request
     * @param int|null $contextId
     * @return bool
     */
    protected function checkAuthorized($request, $contextId = null)
    {
        return EditorialTimelineHelper::isUserAuthorizedManagerOrAdmin($request, $contextId);
    }

    /**
     * Validate CSRF token from either request POST/GET or HTTP header.
     *
     * @param PKPRequest $request
     * @return bool
     */
    protected function validateCsrfToken($request)
    {
        if ($request->checkCSRF()) {
            return true;
        }

        $session = $request->getSession();
        $expectedToken = $session ? (string) $session->getCSRFToken() : '';
        if (empty($expectedToken)) {
            return $this->checkAuthorized($request);
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_SERVER['HTTP_X_XSRF_TOKEN'] ?? '');
        if (!empty($headerToken) && hash_equals($expectedToken, (string) $headerToken)) {
            return true;
        }

        $userToken = (string) $request->getUserVar('csrfToken');
        if (!empty($userToken) && hash_equals($expectedToken, $userToken)) {
            return true;
        }

        return false;
    }

    /**
     * Main entry point: show submission picker or timeline manager.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage|string
     */
    public function index($args, $request)
    {
        return $this->manage($args, $request);
    }

    /**
     * Render timeline manager or submission picker.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage|string
     */
    public function manage($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_EDITOR, LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_GRID, LOCALE_COMPONENT_PKP_DEFAULT, LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_COMMON);

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $templateMgr = TemplateManager::getManager($request);
        $plugin = $this->getPlugin();

        $router = $request->getRouter();
        $isAjax = is_a($router, 'PKPComponentRouter') || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || (bool) $request->getUserVar('isAjax') || $request->getUserVar('format') === 'json';

        $baseUrl = $request->getBaseUrl();
        $pluginPath = $plugin ? $plugin->getPluginPath() : 'plugins/generic/editorialTimelineManager';
        $pluginUrl = $baseUrl . '/' . $pluginPath;

        $templateMgr->assign([
            'pluginName' => $plugin ? $plugin->getName() : 'editorialTimelineManager',
            'pluginPath' => $pluginPath,
            'pluginUrl' => $pluginUrl,
            'baseUrl' => $baseUrl,
            'currentContext' => $context,
        ]);

        $dao = new EditorialTimelineDAO();

        if ($submissionId > 0) {
            $timelineData = $dao->getTimelineData($submissionId, $context->getId());
            if (!$timelineData) {
                return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.submissionNotFound'));
            }

            $templateMgr->assign([
                'submissionId' => $submissionId,
                'timeline' => $timelineData,
                'csrfToken' => $request->getSession()->getCSRFToken(),
            ]);

            if ($isAjax) {
                $html = $templateMgr->fetch($plugin->getTemplateResource('timelineModal.tpl'));
                return new JSONMessage(true, EditorialTimelineHelper::cleanUtf8($html));
            }

            return $templateMgr->display($plugin->getTemplateResource('timelineModal.tpl'));
        }

        // No submission selected: display submission picker
        $recentSubmissions = $dao->searchSubmissions($context->getId(), '', 15);
        $templateMgr->assign([
            'submissions' => $recentSubmissions,
            'csrfToken' => $request->getSession()->getCSRFToken(),
        ]);

        if ($isAjax) {
            $html = $templateMgr->fetch($plugin->getTemplateResource('submissionPicker.tpl'));
            return new JSONMessage(true, EditorialTimelineHelper::cleanUtf8($html));
        }

        return $templateMgr->display($plugin->getTemplateResource('submissionPicker.tpl'));
    }

    /**
     * AJAX endpoint to retrieve timeline data for a submission.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function getTimelineData($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');

        if (!$submissionId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.submissionNotFound'));
        }

        $dao = new EditorialTimelineDAO();
        $data = $dao->getTimelineData($submissionId, $context->getId());

        if (!$data) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.submissionNotFound'));
        }

        return new JSONMessage(true, EditorialTimelineHelper::cleanUtf8($data));
    }

    /**
     * AJAX endpoint to search submissions in the current journal.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function searchSubmissions($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        $context = $request->getContext();
        $query = (string) $request->getUserVar('query');

        $dao = new EditorialTimelineDAO();
        $results = $dao->searchSubmissions($context->getId(), $query, 20);

        return new JSONMessage(true, EditorialTimelineHelper::cleanUtf8($results));
    }

    /**
     * AJAX endpoint to save timeline changes.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function saveTimeline($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $user = $request->getUser();
        $submissionId = (int) $request->getUserVar('submissionId');
        $payloadJson = $request->getUserVar('timelinePayload');

        if (!$submissionId || empty($payloadJson)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.invalidJson'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $result = $dao->saveTimelineChanges($submissionId, $context->getId(), $payload, $user->getId());

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.success.saved'),
                'affected' => $result['affected'],
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint to calculate shifted timeline.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function shiftTimeline($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $daysOffset = (int) $request->getUserVar('daysOffset');
        $options = (array) $request->getUserVar('options');

        if (!$submissionId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.submissionNotFound'));
        }

        $dao = new EditorialTimelineDAO();
        $data = $dao->getTimelineData($submissionId, $context->getId());

        if (!$data) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.submissionNotFound'));
        }

        $shiftedData = $dao->shiftTimelineData($data, $daysOffset, $options);

        return new JSONMessage(true, EditorialTimelineHelper::cleanUtf8($shiftedData));
    }

    /**
     * AJAX endpoint: Directly update file timestamps in database immediately (1-Click).
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateFiles($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $user = $request->getUser();
        $submissionId = (int) $request->getUserVar('submissionId');
        $targetStage = (string) $request->getUserVar('targetStage');
        $targetDate = (string) $request->getUserVar('targetDate');
        $syncUpdated = (bool) $request->getUserVar('syncUpdated');

        if (!$submissionId || empty($targetDate)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $affected = $dao->directUpdateFiles($submissionId, $context->getId(), $targetStage, $targetDate, $syncUpdated);

            // Return fresh data
            $freshData = $dao->getTimelineData($submissionId, $context->getId());

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.success.filesDirectlyUpdated', ['count' => $affected]),
                'affected' => $affected,
                'files' => $freshData['files'],
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Directly update a review assignment in database immediately (1-Click).
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateReview($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $user = $request->getUser();
        $submissionId = (int) $request->getUserVar('submissionId');
        $reviewId = (int) $request->getUserVar('reviewId');
        $dates = (array) $request->getUserVar('dates');

        if (!$submissionId || !$reviewId || empty($dates)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $affected = $dao->directUpdateReview($submissionId, $context->getId(), $reviewId, $dates);

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.success.reviewDirectlyUpdated'),
                'affected' => $affected,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Directly update an editorial decision & author notification email immediately (1-Click).
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateDecision($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $decisionId = (int) $request->getUserVar('decisionId');
        $dateDecided = (string) $request->getUserVar('dateDecided');
        $emailLogId = (int) $request->getUserVar('emailLogId');
        $dateSent = (string) $request->getUserVar('dateSent');

        if (!$submissionId || !$decisionId || empty($dateDecided)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $result = $dao->directUpdateDecision($submissionId, $context->getId(), $decisionId, $dateDecided, $emailLogId, $dateSent);

            return new JSONMessage(
                true,
                EditorialTimelineHelper::cleanUtf8([
                    'message' => __('plugins.generic.editorialTimelineManager.success.decisionDirectlyUpdated'),
                    'result' => $result,
                ]),
            );
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Directly update a single activity log entry.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateActivityLog($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $logId = (int) $request->getUserVar('logId');
        $dateLogged = (string) $request->getUserVar('date_logged');
        $userId = (int) $request->getUserVar('user_id');
        $message = (string) $request->getUserVar('message');
        $isCustom = (bool) $request->getUserVar('is_custom_message');

        if (!$submissionId || !$logId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $affected = $dao->directUpdateActivityLog($submissionId, $context->getId(), $logId, [
                'date_logged' => $dateLogged,
                'user_id' => $userId,
                'message' => $message,
                'is_custom_message' => $isCustom,
            ]);

            $cleanMsg = trim($message);
            while (preg_match('/^##(.*)##$/s', $cleanMsg, $m)) {
                $cleanMsg = trim($m[1]);
            }

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.activityLog.successUpdated', ['id' => $logId]),
                'clean_message' => $cleanMsg,
                'affected' => $affected,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Delete a single activity log entry.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function deleteActivityLog($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $logId = (int) $request->getUserVar('logId');

        if (!$submissionId || !$logId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $affected = $dao->deleteActivityLog($submissionId, $context->getId(), $logId);

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.activityLog.successDeleted', ['id' => $logId]),
                'affected' => $affected,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Create a new activity log entry.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function createActivityLog($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');
        $dateLogged = (string) $request->getUserVar('date_logged');
        $userId = (int) $request->getUserVar('user_id');
        $message = (string) $request->getUserVar('message');

        if (!$submissionId || empty($dateLogged) || empty($message)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $newLog = $dao->createActivityLog($submissionId, $context->getId(), [
                'date_logged' => $dateLogged,
                'user_id' => $userId ?: $request->getUser()->getId(),
                'message' => $message,
            ]);

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.activityLog.successCreated'),
                'log' => $newLog,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Clean / delete all plugin-generated activity logs for this submission.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function cleanPluginActivityLogs($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$request->checkCSRF()) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $submissionId = (int) $request->getUserVar('submissionId');

        if (!$submissionId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $count = $dao->cleanPluginActivityLogs($submissionId, $context->getId());

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.activityLog.successCleaned', ['count' => $count]),
                'deleted' => $count,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Directly update date and time of any event log entry.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateEventLogDate($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$this->validateCsrfToken($request)) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $contextId = $context ? (int) $context->getId() : 0;
        $submissionId = (int) $request->getUserVar('submissionId');
        $logId = (int) $request->getUserVar('logId');
        $dateLogged = (string) $request->getUserVar('date_logged');
        $syncFile = $request->getUserVar('sync_file') !== null ? (bool) $request->getUserVar('sync_file') : true;

        if (!$logId || empty($dateLogged)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $res = $dao->directUpdateEventLogDate($submissionId, $contextId, $logId, $dateLogged, $syncFile);

            if (empty($res) || (isset($res['affected']) && $res['affected'] === 0)) {
                return new JSONMessage(false, 'Failed to update event log #' . $logId . ' in database.');
            }

            $dateLoggedFinal = $res['date_logged'] ?? $dateLogged;
            $ts = strtotime($dateLoggedFinal);
            $formattedLong = $ts ? date('M j, Y', $ts) : $dateLoggedFinal;
            if ($ts && $context && method_exists($context, 'getLocalizedDateFormatLong')) {
                $dfLong = $context->getLocalizedDateFormatLong();
                if (function_exists('PHP81_BC\strftime')) {
                    $formattedLong = \PHP81_BC\strftime($dfLong, $ts);
                } elseif (function_exists('strftime')) {
                    $formattedLong = @strftime($dfLong, $ts);
                }
            }

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.activityLog.successUpdated', ['id' => $logId]),
                'display_date' => $res['date_logged_display'] ?? $dateLogged,
                'display_date_long' => $formattedLong,
                'date_logged' => $dateLoggedFinal,
                'is_file_log' => !empty($res['file_id']),
                'file_id' => $res['file_id'] ?? null,
                'submission_id' => $res['submission_id'] ?? $submissionId,
                'result' => $res,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Retrieve details and exact timestamps for an event log entry.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function getEventLogDetails($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        $context = $request->getContext();
        $contextId = $context ? (int) $context->getId() : 0;
        $submissionId = (int) $request->getUserVar('submissionId');
        $logId = (int) $request->getUserVar('logId');

        if (!$logId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $details = $dao->getEventLogDetails($submissionId, $contextId, $logId);
            if (!$details) {
                return new JSONMessage(false, __('common.notFound'));
            }

            return new JSONMessage(true, $details);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Directly update a single workflow file's created_at and updated_at,
     * and optionally synchronize all its event log history.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateFileDate($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$this->validateCsrfToken($request)) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $contextId = $context ? (int) $context->getId() : 0;
        $submissionId = (int) $request->getUserVar('submissionId');
        $fileId = (int) ($request->getUserVar('submissionFileId') ?: ($request->getUserVar('fileId') ?: $request->getUserVar('file_id')));
        $createdAt = (string) $request->getUserVar('created_at');
        $updatedAt = (string) $request->getUserVar('updated_at');
        $syncHistory = $request->getUserVar('sync_history') !== null ? (bool) $request->getUserVar('sync_history') : true;

        if (!$fileId || empty($createdAt)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $res = $dao->directUpdateFileDateAndHistory($submissionId, $contextId, $fileId, $createdAt, $updatedAt ?: $createdAt, $syncHistory);

            $createdFinal = $res['created_at'] ?? $createdAt;
            $ts = strtotime($createdFinal);
            $formattedLong = $ts ? date('M j, Y', $ts) : $createdFinal;
            if ($ts && $context && method_exists($context, 'getLocalizedDateFormatLong')) {
                $dfLong = $context->getLocalizedDateFormatLong();
                if (function_exists('PHP81_BC\strftime')) {
                    $formattedLong = \PHP81_BC\strftime($dfLong, $ts);
                } elseif (function_exists('strftime')) {
                    $formattedLong = @strftime($dfLong, $ts);
                }
            }

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.success.fileDirectlyUpdated'),
                'display_date' => $res['display_date'] ?? $createdAt,
                'display_date_long' => $formattedLong,
                'created_at' => $createdFinal,
                'submission_file_id' => $fileId,
                'submission_id' => $res['submission_id'] ?? $submissionId,
                'result' => $res,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Directly update a discussion or file note's date_created.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateNoteDate($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        if (!$this->validateCsrfToken($request)) {
            return new JSONMessage(false, __('form.csrfInvalid'));
        }

        $context = $request->getContext();
        $contextId = $context ? (int) $context->getId() : 0;
        $submissionId = (int) $request->getUserVar('submissionId');
        $noteId = (int) $request->getUserVar('noteId');
        $dateCreated = (string) $request->getUserVar('date_created');

        if (!$noteId || empty($dateCreated)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        try {
            $dao = new EditorialTimelineDAO();
            $res = $dao->directUpdateNoteDate($submissionId, $contextId, $noteId, $dateCreated);

            $ts = strtotime($dateCreated);
            $displayDate = $ts ? date('M j, Y H:i', $ts) : $dateCreated;

            return new JSONMessage(true, [
                'message' => __('plugins.generic.editorialTimelineManager.success.noteDirectlyUpdated'),
                'display_date' => $displayDate,
                'result' => $res,
            ]);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }

    /**
     * AJAX endpoint: Retrieve file dates and its event log history.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function getFileTimelineDetails($args, $request)
    {
        if (!$this->checkAuthorized($request)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.roleUnauthorized'));
        }

        $context = $request->getContext();
        $submissionId = (int) ($request->getUserVar('submissionId') ?: $request->getUserVar('articleId'));
        $fileId = (int) ($request->getUserVar('submissionFileId') ?: ($request->getUserVar('fileId') ?: $request->getUserVar('file_id')));

        if (!$fileId) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        if ($submissionId <= 0) {
            $sf = Capsule::table('submission_files')->where('submission_file_id', '=', $fileId)->first();
            if ($sf && !empty($sf->submission_id)) {
                $submissionId = (int) $sf->submission_id;
            }
        }

        try {
            $dao = new EditorialTimelineDAO();
            $details = $dao->getFileTimelineDetails($submissionId, $context->getId(), $fileId);
            if (!$details) {
                return new JSONMessage(false, __('common.notFound'));
            }

            return new JSONMessage(true, $details);
        } catch (Exception $e) {
            return new JSONMessage(false, $e->getMessage());
        }
    }
}
