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
		if (!count($options))
		{
			$options = $report->getOptions();
		}

		$userGroupIds            = $options['userGroups'];
		//$displayUserGroupColumns = $options['displayUserGroupColumns'];
		$beginDate               = date('Y-m-d', strtotime($options['beginDate']['date']));
		$endDate                 = date('Y-m-d', strtotime($options['endDate']['date']));
		$userMembershipTypeIds   = implode("','", $options['userMembershipTypes']);

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


		$selectQueryString = "{{users.id}},
			CONCAT('<a href=\"admin/users/', {{users.id}}, '\">Edit</a>') AS 'Edit',
			{{users.firstName}} AS 'First Name',
			{{users.lastName}} AS 'Last Name',
			CONCAT('<a href=\"mailto:', {{users.email}}, '\">', {{users.email}}, '</a>') AS 'Email',
			DATE_FORMAT({{content.field_userSubscriptionEndDate}}, '%M %d, %Y') AS 'End date'";

//AND craft_content.field_userStatus NOT IN('delinquent')
//AND craft_content.field_userSubscriptionEndDate >= (CURDATE() - INTERVAL 30 DAY)
//AND craft_content.field_userSubscriptionEndDate <= CURDATE();
/*
		if ($displayUserGroupColumns)
		{
			$selectQueryString = $selectQueryString . ',{{content.field_userStatus}} AS Status';
		}*/

		/*$userQuery = craft()->db->createCommand()
			->select($selectQueryString)
			->from('users')
			->join('content', '{{users.id}} = {{content.elementId}}')
			->where(array('not in', "{{content.field_userStatus}}", ('delinquent')))
			->andWhere(['between', "{{content.field_userSubscriptionEndDate}}", '(CURDATE() - INTERVAL 30 DAY)','CURDATE()']);*/

			$userQuery = craft()->db->createCommand("
			SELECT craft_users.id, CONCAT('<a href=\"admin/users/', craft_users.id, '\">Edit</a>') AS 'Edit',
			craft_users.firstName AS 'First Name', `craft_users`.lastName AS 'Last Name',
			CONCAT('<a href=\"mailto:', craft_users.email, '\">',  craft_users.email, '</a>') AS 'Email',
			DATE_FORMAT(craft_content.field_userSubscriptionEndDate, \"%M %d, %Y\") AS 'End date',
			craft_content.field_userMembershipType AS 'Membership Type',
			craft_content.field_userStatus AS 'Status'
FROM craft_users
JOIN craft_content ON craft_users.id = craft_content.elementId
WHERE craft_content.field_userStatus NOT IN('delinquent')
AND craft_content.field_userMembershipType IN('" . $userMembershipTypeIds . "')
AND craft_content.field_userSubscriptionEndDate >= '" . $beginDate . "'
AND craft_content.field_userSubscriptionEndDate <= '" . $endDate . "'"
);

/*
			$entries = craft()->db->createCommand()
				->select("{{content.title}} AS 'Category Name',
									COUNT({{relations.sourceId}}) AS 'Total Entries Assigned Category',
									(COUNT({{relations.sourceId}}) / $totalCategories) * 100 AS '% of Total Categories used'
					")
				->from('content')
				->join('categories', '{{content.elementId}} = {{categories.id}}')
				->join('relations', '{{relations.targetId}} = {{categories.id}}')
				->where(array('in', "{{relations.sourceId}}", $entryIds))
				->andWhere(array('in', "{{relations.targetId}}", $categoryIds))
				->group('{{relations.targetId}}')
				->queryAll();*/

		/*if (is_array($userGroupIds))
		{
			$userQuery->where(array('in', '{{content.field_userStatus}}', $userGroupIds));
		}*/



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
