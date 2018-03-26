<?php
namespace Craft;

/**
 * Event model class.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @see       http://tippingmedia.com
 * @package   craft.app.records
 * @since     1.0
 */

class Venti_EventModel extends BaseElementModel
{

	#-- Constants
	const LIVE    = 'live';
	const EXPIRED  = 'expired';

	#-- Properties
	protected $elementType = 'Venti_Event';

	/**
	 * Returns url formated with specifc model attributes
	 * @access public
	 * @return string
	 */
	// public function getUrl()
	// {
	// 	$urlFormat = $this->getUrlFormat();
	// 	//$path = ($this->uri == '__home__') ? '' : $this->uri;
	// 	//$url = UrlHelper::getSiteUrl($path, null, null, $this->locale);
	// 	$path = craft()->templates->renderObjectTemplate($urlFormat, $this);
	// 	$url = UrlHelper::getSiteUrl($path, null, null, $this->locale);
	//
	// 	return $url;
	// }


	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'id'         		=> AttributeType::Number,
			'elementId'  		=> AttributeType::Number,
			'groupId' 	 		=> AttributeType::Number,
			'eid' 	 	 		=> AttributeType::Number,
			'cid' 	 	 		=> AttributeType::Number,
			'startDate'  		=> array(AttributeType::DateTime, 'required'=> true),
			'endDate'    		=> array(AttributeType::DateTime, 'required'=> true, 'compare' => '>startDate'),
            'allDay'     		=> AttributeType::Number,
            'repeat'     		=> AttributeType::Number,
            'rRule'      		=> AttributeType::String,
            'summary'    		=> AttributeType::String,
            'isrepeat'   		=> AttributeType::Number,
			'location'   		=> Attributetype::Mixed,
			'specificLocation' 	=> AttributeType::String,
			'registration' 		=> AttributeType::Mixed,
		));
	}


	/**
	 * @inheritDoc BaseModel::getAttribute()
	 *
	 * @param string $name
	 * @param bool   $flattenValue
	 *
	 * Updates location variable to return Venti_Location element criteria model else return normal.
	 * @return mixed
	 */
	public function getAttribute($name, $flattenValue = false)
	{

		if($name === 'location' && is_array(parent::getAttribute('location')) && craft()->request->isSiteRequest())
		{
			$criteria = craft()->elements->getCriteria('Venti_Location');
	        $criteria->id = parent::getAttribute('location')[0];
	        return $criteria->find();
		}

		if($name === 'uri' && parent::getAttribute('uri') !== "")
		{
			$urlFormat = $this->getUrlFormat();
			$path = craft()->templates->renderObjectTemplate($urlFormat, $this);
			//$uri = UrlHelper::getSiteUrl($path, null, null, $this->locale);

			return $path;
		}

	    return parent::getAttribute($name, $flattenValue);
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
		$group = $this->getGroup();

		if ($group)
		{
			// The slug *might* not be set if this is a Draft and they've deleted it for whatever reason
			$url = UrlHelper::getCpUrl('venti/'.$group->handle.'/'.$this->id.($this->slug ? '-'.$this->slug : ''));

			if (craft()->isLocalized() && $this->locale != craft()->language)
			{
				$url .= '/'.$this->locale;
			}

			return $url;
		}
	}

	/**
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$group = $this->getGroup();

		if ($group)
		{
			return $group->getFieldLayout();
		}
	}


	/**
	 * Returns the event's group.
	 *
	 * @return Venti_GroupModel|null
	 */
	public function getGroup()
	{
		if ($this->groupId)
		{
			return craft()->venti_groups->getGroupById($this->groupId);
		}
	}

	/**
	 * Returns the event's group.
	 *
	 * @return Venti_GroupModel|null
	 */
	public function group()
	{
		if ($this->groupId)
		{
			return craft()->venti_groups->getGroupById($this->groupId);
		}
	}

    /**
	 * Returns the event's group color.
	 *
	 * @return String|null
	 */
	public function getColor()
	{
		if ($this->groupId)
		{
			$group = craft()->venti_groups->getGroupById($this->groupId);
            return $group->color;
		}
	}


	/**
	 * Returns location model
	 * @access public
	 * @return Venti_LocationModel
	 */
	public function getLocation()
	{
		$locValue = is_array($this->location) ? $this->location : false;
		$criteria = craft()->elements->getCriteria("Venti_Location");
		$criteria->id = $locValue;
		return $criteria->find();
	}


	public function excludedDates()
	{
		$datesDict = array();
		if($this->repeat == true)
		{
			$exdates = craft()->venti_rule->getIncludedExcludedDates($this->rRule);
			if ($exdates && array_key_exists('excludedDates',$exdates))
			{
				$datesDict = $exdates['excludedDates'];
			}
		}
		return $datesDict;
	}


	public function includedDates()
	{
		$datesDict = array();
		if($this->repeat == true)
		{
			$rdates = craft()->venti_rule->getIncludedExcludedDates($this->rRule);
			if ($rdates && array_key_exists('includedDates',$rdates))
			{
				$datesDict = $rdates['includedDates'];
			}
		}
		return $datesDict;
	}



	/**
	 * @inheritDoc BaseElementModel::getLocales()
	 *
	 * @return array
	 */
	public function getLocales()
	{
		$locales = array();
		foreach ($this->getGroup()->getLocales() as $locale)
		{
			$locales[$locale->locale] = array('enabledByDefault' => $locale->enabledByDefault);
		}

		return $locales;
	}



	/**
	 * @inheritDoc BaseElementModel::getUrlFormat()
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$group = $this->getGroup();

		if ($group && $group->hasUrls)
		{
			$groupLocales = $group->getLocales();

			if (isset($groupLocales[$this->locale]))
			{

				return $groupLocales[$this->locale]->urlFormat;

			}
		}
	}



	/**
	 * Returns the reference string to this element.
	 *
	 * @return string|null
	 */
	public function getRef()
	{
		return $this->getGroup()->handle.'/'.$this->id."-".$this->slug;
	}




	/**
	 * @inheritDoc BaseElementModel::getStatus()
	 *
	 * @return string|null
	 */
	public function getStatus()
	{
		$status = parent::getStatus();
        if ($status == static::ENABLED)
		{
			$currentTime = DateTimeHelper::currentTimeStamp();
			$endDate    = $this->endDate->getTimestamp();

			return static::LIVE;

			// if ($endDate >= $currentTime)
			// {
			// 	return static::LIVE;
			// }
			// else
			// {
			// 	return static::EXPIRED;
			// }
		}

		return $status;
	}
}
