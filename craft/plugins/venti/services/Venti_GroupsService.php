<?php
namespace Craft;

/**
 * Venti Groups service
 */
class Venti_GroupsService extends BaseApplicationComponent
{
	/**
	 * @var
	 */
	private $_allGroupIds;
	private $_groupsById;
	private $_fetchedAllGroups = false;
	private $_editableGroupIds;

	/**
	 * Returns all of the group IDs.
	 *
	 * @return array
	 */
	public function getAllGroupIds()
	{
		if (!isset($this->_allGroupIds))
		{
			if ($this->_fetchedAllGroups)
			{
				$this->_allGroupIds = array_keys($this->_groupsById);
			}
			else
			{
				$this->_allGroupIds = craft()->db->createCommand()
					->select('id')
					->from('venti_groups')
					->queryColumn();
			}
		}

		return $this->_allGroupIds;
	}

	/**
	 * Returns all groups.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllGroups($indexBy = null)
	{
		if (!$this->_fetchedAllGroups)
		{
			$groupRecords = Venti_GroupRecord::model()->ordered()->findAll();
			$this->_groupsById = Venti_GroupModel::populateModels($groupRecords, 'id');
			$this->_fetchedAllGroups = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_groupsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_groupsById);
		}
		else
		{
			$groups = array();

			foreach ($this->_groupsById as $group)
			{
				$groups[$group->$indexBy] = $group;
			}

			return $groups;
		}
	}

	/**
	 * Gets the total number of groups.
	 *
	 * @return int
	 */
	public function getTotalGroups()
	{
		return count($this->getAllGroupIds());
	}

	/**
	 * Returns a group by its ID.
	 *
	 * @param $calendarId
	 * @return Venti_GroupModel|null
	 */
	public function getGroupById($groupId)
	{
		if (!isset($this->_groupsById) || !array_key_exists($groupId, $this->_groupsById))
		{
			$groupRecord = Venti_GroupRecord::model()->findById($groupId);

			if ($groupRecord)
			{
				$this->_groupsById[$groupId] = Venti_GroupModel::populateModel($groupRecord);
			}
			else
			{
				$this->_groupsById[$groupId] = null;
			}
		}

		return $this->_groupsById[$groupId];
	}

	/**
	 * Gets a group by its handle.
	 *
	 * @param string $groupHandle
	 * @return Venti_GroupModel|null
	 */
	public function getGroupByHandle($groupHandle)
	{
		$groupRecord = Venti_GroupRecord::model()->findByAttributes(array(
			'handle' => $groupHandle
		));

		if ($groupRecord)
		{
			return Venti_GroupModel::populateModel($groupRecord);
		}
	}

	/**
	 * Saves a group.
	 *
	 * @param Venti_GroupModel $group
	 * @throws \Exception
	 * @return bool
	 */
	public function saveGroup(Venti_GroupModel $group)
	{
		$success = false;
		if ($group->id)
		{
			$groupRecord = Venti_GroupRecord::model()->findById($group->id);

			if (!$groupRecord)
			{
				throw new Exception(Craft::t('No group exists with the ID “{id}”', array('id' => $group->id)));
			}

			$oldGroup = Venti_GroupModel::populateModel($groupRecord);
			$isNewGroup = false;
		}
		else
		{
			$groupRecord = new Venti_GroupRecord();
			$isNewGroup = true;
		}

		$groupRecord->name       = $group->name;
		$groupRecord->handle     = $group->handle;
		$groupRecord->color      = $group->color;
		$groupRecord->description= $group->description;
		$groupRecord->hasUrls    = $group->hasUrls;
		$groupRecord->template   = $group->template;

		$groupRecord->hasUrls = $group->hasUrls = true;

		if ($group->hasUrls)
		{
			$groupRecord->template = $group->template;
		}
		else
		{
			$groupRecord->template = $group->template = null;
		}

		$groupRecord->validate();
		$group->addErrors($groupRecord->getErrors());

		// Make sure that all of the URL formats are set properly
		$groupLocales = $group->getLocales();

		if (!$groupLocales)
		{
			$group->addError('localeErrors', Craft::t('At least one locale must be selected for the group.'));
		}

		$firstGroupLocale = null;

		foreach ($groupLocales as $localeId => $groupLocale)
		{
			// Is this the first one?
			if ($firstGroupLocale === null)
			{
				$firstGroupLocale = $groupLocale;
			}

			$errorKey = 'urlFormat-'.$localeId;

			if (empty($groupLocale->urlFormat))
			{
				$group->addError($errorKey, Craft::t('URI cannot be blank.'));
			}
			else if ($group)
			{
				// Make sure no other elements are using this URI already
				$query = craft()->db->createCommand()
					->from('elements_i18n elements_i18n')
					->where(
						array('and', 'elements_i18n.locale = :locale', 'elements_i18n.uri = :uri'),
						array(':locale' => $localeId, ':uri' => $groupLocale->urlFormat)
					);

				if ($group->id)
				{
					$query->join('venti_events venti', 'venti.id = elements_i18n.elementId')
						->andWhere('venti.groupId != :groupId', array(':groupId' => $group->id));
				}

				$count = $query->count('elements_i18n.id');

				if ($count)
				{
					$group->addError($errorKey, Craft::t('This URI is already in use.'));
				}
			}

		}

		if (!$group->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;

			try
			{
				// Fire an 'onBeforeSaveGroup' event
				$event = new Event($this, array(
					'group'      => $group,
					'isNewGroup' => $isNewGroup,
				));

				$this->onBeforeSaveGroup($event);

				// Is the event giving us the go-ahead?
				if ($event->performAction)
				{

					if (!$isNewGroup && $oldGroup->fieldLayoutId)
					{
						$fieldLayout = $oldGroup->getFieldLayout();
						if ($fieldLayout->type != "Venti_Event_Default")
						{
							// Drop the old field layout
							craft()->fields->deleteLayoutById($oldGroup->fieldLayoutId);
						}

					}


					if (isset($group->fieldLayoutId) && $group->fieldLayoutId == null)
					{

						$fieldLayout = $group->getFieldLayout();
						// Save the new one
						craft()->fields->saveLayout($fieldLayout);
						// Update the group record/model with the new layout ID
						$group->fieldLayoutId = $fieldLayout->id;
						$groupRecord->fieldLayoutId = $fieldLayout->id;

					}
					else
					{

						$groupRecord->fieldLayoutId = $group->fieldLayoutId;

					}


					// Save it!
					$groupRecord->save(false);

					// Now that we have a group ID, save it on the model
					if (!$group->id)
					{
						$group->id = $groupRecord->id;
					}

					// Might as well update our cache of the group while we have it.
					$this->_groupsById[$group->id] = $group;

					// Update the groups_i18n table
					$newLocaleData = array();

					if (!$isNewGroup)
					{
						// Get the old group locales
						$oldGroupLocaleRecords = Venti_GroupLocaleRecord::model()->findAllByAttributes(array(
							'groupId' => $group->id
						));

						$oldGroupLocales = Venti_GroupLocaleModel::populateModels($oldGroupLocaleRecords, 'locale');
					}

					foreach ($groupLocales as $localeId => $locale)
					{
						// Was this already selected?
						if (!$isNewGroup && isset($oldGroupLocales[$localeId]))
						{
							$oldLocale = $oldGroupLocales[$localeId];

							// Has anything changed?
							if ($locale->enabledByDefault != $oldLocale->enabledByDefault || $locale->urlFormat != $oldLocale->urlFormat)
							{
								craft()->db->createCommand()->update('venti_groups_i18n', array(
									'enabledByDefault' => (int)$locale->enabledByDefault,
									'urlFormat'        => $locale->urlFormat,
								), array(
									'id' => $oldLocale->id
								));
							}
						}
						else
						{
							$newLocaleData[] = array($group->id, $localeId, (int)$locale->enabledByDefault, $locale->urlFormat);
						}
					}

					// Insert the new locales
					craft()->db->createCommand()->insertAll('venti_groups_i18n',
						array('groupId', 'locale', 'enabledByDefault', 'urlFormat'),
						$newLocaleData
					);

					if (!$isNewGroup)
					{
						// Drop any locales that are no longer being used, as well as the associated entry/element locale
						// rows

						$droppedLocaleIds = array_diff(array_keys($oldGroupLocales), array_keys($groupLocales));

						if ($droppedLocaleIds)
						{
							craft()->db->createCommand()->delete('venti_groups_i18n',
								array('and', 'groupId = :groupId', array('in', 'locale', $droppedLocaleIds)),
								array(':groupId' => $group->id)
							);
						}
					}

					// Finally, deal with the existing entries...

					if (!$isNewGroup)
					{
						$criteria = craft()->elements->getCriteria('Venti_Event');

						// Get the most-primary locale that this group was already enabled in
						$locales = array_values(array_intersect(craft()->i18n->getSiteLocaleIds(), array_keys($oldGroupLocales)));

						if ($locales)
						{
							$criteria->locale = $locales[0];
							$criteria->groupId = $group->id;
							$criteria->status = null;
							$criteria->localeEnabled = null;
							$criteria->limit = null;

							craft()->tasks->createTask('ResaveElements', Craft::t('Resaving {group} entries', array('group' => $group->name)), array(
								'elementType' => 'Venti_Event',
								'criteria'    => $criteria->getAttributes()
							));
						}
					}

					$success = true;
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

			$success = true;
		}
		else
		{
			$success = false;
		}

		if ($success)
		{
			// Fire an 'onSaveGroup' event
			$this->onSaveGroup(new Event($this, array(
				'group'      => $group,
				'isNewGroup' => $isNewGroup,
			)));
		}


		return $success;
	}

	/**
	 * Deletes a group by its ID.
	 *
	 * @param int $groupId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteGroupById($groupId)
	{
		if (!$groupId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the field layout
			$fieldLayoutId = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('venti_groups')
				->where(array('id' => $groupId))
				->queryScalar();

			if ($fieldLayoutId)
			{
				$fieldLayout = craft()->fields->getLayoutById($fieldLayoutId);
				if ($fieldLayout->type != "Venti_Event_Default")
				{
					craft()->fields->deleteLayoutById($fieldLayoutId);
				}

			}

			// Grab the event ids so we can clean the elements table.
			$eventIds = craft()->db->createCommand()
				->select('id')
				->from('venti_events')
				->where(array('groupId' => $groupId))
				->queryColumn();

			craft()->elements->deleteElementById($eventIds);

			$affectedRows = craft()->db->createCommand()->delete('venti_groups', array('id' => $groupId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}
	

	/**
	 * Returns group models found by fieldLayoutId.
	 *
	 * @param int         $layoutId
	 *
	 * @return GroupModel[].
	 */

	public function getGroupsByLayoutId($layoutId)
	{
		$records = craft()->db->createCommand()
			->select('*')
			->from('venti_groups venti_groups')
			->where('venti_groups.fieldLayoutId = :fieldLayoutId', array(':fieldLayoutId' => $layoutId))
			->queryAll();

		return $groups = Venti_GroupModel::populateModels($records, null);
	}


	/**
	 * Save groups with new default field layout id.
	 *
	 * @param array         $groups
	 * @param int           $newLayoutId
	 *
	 * @return bool
	 */
	public function updateGroupLayoutIds($groups, $newLayoutId)
	{
		$success = true;

		if ($groups)
		{
			foreach ($groups as $group)
			{
				$group->fieldLayoutId = $newLayoutId;
				if ($this->saveGroup($group))
				{
					$success = true;
				}
			}
		}

		return $success;
	}

	/**
	 * Returns a groups’s locales.
	 *
	 * @param int         $groupId
	 * @param string|null $indexBy
	 *
	 * @return GroupLocaleModel[] The group’s locales.
	 */
	public function getGroupLocales($groupId, $indexBy = null)
	{
		$records = craft()->db->createCommand()
			->select('*')
			->from('venti_groups_i18n venti_groups_i18n')
			->join('locales locales', 'locales.locale = venti_groups_i18n.locale')
			->where('venti_groups_i18n.groupId = :groupId', array(':groupId' => $groupId))
			->order('locales.sortOrder')
			->queryAll();

		return Venti_GroupLocaleModel::populateModels($records, $indexBy);
	}



	public function getRoutesFromFormats()
	{
		$groups = $this->getAllGroups();
		$routes = array();

		foreach ($groups as $group)
		{
			if ($group && $group->hasUrls)
			{
				$template = $group->template;
				$groupLocales = $group->getLocales();

				if ($groupLocales)
				{
					foreach ($groupLocales as $glocale)
					{
						$routes[$groupLocales[$glocale->locale]->urlFormat] = $template;
					}
				}
			}
		}

		return $routes;
	}



	/**
	 * Returns all of the groups IDs that are editable by the current user.
	 *
	 * @return array All the editable groups’ IDs.
	 */
	public function getEditableGroupIds()
	{
		if (!isset($this->_editableGroupIds))
		{
			$this->_editableGroupIds = array();

			foreach ($this->getAllGroupIds() as $groupId)
			{
				if (craft()->userSession->checkPermission('editGroupEvents:'.$groupId))
				{
					$this->_editableGroupIds[] = $groupId;
				}
			}
		}

		return $this->_editableGroupIds;
	}


	/**
	 * Fires an 'onBeforeSaveGroup' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onBeforeSaveGroup(Event $event)
	{
		$this->raiseEvent('onBeforeSaveGroup', $event);
	}

	/**
	 * Fires an 'onSaveGroup' event.
	 *
	 * @param Event $event
	 *
	 * @return null
	 */
	public function onSaveGroup(Event $event)
	{
		$this->raiseEvent('onSaveGroup', $event);
	}
}
