<?php
namespace Craft;

/**
 * Venti - Event record
 */
class Venti_EventRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'venti_events';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'elementId' => AttributeType::Number,
			'startDate' => array(AttributeType::DateTime, 'required' => true),
			'endDate'   => array(AttributeType::DateTime, 'required' => true, 'compare' => '>startDate'),
            'allDay'    => AttributeType::Number,
            'repeat'    => AttributeType::Number,
            'rRule'     => AttributeType::String,
            'summary'   => AttributeType::String,
			'locale'    => array(AttributeType::Locale, 'required' => true),
			'location'  => AttributeType::Mixed,
			'specificLocation' => AttributeType::String,
			'registration' => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'  => array(static::BELONGS_TO, 'ElementRecord', 'elementId', 'required' => true, 'onDelete' => static::CASCADE),
			'group' => array(static::BELONGS_TO, 'Venti_GroupRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}

}
