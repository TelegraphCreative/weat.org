<?php
namespace Craft;

/**
 * Venti Location controller
 */
class Venti_LocationController extends Venti_BaseLocationController
{
	/**
	 * Group index
	 */
	public function actionLocationIndex()
	{
		$variables['locations'] = craft()->venti_locations->getAllLocations();

		$this->renderTemplate('venti/locations/_index', $variables);
	}

	/**
	 * Edit a Location.
	 *
	 * @param array $variables
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditLocation(array $variables = array())
	{

		// Make sure they have permission to edit this entry
		$locationMOD = new Venti_LocationModel();
		if (array_key_exists('locationId',$variables))
		{
			$locationMOD->setAttribute('id',$variables['locationId']);
		}

		$this->enforceEditLocationPermissions($locationMOD);
		$currentUser = craft()->userSession->getUser();

		$variables['brandNewLocation'] = false;
		$variables['fullPageForm'] = true;
		$variables['defaultCountry'] = LocationHelper::country();


		if (!empty($variables['locationId']))
		{
			if (empty($variables['location']))
			{
				$variables['location'] = craft()->venti_locations->getLocationById($variables['locationId']);

				if (!$variables['location'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['location']->title;
		}
		else
		{
			if (empty($variables['location']))
			{
				$variables['location'] = new Venti_LocationModel();
				$variables['brandNewLocation'] = true;
			}

			$variables['title'] = Craft::t('Create a new location');
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Venti'), 'url' => UrlHelper::getUrl('venti')),
			array('label' => Craft::t('Locations'), 'url' => UrlHelper::getUrl('venti/locations')),
		);
		// Can't just use the entry's getCpEditUrl() because that might include the locale ID when we don't want it
		$variables['baseCpEditUrl'] = 'venti/location/{id}-{slug}';

		$variables['canDeleteLocation'] = $variables['location']->id && (
			($currentUser->can('deleteLocations'))
		);

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = $variables['baseCpEditUrl'];

		$variables['countries'] = LocationHelper::countryOptions();
		craft()->templates->includeCssResource('css/entry.css');
		$this->renderTemplate('venti/locations/_edit', $variables);
	}

	/**
	 * Saves a location
	 */
	public function actionSaveLocation()
	{
		$this->requirePostRequest();

		$location = $this->_getLocationModel();

		// Shared attributes
		$location->address         		= craft()->request->getPost('address');
		$location->addressTwo         	= craft()->request->getPost('addressTwo');
		$location->city        			= craft()->request->getPost('city');
		$location->state         		= craft()->request->getPost('state');
		$location->zipCode         		= craft()->request->getPost('zipCode');
		$location->country				= craft()->request->getPost('country');
		$location->longitude         	= craft()->request->getPost('longitude');
		$location->latitude        		= craft()->request->getPost('latitude');
		$location->website         		= craft()->request->getPost('website');

		$location->getContent()->title = craft()->request->getPost('title', $location->title);
		$location->setContentFromPost('fields');

		// Save it
		if (!craft()->venti_locations->saveLocation($location))
		{
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $location->getErrors(),
				));
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t save the location'));
				$this->redirectToPostedUrl($event);

				// Send the event back to the template
				craft()->urlManager->setRouteVariables(array(
					'location' => $location,
				));
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$return['success'] = true;

				if (craft()->request->isCpRequest())
				{
					$return['cpEditUrl'] = $entry->getCpEditUrl();
				}
				$this->returnJson($return);
			}
			else
			{
				$this->redirectToPostedUrl($location);
			}
		}
	}

	/**
	 * Fetches or creates an Venti_LocationModel.
	 *
	 * @throws Exception
	 * @return Venti_LocationModel
	 */
	private function _getLocationModel()
	{
		$locationId = craft()->request->getPost('locationId');

		if ($locationId)
		{
			$location = craft()->venti_locations->getLocationById($locationId);

			if (!$location)
			{
				throw new Exception(Craft::t('No location exists with the ID “{id}”.', array('id' => $locationId)));
			}
		}
		else
		{
			$location = new Venti_LocationModel();

		}

		return $location;
	}

	/**
	 * Deletes an event.
	 */
	public function actionDeleteLocation()
	{
		$this->requirePostRequest();
		$locationId = craft()->request->getRequiredPost('locationId');

		if (craft()->request->isAjaxRequest())
        {
            if(craft()->elements->deleteElementById($locationId))
            {
				craft()->userSession->setNotice(Craft::t('Location deleted'));
                $this->returnJson(array(
                    'success' => true
                ));
            }
            else
            {
				craft()->userSession->setError(Craft::t('Couldn’t delete location'));
                $this->returnJson(array(
                    'errors' => $location->getErrors(),
                ));
            }
        }
        else
        {
			if (craft()->elements->deleteElementById($locationId))
			{
				craft()->userSession->setNotice(Craft::t('Location deleted'));
				$this->redirectToPostedUrl();
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t delete location'));
			}
		}
	}

}
