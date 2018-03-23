<?php
namespace Craft;

/**
 * Venti_Events service
 */
class Venti_EventsService extends BaseApplicationComponent
{
	/**
	 * Returns an event by its ID.
	 *
	 * @param int $eventId
	 * @return Events_EventModel|null
	 */
	public function getEventById($eventId, $localeId = null)
	{
		return craft()->elements->getElementById($eventId, 'Venti_Event', $localeId);
	}


	/**
	 * Returns an event by its EID.
	 *
	 * @param int $recurrId
	 * @return Events_EventModel|null
	 */
	public function getEventByRecurrId($recurrId, $localeId = null)
	{
		if (!$recurrId)
	    {
	         return null;
	    }

		$criteria = craft()->elements->getCriteria('Venti_Event');
	    $criteria->cid = $recurrId;
	    $criteria->locale = $localeId;
	    $criteria->status = null;
	    $criteria->localeEnabled = null;
	    return $criteria->first();
	}


	/**
	 * Returns an next event by ID.
	 *
	 * @param int $eventId
	 * @return Events_EventModel|null
	 */
	public function nextEvent($criteria = null)
	{
		$now = new DateTime('now', new \DateTimeZone(craft()->getTimeZone()));

		if ($criteria == null && !array_key_exists('id', $criteria)) {
			return null;
		}

			$eventId = $criteria['id'];
			$localeId = array_key_exists('locale', $criteria) ? $criteria['locale'] : craft()->language;

	        if ($eventId == null)
	        {
	            $criteria = craft()->elements->getCriteria('Venti_Event',
	                array(
	                    "startDate" => array('>'.$now),
						"limit" => 1,
	                    "locale" => $localeId
	                )
	            );
	        }
	        else
	        {
	            $criteria = craft()->elements->getCriteria('Venti_Event',
	                array(
	                    "startDate" => array('and','>'.$now),
	                    "id" => $eventId,
						"limit" => 1,
	                    "locale" => $localeId
	                )
	            );
	        }

        return $criteria->first();

	}

	/**
	 * Saves an event.
	 *
	 * @param Venti_EventModel $event
	 * @throws Exception
	 * @return array
	 */
	public function saveEvent(Venti_EventModel $event, $locale = null)
	{
		$isNewEvent = !$event->id;

		#-- Venti Settings
		$settings = craft()->plugins->getPlugin('venti')->getSettings();

		#-- Event data
		if (!$isNewEvent)
		{

			$eventRecord = Venti_EventRecord::model()->findByAttributes(array("id" => $event->cid));

			if (!$eventRecord)
			{
				throw new Exception(Craft::t('No event exists with the ID “{id}”', array('id' => $event->cid)));
			}
		}
		else
		{
			$eventRecord = new Venti_EventRecord();
		}

		$eventRecord->groupId 	= $event->groupId;
		$eventRecord->startDate = $event->startDate;
		$eventRecord->endDate   = $event->endDate;
		$eventRecord->repeat    = $event->repeat;
		$eventRecord->allDay    = $event->allDay;
		$eventRecord->summary   = $event->summary;
		$eventRecord->rRule     = $event->rRule;
		$eventRecord->locale    = $event->locale;
		$eventRecord->location  = $event->location;
		$eventRecord->specificLocation = $event->specificLocation;
		$eventRecord->registration = $event->registration;


		$eventRecord->validate();
		$event->addErrors($eventRecord->getErrors());

		if ($event->hasErrors())
		{
			return false;
		}




		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

		try
		{
			#-- Fire an 'onBeforeSaveEvent' event
			$evt = new Event($this, array(
				'event'      => $event,
				'isNewEvent' => $isNewEvent
			));

			$this->onBeforeSaveEvent($evt);

			#-- Is the event giving us the go-ahead?
			if ($evt->performAction)
			{

				$success = craft()->elements->saveElement($event);

				if (!$success)
				{
					#-- Save the new dateCreated and dateUpdated dates on the model
					if ($isNewEvent)
					{
						$event->dateCreated = new DateTime('now');
					}

					$event->dateUpdated = new DateTime('now');

					if ($event->getError('title'))
					{
						#-- Grab all of the original errors.
						$errors = $event->getErrors();

						#-- Grab just the title error message.
						$originalTitleError = $errors['title'];

						#-- Clear the old.
						$event->clearErrors();

						#-- Create the new "title" error message.
						$errors['title'] = str_replace(Craft::t('Title'), $eventType->titleLabel, $originalTitleError);

						#-- Add all of the errors back on the model.
						$event->addErrors($errors);
					}

					return false;
				}

				#-- Now that we have an element ID, save it on the other stuff
				if ($isNewEvent)
				{
					//$eventRecord->id = $event->id;
					$eventRecord->elementId = $event->id;
				}

				$success = $eventRecord->save(false);


				if($success)
				{
					#-- Initial event is already saved according to everything below this.
					$isNewEvent = false;

					#-- Save Repeat Events
					if ($event->repeat == 1 && $event->rRule != null)
					{

						if(craft()->venti_recurr->saveRecurrData($event,$eventRecord))
						{
							craft()->userSession->setNotice(Craft::t('Recurring events saved'));
						}
					}
					else
					{
						#-- If recurr data already present delete to make way for he new
						if (($recurrRecord = Venti_RecurrRecord::model()->findByAttributes(array("cid" => $eventRecord->getAttribute('id'))))) {
							$recurrRecord->deleteAllByAttributes(array("cid" => $eventRecord->getAttribute('id')));
						}

						#-- Saving Single Event
						$recurrModel = new Venti_RecurrModel();
						$recurrModel->setAttribute('cid', $eventRecord->getAttribute('id'));
						$recurrModel->setAttribute('startDate', $eventRecord->getAttribute('startDate'));
						$recurrModel->setAttribute('endDate', $eventRecord->getAttribute('endDate'));
						$recurrModel->setAttribute('isrepeat', 0);


						if(!craft()->venti_recurr->saveRecurrEvent($recurrModel))
						{
							craft()->userSession->setNotice(Craft::t('Single event not saved'));
						}
						else
						{
							craft()->userSession->setNotice(Craft::t('Event saved'));
						}
					}

					#-- Update search index with event
					craft()->search->indexElementAttributes($event);


					#-- Update the locale records and content

					#-- We're saving all of the element's locales here to ensure that they all exist and to update the URI in
					#-- the event that the URL format includes some value that just changed

					$eventRecords = array();

					if (!$isNewEvent)
					{

						$existingEventRecords = Venti_EventRecord::model()->findAllByAttributes(array(
							'elementId' => $event->id
						));

						foreach ($existingEventRecords as $record)
						{
							$eventRecords[$record->locale] = $record;
						}
					}

					$mainEventLocaleId = $event->locale;

					$locales = $event->getLocales();
					$localeIds = array();

					if (!$locales)
					{
						throw new Exception('All elements must have at least one locale associated with them');
					}

					foreach ($locales as $localeId => $localeInfo)
					{
						if (is_numeric($localeId) && is_string($localeInfo))
						{
							$localeId = $localeInfo;
							$localeInfo = array();
						}

						$localeIds[] = $localeId;

						if (!isset($localeInfo['enabledByDefault']))
						{
							$localeInfo['enabledByDefault'] = true;
						}

						if (isset($eventRecords[$localeId]))
						{

							$plugin = craft()->plugins->getPlugin('venti');
							$translateable = $plugin->getSettings()->getAttribute('translate');

							#-- If translateable and already saved event, skip to next
							#-- else save event attributes to all locales.
							if ($translateable)
							{
								continue;
							}
							else
							{
								$localeEventRecord = $eventRecords[$localeId];

								$localeEventRecord->elementId 	= $event->id;
								$localeEventRecord->groupId		= $event->groupId;
								$localeEventRecord->locale 		= $localeId;
								$localeEventRecord->startDate  	= $event->startDate;
								$localeEventRecord->endDate  	= $event->endDate;
								$localeEventRecord->rRule  		= $event->rRule;
								$localeEventRecord->summary  	= $event->summary;
								$localeEventRecord->allDay  	= $event->allDay;
								$localeEventRecord->repeat  	= $event->repeat;
								$localeEventRecord->location    = $event->location;
								$localeEventRecord->specificLocation    = $event->specificLocation;
								$localeEventRecord->registration    = $event->registration;
							}

						}
						else
						{
							$localeEventRecord = new Venti_EventRecord();

							$localeEventRecord->elementId 	= $event->id;
							$localeEventRecord->groupId		= $event->groupId;
							$localeEventRecord->locale 		= $localeId;
							$localeEventRecord->startDate  	= $event->startDate;
							$localeEventRecord->endDate  	= $event->endDate;
							$localeEventRecord->rRule  		= $event->rRule;
							$localeEventRecord->summary  	= $event->summary;
							$localeEventRecord->allDay  	= $event->allDay;
							$localeEventRecord->repeat  	= $event->repeat;
							$localeEventRecord->location    = $event->location;
							$localeEventRecord->specificLocation    = $event->specificLocation;
							$localeEventRecord->registration    = $event->registration;

						}

						#-- Is this the main locale?
						$isMainEvent = ($localeId == $mainEventLocaleId);

						$localeEventRecord->validate();
						$event->addErrors($localeEventRecord->getErrors());

						if ($event->hasErrors())
						{
							return false;
						}


						$success = $localeEventRecord->save(false);

						if ($success)
						{
							#-- Save Repeat Events
							if ($event->repeat == 1 && $event->rRule != null)
							{
								if(craft()->venti_recurr->saveRecurrData($event, $localeEventRecord))
								{
									craft()->userSession->setNotice(Craft::t('Recurring events saved'));
								}

							}
							else
							{
								#-- If recurr data already present delete to make way for he new
						        if (($recurrRecord = Venti_RecurrRecord::model()->findByAttributes(array("cid" => $localeEventRecord->getAttribute('id'))))) {
						            $recurrRecord->deleteAllByAttributes(array("cid" => $localeEventRecord->getAttribute('id')));
						        }

								#-- Saving Single Event
								$recurrModel = new Venti_RecurrModel();
					            $recurrModel->setAttribute('cid', $localeEventRecord->getAttribute('id'));
					            $recurrModel->setAttribute('startDate', $localeEventRecord->getAttribute('startDate'));
					            $recurrModel->setAttribute('endDate', $localeEventRecord->getAttribute('endDate'));
					            $recurrModel->setAttribute('isrepeat', 0);


								if(!craft()->venti_recurr->saveRecurrEvent($recurrModel))
								{
					                craft()->userSession->setNotice(Craft::t('Single event not saved'));
					            }

							}
						}
						else
						{
							#-- Pass any validation errors on to the element
							$event->addErrors($localeEventRecord->getErrors());
							if ($event->hasErrors())
							{
								return false;
							}

							#-- Don't bother with any of the other locales
							break;
						}
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
			#-- Fire an 'onSaveEntry' event
			$this->onSaveEvent(new Event($this, array(
				'event'      => $event,
				'isNewEvent' => $isNewEvent
			)));
		}

		return $success;
	}

	// Events

	/**
	 * Fires an 'onBeforeSaveEvent' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeSaveEvent(Event $event)
	{
		$this->raiseEvent('onBeforeSaveEvent', $event);
	}

	/**
	 * Fires an 'onSaveEvent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveEvent(Event $event)
	{
		$this->raiseEvent('onSaveEvent', $event);
	}

}
