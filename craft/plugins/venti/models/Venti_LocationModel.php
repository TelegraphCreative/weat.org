<?php
namespace Craft;

/**
 * Location model class.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @see       http://tippingmedia.com
 * @package   craft.app.records
 * @since     1.0
 */

class Venti_LocationModel extends BaseElementModel
{


    #-- Properties
	protected $elementType = 'Venti_Location';


    public function getMapUrl($type = 'google')
    {
        $mapurl = "";
        switch ($type) {
            case 'google':
                $mapurl = "http://maps.google.com/?q=" . $this->fullAddress();
                break;
            case 'apple':
                $mapurl = "http://maps.apple.com/?address=" . $this->fullAddress();
                break;
        }
        return $mapurl;
    }

    public function fullAddress()
    {
        return $this->address ." ". $this->city ." ". $this->state ." ". $this->zipCode;
    }


	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'id'            => AttributeType::Number,
			'address'       => AttributeType::String,
			'addressTwo'    => array(AttributeType::String, 'default' => null),
			'city'          => AttributeType::String,
			'state'         => AttributeType::String,
			'zipCode'       => AttributeType::String,
            'country'       => AttributeType::String,
            'latitude'      => AttributeType::String,
            'longitude'     => AttributeType::String,
            'website'       => AttributeType::String,
		));
	}

    /**
	 * Returns whether the current user can edit the element.
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return true;
	}

    /**
	 * @inheritDoc BaseElementModel::getCpEditUrl()
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{

		// The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
		$url = UrlHelper::getCpUrl('venti/location/'.$this->id.($this->slug ? '-'.$this->slug : ''));

		return $url;

	}

    /**
	 * Returns the reference string to this element.
	 *
	 * @return string|null
	 */
	public function getRef()
	{
		return 'location/'.$this->id."-".$this->slug;
	}
}
