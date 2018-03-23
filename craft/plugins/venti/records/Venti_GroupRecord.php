<?php
namespace Craft;

/**
 * Venti - Group record
 */
class Venti_GroupRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'venti_groups';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'name'          => array(AttributeType::Name, 'required' => true),
			'handle'        => array(AttributeType::Handle, 'required' => true),
			'fieldLayoutId' => AttributeType::Number,
			'hasUrls'       => array(AttributeType::Bool, 'default' => true),
			'template'      => AttributeType::String,
			'color'			=> AttributeType::String,
			'description'   => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'locales'       => array(static::HAS_MANY, 'GroupLocaleRecord', 'groupId'),
			'fieldLayout' 	=> array(static::BELONGS_TO, 'FieldLayoutRecord', 'onDelete' => static::SET_NULL),
			'venti'         => array(static::HAS_MANY, 'Venti_EventRecord', 'eventId'),
		);
	}

	/**
	 * @return array
	 */
	public function defineIndexes()
	{
		return array(
			array('columns' => array('name'), 'unique' => true),
			array('columns' => array('handle'), 'unique' => true),
		);
	}

	/**
	 * @return array
	 * this may need to go when Yii 2 hits.
	 */
	public function scopes()
	{
		return array(
			'ordered' => array('order' => 'name'),
			'orderId' => array('order' => 'id DESC'),
		);
	}
}
