<?php
namespace Craft;

class WeatReportsAwardsDataSource extends SproutReportsBaseDataSource
{
	//private $_settings = craft()->globals->getSetByHandle('wef');
	public function getName()
	{
		return Craft::t('Award recipients');
	}

	public function getDescription()
	{
		return Craft::t('List of awards and their recipients.');
	}

	/**
	 * @param  SproutReports_ReportModel &$report
	 *
	 * @return array|null
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{

		$results = array();
		$criteria = craft()->elements->getCriteria(ElementType::Entry);
		$criteria->section = 'awards';
		$criteria->order = 'title';
		$entries = $criteria->find();
		foreach($entries as $entry) {
			$element = craft()->entries->getEntryById($entry->id);
			foreach($element->awardRecipients as $block) {
				$member = $block->awardRecipient->status(null)->first();
				if(!$member) {
					continue;
				}

				$results[] = array(
					'Award name' => $element->title,
					'Year' => date_format($block->awardDate, 'Y'),
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
		return $options;
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
