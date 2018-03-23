<?php
namespace Craft;

class Charge_ConnectService extends BaseApplicationComponent
{
    public $plugin;
    public $settings;
    public $enabled = false;
    public $behaviour = 'destination';

    private $connectBase = 'https://connect.stripe.com/oauth/authorize';
    private $authBase = 'https://connect.stripe.com/oauth/token';
    private $connectParams = ['response_type' => 'code', 'scope' => 'read_write', 'client_id' => '', 'redirect_uri' => '', 'state' => ''];
    private $redirectPath = 'charge/connect/oauthCallback';
    private $clientId = '';

    public $account;

    public function init()
    {
        $this->plugin = craft()->plugins->getPlugin('charge');
        $this->settings = (isset($this->plugin->settings['connect']) ? $this->plugin->settings['connect'] : []);

        if (isset($this->settings['enabled']) && $this->settings['enabled'] == true) {

            $key = 'devClientId';
            if(craft()->charge->getMode() == 'live') {
                $key = 'prodClientId';
            }
            // Only enabled when we have an appropriate client_id
            if(isset($this->settings[$key]) && $this->settings[$key] != '') {
                $this->enabled = true;
                $this->clientId = $this->settings[$key];
            }

            if(isset($this->settings['behaviour'])) {
                $this->behaviour = $this->settings['behaviour'];
            }
        }
    }

    public function getConnectBehaviour()
    {
        return $this->behaviour;
    }

    public function getConnectEnabledStatus()
    {
        return $this->enabled;
    }

    public function getAccountStatus($elementId = '')
    {
        if (!$this->enabled) {
            return 'disabled';
        }

        if($elementId == '') {
            return 'unconnected';
        }

        // Ok. enabled, and not a guest. Actually check
        $this->account = $this->getAccountForElement($elementId);

        if(!is_null($this->account)) return 'connected';
        return 'unconnected';
    }

    public function getAccountStatusByUserId($userId)
    {
        return $this->getAccountStatus($userId);
    }

    public function getConnectUrl($stateInfo = [])
    {
        if(!$this->getAccountStatus() == 'unconnected') {
            return ''; // You can only get a button if you can actually connect
        }

        $this->connectParams['client_id'] = $this->clientId;
        $this->connectParams['redirect_uri'] = $this->getRedirectUrl();

        // Pass a state variable so we can figure things out again later
        $this->connectParams['state'] = $this->_buildStateInfo($stateInfo);

        $url = $this->connectBase;
        $params = [];
        foreach($this->connectParams as $key => $val) {
            if($val != '') {
                $params[] = $key.'='.$val;
            }
        }
        $url = $url . '?' . implode('&', $params);
        return $url;
    }

    public function getRedirectUrl()
    {
        $path = $this->redirectPath;
        $path = craft()->config->get('actionTrigger').'/'.trim($path, '/');

        return UrlHelper::getSiteUrl($path);
    }

    public function disconnectAccount(Charge_AccountModel $account)
    {
        // We just blindingly delete our local record of this.
        // No need to be fancy about things.
        $accountRecord = $this->_getAccountRecordById($account->id);

        return $accountRecord->deleteByPk($account->id);
    }

    public function handleOauthCallback($code, $stateInfo = [])
    {
        if(!isset($stateInfo['elementId'])) {
            return false;
        }
        $elementId = $stateInfo['elementId'];

        // @todo - validate the current user has permissions against that elementId
        // to prevent assigning a stripe account to an element they don't have access to


        // OAuths only if they're in the right mode
        if($this->getAccountStatus($elementId) != 'unconnected') {
            return false;
        }

        $response = $this->getAuthCode($code);
        if($response == false) {
            return false;
        }

        // Valid. Create a new account record
        $account = new Charge_AccountModel();
        $account->elementId = $elementId;

        $account->accessToken = $response['access_token'];
        $account->livemode = $response['livemode'];
        $account->refreshToken = $response['refresh_token'];
        $account->tokenType = $response['token_type'];
        $account->stripePublishableKey = $response['stripe_publishable_key'];
        $account->stripeUserId = $response['stripe_user_id'];
        $account->scope = $response['scope'];
        $account->enabled = true;

        $saved = craft()->charge_account->saveAccount($account);
        if(!$saved) {
            return false;
        }

        return $account;
    }


    private function getAuthCode($code)
    {
        $body = [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'code' => $code,
            'client_secret' => craft()->charge_stripe->stripeSK
        ];


        try {
            $client = new \Guzzle\Http\Client();
            $request = $client->post($this->authBase);
            $request->setBody($body);

            $response = $request->send();

            if ($response->isSuccessful()) {
                $body = $response->json();
                return $body;
            }

        } catch(Exception $e) {
            // Nope
            ChargePlugin::log("Exception while performing connect callback");
            return false;
        }

        return false;
    }


    public function getAll()
    {
        $accountRecords = Charge_AccountRecord::model()->findAll();

        return Charge_AccountModel::populateModels($accountRecords);
    }


    public function getAccountByStripeId($stripeId)
    {
        $accountRecord = Charge_AccountRecord::model()->findByAttributes(['stripeUserId' => $stripeId]);

        return Charge_AccountModel::populateModel($accountRecord);
    }

    public function getAccountById($id)
    {
        $accountRecord = Charge_AccountRecord::model()->findById($id);

        return Charge_AccountModel::populateModel($accountRecord);
    }


    public function getAccountForElement($elementId)
    {
        $livemode = false;
        if(craft()->charge->getMode() == 'live') $livemode = true;

        $account = Charge_AccountRecord::model()->findByAttributes([
            'elementId' => $elementId,
            'livemode' => $livemode
        ]);

        if ($account) {
            return Charge_AccountModel::populateModel($account);
        }

        return null;
    }

    public function getAllActiveAccounts()
    {
        $livemode = false;
        if(craft()->charge->getMode() == 'live') $livemode = true;

        $accounts = Charge_AccountRecord::model()->findAllByAttributes([
            'livemode' => $livemode
        ]);

        if ($accounts) {
            return Charge_AccountModel::populateModels($accounts);
        }

        return [];
    }

    private function _getAccountRecordById($accountId = null)
    {
        if ($accountId) {
            $accountRecord = Charge_AccountRecord::model()->findById($accountId);

            if (!$accountRecord) {
                $this->_noAccountExists($accountId);
            }
        } else {
            $accountRecord = new Charge_AccountRecord();
        }

        return $accountRecord;
    }

    /**
     * Throws a "No account exists" exception.
     *
     * @access private
     * @param int $accountId
     * @throws Exception
     */
    private function _noAccountExists($accountId)
    {
        throw new Exception(Craft::t('No stripe accounts exists with the ID “{id}”', ['id' => $accountId]));
    }

    private function _buildStateInfo($stateInfo = [])
    {
        return base64_encode(serialize($stateInfo));
    }

    public function setupModelForConnect(ChargeModel $model)
    {
        $passedDestinationId = $model->destination;
        $passedAccountId = $model->accountId;

        // Just to be safe
        $model->destination = null;
        $model->accountId = null;

        if($this->getConnectEnabledStatus() == false) {
            return $model;
        }

        $account = null;
        if($passedDestinationId != '') {
            $account = $this->getAccountByStripeId($passedDestinationId);
        } elseif ( $passedAccountId != '' ) {
            $account = $this->getAccountById($passedAccountId);
        }


        if($account != null) {
            $model->destination = $account->stripeUserId;
            $model->accountId = $account->id;
        }

        if($model->destination != '') {

            if($model->applicationFee != '' && $model->applicationFee > 0) {
                $model->applicationFeeInCents = ($model->applicationFee * 100);
            }

            if($model->applicationFeeInPercent != '' && $model->applicationFeeInPercent > 0 ) {
                $model->applicationFeeInCents = round($model->amountInCents * ( $model->applicationFeeInPercent / 100));
            }
        }

        return $model;
    }



}
