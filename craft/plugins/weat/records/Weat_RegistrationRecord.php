<?php
/**
 * Weat plugin for Craft CMS
 *
 * Weat Record
 *
 *
 * @author    Bo Bartlett - Telegraph Creative
 * @copyright Copyright (c) 2018 Bo Bartlett - Telegraph Creative
 * @link      https://telegraphcreative.co/
 * @package   Weat
 * @since     1.0.0
 */

namespace Craft;

class Weat_RegistrationRecord extends BaseRecord
{
	/**
	 * Returns the name of the database table the model is associated with (sans table prefix). By convention,
	 * tables created by plugins should be prefixed with the plugin name and an underscore.
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'weat_registration';
	}

	/**
	 * Returns an array of attributes which map back to columns in the database table.
	 *
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			//'eventId' => AttributeType::Number,
			'ticketId' => AttributeType::Number,
			//'userId' => AttributeType::Number,
			'firstName' => array(AttributeType::String, 'maxLength' => 100),
			'lastName' => array(AttributeType::String, 'maxLength' => 100),
			'address1' => AttributeType::String,
			'address2' => AttributeType::String,
			'city' => AttributeType::String,
			'state' => AttributeType::String,
			'zip' => AttributeType::String,
			'email' => AttributeType::Email,
			'numberOfTickets' => array(AttributeType::Number, 'default' => 1),
			'subscriptionAdded' => array(AttributeType::Bool, 'default' => false),
			'meta'  => array(AttributeType::String, 'column' => ColumnType::MediumText),
		);
	}

	/**
	 * If your record should have any relationships with other tables, you can specify them with the
	 * defineRelations() function
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'user' => array(static::BELONGS_TO, 'UserRecord', 'onDelete' => static::SET_NULL),
			'charge' => array(static::BELONGS_TO, 'ChargeRecord', 'onDelete' => static::SET_NULL),
			'event'  => array(static::BELONGS_TO, 'ElementRecord', 'elementId', 'onDelete' => static::SET_NULL),
		);
	}
}
