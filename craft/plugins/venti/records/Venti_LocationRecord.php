<?php
namespace Craft;

/**
 * Venti - Location record
 */
class Venti_LocationRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'venti_locations';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'address' 		=> AttributeType::String,
			'addressTwo' 	=> AttributeType::String,
            'city'    		=> AttributeType::String,
            'state'    		=> AttributeType::String,
            'zipCode'     	=> AttributeType::String,
			'country'     	=> AttributeType::String,
            'longitude'   	=> AttributeType::String,
			'latitude'    	=> AttributeType::String,
			'website'		=> AttributeType::String,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'  => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
