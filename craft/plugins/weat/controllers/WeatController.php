<?php
namespace Craft;

class WeatController extends BaseController
{
	public $errors = [];
	protected $allowAnonymous = array('actionUserExists');

	/*public function actionSaveCharge()
	{
		craft()->userGroups->assignUserToGroups($user->id, $groupId);
		$this->requirePostRequest();

		$charge = $this->_setChargeFromPost();
		$this->_setContentFromPost($charge);

		if (craft()->charge_charge->saveCharge($charge)) {
			$this->redirectToPostedUrl($charge);
		}

		craft()->userSession->setError(Craft::t("Couldn’t save charge."));
		craft()->urlManager->setRouteVariables([
			'charge' => $charge
		]);
	}*/
	public function actionSaveEvent() {
		$this->requirePostRequest();
		$opts = craft()->request->getPost('opts','');
		if (craft()->weat_registration->freeEventRegistration()) {
		//if(1==1) {
			craft()->userSession->setNotice(Craft::t('Registration saved.'));
			$this->redirectToPostedUrl();
		} else {
			// Prepare a flash error message for the user.
			craft()->userSession->setError(Craft::t('Couldn’t save registration.'));

			// Make the ingredient model available to the template as an 'ingredient' variable,
			// since it contains the user's dumb input as well as the validation errors.
			/*craft()->urlManager->setRouteVariables(array(
				 'ingredient' => $ingredient
			));*/
		}
	}

	public function actionUserExists() {
		// FROM JS
		//var url = Craft.getActionUrl('cocktailRecipes/ingredients/saveIngredient', { id: 10 });
		/*
		var data = {
    // ...
};

Craft.postActionRequest('cocktailRecipes/ingredients/saveIngredient', data, function(response) {
    // ...
});
*/
		$this->requirePostRequest();
		//$this->requireAjaxRequest();

		$loginName = craft()->request->getRequiredPost('loginName');

		//$loginName = 'bo@wearetelegraph.com';
		$user = craft()->users->getUserByUsernameOrEmail($loginName);
		$result = false;
		if($user) {
			$result = true;
		}
		$this->returnJson(array('userexists' => $result));
	}

	public function actionAdminSaveRegistration() {
		$this->requirePostRequest();
		$registration = $this->_getRegistrationModel();

		$meta = array();

		$userId = craft()->request->getPost('userId');
		if (is_array($userId)) {
			$userId = current($userId);
		}
		$registration->userId = $userId;
		$user = craft()->users->getUserById($userId);
		if($user) {
			$registration->firstName = $user->firstName;
			$registration->lastName = $user->lastName;
			$registration->email = $user->email;
			$registration->address1 = $meta['addressLine1'] = $user->userAddress1;
			$registration->address2 = $meta['addressLine2'] = $user->userAddress2;
			$registration->city = $meta['addressCity'] = $user->userAddressCity;
			$registration->state = $meta['addressState'] = $user->userAddressState->label;
			$registration->zip = $meta['addressZip'] = $user->userAddressZip;
			$meta['userPhoneNumber' ] = $user->userPhoneNumber;
			$meta['userCompanyName'] = $user->userCompanyName;
		} else {
			$registration->addError('userId', Craft::t('Select a user.'));
		}

		$eventId = craft()->request->getPost('eventId');
		if (is_array($eventId)) {
			$eventId = current($eventId);
		}
		$registration->elementId = $meta['eventId'] = $eventId;
		$event = craft()->venti_events->getEventById($eventId);
		if($event) {
			$meta['eventName'] = $event->title;
		} else {
			$registration->addError('elementId', Craft::t('Select an event.'));
		}

		$registration->numberOfTickets = $meta['quantity'] = craft()->request->getPost('quantity', '1');
		$meta['individualPrice'] = craft()->request->getPost('individualPrice', '0.00');
		$meta['ticketName'] = craft()->request->getPost('ticketName', 'Admin ticket');
		$registration->chargeId = craft()->request->getPost('chargeId', NULL);
		$meta['type'] = 'registration';

		$registration->subscriptionAdded = $meta['includeSubscription'] = craft()->request->getPost('includeSubscription', '0');
		$meta['newsletter'] = 'on';

		$registration->ticketId = NULL;


		$registration->meta = serialize($meta);

		if (craft()->weat_registration->adminSaveRegistration($registration)) {
			craft()->userSession->setNotice(Craft::t('Registration saved.'.$user->firstName));
			$this->redirectToPostedUrl($registration);
		} else {
				craft()->userSession->setError(Craft::t('Couldn’t save registration.'));
		}

		// Send the model back to the template
		craft()->urlManager->setRouteVariables(['userId' => $userId, 'registration' => $registration, 'meta' => $meta]);


		/*
		$meta = array();
		$meta['quantity']

		a:15:{
			s:4:

		}
		$record->chargeId = NULL;
		$record->userId = $userId;
		$record->elementId = craft()->request->getPost('meta.eventId');
		*/







		/*
		$meta = array();
		$meta['quantity']

		a:15:{
			--s:8:"quantity";s:1:"1";
			--s:15:"individualPrice";s:5:"25.00";
			--s:9:"eventName";s:16:"Conference 20/20";
			s:19:"includeSubscription";s:1:"0";
			s:4:"type";s:12:"registration";
			s:7:"eventId";s:3:"139";
			s:10:"ticketName";s:13:"General entry";
			s:15:"userPhoneNumber";s:10:"2103253087";
			s:15:"userCompanyName";s:5:"Stuff";
			s:10:"newsletter";s:2:"on";
			s:12:"addressLine1";s:28:"1825 Fort View Road, Ste 108";
			s:12:"addressLine2";s:0:"";
			s:11:"addressCity";s:6:"Austin";
			s:12:"addressState";s:2:"TX";
			s:10:"addressZip";s:5:"78704";
		}
		$record->chargeId = NULL;
		$record->userId = $userId;
		$record->elementId = craft()->request->getPost('meta.eventId');
		$record->firstName = craft()->request->getPost('meta.firstName');
		$record->lastName = craft()->request->getPost('meta.lastName');
		$record->email = craft()->request->getPost('meta.customerEmail');
		$record->ticketId = craft()->request->getPost('meta.ticketId');
		--$record->numberOfTickets = craft()->request->getPost('meta.quantity');
		$record->subscriptionAdded = craft()->request->getPost('meta.includeSubscription');
		$record->address1 = craft()->request->getPost('meta.addressLine1');
		$record->address2 = craft()->request->getPost('meta.addressLine2');
		$record->city = craft()->request->getPost('meta.addressCity');
		$record->state = craft()->request->getPost('meta.addressState');
		$record->zip = craft()->request->getPost('meta.addressZip');
		$record->meta = serialize(craft()->request->getPost('meta'));
		craft()->userSession->setFlash('registerThankYouItem', craft()->request->getPost('meta.eventName') . ' -  ' . craft()->request->getPost('meta.ticketName'));
		$meta = craft()->request->getPost('meta','');
		if (craft()->weat_registration->adminSaveRegistration()) {
			craft()->userSession->setNotice(Craft::t('Registration saved.'));
			$this->redirectToPostedUrl();
		} else {
			craft()->userSession->setError(Craft::t('Couldn’t save registration.'));
		}
		*/
	}



	private function _getRegistrationModel() {
		$registrationId = craft()->request->getPost('registrationId');

		if ($registrationId) {
			$registration = null;//craft()->venti_locations->getLocationById($locationId);

			if (!$registration) {
				throw new Exception(Craft::t('No location exists with the ID “{id}”.', array('id' => $registrationId)));
			}
		} else {
			$registration = new Weat_RegistrationModel();
		}

		return $registration;
	}

}
