<?php
namespace Craft;

class WeatReportsUserExpireDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('Users by expiration');
	}

	public function getDescription()
	{
		return Craft::t('Create reports about your users and user groups.');
	}

	public function isAllowHtmlEditable()
	{
		return true;
	}

	/**
	 * @param  SproutReports_ReportModel &$report
	 *
	 * @return array|null
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		// First, use dynamic options, fallback to report options
		if (!count($options)) {
			$options = $report->getOptions();
		}

		$userGroupIds            = $options['userGroups'];
		//$displayUserGroupColumns = $options['displayUserGroupColumns'];
		$userMembershipTypeSelect = '';
		if(array_key_exists('userMembershipTypes', $options)) {
			$userMembershipTypeIds   = implode("','", $options['userMembershipTypes']);
			$userMembershipTypeSelect = "AND craft_content.field_userMembershipType IN('" . $userMembershipTypeIds . "')";
		}

		$includeAdmins = false;

		if (is_array($userGroupIds) && in_array('admin', $userGroupIds)) {
			$includeAdmins = true;
			// Admin is always the first in our array if it exists
			unset($userGroupIds[0]);
		}

		$userGroups = craft()->userGroups->getAllGroups();

		/* Translate the field values */
		$userStatuses = craft()->fields->getFieldByHandle('userStatus');
		$userStatusesWhenThen = 'CASE {{content.field_userStatus}}';
		foreach ($userStatuses->settings['options'] as $userStatus) {
			$userStatusesWhenThen .= " WHEN '" . $userStatus['value'] . "' THEN '" . $userStatus['label'] . "'";
		}
		$userStatusesWhenThen .= " ELSE {{content.field_userStatus}} END";

		/* Translate the field values */
		$userMembershipTypes = craft()->fields->getFieldByHandle('userMembershipType');
		$userMembershipTypesWhenThen = 'CASE {{content.field_userMembershipType}}';
		foreach ($userMembershipTypes->settings['options'] as $userMembershipType) {
			$userMembershipTypesWhenThen .= " WHEN '" . $userMembershipType['value'] . "' THEN '" . $userMembershipType['label'] . "'";
		}
		$userMembershipTypesWhenThen .= " ELSE {{content.field_userMembershipType}} END";

		/* Create the SELECT statement */
		$selectQueryString = "{{users.id}},
		{{users.firstName}} AS 'First Name',
		{{users.lastName}} AS 'Last Name',
		{{users.email}} AS 'Email',
		DATE_FORMAT({{content.field_userSubscriptionEndDate}}, \"%M %d, %Y\") AS 'End date',
		" . $userMembershipTypesWhenThen . " AS 'Membership Type',
		" . $userStatusesWhenThen . " AS 'Status'";

		$userQuery = craft()->db->createCommand()
			->select($selectQueryString)
			->from('users')
			->join('content', '{{users.id}} = {{content.elementId}}')
			->where('1=1');

			if(array_key_exists('beginDate', $options)) {
				if(array_key_exists('date', $options['beginDate'])) {
					$beginDate = date('Y-m-d', strtotime($options['beginDate']['date']));
					//$userQuery->andWhere(array('>=', $beginDate));
					$userQuery->andWhere('craft_content.field_userSubscriptionEndDate >= "' . $beginDate . '"');
				}
			}
			if(array_key_exists('endDate', $options)) {
				if(array_key_exists('date', $options['endDate'])) {
					$endDate = date('Y-m-d', strtotime($options['endDate']['date']));
					//$userQuery->andWhere(array('<=', $endDate));
					$userQuery->andWhere('craft_content.field_userSubscriptionEndDate <= "' . $endDate . '"');
				}
			}
			if(array_key_exists('userMembershipTypes', $options)) {
				//$userMembershipTypeIds = implode("','", $options['userMembershipTypes']);
				//$userMembershipTypeIds = "'weat','professional','student'";
				$userQuery->andWhere(array('in', '{{content.field_userMembershipType}}', $options['userMembershipTypes']));
			}
			if(array_key_exists('userGroups', $options)) {
				//$userStatusIds = implode("','", $options['userGroups']);
				$userQuery->andWhere(array('in', '{{content.field_userStatus}}', $options['userGroups']));
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
		//return $usersById;
		return $users;
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


		$userMembershipTypes = craft()->fields->getFieldByHandle('userMembershipType');

		foreach ($userMembershipTypes->settings['options'] as $userMembershipType)
		{
			//print_r($userMembershipType);
			$userMembershipTypeOptions[] = array(
				'label' => $userMembershipType['label'],
				'value' => $userMembershipType['value']
			);
		}

		$optionErrors = $this->report->getErrors('options');
		$optionErrors = array_shift($optionErrors);

		return craft()->templates->render('weat/datasources/_options/usergroups', array(
			'userGroupOptions' => $userStatusOptions,//$userStatuses->settings->options, //$userGroupOptions,
			'userMembershipTypeOptions' => $userMembershipTypeOptions,
			'options'          => count($options) ? $options : $this->report->getOptions(),
			'errors'           => $optionErrors,
			'handle'           => $this->report->handle
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
