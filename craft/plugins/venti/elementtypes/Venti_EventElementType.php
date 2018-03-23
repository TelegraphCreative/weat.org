<?php
namespace Craft;

/**
 * Venti - Event element type
 */
class Venti_EventElementType extends BaseElementType
{
	/**
	 * Returns the element type name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return Craft::t('Events');
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
	     return true;
	}


	/**
	* @inheritDoc IElementType::hasStatuses()
	*
	* @return bool
	*/
	public function hasStatuses()
	{
		return true;
	}


	/**
     * @inheritDoc IElementType::getStatuses()
     *
     * @return array|null
     */
    public function getStatuses()
    {
        return array(
			Venti_EventModel::LIVE => Craft::t('Live'),
            Venti_EventModel::EXPIRED => Craft::t('Expired'),
            BaseElementModel::DISABLED => Craft::t('Disabled')
        );
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
				'label'    => Craft::t('All Events'),
				'criteria' => array(
					'isrepeat' => 'null'
				)
			)
		);

		foreach (craft()->venti_groups->getAllGroups() as $group)
		{
			$key = 'group:'.$group->id;

			$sources[$key] = array(
				'label'    => $group->name,
				'criteria' => array(
					'groupId' => $group->id,
					'isrepeat' => 'is not null'
				)
			);
		}

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
			'startDate' => Craft::t('Start Date'),
			'endDate'   => Craft::t('End Date'),
			'allDay'	=> Craft::t('All Day'),
			'group'		=> Craft::t('Group'),
			'summary'   => Craft::t('Repeats'),
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
		switch ($attribute)
		{
			case 'startDate':
			case 'endDate':
			{
				$date = $element->$attribute;

				if ($date)
				{
					if ($element->allDay == 1)
					{
						return $date->localeDate();
					}
					else
					{
						return $date->localeDate() .' '. $date->localeTime();
					}

				}
				else
				{
					return '';
				}
			}
			case 'allDay':
			{
				if($element->$attribute == 1)
				{
					return Craft::t('Yes');
				}
				else
				{
					return Craft::t('No');
				}
			}
			case 'group':
			{
				$color = $element->getColor();
				return "<div class='group-label-color'><span class='menu-label-color' style='background-color:".$color.";'></span></div>";
			}
			case 'summary':
			{
				//$summary = "<a href='#view-dates'>". $element->$attribute . "</a>";
				$summary = $element->$attribute;
				return $element->$attribute != "" ? $summary : '';
			}

			default:
			{
				return parent::getTableAttributeHtml($element, $attribute);
			}
		}
	}

	/**
	 * Defines any custom element criteria attributes for this element type.
	 *
	 * @return array
	 */
	public function defineCriteriaAttributes()
	{
		return array(
			'elementId'     	=> AttributeType::Mixed,
			'group'   			=> AttributeType::Mixed,
			'groupId' 			=> AttributeType::Mixed,
			'eid' 				=> AttributeType::Number,
			'cid' 				=> AttributeType::Number,
			'startDate'  		=> AttributeType::Mixed,
			'endDate'    		=> AttributeType::Mixed,
            'rRule'      		=> AttributeType::Mixed,
            'repeat'    		=> AttributeType::Mixed,
			'allDay'    		=> AttributeType::Number,
            'summary'    		=> AttributeType::Mixed,
			'isrepeat'    		=> AttributeType::Number,
			'location'   		=> Attributetype::Mixed,
			'specificLocation' 	=> AttributeType::Mixed,
			'registration'	 	=> AttributeType::Mixed,
			'order'      		=> array(AttributeType::String, 'default' => 'recurr.startDate asc'),
			'between'       	=> AttributeType::Mixed,
			'status'        	=> array(AttributeType::String, 'default' => Venti_EventModel::LIVE),
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
			->addSelect('venti.elementId, venti.groupId, venti.allDay, venti.rRule, venti.repeat, venti.summary, venti.locale, venti.location, venti.specificLocation, venti.registration, recurr.startDate, recurr.endDate, recurr.isrepeat, recurr.eid, recurr.cid')
			->join('venti_events venti', 'venti.elementId = elements.id')
			->rightJoin('venti_recurr recurr', 'recurr.cid = venti.id')
			->andWhere(DbHelper::parseParam('venti.locale', $criteria->locale, $query->params))
			->group('recurr.eid');



		if ($criteria->groupId)
		{
			$query->andWhere(DbHelper::parseParam('venti.groupId', $criteria->groupId, $query->params));
		}

		if ($criteria->group)
		{
			$query->join('venti_groups venti_groups', 'venti_groups.id = venti.groupId');
			if (is_numeric($criteria->group))
			{
				$query->andWhere(DbHelper::parseParam('venti_groups.id', $criteria->group, $query->params));
			}
			else
			{
				$query->andWhere(DbHelper::parseParam('venti_groups.handle', $criteria->group, $query->params));
			}

		}

		if ($criteria->startDate)
		{
			$query->andWhere(DbHelper::parseDateParam('recurr.startDate', $criteria->startDate, $query->params));
		}

		if ($criteria->endDate)
		{
			$query->andWhere(DbHelper::parseDateParam('recurr.endDate', $criteria->endDate, $query->params));
		}

		if($criteria->summary)
        {
            $query->andWhere(DbHelper::parseParam('venti.summary', $criteria->summary, $query->params));
        }

		if($criteria->isrepeat)
        {
            $query->andWhere(DbHelper::parseParam('recurr.isrepeat', $criteria->isrepeat, $query->params));
        }

		if($criteria->rRule)
        {
            $query->andWhere(DbHelper::parseParam('venti.rRule', $criteria->rRule, $query->params));
        }

		if($criteria->eid)
        {
            $query->andWhere(DbHelper::parseParam('recurr.eid', $criteria->eid, $query->params));
        }

		if($criteria->repeat)
        {
            $query->andWhere(DbHelper::parseParam('venti.repeat', $criteria->repeat, $query->params));
        }

		if($criteria->allDay)
        {
            $query->andWhere(DbHelper::parseParam('venti.allDay', $criteria->allDay, $query->params));
        }

		if($criteria->location)
        {
            $query->andWhere(DbHelper::parseParam('venti.location', $criteria->location, $query->params));
        }

		if($criteria->specificLocation)
        {
            $query->andWhere(DbHelper::parseParam('venti.specificLocation', $criteria->specificLocation, $query->params));
        }

		if($criteria->registration)
        {
            $query->andWhere(DbHelper::parseParam('venti.registration', $criteria->registration, $query->params));
        }


		if($criteria->between)
        {
        	$dates = array();
        	$interval = array();

        	if(!is_array($criteria->between))
        	{
        		$criteria->between = ArrayHelper::stringToArray($criteria->between);
        	}

        	if (count($criteria->between) == 2)
        	{
        		foreach ($criteria->between as $ref)
        		{
        			if (!$ref instanceof \DateTime)
					{
						$dates[] = DateTime::createFromString($ref, craft()->getTimeZone());
					}
					else
					{
						$dates[] = $ref;
					}
        		}

        		if ($dates[0] > $dates[1])
        		{
        			$interval[0] = $dates[1];
        			$interval[1] = $dates[0];
        		}
        		else
        		{
        			$interval = $dates;
        		}

        		$query->andWhere('(recurr.startDate BETWEEN :betweenStartDate AND :betweenEndDate) OR (:betweenStartDate BETWEEN recurr.startDate AND recurr.endDate)',
        			array(
        				':betweenStartDate'   => DateTimeHelper::formatTimeForDb($interval[0]->getTimestamp()),
        				':betweenEndDate'     => DateTimeHelper::formatTimeForDb($interval[1]->getTimestamp()),
        			)
        		);
        	}
        }

		if ($criteria->ref)
		{
			$refs = ArrayHelper::stringToArray($criteria->ref);
			$conditionals = array();

			foreach ($refs as $ref)
			{
				$parts = array_filter(explode('/', $ref));

				if ($parts)
				{
					if (count($parts) == 1)
					{
						$conditionals[] = DbHelper::parseParam('elements_i18n.slug', $parts[0], $query->params);
					}
					else
					{
						$conditionals[] = array('and',
							DbHelper::parseParam('venti_groups.handle', $parts[0], $query->params),
							DbHelper::parseParam('elements_i18n.slug', $parts[1], $query->params)
						);
					}
				}
			}

			if ($conditionals)
			{
				if (count($conditionals) == 1)
				{
					$query->andWhere($conditionals[0]);
				}
				else
				{
					array_unshift($conditionals, 'or');
					$query->andWhere($conditionals);
				}
			}
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
		$element->startDate 		= DateTime::createFromString($params['startDate'], craft()->timezone);
		$element->endDate 			= DateTime::createFromString($params['endDate'], craft()->timezone);
		$element->rRule  			= $params['rRule'];
		$element->summary 			= $params['summary'];
		$element->repeat 			= $params['repeat'];
		$element->isrepeat 			= $params['isrepeat'];
		$element->allDay 			= $params['allDay'];
		$element->location   		= $params['location'];
		$element->specificLocation 	= $params['specificLocation'];
		$element->registration    	= $params['registration'];



		return craft()->venti_events->saveEvent($element,$element->locale);
	}



	/**
	 * Populates an element model based on a query result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function populateElementModel($row)
	{
		return Venti_EventModel::populateModel($row);
	}


	/**
	 *
	 *
	 * @param array $row
	 * @return array
	 */

	public function routeRequestForMatchedElement(BaseElementModel $element)
	{
		//$element = craft()->urlManager->getMatchedElement();//
		if ($element->getStatus() == Venti_EventModel::LIVE)
		{
			$group = craft()->venti_groups->getGroupById($element->groupId);

			if ($group->hasUrls && array_key_exists(craft()->language, $group->getLocales()))
			{
			    return array(
			        'action' => 'templates/render',
			        'params' => array(
			            'template' => $group->template,
			            'variables' => array(
			                'event' => $element
			            )
			        )
			    );
			}
		}

		return false;
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


		// Now figure out what we can do with these
		$actions = array();

		$groups = craft()->venti_groups->getEditableGroupIds();

		if (!empty($groups))
		{
			#-- for now these are always on
			$userSessionService = craft()->userSession;
			$canSetStatus = false;
			$canEdit = false;
			$canDelete = false;

			foreach ($groups as $group)
			{
				$canPublishEvents = $userSessionService->checkPermission('publishEvents:'.$group['id']);
				$canDeleteEvents = $userSessionService->checkPermission('deleteEvents:'.$group['id']);

				// Only show the Set Status action if we're sure they can make changes in all the groups
				if (!(
					$canPublishEvents && $canDeleteEvents
				))
				{
					$canSetStatus = false;
				}

				// Show the Edit action if they can publish changes to *any* of the groups
				// (the trigger will disable itself for events that aren't editable)
				if ($canPublishEvents)
				{
					$canEdit = true;
				}

				if($canDeleteEvents)
				{
					$canDelete = true;
				}
			}


			if ($canSetStatus)
			{
				$setStatusAction = craft()->elements->getAction('SetStatus');
				$setStatusAction->onSetStatus = function(Event $event)
				{
					if ($event->params['status'] == BaseElementModel::ENABLED)
					{
						// Set a Post Date as well
						// craft()->db->createCommand()->update(
						// 	'venti_events',
						// 	array('and', array('in', 'id', $event->params['elementIds']))
						// );
					}
				};
				$actions[] = $setStatusAction;
			}


			if ($canEdit)
			{
				$editAction = craft()->elements->getAction('Edit');
				$editAction->setParams(array(
					'label' => Craft::t('Edit event'),
				));
				$actions[] = $editAction;
			}


			// Delete?
			if ($canDelete)
			{
				$deleteAction = craft()->elements->getAction('Delete');
				$deleteAction->setParams(array(
					'confirmationMessage' => Craft::t('Are you sure you want to delete the selected events?'),
					'successMessage'      => Craft::t('Events deleted.'),
				));
				$actions[] = $deleteAction;
			}
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
		return array('startDate','endDate','summary','title');
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

		$localeData = craft()->i18n->getLocaleData(craft()->language);
        $dateFormatter = $localeData->getDateFormatter();
        $dateFormat = $dateFormatter->getDatepickerPhpFormat();
        $timeFormat = $dateFormatter->getTimepickerPhpFormat();

		#-- Start/End Dates
		$html = craft()->templates->render('venti/_editor', array(
			'event' 			=> $element,
			'dateFormat' 		=> $dateFormat,
			'timeFormat' 		=> $timeFormat,
			'group' 			=> craft()->venti_groups->getGroupById($element->groupId),
			'namespacedId' 		=> $namespacedId,
			'permissionSuffix'  => ':'.craft()->venti_groups->getGroupById($element->groupId)
		));

		#-- Everything else
		$html .= parent::getEditorHtml($element);

		return $html;
	}



	/**
     * @inheritDoc IElementType::getElementQueryStatusCondition()
     *
     * @param DbCommand $query
     * @param string    $status
     *
     * @return array|false|string|void
     */
    public function getElementQueryStatusCondition(DbCommand $query, $status)
    {
        $currentTimeDb = DateTimeHelper::currentTimeForDb();

        switch ($status)
        {
            case Venti_EventModel::LIVE:
            {
                return array('and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    //"recurr.endDate <= '{$currentTimeDb}'",
                    //array('or', 'recurr.endDate is null', "recurr.endDate > '{$currentTimeDb}'")
                );
            }

            case Venti_EventModel::EXPIRED:
            {
                return array('and',
                    'elements.enabled = 1',
                    'elements_i18n.enabled = 1',
                    'recurr.endDate is not null',
                    "recurr.endDate <= '{$currentTimeDb}'"
                );
            }
        }
    }
}
