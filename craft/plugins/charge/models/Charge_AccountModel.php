<?php
namespace Craft;

class Charge_AccountModel extends BaseModel
{

    public function __toString()
    {
        return $this->stripeUserId;
    }

    protected function defineAttributes()
    {
        $attributes = [
            'id'                   => [AttributeType::Number, 'required' => true],
            'accessToken'          => [AttributeType::String],
            'livemode'             => [AttributeType::Bool, 'default' => false],
            'refreshToken'         => [AttributeType::String],
            'tokenType'            => [AttributeType::String],
            'stripePublishableKey' => [AttributeType::String],
            'stripeUserId'         => [AttributeType::String],
            'scope'                => [AttributeType::String],
            'elementId'            => [AttributeType::Number],
            'enabled'              => [AttributeType::Bool, 'default' => false],
            'dateCreated'          => [AttributeType::DateTime],
            'parentElement'         => [AttributeType::Mixed]
        ];

        return $attributes;
    }

    public function title()
    {
        if($this->parentElement != null) {
            return $this->parentElement->title;
        }
        return '';
    }

    public function stripeId()
    {
        return $this->stripeUserId;
    }

    public static function populateModels($data, $indexBy = null)
    {
        $return = parent::populateModels($data, $indexBy);

        // Grab all the parent elements in a single event for performance
        $parents = [];
        foreach($return as $key => $ret) {
            $return[$key]['parentElement'] = craft()->elements->getElementById($ret['elementId']);
        }

        return $return;
    }

    public static function populateModel($data)
    {
        $return = parent::populateModel($data);

        $return['parentElement'] =  craft()->elements->getElementById($return['elementId']);

        return $return;
    }

    public function stripeLink()
    {
        $base = 'https://dashboard.stripe.com/';
        if ($this->livemode !== true) {
            $base .= 'test/';
        }
        $base .= 'applications/users/';


        return $base . $this->stripeUserId;
    }

    public function parentElement($elementId = '')
    {
        return craft()->elements->getElementById($this->elementId);
    }


}
