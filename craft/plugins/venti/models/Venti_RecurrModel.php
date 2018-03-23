<?php
namespace Craft;

/**
 * Venti - Recurr model
 */
class Venti_RecurrModel extends BaseElementModel
{
	protected $elementType = 'Venti_Event';

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'cid' 	 	 => AttributeType::Number,
			'startDate'  => AttributeType::DateTime,
			'endDate'    => AttributeType::DateTime,
            'isrepeat'   => AttributeType::Number,
		));
	}
}
