<?php
namespace Craft;

/**
 * Venti_Locations service
 */
class Venti_LocationsService extends BaseApplicationComponent
{

	private $_allLocationIds;
	private $_locationsById;
	private $_fetchedAllLocations = false;
	/**
	 * Returns a location by its ID.
	 *
	 * @param int $locationId
	 * @return Events_LocationModel|null
	 */
	public function getLocationById($locationId)
	{
		return craft()->elements->getElementById($locationId, 'Venti_Location');
	}


	public function getAllLocations($indexBy = null)
	{
		if (!$this->_fetchedAllLocations)
		{
			$locationRecords = Venti_LocationRecord::model()->findAll();
			$this->_locationsById = Venti_LocationModel::populateModels($locationRecords, 'id');
			$this->_fetchedAllLocations = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_locationsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_locationsById);
		}
		else
		{
			$locations = array();

			foreach ($this->_locationsById as $location)
			{
				$locations[$location->$indexBy] = $location;
			}

			return $locations;
		}
	}



	/**
	 * Saves a location.
	 *
	 * @param Venti_LocationModel $event
	 * @throws Exception
	 * @return array
	 */
	public function saveLocation(Venti_LocationModel $location)
	{
		$isNewLocation = !$location->id;

		#-- Location data
		if (!$isNewLocation)
		{

			$locationRecord = Venti_LocationRecord::model()->findByAttributes(array("id" => $location->id));

			if (!$locationRecord)
			{
				throw new Exception(Craft::t('No location exists with the ID “{id}”', array('id' => $location->id)));
			}
		}
		else
		{
			$locationRecord = new Venti_LocationRecord();
		}


		$locationRecord->address 		= $location->address;
		$locationRecord->addressTwo 	= $location->addressTwo;
		$locationRecord->city   		= $location->city;
		$locationRecord->state   		= $location->state;
		$locationRecord->zipCode    	= $location->zipCode;
		$locationRecord->country   		= $location->country;
		$locationRecord->longitude     	= $location->longitude;
		$locationRecord->latitude    	= $location->latitude;
		$locationRecord->website    	= $location->website;


		$locationRecord->validate();
		$location->addErrors($locationRecord->getErrors());

		if ($location->hasErrors())
		{
			return false;
		}



		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			#-- Fire an 'onBeforeSaveLocation' event
			$evt = new Event($this, array(
				'location'      => $location,
				'isNewLocation' => $isNewLocation
			));

			$this->onBeforeSaveLocation($evt);

			#-- Is the event giving us the go-ahead?
			if ($evt->performAction)
			{

				$success = craft()->elements->saveElement($location);

				if (!$success)
				{
					#-- Save the new dateCreated and dateUpdated dates on the model
					//$location->dateCreated = new DateTime('@'.$elementRecord->dateCreated->getTimestamp());
					//$location->dateUpdated = new DateTime('@'.$elementRecord->dateUpdated->getTimestamp());

					if ($location->getError('title'))
					{
						#-- Grab all of the original errors.
						$errors = $location->getErrors();

						#-- Grab just the title error message.
						$originalTitleError = $errors['title'];

						#-- Clear the old.
						$location->clearErrors();

						#-- Create the new "title" error message.
						//$errors['title'] = str_replace(Craft::t('Title'), $eventType->titleLabel, $originalTitleError);

						#-- Add all of the errors back on the model.
						$location->addErrors($errors);
					}

					return false;
				}

				#-- Now that we have an element ID, save it on the other stuff
				if ($isNewLocation)
				{

					$locationRecord->id = $location->id;
				}

				$success = $locationRecord->save(false);


				if($success)
				{
					#-- Initial location is already saved according to everything below this.
					$isNewLocation = false;

					#-- Update search index with event
					craft()->search->indexElementAttributes($location);

					#-- Update the locale records and content
					#-- the event that the URL format includes some value that just changed

					$locationRecords = array();

					if (!$isNewLocation)
					{

						$existingLocationRecords = Venti_LocationRecord::model()->findAllByAttributes(array(
							'id' => $location->id
						));

					}
				}
			}
			else
			{
				$success = false;
			}

			if ($transaction !== null)
			{
				$transaction->commit();
			}

		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}


		if ($success)
		{
			#-- Fire an 'onSaveLocation' event
			$this->onSaveLocation(new Event($this, array(
				'location'      => $location,
				'isNewLocation' => $isNewLocation
			)));
		}

		return $success;
	}

	// Events

	/**
	 * Fires an 'onBeforeSaveLocation' event.
	 *
	 * @param Event $location
	 */
	public function onBeforeSaveLocation(Event $event)
	{
		$this->raiseEvent('onBeforeSaveLocation', $event);
	}

	/**
	 * Fires an 'onSaveLocation' event.
	 *
	 * @param Event $event
	 */
	public function onSaveLocation(Event $event)
	{
		$this->raiseEvent('onSaveLocation', $event);
	}

}
