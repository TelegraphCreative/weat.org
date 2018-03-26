<?php
namespace Craft;

/**
 * Venti - Recurr record
 */
class Venti_RecurrRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'venti_recurr';
	}

    public function primaryKey()
    {
       return 'eid';
    }

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
            'eid'       => array(AttributeType::Number, 'unique' => true, 'column' => ColumnType::PK),
            'cid'       => array(AttributeType::Number, 'unique' => false),
			'startDate' => array(AttributeType::DateTime, 'required' => true),
			'endDate'   => array(AttributeType::DateTime, 'required' => true),
            'isrepeat'  => AttributeType::Number,
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'event'  => array(static::BELONGS_TO, 'Venti_EventRecord', 'cid', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
