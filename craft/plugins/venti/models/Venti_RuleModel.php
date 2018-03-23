<?php
namespace Craft;

/**
 * Venti - Rrule model
 */
class Venti_RruleModel extends BaseElementModel
{
    protected $elementType = 'Venti_Event';

    /**
     * @access protected
     * @return array
     */
    protected function defineAttributes()
    {
        return array(
            'eventId'         => AttributeType::Number,
            'start'           => AttributeType::DateTime,
            'until'           => AttributeType::DateTime,
            'frequency'       => AttributeType::Number,
            'interval'        => AttributeType::Number,
            'bySecond'        => AttributeType::String,
            'byMinute'        => AttributeType::String,
            'byHour'          => AttributeType::String,
            'byMonth'         => AttributeType::String,
            'byDay'           => AttributeType::String,
            'byWeekNo'        => AttributeType::String,
            'byMonthDay'      => AttributeType::String,
            'yearDay'         => AttributeType::String,
            'wkSt'            => Attributetype::String,
            'bySetPos'        => Attributetype::String,
            'count'           => AttributeType::Number,
        );
    }
}
