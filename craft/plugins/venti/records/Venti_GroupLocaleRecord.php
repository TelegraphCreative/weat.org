<?php
namespace Craft;

/**
 * Class Venti_GroupLocaleRecord
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @see       http://tippingmedia.com
 * @package   craft.app.records
 * @since     1.0
 */

class Venti_GroupLocaleRecord extends BaseRecord
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::getTableName()
	 *
	 * @return string
	 */
	public function getTableName()
	{
		return 'venti_groups_i18n';
	}

	/**
	 * @inheritDoc BaseRecord::defineRelations()
	 *
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'group' => array(static::BELONGS_TO, 'Venti_GroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
			'locale'  => array(static::BELONGS_TO, 'LocaleRecord', 'locale', 'required' => true, 'onDelete' => static::CASCADE, 'onUpdate' => static::CASCADE),
		);
	}

	/**
	 * @inheritDoc BaseRecord::defineIndexes()
	 *
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('groupId', 'locale'), 'unique' => true),
		);
	}

	// Protected Methods
	// =========================================================================

	/**
	 * @inheritDoc BaseRecord::defineAttributes()
	 *
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'locale'           	=> array(AttributeType::Locale, 'required' => true),
			'enabledByDefault' 	=> array(AttributeType::Bool, 'default' => true),
			'urlFormat'        	=> AttributeType::UrlFormat,
			'enabled' 		 	=> array(AttributeType::Bool, 'default' => true),
		);
	}
}
