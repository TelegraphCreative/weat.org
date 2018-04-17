<?php
/**
 * Weat plugin for Craft CMS
 *
 * Weat_Registration Service
 *
 * @author    Bo Bartlett - Telegraph Creative
 * @copyright Copyright (c) 2018 Bo Bartlett - Telegraph Creative
 * @link      https://telegraphcreative.co/
 * @package   Weat
 * @since     1.0.0
 */

namespace Craft;

class Weat_RegistrationService extends BaseApplicationComponent
{
	protected $registrationRecord;
	/**
	 * Create a new instance of the Cocktail Recpies Service.
	 * Constructor allows IngredientRecord dependency to be injected to assist with unit testing.
	 *
	 * @param @ingredientRecord IngredientRecord The ingredient record to access the database
	 */
	public function __construct($registrationRecord = null)
	{
		$this->registrationRecord = $registrationRecord;
		if (is_null($this->registrationRecord)) {
			$this->registrationRecord = Weat_RegistrationRecord::model();
		}
	}
	/**
	 * This function can literally be anything you want, and you can have as many service functions as you want
	 *
	 * From any other plugin file, call it like this:
	 *
	 *     craft()->weat_events->exampleService()
	 */
	public function saveRegistration(Event $event)
	{
		$record = new Weat_RegistrationRecord();
		$chargeParam = $event->params['charge'];
		$charge = craft()->charge->getChargeByHash($chargeParam->hash);
		$meta = $charge->meta;
		$user = craft()->users->getUserById($charge->userId);

		$record->chargeId = $charge->id;
		$record->userId = $charge->userId;
		$record->elementId = isset($meta['eventId']) ? $meta['eventId'] : NULL;
		$record->firstName = $user->firstName;
		$record->lastName = $user->lastName;
		$record->email = $user->email;
		$record->ticketId = 2;
		$record->numberOfTickets = isset($meta['quantity']) ? $meta['quantity'] : NULL;
		$record->subscriptionAdded = isset($meta['includeSubscription']) ? $meta['includeSubscription'] : NULL;
		$record->address1 = isset($meta['addressLine1']) ? $meta['addressLine1'] : NULL;
		$record->address2 = isset($meta['addressLine2']) ? $meta['addressLine2'] : NULL;
		$record->city = isset($meta['addressCity']) ? $meta['addressCity'] : NULL;
		$record->state = isset($meta['addressState']) ? $meta['addressState'] : NULL;
		$record->zip = isset($meta['addressZip']) ? $meta['addressZip'] : NULL;
		$record->meta = serialize($meta);
		$record->save(false);
	}

	public function switchSubscription($event)
	{
		$charge = new ChargeModel();
		/*$charge->planAmount = 5.99;
		$charge->planIntervalCount = '1';
		$charge->planInterval = 'month';
		$charge->planCurrency = 'usd';
		$charge->planName = '5.99 USD Monthly';*/
		$charge->planAmount = 50.00;
		$charge->planIntervalCount = '1';
		$charge->planInterval = 'year';
		$charge->planCurrency = 'usd';
		$charge->planName = '50.00 USD Yearly';

		$subscriptionId = 45;
		$chargeParam = $event->params['charge'];
		$charge = craft()->charge->getChargeByHash($chargeParam->hash);
		$subscriptionId = $charge->subscription->id;
		//craft()->charge_subscription->updateSubscription($subscriptionId, $charge);
		$this->updateSubscription($subscriptionId, $charge);







		/*
		$charge->planName = 'WEAT Annual Membership';
		'description' : itemLabel,
		'planInterval' : 'year',
		'planIntervalCount' : '1',
		'planAmount' : 50.00,

		$valid = $this->_collectData();

		if($valid) {
			if ($this->charge->validateSimple()) {
				if(isset($opts['planAmount'])) $this->charge->planAmount = $opts['planAmount'];
				if(isset($opts['planInterval'])) $this->charge->planInterval = $opts['planInterval'];
				if(isset($opts['planIntervalCount'])) $this->charge->planIntervalCount = $opts['planIntervalCount'];
				if(isset($opts['planCurrency'])) $this->charge->planCurrency = $opts['planCurrency'];
				if(isset($opts['destination'])) $this->charge->destination = $opts['destination'];
				if(isset($opts['accountId'])) $this->charge->accountId = $opts['accountId'];
				if(isset($opts['applicationFee'])) $this->charge->applicationFee = $opts['applicationFee'];
				if(isset($opts['applicationFeeInPercent'])) $this->charge->applicationFeeInPercent = $opts['applicationFeeInPercent'];
				craft()->charge_subscription->updateSubscription($subscriptionId, $this->charge);
			}
		}*/
	}

	/* Duplicate of craft()->charge_subscription->updateSubscription to get rid of prorate */
	public function updateSubscription($subscriptionId, ChargeModel $chargeModel)
	{
			// Get the subscription for this id, so we can check validitiy
			$subscriptionModel = craft()->charge_subscription->findById($subscriptionId);

			if(is_null($subscriptionModel)) {
					throw new Exception('Sorry, this is not a valid subscription');
			}



			// Ok. Appears good to attempt to reactivate the subscription
			try {
					$realMode = null;
					// Make sure we're in the right mode too
					// We might be test mode, but trying to cancel a live sub, and v/v
					if(craft()->charge->getMode() != $subscriptionModel->mode) {
							// Shift the mode over
							$realMode = craft()->charge->getMode();
							craft()->charge->init($subscriptionModel->mode);
					}

					craft()->charge_log->note('Updating user payment subscription');

					$updatedPlan = craft()->charge_plan->findOrCreate($chargeModel);
					if($updatedPlan == false || $updatedPlan == null) {
							craft()->charge_log->error('Failed creating a new plan for updated subscription');
							return false;
					}

					// Only attempt to change the plan if the new plan is different from the old plan
					if($updatedPlan->stripeId == $subscriptionModel->stripeId) {
							// These are the same.
							// Stop right there..
							craft()->charge_log->error('Attempted to update a user subscription to the same subscription. Stopping request');
							return false;
					}

					$options = [];
					$options['plan'] = $updatedPlan->stripeId;
					$options['prorate'] = true; // this is the difference

					$subscriptionArray = craft()->charge->stripe->subscriptions()->update($subscriptionModel->customerId, $subscriptionModel->stripeId, $options);

					// Reset the mode
					if($realMode != null) {
							craft()->charge->init($realMode);
					}

					craft()->charge_log->success('User payment subscription updated');
					$this->updateSubscriptionFromAction($subscriptionModel, $subscriptionArray);

					// Fire to the onChange action with the model
					craft()->charge_actions->fireOnChange($chargeModel);

		// Update the user's associated membership subscription and user group
		craft()->charge_subscriber->changeSubscription($chargeModel->actions['onSuccess']['subscription'], craft()->charge_charge->getChargeById($subscriptionModel->chargeId));

					return true;
			} catch(\Exception $e) {
					craft()->charge_log->exception('Failed to reactivate the subscription, with an api exception', ['subscription' => $subscriptionModel]);
					throw new $e;
			}


			return false;
	}

	public function updateUser(Event $event)
	{
		WeatPlugin::log('update user');
		$record = new Weat_RegistrationRecord();

		$chargeParam = $event->params['charge'];
		$charge = craft()->charge->getChargeByHash($chargeParam->hash);
		WeatPlugin::log('User id is' . $charge->userId);
		WeatPlugin::log('Id is' . $charge->id);
		//WeatPlugin::log('User id might be ' . $charge->user->id);
		$meta = $charge->meta;
		$user = craft()->users->getUserById($charge->userId);
		if(isset($user)) {
			WeatPlugin::log('user is set');
			if(isset($meta['addressLine1'])) {
				WeatPlugin::log('has an address');
				//$user->setContent(['userAddress1' => $meta['addressLine1']]);
				$user->getContent()->userAddress1 = $meta['addressLine1'];
				//$user->userAddress1 = $meta['addressLine1'];
			}
			if(isset($meta['addressLine2'])) {
				$user->getContent()->userAddress2 = $meta['addressLine2'];
				//$user->userAddress2 = $meta['addressLine2'];
				//$user->setContent([ 'fields' => ['userAddress2' => $meta['addressLine2']] ]);
			}
			if(isset($meta['addressCity'])) {
				$user->getContent()->userAddressCity = $meta['addressCity'];
			}
			if(isset($meta['addressState'])) {
				$user->getContent()->userAddressState = $meta['addressState'];
			}
			if(isset($meta['addressZip'])) {
				$user->getContent()->userAddressZip = $meta['addressZip'];
			}
			craft()->users->saveUser($user);
		}
	}

		public function getAllRegistrations()
		{
			//$records = $this->ingredientRecord->findAll(array('order'=>'t.name'));
			//$records = $this->registrationRecord->findAll();
			$records = Weat_RegistrationRecord::model()->findAll();
			//return Weat_IngredientModel::populateModels($records, 'id');
			return Weat_RegistrationModel::populateModels($records, 'id');
			//$records = Charge_LogRecord::model()->findAll(['order' => 'id desc']);

			//return Charge_LogModel::populateModels($records);
		 }

}
