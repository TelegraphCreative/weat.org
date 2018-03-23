<?php
namespace Craft;

/**
 * Venti_Event controller
 */
class Venti_EventController extends Venti_BaseEventController
{

	protected $allowAnonymous = array('actionViewEventByStartDate,actionViewDetail,actionViewEventByEid,actionViewICS');


	/**
	 * Event index
	 */
	public function actionEventIndex()
	{
		$variables['groups'] = craft()->venti_groups->getAllGroups();

		$this->renderTemplate('venti/_index', $variables);
	}

	/**
	 * Edit an event.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditEvent(array $variables = array())
	{

		$this->_prepEditEntryVariables($variables);

		// Make sure they have permission to edit this entry
		$eventMOD = new Venti_EventModel();
		if (array_key_exists('eventId',$variables))
		{
			$eventMOD->setAttribute('id',$variables['eventId']);
		}
		$eventMOD->setAttribute('locale',$variables['localeId']);
		$eventMOD->setAttribute('groupId',$variables['group']['id']);

		$this->enforceEditEventPermissions($eventMOD);

		$currentUser = craft()->userSession->getUser();
		$variables['permissionSuffix'] = ':'.$variables['group']['id'];

        $variables['fullPageForm'] = true;

		if (!empty($variables['groupHandle']))
		{
			$variables['group'] = craft()->venti_groups->getGroupByHandle($variables['groupHandle']);
		}
		else if (!empty($variables['groupId']))
		{
			$variables['group'] = craft()->venti_groups->getGroupById($variables['groupId']);
		}

		if (empty($variables['group']))
		{
			throw new HttpException(404);
		}

		// Now let's set up the actual event
		if (empty($variables['event']))
		{
			if (!empty($variables['eventId']))
			{

				$variables['event'] = craft()->venti_events->getEventById($variables['eventId'], $variables['localeId']);

				if (!$variables['event'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['event'] = new Venti_EventModel();
				$variables['event']->groupId = $variables['group']->id;
				$variables['event']->enabled = true;

				if (!empty($variables['localeId']))
				{
					$variables['event']->locale = $variables['localeId'];
				}

				if (craft()->isLocalized())
				{
					// Set the default locale status based on the section's settings
					foreach ($variables['group']->getLocales() as $locale)
					{
						if ($locale->locale == $variables['event']->locale)
						{
							$variables['event']->localeEnabled = $locale->enabledByDefault;
							break;
						}
					}
				}
				else
				{
					// Set the default event status based on the group's settings
					foreach ($variables['group']->getLocales() as $locale)
					{
						if (!$locale->enabledByDefault)
						{
							$variables['event']->enabled = false;
						}
						break;
					}
				}
			}
		}

		// Tabs
		$variables['tabs'] = array();

		foreach ($variables['group']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['event']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['event']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = array(
				'label' => $tab->name,
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		if (!$variables['event']->id)
		{
			$variables['title'] = Craft::t('Create a new event');
		}
		else
		{
			$variables['title'] = $variables['event']->title;
		}


		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Events'), 'url' => UrlHelper::getUrl('venti')),
			array('label' => $variables['group']->name, 'url' => UrlHelper::getUrl('venti'))
		);

		$variables['canDeleteEvent'] = $variables['event']->id && (
			($currentUser->can('deleteEvents'.$variables['permissionSuffix']))
		);

		// Redirect Urls
		// ---------------------------------------------------------------------

		// Can't just use the entry's getCpEditUrl() because that might include the locale ID when we don't want it
		$variables['baseCpEditUrl'] = 'venti/'.$variables['group']->handle.'/'.$variables['event']->id.'-'.$variables['event']->slug;
		// on command save go back to event
		$variables['saveShortcutRedirect'] = $variables['baseCpEditUrl'] .
			(craft()->isLocalized() && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');
		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
			(craft()->isLocalized() && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');


		// Enabled locales
		// ---------------------------------------------------------------------

		if (craft()->isLocalized())
		{
			if ($variables['event']->id)
			{
				$variables['enabledLocales'] = craft()->elements->getEnabledLocalesForElement($variables['event']->id);
			}
			else
			{
				$variables['enabledLocales'] = array();

				foreach ($variables['group']->getLocales() as $locale)
				{
					if ($locale->enabledByDefault)
					{
						$variables['enabledLocales'][] = $locale->locale;
					}
				}
			}
		}


		// Render the template!
		craft()->templates->includeCssResource('css/entry.css');
		$this->renderTemplate('venti/_edit', $variables);
	}

	/**
	 * Saves an event.
	 */
	public function actionSaveEvent()
	{
		$this->requirePostRequest();

		$eventId = craft()->request->getPost('eventId');
		$locale = craft()->request->getPost('locale');

		$event = $this->_getEventModel();

		// Set the event attributes, defaulting to the existing values for whatever is missing from the post data
		$event->groupId 			= craft()->request->getPost('groupId', $event->groupId);
		$event->startDate  			= (($startDate = craft()->request->getPost('startDate')) ? DateTime::createFromString($startDate, craft()->timezone) : null);
		$event->endDate    			= (($endDate   = craft()->request->getPost('endDate'))   ? DateTime::createFromString($endDate,   craft()->timezone) : null);
		$event->repeat   			= craft()->request->getPost('repeat');
		$event->allDay 				= craft()->request->getPost('allDay');
		$event->summary 			= craft()->request->getPost('summary');
		$event->isrepeat 			= (craft()->request->getPost('isrepeat') ? craft()->request->getPost('isrepeat') : null);
		$event->rRule 				= (craft()->request->getPost('rRule') ? craft()->request->getPost('rRule') : null);
		$event->slug        		= craft()->request->getPost('slug');
		$event->enabled     		= (bool) craft()->request->getPost('enabled', $event->enabled);
		$event->localeEnabled 		= (bool) craft()->request->getPost('localeEnabled', $event->localeEnabled);
		$event->location    		= (craft()->request->getPost('location') ? craft()->request->getPost('location') : null);
		$event->specificLocation   	= (craft()->request->getPost('specificLocation') ? craft()->request->getPost('specificLocation') : null);


		$this->enforceEditEventPermissions($event);
		$userSessionService = craft()->userSession;
		$currentUser = $userSessionService->getUser();

		$continueEditingUrl = craft()->request->getPost('continueEditingUrl');


		if (craft()->request->getPost('registration') && array_key_exists('type',craft()->request->getPost('registration')))
		{
			$event->registration = craft()->request->getPost('registration');
		}
		else
		{
			$event->registration = null;
		}

		$event->getContent()->title = craft()->request->getPost('title', $event->title);
		$event->setContentFromPost('fields');

		//permission enforcement
		if ($event->enabled)
		{
			if ($event->id)
			{
				$userSessionService->requirePermission('publishEvents:'.$event->groupId);
			}
			else if (!$currentUser->can('publishEvents:'.$event->groupId))
			{
				$event->enabled = false;
			}
		}


		if (!craft()->venti_events->saveEvent($event,$event->locale))
		{
			$userSessionService->setError(Craft::t('Couldn’t save event.'));

			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $event->getErrors(),
				));
			}
			else
			{
				/* Send the event back to the template
				 * newGroup is applied if event group was changed by select so tabs and fields pull from correct group.
				 */
				craft()->urlManager->setRouteVariables(array(
					'event' => $event,
					'newGroup' => $event->getGroup(),
					'locale' => $locale,
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
					$return['cpEditUrl'] = $event->getCpEditUrl();
				}
				$this->returnJson($return);
			}
			else
			{
				$this->redirectToPostedUrl($event);
			}
		}
	}

	/**
	 * Deletes an event.
	 */
	public function actionDeleteEvent()
	{
		$this->requirePostRequest();

		$eventId = craft()->request->getRequiredPost('eventId');
		$localeId = craft()->request->getPost('locale');
		$event = craft()->venti_events->getEventById($eventId, $localeId);

		craft()->userSession->requirePermission('deleteEvents:'.$event->groupId);

		if (craft()->request->isAjaxRequest())
        {

            if(craft()->elements->deleteElementById($eventId))
            {
				craft()->userSession->setNotice(Craft::t('Event deleted'));
                $this->returnJson(array(
                    'success' => true
                ));
            }
            else
            {
				craft()->userSession->setError(Craft::t('Couldn’t delete event'));
                $this->returnJson(array(
                    'errors' => $event->getErrors(),
                ));
            }
        }
        else
        {
			if (craft()->elements->deleteElementById($eventId))
			{
				craft()->userSession->setNotice(Craft::t('Event deleted'));
				$this->redirectToPostedUrl();
			}
			else
			{
				craft()->userSession->setError(Craft::t('Couldn’t delete event'));
			}
		}
	}


	/**
	 * Fetches or creates an Venti_EventModel.
	 *
	 * @throws Exception
	 * @return Venti_EventModel
	 */
	private function _getEventModel()
	{
		$eventId = craft()->request->getPost('eventId');
		$localeId = craft()->request->getPost('locale');

		if ($eventId)
		{
			$event = craft()->venti_events->getEventById($eventId, $localeId);

			if (!$event)
			{
				throw new Exception(Craft::t('No event exists with the ID “{id}”.', array('id' => $eventId)));
			}
		}
		else
		{
			$event = new Venti_EventModel();
			$event->groupId = craft()->request->getRequiredPost('groupId');

			if ($localeId)
			{
				$event->locale = $localeId;
			}
		}

		return $event;
	}


	/*
     *
     *
     */
    public function actionRecurTextTransform()
    {
        $this->requireAjaxRequest();
        $rule = craft()->request->getPost('rule');
        $lang = craft()->request->getPost('lang') ? craft()->request->getPost('lang') : null;
        $text = craft()->venti_recurr->recurTextTransform($rule, $lang);
        $this->returnJson($text);
    }


	/**
	 * Get Recur Rule String
	 * @return string
	 */

	public function actionGetRuleString()
    {
        $this->requireAjaxRequest();
        $post = craft()->request->getPost();
        $repeat = reset($post)['repeat'];
        $locale = array_key_exists('locale', $repeat) ? $repeat['locale'] : craft()->locale;
        $localeData = craft()->i18n->getLocaleData();
        $lang = $localeData->getLanguageId($locale);
        $ruleString = craft()->venti_rule->getRRule($repeat);
        $ruleHumanString = craft()->venti_rule->recurTextTransform($ruleString, $lang);
		$dateDict = craft()->venti_rule->getIncludedExcludedDates($ruleString);
        $output = [
            "rrule" => $ruleString,
            "readable" => $ruleHumanString,
			"excluded" => array(),
			"included" => array(),
        ];
		if($dateDict != false)
		{
			if (array_key_exists('excludedDates',$dateDict))
			{
				$output['excluded'] = $dateDict['excludedDates'];
			}
			if (array_key_exists('includedDates',$dateDict))
			{
				$output['included'] = $dateDict['includedDates'];
			}
		}

        $this->returnJson($output);
    }



	/**
	 * Switches between two groups.
	 *
	 * @return null
	 */
	public function actionSwitchGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$event = $this->_getEventModel();
		$this->enforceEditEventPermissions($event);
		$this->_populateEventModel($event);

		$variables['groupId'] = $event->groupId;
		$variables['event'] = $event;
		$variables['element'] = craft()->elements->getElementById($event->id);

		$this->_prepEditEntryVariables($variables);

		$paneHtml = craft()->templates->render('venti/events/_tabs', $variables) .
			craft()->templates->render('venti/events/_fields', $variables);

		$this->returnJson(array(
			'variables' => $variables,
			'paneHtml' => $paneHtml,
			'headHtml' => craft()->templates->getHeadHtml(),
			'footHtml' => craft()->templates->getFootHtml(),
		));
	}


	/*
     * Render repeat date modal from ajax call.
     */
    public function actionModal()
    {
        $this->requireAjaxRequest();

        $defaultValues = [
            "frequency" => 0,
            'by' => ['0'],
            'endsOn' => ['0'],
            'on'  => [],
            'starts' => '',
            'enddate' => '',
            'occur' => '',
            'every' => '',
            'exclude' => []
        ];

        $rule = craft()->request->getPost("rrule");
        $locale = craft()->request->getPost("locale");
        $values = $rule != "" ?  craft()->venti_rule->modalValuesArray($rule) : $defaultValues;
        $this->renderTemplate('venti/_modal',array('values' => $values, 'locale' => $locale),false);
    }


/**
	 * Preps entry edit variables.
	 *
	 * @param array &$variables
	 *
	 * @throws HttpException|Exception
	 * @return null
	 */
	private function _prepEditEntryVariables(&$variables)
	{

		// Get the group
		// ---------------------------------------------------------------------

		if (!empty($variables['groupHandle']))
		{
			$variables['group'] = craft()->venti_groups->getGroupByHandle($variables['groupHandle']);
		}
		else if (!empty($variables['groupId']))
		{
			$variables['group'] = craft()->venti_groups->getGroupById($variables['groupId']);
		}

		if (empty($variables['group']))
		{
			throw new HttpException(404);
		}

		// Get the locale date/time formats
		// ---------------------------------------------------------------------
		$localeData = craft()->i18n->getLocaleData(craft()->language);
        $dateFormatter = $localeData->getDateFormatter();
        $dateFormat = $dateFormatter->getDatepickerPhpFormat();
        $timeFormat = $dateFormatter->getTimepickerPhpFormat();

		$variables['dateFormat'] = $dateFormat;
		$variables['timeFormat'] = $timeFormat;



		// Get the locale
		// ---------------------------------------------------------------------

		if (craft()->isLocalized())
		{
			#-- Only use the locales that the user has access to
			$groupLocaleIds = array_keys($variables['group']->getLocales());
			$editableLocaleIds = craft()->i18n->getEditableLocaleIds();
			$variables['localeIds'] = array_merge(array_intersect($groupLocaleIds, $editableLocaleIds));
		}
		else
		{
			$variables['localeIds'] = array(craft()->i18n->getPrimarySiteLocaleId());
		}

		if (!$variables['localeIds'])
		{
			throw new HttpException(403, Craft::t('Your account doesn’t have permission to edit any of this groups’ locales.'));
		}

		if (empty($variables['localeId']))
		{
			$variables['localeId'] = craft()->language;

			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				$variables['localeId'] = $variables['localeIds'][0];
			}
		}
		else
		{
			#-- Make sure they were requesting a valid locale
			if (!in_array($variables['localeId'], $variables['localeIds']))
			{
				throw new HttpException(404);
			}
		}

		// Redirect Urls
		// ---------------------------------------------------------------------

		if (array_key_exists('event', $variables) && array_key_exists('localeId', $variables))
		{
			// Can't just use the entry's getCpEditUrl() because that might include the locale ID when we don't want it
			$variables['baseCpEditUrl'] = 'venti/'.$variables['group']->handle.'/'.$variables['event']->id.'-'.$variables['event']->slug;
			// on command save go back to event
			$variables['saveShortcutRedirect'] = $variables['baseCpEditUrl'] .
				(craft()->isLocalized() && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');
			// Set the "Continue Editing" URL
			$variables['continueEditingUrl'] = $variables['baseCpEditUrl'] .
				(craft()->isLocalized() && craft()->getLanguage() != $variables['localeId'] ? '/'.$variables['localeId'] : '');
		}

	}

	public function actionViewEventByEid(array $variables = array())
	{

		$criteria = craft()->elements->getCriteria('Venti_Event');
	    $criteria->eid = $variables['eid'];
	    $criteria->localeEnabled = null;
		$event = $criteria->first();

		$group = craft()->venti_groups->getGroupById($event['groupId']);
		$template = $group['template'];

		$this->renderTemplate($template,array('event' => $event),false);
	}

	#-- slug & startdate (YYYY-mm-dd)
	public function actionViewEventByStartDate(array $variables = array())
	{
		$startDate = new \DateTime($variables['year'] . "-" . $variables['month'] . "-" . $variables['day']);

		$criteria = craft()->elements->getCriteria('Venti_Event');
	    $criteria->startDate = array('and','>='.$startDate->format('Y-m-d'), '<='.$startDate->modify('+1 day')->format('Y-m-d'));
		$criteria->slug = $variables['slug'];
	    $criteria->localeEnabled = null;
		$event = $criteria->first();

		$group = craft()->venti_groups->getGroupById($event['groupId']);
		$template = $group['template'];

		$this->renderTemplate($template,array('event' => $event),false);

	}

	public function actionViewDetail()
	{
		$segments = craft()->request->getSegments();
		$criteria = craft()->elements->getCriteria('Venti_Event');

		if(DateTime::createFromFormat('Y-m-d', end($segments)) !== FALSE)
		{
			$startDate = new \DateTime(end($segments));
			$criteria->startDate = array('and','>='.$startDate->format('Y-m-d'), '<='.$startDate->modify('+1 day')->format('Y-m-d'));
		}
		elseif(is_numeric(end($segments)))
		{
			$criteria->eid = end($segments);
		}

		#-- assuming second item in segments is slug of event.
		$criteria->slug = $segments[1];
	    $criteria->localeEnabled = null;
		$event = $criteria->first();

		$group = craft()->venti_groups->getGroupById($event['groupId']);
		$template = $group['template'];

		$this->renderTemplate($template,array('event' => $event),false);
	}


	public function actionUpdateEventDates()
    {
		$this->requirePostRequest();

		$timezone = new \DateTimeZone(craft()->getTimeZone());
		#-- ID of event being updated
		$id = craft()->request->getPost('id');
		#-- Grab original event by id
		$element = craft()->elements->getElementById($id);

		#-- Create date times of start & end dates
		$start = new \DateTime(craft()->request->getPost('start'), $timezone);
		$end = new \DateTime(craft()->request->getPost('end'), $timezone);
		$locale = craft()->request->getPost('locale');


		$model = new Venti_EventModel();
		$model->setAttributes($element->getAttributes());
		$model->startDate = $start;
		$model->endDate = $end;

		if($element->repeat == 1)
		{
			$rrule = craft()->venti_rule->updateDTStartEnd($element->rRule, $start, $end);
			$model->rRule = $rrule;
			$model->repeat = 1;
		}

		if(!craft()->venti_events->saveEvent($model,$locale))
		{
			craft()->userSession->setError(Craft::t('Event updates could not be completed.'));
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $model->getErrors(),
				));
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$return['success'] = true;
				$this->returnJson($return);
			}
		}
    }

	public function actionViewICS()
	{	$segments = craft()->request->getSegments();
		return craft()->venti_ics->renderICSFile($segments[2]);
	}


	public function actionRemoveOccurence()
	{
		$this->requirePostRequest();

		$timezone = new \DateTimeZone(craft()->getTimeZone());
		#-- ID of event being updated
		$id = craft()->request->getPost('id');
		#-- Grab original event by id
		$element = craft()->elements->getElementById($id);

		$exDate = new \DateTime(craft()->request->getPost('exDate'), $timezone);
		$locale = craft()->request->getPost('locale');

		$model = new Venti_EventModel();
		$model->setAttributes($element->getAttributes());

		if($element->repeat == 1)
		{
			$rrule = craft()->venti_rule->addExcludedDate($element->rRule, $exDate, $locale );
			$model->rRule = $rrule;
			$model->repeat = 1;
		}

		if(!craft()->venti_events->saveEvent($model,$locale))
		{
			craft()->userSession->setError(Craft::t('Event updates could not be completed.'));
			if (craft()->request->isAjaxRequest())
			{
				$this->returnJson(array(
					'errors' => $model->getErrors(),
				));
			}
		}
		else
		{
			if (craft()->request->isAjaxRequest())
			{
				$return['success'] = true;
				$this->returnJson($return);
			}
		}
	}

	/**
	 * Populates an EventModel with post data.
	 *
	 * @param EventModel $event
	 *
	 * @return null
	 */
	public function _populateEventModel(Venti_EventModel $event)
	{
		$event->slug          = craft()->request->getPost('slug', $event->slug);
		$event->groupId       = craft()->request->getPost('groupId', $event->groupId);
		$event->startDate     = (($startDate = craft()->request->getPost('startDate')) ? DateTime::createFromString($startDate, craft()->timezone) : null);
		$event->endDate    	  = (($endDate   = craft()->request->getPost('endDate'))   ? DateTime::createFromString($endDate,   craft()->timezone) : null);
		$event->rRule     	  = craft()->request->getPost('rRule', $event->rRule);
		$event->repeat 		  = (bool) craft()->request->getPost('repeat', $event->repeat);
		$event->summary 	  = craft()->request->getPost('summary', $event->summary);
		$event->allDay 		  = (bool) craft()->request->getPost('allDay', $event->allDay);
		$event->location 	  = craft()->request->getPost('location', $event->location);
		$event->registration  = craft()->request->getPost('registration', $event->registration);
		$event->enabled       = (bool) craft()->request->getPost('enabled', $event->enabled);
		$event->localeEnabled = (bool) craft()->request->getPost('localeEnabled', $event->localeEnabled);

		$event->getContent()->title = craft()->request->getPost('title', $event->title);

		$fieldsLocation = craft()->request->getParam('fieldsLocation', 'fields');
		$event->setContentFromPost($fieldsLocation);
	}

}
