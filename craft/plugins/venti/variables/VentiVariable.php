<?php
namespace Craft;

class VentiVariable
{
	function events($criteria = null)
	{
		return craft()->elements->getCriteria('Venti_Event',$criteria);
	}

	function nextEvent($criteria = null)
	{
		return craft()->venti_events->nextEvent($criteria);
	}

	function groups($indexBy = null)
	{
		return craft()->venti_groups->getAllGroups($indexBy);
	}

	function groupIds()
	{
		return craft()->venti_groups->getAllGroupIds();
	}

	function getGroupById($groupId = null)
	{
		return craft()->venti_groups->getGroupById($groupId);
	}

	function group($groupId = null)
	{
		return craft()->venti_groups->getGroupById($groupId);
	}

	function getGroupByHandle($groupHandle = null)
	{
		return craft()->venti_groups->getGroupByHandle($groupHandle);
	}

	function locations()
	{
		return craft()->elements->getCriteria('Venti_Location');
	}

	public function settings()
    {
    	return craft()->venti_settings;
    }

	public function getCalendarSettingSources()
	{
		return craft()->venti_calendar->getCalendarSettingSources();
	}
}
