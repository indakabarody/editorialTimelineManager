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

        $this->addRoleAssignment([ROLE_ID_MANAGER, ROLE_ID_SUB_EDITOR, ROLE_ID_ASSISTANT, ROLE_ID_SITE_ADMIN], ['index', 'manage', 'getTimelineData', 'searchSubmissions', 'saveTimeline', 'directUpdateFiles', 'directUpdateReview', 'generateSql', 'shiftTimeline', 'directUpdateActivityLog', 'deleteActivityLog', 'createActivityLog', 'cleanPluginActivityLogs']);
    }

    /**
     * @copydoc PKPHandler::authorize()
     */
    public function authorize($request, &$args, $roleAssignments)
    {
        import('lib.pkp.classes.security.authorization.ContextRequiredPolicy');
        $this->addPolicy(new ContextRequiredPolicy($request));

        import('lib.pkp.classes.security.authorization.PKPSiteAccessPolicy');
        $this->addPolicy(new PKPSiteAccessPolicy($request, null, $roleAssignments));

        return parent::authorize($request, $args, $roleAssignments);
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
                return new JSONMessage(true, $templateMgr->fetch($plugin->getTemplateResource('timelineModal.tpl')));
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
            return new JSONMessage(true, $templateMgr->fetch($plugin->getTemplateResource('submissionPicker.tpl')));
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

        return new JSONMessage(true, $data);
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
        $context = $request->getContext();
        $query = (string) $request->getUserVar('query');

        $dao = new EditorialTimelineDAO();
        $results = $dao->searchSubmissions($context->getId(), $query, 20);

        return new JSONMessage(true, $results);
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
     * AJAX endpoint to generate SQL script preview.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function generateSql($args, $request)
    {
        $submissionId = (int) $request->getUserVar('submissionId');
        $payloadJson = $request->getUserVar('timelinePayload');

        if (!$submissionId || empty($payloadJson)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.missingData'));
        }

        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            return new JSONMessage(false, __('plugins.generic.editorialTimelineManager.error.invalidJson'));
        }

        $dao = new EditorialTimelineDAO();
        $sqlScript = $dao->generateSqlScript($submissionId, $payload);

        return new JSONMessage(true, [
            'sql' => $sqlScript,
        ]);
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

        return new JSONMessage(true, $shiftedData);
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
     * AJAX endpoint: Directly update a single activity log entry.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function directUpdateActivityLog($args, $request)
    {
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
}
