<?php
namespace Craft;

/**
 * Class SproutReportsQueryDataSource
 *
 * @package Craft
 */
class SproutReportsQueryDataSource extends SproutReportsBaseDataSource
{
	public function getName()
	{
		return Craft::t('Custom Query');
	}

	/**
	 * @return null|string
	 */
	public function getDescription()
	{
		return Craft::t('Create reports using a custom database query');
	}

	/**
	 * @return bool
	 */
	public function isAllowHtmlEditable()
	{
		return true;
	}

	/**
	 * @param SproutReports_ReportModel $report
	 * @param array                     $options
	 * @return bool|\CDbDataReader
	 */
	public function getResults(SproutReports_ReportModel &$report, $options = array())
	{
		$query = $report->getOption('query');

		try
		{
			return craft()->db->createCommand($query)->queryAll();
		}
		catch (\Exception $e)
		{
			$report->setResultsError($e->getMessage());
		}

		return false;
	}

	/**
	 * @param array $options
	 *
	 * @return string
	 */
	public function getOptionsHtml(array $options = array())
	{
		$optionErrors = $this->report->getErrors('options');
		$optionErrors = array_shift($optionErrors);

		return craft()->templates->render('sproutreports/datasources/_options/query', array(
			'options' => count($options) ? $options : $this->report->getOptions(),
			'errors' => $optionErrors
		));
	}

	/**
	 * @param array $options
	 * @return bool
	 */
	public function validateOptions(array $options = array(), array &$errors = array())
	{
		if (empty($options['query']))
		{
			$errors['query'][] = Craft::t('Query cannot be blank.');

			return false;
		}

		return true;
	}
}
