<?php
namespace Craft;

class WeatController extends BaseController
{
	public $errors = [];


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

}
