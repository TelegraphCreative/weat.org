<?php

namespace Craft;

/**
 * Registration Model
 *
 * Provides a read-only object representing an ingredient, which is returned
 * by our service class and can be used in our templates and controllers.
 */
class Weat_RegistrationModel extends BaseElementModel
{
/**
	 * Defines what is returned when someone puts {{ ingredient }} directly
	 * in their template.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->firstName;
	}

	/**
	 * Define the attributes this model will have.
	 *
	 * @return array
	 */
	public function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'id' => AttributeType::Number,
			'ticketId' => AttributeType::Number,
			'firstName' => AttributeType::String,
			'lastName' => AttributeType::String,
			'address1' => AttributeType::String,
			'address2' => AttributeType::String,
			'city' => AttributeType::String,
			'state' => AttributeType::String,
			'zip' => AttributeType::String,
			'email' => AttributeType::Email,
			'numberOfTickets' => AttributeType::Number,
			'subscriptionAdded' => AttributeType::Bool,
			'meta'  => AttributeType::String,
			'userId' => AttributeType::Number,
			'chargeId' => AttributeType::Number,
			'elementId' => AttributeType::Number,
			'user' => [AttributeType::Mixed],
			'charge' => [AttributeType::Mixed],
			'event' => [AttributeType::Mixed],
		));
	}

	public static function populateModels($data, $indexBy = null)
	{
			$return = parent::populateModels($data, $indexBy);

			// Grab all the parent elements in a single event for performance
			/*$parents = [];
			foreach($return as $key => $ret) {
					$return[$key]['parentElement'] = craft()->elements->getElementById($ret['elementId']);
			}*/

			return $return;
	}
}
