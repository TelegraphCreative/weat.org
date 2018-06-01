<?php
namespace Craft;

class WeatReportsAllUsersDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('All Users');
	}

	public function getDescription()
	{
		return Craft::t('A report with every user in the system.');
	}

	public function isAllowHtmlEditable()
	{
		return false;
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


				$selectQueryString = '{{users.id}} AS ID,
					{{content.field_userSalutation}} AS Salutation,
					{{users.firstName}} AS First Name,
					{{users.lastName}} AS Last Name,
					{{users.email}} AS Email,
					{{content.field_userMembershipType}} AS Membership Type,
					{{content.field_userStatus}} AS Status,
					{{content.field_userSection}} AS Section,
					{{content.field_userPhoneNumber}} AS Phone,
					{{content.field_userTitle}} AS Title,
					{{content.field_userCompanyName}} AS Company,
					{{content.field_userAddressCity}} AS City,
					{{content.field_userAddressState}} AS State,
					{{content.field_userAddressZip}} AS Zip,
					{{content.field_userAddress1}} AS Address 1,
					{{content.field_userAddress2}} AS Address 2,
					{{content.field_userStartDate}} AS Join Date,
					{{content.field_userSubscriptionStartDate}} AS Subscription Start Date,
					{{content.field_userSubscriptionEndDate}} AS Subscription End Date,
					{{content.field_userTerminateDate}} AS Teminate Date,
					{{content.field_licenseWastewater}} AS License Wastewater,
					{{content.field_licenseWater}} AS License Water,
					{{content.field_licenseProfessionalEngineer}} AS License Professional Engineer,
					{{content.field_licenseGroundWater}} AS License Ground Water,
					{{content.field_licenseSurfaceWater}} AS License Surface Water,
					{{content.field_abilaId}} AS Abila ID
					';
					//field_email	field_social
					//	field_groupType	field_sponsor		field_chargeType	field_chargeAddress1	field_chargeAddress2	field_chargeCity	field_chargeState	field_fax	field_phone2	field_email2	field_notes	field_userPhoneExtension			uid



				$userQuery = craft()->db->createCommand()
					->select($selectQueryString)
					->from('users')
					->join('content', '{{users.id}} = {{content.elementId}}');
/*
				if (is_array($userGroupIds))
				{
					$userQuery->where(array('in', '{{usergroups_users.groupId}}', $userGroupIds));
				}

				if ($includeAdmins)
				{
					$userQuery->orWhere('{{users.admin}} = 1');
				}

				$userQuery->group('{{users.id}}');*/

				// @todo - can we query users and user their ids as the array key?
				$users = $userQuery->queryAll();

		/*$usersById = array();
		foreach ($users as $user)
		{
			$usersById[$user['id']] = $user;
			unset ($usersById[$user['id']]['id']);
		}


		return $usersById;*/
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
		return true;
	}
}
