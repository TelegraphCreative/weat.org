<?php
namespace Craft;

/**
 * Group locale model class.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @see       http://tippingmedia.com
 * @package   craft.app.records
 * @since     1.0
 */

class Venti_GroupLocaleModel extends BaseModel
{
	// Properties
	// =========================================================================

	/**
	 * @var bool
	 */
	public $urlFormatIsRequired = false;


	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::rules()
	 *
	 * @return array
	 */
	public function rules()
	{
		$rules = parent::rules();

		if ($this->urlFormatIsRequired)
		{
			$rules[] = array('urlFormat', 'required');
		}


		return $rules;
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseModel::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'               => AttributeType::Number,
			'groupId'          => AttributeType::Number,
			'locale'           => AttributeType::Locale,
			'enabledByDefault' => array(AttributeType::Bool, 'default' => true),
			'urlFormat'        => array(AttributeType::UrlFormat, 'label' => 'URL Format'),
			'enabled'          => array(AttributeType::Bool, 'default' => true),
		);
	}
}
