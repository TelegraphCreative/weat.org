<?php
namespace Craft;

/**
 * Venti_Settings controller
 */
class Venti_SettingsController extends BaseController
{
    /**
     * Logged in member requried to access controller.
     */
    public function init()
    {
        $this->requireLogin();
    }

    /**
     * Renders the License settings page template
     */
    public function actionLicense()
    {
        $this->serveTemplate('license');
    }

    /**
     * Renders the General settings page template
     */
    public function actionGeneral()
    {
        $this->serveTemplate('general');
    }

    /**
     * Renders the Events settings page template
     */
    public function actionEvents()
    {
        $this->serveTemplate('events');
    }


    /**
     * Renders the group settings page template
     */
    public function actionGroups()
    {
        $this->serveTemplate('groups');
    }


    public function actionSaveSettings()
    {

        $this->requirePostRequest();
        $postData     = craft()->request->getPost('settings', array());
        $updateLayout = craft()->request->getPost('update_layout', false);
        
        if ($updateLayout) {
            $oldLayout = craft()->fields->getLayoutByType('Venti_Event_Default');
            $groupsWithOldLayoutId = craft()->venti_groups->getGroupsByLayoutId($oldLayout->id);

            $fieldLayout = $this->saveGroupLayout();

            if (!craft()->venti_groups->updateGroupLayoutIds($groupsWithOldLayoutId, $fieldLayout->id))
            {
                craft()->userSession->setNotice(Craft::t('Group default layouts not updated.'));
            }
        }

        $plugin = craft()->plugins->getPlugin('venti');
        craft()->plugins->savePluginSettings($plugin, $postData);

        craft()->userSession->setNotice(Craft::t('Settings Saved'));
        $this->redirectToPostedUrl();
    }


    /**
     * Saves group layout. If already present remove before saving.
     */
    private function saveGroupLayout()
    {
        $fieldLayout = craft()->request->getPost('fieldLayout');

        craft()->fields->deleteLayoutsByType('Venti_Event_Default');

        if ($fieldLayout) {
            $fieldLayout       = craft()->fields->assembleLayoutFromPost();
            $fieldLayout->type = 'Venti_Event_Default';
            craft()->fields->saveLayout($fieldLayout);
            return $fieldLayout;
        }
    }


    private function serveTemplate($template)
    {
        $validLicense = craft()->venti_license->validateGumroadLicense();

        $this->renderTemplate(
            'venti/settings/_' . $template,
            array(
                'settings'  => $this->getSettingsModel(),
                'license'   => $validLicense,
                'countries' => LocationHelper::countryOptions()
            )
        );
    }

    /**
     * @return Venti_SettingsModel
     */
    private function getSettingsModel()
    {
        return craft()->venti_settings->getSettingsModel();
    }
}
