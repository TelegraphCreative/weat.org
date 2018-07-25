<?php
namespace Craft;

class WeatReportsCommitteeDataSource extends SproutReportsBaseDataSource
{
	//private $_settings = craft()->globals->getSetByHandle('wef');
	public function getName()
	{
		return Craft::t('Comittee members');
	}

	public function getDescription()
	{
		return Craft::t('List of committees and their members.');
	}

	/**
	 * @param  SproutReports_ReportModel &$report
	 *
	 * @return array|null
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$settings = craft()->globals->getSetByHandle('wef');
		// First, use dynamic options, fallback to report options
		if (!count($options))
		{
			$options = $report->getOptions();
		}

/*
		$querySelect = '{{users.email}},
		{{content.field_userWefId}} AS wefId';

		$query = craft()->db->createCommand()
			->select($querySelect)
			->from('users')
			->join('content', '{{users.id}} = {{content.elementId}}');
		$results = $query->queryAll();
		$entries = craft()->elements->getCriteria->section( 'committees' ).relatedTo({
			targetElement: currentUser,
			field: 'committeeMembers.member'
		})*/

		$results = array();
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->section = 'committees';
		$criteria->order = 'title';
		$entries = $criteria->find();
		foreach($entries as $entry) {
			$element = craft()->entries->getEntryById($entry->id);
			//$results[] = array($element->title);
			foreach($element->committeeMembers as $block) {
				$member = $block->member->status(null)->first();
				if(!$member) {
					continue;
				}
				$results[] = array(
					'Committee name' => $element->title,
					'Position' => $block->position,
					'First name' => $member->firstName,
					'Last name' => $member->lastName,
					'Email' => $member->email,
					'Address 1' => $member->userAddress1,
					'Address 2' => $member->userAddress2,
					'City' => $member->userAddressCity,
					'State' => $member->userAddressState,
					'Zip' => $member->userAddressZip,
					'Phone' => $member->userPhoneNumber,
					'Section' => $member->userSection->label,
					'Status' => $member->userStatus->label,
					'Join date' => $member->userStartDate,
					'Subscription start date' => $member->userSubscriptionStartDate,
					'Subscription end date' => $member->userSubscriptionEndDate
				);
			}
		}

		return $results;
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
return;
		return craft()->templates->render('weat/datasources/_options/wef', array(
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
		/*if (empty($options['userGroups']))
		{
			$errors['userGroups'][] = Craft::t('Select at least one User Group.');

			return false;
		}*/

		return true;
	}

}
