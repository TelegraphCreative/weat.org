<?php
namespace Craft;

/**
 * Calendar Feed model class.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @see       http://tippingmedia.com
 * @package   craft.app.records
 * @since     1.0
 */

class Venti_CalendarFeedModel extends BaseElementModel
{

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'         => AttributeType::Number,
			'title'      => AttributeType::String,
			'start'      => AttributeType::String,
			'end'        => AttributeType::String,
            'allDay'     => AttributeType::Bool,
            'overlap'    => array(AttributeType::Bool, 'default' => 'true'),
            'color'      => AttributeType::String,

		);
	}
}
