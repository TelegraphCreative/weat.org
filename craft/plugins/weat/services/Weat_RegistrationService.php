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
			$record->eventId = 139;
			$record->ticketId = 2;
			$record->userId = 1;
			$record->numberOfTickets = 4;
			$record->subscriptionAdded = false;
			$record->address1 = $event->params['charge']->cardAddressLine1;
			$record->city = $event->params['charge']->cardAddressCity;
			$record->state = $event->params['charge']->cardAddressState;
			$record->zip = $event->params['charge']->cardAddressZip;
			//if()
			$record->firstName = serialize($event->params['charge']->meta);
			//$record->address1 = serialize($event->params['charge']->address1);
			$record->save(false);
		}

}
