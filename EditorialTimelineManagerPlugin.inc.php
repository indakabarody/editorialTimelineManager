<?php

/**
 * @file plugins/generic/editorialTimelineManager/EditorialTimelineManagerPlugin.inc.php
 *
 * Copyright (c) 2026 Indaka Barody
 * Distributed under the GNU GPL v3. For full terms see the file LICENSE.
 *
 * @class EditorialTimelineManagerPlugin
 * @brief Main plugin class for Editorial Timeline Manager.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

class EditorialTimelineManagerPlugin extends GenericPlugin
{
    /**
     * @copydoc Plugin::register()
     */
    public function register($category, $path, $mainContextId = null)
    {
        $success = parent::register($category, $path, $mainContextId);

        if ($success) {
            // Register plugin aliases to ensure reliable lookup regardless of casing or suffix
            $plugins = &PluginRegistry::getPlugins();
            $plugins[$category]['editorialTimelineManager'] = $this;
            $plugins[$category]['editorialtimelinemanager'] = $this;
            $plugins[$category]['editorialTimelineManagerPlugin'] = $this;
            $plugins[$category]['editorialtimelinemanagerplugin'] = $this;

            if ($this->getEnabled($mainContextId)) {
                // Hook into workflow tabs in submission workflow
                HookRegistry::register('Template::Workflow', [$this, 'callbackTemplateWorkflow']);

                // Hook into template display to add CSS and JS assets
                HookRegistry::register('TemplateManager::display', [$this, 'callbackTemplateDisplay']);

                // Intercept page handler requests for /editorialTimeline
                HookRegistry::register('LoadHandler', [$this, 'callbackLoadHandler']);
            }
        }

        return $success;
    }

    /**
     * Provide display name for the plugin.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return __('plugins.generic.editorialTimelineManager.displayName');
    }

    /**
     * Provide description for the plugin.
     *
     * @return string
     */
    public function getDescription()
    {
        return __('plugins.generic.editorialTimelineManager.description');
    }

    /**
     * Add action links in the plugin settings list.
     *
     * @param PKPRequest $request
     * @param array $actionArgs
     * @return array
     */
    public function getActions($request, $actionArgs)
    {
        $actions = parent::getActions($request, $actionArgs);

        if (!$this->getEnabled()) {
            return $actions;
        }

        $dispatcher = $request->getDispatcher();
        import('lib.pkp.classes.linkAction.request.AjaxModal');

        $modalUrl = $dispatcher->url($request, ROUTE_PAGE, null, 'editorialTimeline', 'manage', null, [
            'submissionId' => 0,
        ]);

        $linkAction = new LinkAction('timelineManager', new AjaxModal($modalUrl, $this->getDisplayName(), 'modal_large'), __('plugins.generic.editorialTimelineManager.openManager'), 'settings');

        array_unshift($actions, $linkAction);
        return $actions;
    }

    /**
     * Handle plugin component management actions.
     *
     * @param array $args
     * @param PKPRequest $request
     * @return JSONMessage
     */
    public function manage($args, $request)
    {
        $this->import('pages.EditorialTimelineHandler');
        $handler = new EditorialTimelineHandler();
        $handler->setPlugin($this);

        $verb = $request->getUserVar('verb');
        switch ($verb) {
            case 'manage':
            case 'timeline':
            case 'settings':
            case '':
            case null:
                return $handler->manage($args, $request);

            case 'getTimelineData':
                return $handler->getTimelineData($args, $request);

            case 'searchSubmissions':
                return $handler->searchSubmissions($args, $request);

            case 'saveTimeline':
                return $handler->saveTimeline($args, $request);

            case 'directUpdateFiles':
                return $handler->directUpdateFiles($args, $request);

            case 'directUpdateReview':
                return $handler->directUpdateReview($args, $request);

            case 'generateSql':
                return $handler->generateSql($args, $request);

            case 'shiftTimeline':
                return $handler->shiftTimeline($args, $request);

            case 'directUpdateActivityLog':
                return $handler->directUpdateActivityLog($args, $request);

            case 'deleteActivityLog':
                return $handler->deleteActivityLog($args, $request);

            case 'createActivityLog':
                return $handler->createActivityLog($args, $request);

            case 'cleanPluginActivityLogs':
                return $handler->cleanPluginActivityLogs($args, $request);

            default:
                return $handler->manage($args, $request);
        }
    }

    /**
     * Route page requests to EditorialTimelineHandler.
     *
     * @param string $hookName
     * @param array $params
     * @return bool
     */
    public function callbackLoadHandler($hookName, $params)
    {
        $page = $params[0];
        if ($this->getEnabled() && strtolower($page) === 'editorialtimeline') {
            $this->import('pages.EditorialTimelineHandler');
            define('HANDLER_CLASS', 'EditorialTimelineHandler');
            return true;
        }
        return false;
    }

    /**
     * Inject tab into workflow.tpl.
     *
     * @param string $hookName
     * @param array $params
     * @return bool
     */
    public function callbackTemplateWorkflow($hookName, $params)
    {
        $smarty = &$params[1];
        $output = &$params[2];

        $submission = $smarty->getTemplateVars('submission');
        if (!$submission) {
            return false;
        }

        AppLocale::requireComponents(LOCALE_COMPONENT_PKP_SUBMISSION, LOCALE_COMPONENT_APP_SUBMISSION, LOCALE_COMPONENT_PKP_EDITOR, LOCALE_COMPONENT_APP_EDITOR, LOCALE_COMPONENT_PKP_GRID, LOCALE_COMPONENT_PKP_DEFAULT, LOCALE_COMPONENT_APP_DEFAULT, LOCALE_COMPONENT_PKP_MANAGER, LOCALE_COMPONENT_APP_MANAGER, LOCALE_COMPONENT_PKP_COMMON, LOCALE_COMPONENT_APP_COMMON);

        $request = Application::get()->getRequest();
        $dispatcher = $request->getDispatcher();

        $timelineUrl = $dispatcher->url($request, ROUTE_PAGE, null, 'editorialTimeline', 'manage', null, [
            'submissionId' => $submission->getId(),
        ]);

        $smarty->assign([
            'editorialTimelineUrl' => $timelineUrl,
            'editorialTimelineSubmissionId' => $submission->getId(),
            'pluginName' => $this->getName(),
        ]);

        $output .= $smarty->fetch($this->getTemplateResource('workflowTab.tpl'));

        return false;
    }

    /**
     * Inject CSS and JS assets on workflow or timeline pages.
     *
     * @param string $hookName
     * @param array $params
     * @return bool
     */
    public function callbackTemplateDisplay($hookName, $params)
    {
        $templateMgr = &$params[0];
        $template = $params[1];

        $request = Application::get()->getRequest();
        $baseUrl = $request->getBaseUrl();

        $pluginPath = $this->getPluginPath();
        $cssFile = Core::getBaseDir() . '/' . $pluginPath . '/css/editorialTimeline.css';
        $jsFile = Core::getBaseDir() . '/' . $pluginPath . '/js/editorialTimeline.js';
        $vCss = file_exists($cssFile) ? filemtime($cssFile) : time();
        $vJs = file_exists($jsFile) ? filemtime($jsFile) : time();

        $templateMgr->addStyleSheet('editorialTimelineCss', $baseUrl . '/' . $pluginPath . '/css/editorialTimeline.css?v=' . $vCss, ['contexts' => 'backend']);
        $templateMgr->addJavaScript('editorialTimelineJs', $baseUrl . '/' . $pluginPath . '/js/editorialTimeline.js?v=' . $vJs, ['contexts' => 'backend']);

        return false;
    }
}
