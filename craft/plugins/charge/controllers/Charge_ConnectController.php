<?php
namespace Craft;

class Charge_ConnectController extends Charge_BaseController
{
    public $allowAnonymous = ['actionOauthCallback'];

    public function actionIndex(array $variables = [])
    {
        $this->requireAdmin();
        $variables['accounts'] = craft()->charge_connect->getAll();

        $this->renderTemplate('charge/connect/index', $variables);
    }

    public function actionView(array $variables = [])
    {
        $this->requireAdmin();
        $accountId = $variables['accountId'];
        $account = craft()->charge_connect->getAccountById($accountId);

        if ($account == null) $this->redirect('charge/connect');

        // Get the related charges for this account

        // Grab the item elements
        $criteria = craft()->elements->getCriteria('Charge');
        $criteria->accountId = $account->id;
        $variables['listItems'] = $criteria->find();


        $variables['account'] = $account;;
/*
        $variables['tabs'] = [];

        foreach ($variables['chargeModel']->getFieldLayout()->getTabs() as $index => $tab) {
            // Do any of the fields on this tab have errors?
            $hasErrors = false;
            if ($variables['charge']->hasErrors()) {
                foreach ($tab->getFields() as $field) {
                    if ($variables['charge']->getErrors($field->getField()->handle)) {
                        $hasErrors = true;
                        break;
                    }
                }
            }
            $variables['tabs'][] = [
                'label' => Craft::t($tab->name),
                'url' => '#tab' . ($index + 1),
                'class' => ($hasErrors ? 'error' : null)
            ];
        }
*/
        $this->renderTemplate('charge/connect/_view', $variables);

    }

    public function actionDisconnectAccount()
    {
        $this->requireLogin();
        $this->requirePostRequest();
        //$user = craft()->userSession->getUser();
        $elementId = craft()->request->getRequiredPost('elementId');

        // validate permissions
        // only users with permission to edit this element can permform this action
        $this->checkHasPermissionToDisconnect($elementId);


        // Get this user's connected account
        $account = craft()->charge_connect->getAccountForElement($elementId);


        if(!is_null($account)) {

            craft()->charge_connect->disconnectAccount($account);
            craft()->userSession->setNotice(Craft::t('Stripe Account disconnected'));

            $this->redirectToPostedUrl();

        } else {
            craft()->userSession->setError(Craft::t('No Connected Account found for this element'));
        }
    }

    public function actionOauthCallback()
    {
        $code = craft()->request->getQuery('code');
        $state = craft()->request->getQuery('state');
        $stateInfo = [];
        if($state != '') {
            $stateInfo = unserialize(base64_decode($state));
        }

        $redirectPath = '/';
        if(isset($stateInfo['uri'])) {
            $redirectPath = $stateInfo['uri'];
        }

        if($code == '') {
            craft()->userSession->setError(Craft::t('Sorry, something went wrong authorising your account.'));
        } else {
            $result = craft()->charge_connect->handleOauthCallback($code, $stateInfo);
            if($result) {
                craft()->userSession->setNotice(Craft::t('Stripe account connected'));
                $this->redirect($redirectPath);
            } else {
                craft()->userSession->setError(Craft::t('Sorry, something went wrong authorising your account.'));
            }
        }

        $this->redirect($redirectPath);
    }


    private function checkHasPermissionToDisconnect($elementId)
    {
        if(craft()->userSession->isAdmin()) return true; // Admins can do everything

        $elementTypeClass = craft()->elements->getElementTypeById($elementId);
        $permissionCheck = '';


        switch($elementTypeClass) {
            case 'Entry':
                $entry = craft()->entries->getEntryById($elementId);

                $permissionCheck = 'editEntries:'.$entry->sectionId;
            break;
            case 'User':

                $permissionCheck = 'editUsers';

            break;
            default:

                craft()->charge_log->error('Failed to get permission check on an unknown element type - '.$elementTypeClass);
                return false;

            break;
        }

        return craft()->userSession->checkPermission($permissionCheck);
    }





}
