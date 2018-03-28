<?php
namespace Craft;

class Charge_LogModel extends BaseModel
{

    protected function defineAttributes()
    {
        return [
            'id'          => [AttributeType::Number],
            'mode'        => [AttributeType::Enum, 'values' => 'test,live,unset', 'default' => 'test', 'required' => true],
            'level'       => [AttributeType::String],
            'requestKey'  => [AttributeType::String],
            'type'        => [AttributeType::String],
            'source'      => [AttributeType::String],
            'extra'       => [AttributeType::Mixed],
            'dateCreated' => [AttributeType::String]
        ];
    }

}



