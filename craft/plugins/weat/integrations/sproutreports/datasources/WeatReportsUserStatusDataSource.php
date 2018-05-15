<?php
namespace Craft;

class WeatReportsUserStatusDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('Users by status');
	}

	public function getDescription()
	{
		return Craft::t('Create reports about your users and user groups.');
	}

	/**
	 * @param  SproutReports_ReportModel &$report
	 *
	 * @return array|null
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();
		}

		$userGroupIds            = $options['userGroups'];
		$displayUserGroupColumns = $options['displayUserGroupColumns'];

		$includeAdmins = false;

		if (is_array($userGroupIds) && in_array('admin', $userGroupIds))
		{
			$includeAdmins = true;

			// Admin is always the first in our array if it exists
			unset($userGroupIds[0]);
		}

		$userGroups = craft()->userGroups->getAllGroups();

		$userGroupsByName = array();
		foreach ($userGroups as $userGroup)
		{
			$userGroupsByName[$userGroup->name] = 0;
		}

		$selectQueryString = '{{users.id}},
			{{users.username}} AS Username,
			{{users.email}} AS Email,
			{{users.firstName}} AS First Name,
			{{users.lastName}} AS Last Name';

		if ($displayUserGroupColumns)
		{
			$selectQueryString = $selectQueryString . ',{{content.field_userStatus}} AS Status';
		}

		$userQuery = craft()->db->createCommand()
			->select($selectQueryString)
			->from('users')
			->join('content', '{{users.id}} = {{content.elementId}}');

		if (is_array($userGroupIds))
		{
			$userQuery->where(array('in', '{{content.field_userStatus}}', $userGroupIds));
		}



		// @todo - can we query users and user their ids as the array key?
		$users = $userQuery->queryAll();

		// Update users to be indexed by their ids
		$usersById = array();
		foreach ($users as $user)
		{
			$usersById[$user['id']] = $user;
			unset ($usersById[$user['id']]['id']);
		}


		return $usersById;
	}

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function getOptionsHtml(array $options = array())
	{
		$userStatuses = craft()->fields->getFieldByHandle('userStatus');

		foreach ($userStatuses->settings['options'] as $userStatus)
		{
			$userStatusOptions[] = array(
				'label' => $userStatus['label'],
				'value' => $userStatus['value']
			);
		}

		$optionErrors = $this->report->getErrors('options');
		$optionErrors = array_shift($optionErrors);

		return craft()->templates->render('weat/datasources/_options/users', array(
			'userGroupOptions' => $userStatusOptions,//$userStatuses->settings->options, //$userGroupOptions,
			'options'          => count($options) ? $options : $this->report->getOptions(),
			'errors'           => $optionErrors
		));
	}

	/**
	 * Validate our data source options
	 *
	 * @param array $options
	 * @return array|bool
	 */
	public function validateOptions(array $options = array(), array &$errors = array())
	{
		if (empty($options['userGroups']))
		{
			$errors['userGroups'][] = Craft::t('Select at least one User Group.');

			return false;
		}

		return true;
	}
}
