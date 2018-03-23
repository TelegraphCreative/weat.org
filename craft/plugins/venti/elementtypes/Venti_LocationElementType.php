<?php
namespace Craft;

/**
 * Venti - Location element type
 */
class Venti_LocationElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Locations');
	}

	/**
	 * Returns whether this element type has content.
	 *
	 * @return bool
	 */
	public function hasContent()
	{
		return true;
	}

	/**
	 * Returns whether this element type has titles.
	 *
	 * @return bool
	 */
	public function hasTitles()
	{
		return true;
	}



	public function isLocalized()
	{
	     return false;
	}


	/**
	* @inheritDoc IElementType::hasStatuses()
	*
	* @return bool
	*/
	public function hasStatuses()
	{
		return false;
	}


	/**
	 * Returns this element type's sources.
	 *
	 * @param string|null $context
	 * @return array|false
	 */
	public function getSources($context = null)
	{
		$sources = array(
			'*' => array(
				'label'    => Craft::t('All Locations'),
				'hasThumbs' => false,
			)
		);
		return $sources;
	}




	/**
	 * Returns the attributes that can be shown/sorted by in table views.
	 *
	 * @param string|null $source
	 * @return array
	 */
	public function defineTableAttributes($source = null)
	{
		return array(
			'title'     => Craft::t('Title'),
			'address' 	=> Craft::t('Address'),
			'state'   	=> Craft::t('State'),
			'zipCode'	=> Craft::t('Zip Code'),
			'country'   => Craft::t('Country'),
		);
	}

	/**
	 * Returns the table view HTML for a given attribute.
	 *
	 * @param BaseElementModel $element
	 * @param string $attribute
	 * @return string
	 */
	public function getTableAttributeHtml(BaseElementModel $element, $attribute)
	{
		return parent::getTableAttributeHtml($element, $attribute);
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'address'   	=> AttributeType::String,
			'addressTwo' 	=> AttributeType::String,
			'city' 			=> AttributeType::String,
			'state'  		=> AttributeType::String,
			'zipCode'    	=> AttributeType::String,
			'country'  		=> AttributeType::String,
            'longitude'     => AttributeType::String,
            'latitude'    	=> AttributeType::String,
			'website'    	=> AttributeType::String,
			'town'			=> AttributeType::String,
			'region'		=> AttributeType::String,
			'province'      => AttributeType::String,
			'postalCode'    => AttributeType::String,
		);
	}

	/**
	 * Modifies an element query targeting elements of this type.
	 *
	 * @param DbCommand $query
	 * @param ElementCriteriaModel $criteria
	 * @return mixed
	 */
	public function modifyElementsQuery(DbCommand $query, ElementCriteriaModel $criteria)
	{
		$query
			->addSelect('loc.id, loc.address, loc.addressTwo, loc.city, loc.state, loc.zipCode, loc.country, loc.longitude, loc.latitude, loc.website')
			->join('venti_locations loc', 'loc.id = elements.id');


		if ($criteria->address)
		{
			$query->andWhere(DbHelper::parseDateParam('loc.address', $criteria->address, $query->params));
		}

		if ($criteria->addressTwo)
		{
			$query->andWhere(DbHelper::parseDateParam('loc.addressTwo', $criteria->addressTwo, $query->params));
		}

		if($criteria->city)
        {
            $query->andWhere(DbHelper::parseParam('loc.city', $criteria->city, $query->params));
        }

		if($criteria->town)
        {
            $query->andWhere(DbHelper::parseParam('loc.city', $criteria->town, $query->params));
        }

		if($criteria->state)
        {
            $query->andWhere(DbHelper::parseParam('loc.state', $criteria->state, $query->params));
        }

		if($criteria->province)
        {
            $query->andWhere(DbHelper::parseParam('loc.state', $criteria->province, $query->params));
        }

		if($criteria->region)
        {
            $query->andWhere(DbHelper::parseParam('loc.state', $criteria->region, $query->params));
        }

		if($criteria->zipCode)
		{
			$query->andWhere(DbHelper::parseParam('loc.zipCode', $criteria->zipCode, $query->params));
		}

		if($criteria->postalCode)
		{
			$query->andWhere(DbHelper::parseParam('loc.zipCode', $criteria->postalCode, $query->params));
		}

		if($criteria->latitude)
        {
            $query->andWhere(DbHelper::parseParam('loc.latitude', $criteria->latitude, $query->params));
        }

		if($criteria->longitude)
        {
            $query->andWhere(DbHelper::parseParam('loc.longitude', $criteria->longitude, $query->params));
        }

		if($criteria->country)
        {
            $query->andWhere(DbHelper::parseParam('loc.country', $criteria->country, $query->params));
        }

		if($criteria->website)
        {
            $query->andWhere(DbHelper::parseParam('loc.website', $criteria->website, $query->params));
        }

	}


	/**
	 * @inheritDoc IElementType::saveElement()
	 *
	 * @param BaseElementModel $element
	 * @param array            $params
	 *
	 * @return bool
	 */
	public function saveElement(BaseElementModel $element, $params)
	{
		//return craft()->elements->saveElement($element);

		$element->address 		= $params['address'];
		$element->addressTwo 	= $params['addressTwo'];
		$element->city  		= $params['city'];
		$element->state 		= $params['state'];
		$element->zipCode 		= $params['zipCode'];
		$element->country 		= $params['country'];
		$element->longitude 	= $params['longitude'];
		$element->latitude 		= $params['latitude'];
		$element->website 		= $params['website'];

		return craft()->venti_locations->saveLocation($element);
	}



	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return Venti_LocationModel::populateModel($row);
	}



	/**
	 * @inheritDoc IElementType::getAvailableActions()
	 *
	 * @param string|null $source
	 *
	 * @return array|null
	 */
	public function getAvailableActions($source = null)
	{

		#-- for now these are always on
		$userSessionService = craft()->userSession;
		$canEdit = false;
		$canDelete = false;

		// Now figure out what we can do with these
		$actions = array();

		$canPublishLocations = $userSessionService->checkPermission('publishLocations');
		$canDeleteLocations = $userSessionService->checkPermission('deleteLocations');

		if ($canPublishLocations)
		{
			$canEdit = true;
		}

		if ($canDeleteLocations)
		{
			$canDelete = true;
		}


		if ($canEdit)
		{
			$editAction = craft()->elements->getAction('Edit');
			$editAction->setParams(array(
				'label' => Craft::t('Edit location'),
			));
			$actions[] = $editAction;
		}


		// Delete?
		if ($canDelete)
		{
			$deleteAction = craft()->elements->getAction('Delete');
			$deleteAction->setParams(array(
				'confirmationMessage' => Craft::t('Are you sure you want to delete the selected locations?'),
				'successMessage'      => Craft::t('Locations deleted.'),
			));
			$actions[] = $deleteAction;
		}

		return $actions;
	}




	/**
	 * @inheritDoc IElementType::defineSearchableAttributes()
	 *
	 * @return array
	 */
	public function defineSearchableAttributes()
	{
		//return array('startDate','endDate','summary');
	}




	/**
	 * Returns the HTML for an editor HUD for the given element.
	 *
	 * @param BaseElementModel $element
	 * @return string
	 */
	public function getEditorHtml(BaseElementModel $element)
	{
		#-- Reformat the input name into something that looks more like an ID
		//$id = craft()->templates->formatInputId($element->id);
    	#-- Figure out what that ID is going to look like once it has been namespaced
    	$namespacedId = craft()->templates->getNamespace();

		#-- Start/End Dates
		$html = craft()->templates->render('venti/locations/_editor', array(
			'location' => $element,
			'countries' => LocationHelper::countryOptions(),
			'defaultCountry' => LocationHelper::country(),
			'namespacedId' => $namespacedId,
		));

		#-- Everything else
		$html .= parent::getEditorHtml($element);

		return $html;
	}

}
