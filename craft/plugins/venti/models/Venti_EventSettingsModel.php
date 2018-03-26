<?php

/**
 * Event settings model class.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @see       http://tippingmedia.com
 * @package   craft.app.records
 * @since     1.0
 */

namespace Craft;

class Venti_EventSettingsModel extends BaseModel
{
    protected function defineAttributes()
    {
        return array(
            'groupSources'          => AttributeType::Mixed,
            'limit'                 => array(AttributeType::Number, 'default' => null),
            'eventSelectionLabel'   => array(AttributeType::String, 'default' => Craft::t('Select an event')),
        );
    }
}
