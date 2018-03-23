<?php
namespace Craft;
/**
 * Venti Plugin
 * @author Tipping Media
 */
class VentiPlugin extends BasePlugin
{

	public function init()
    {
		parent::init();

        require_once(CRAFT_PLUGINS_PATH.'venti/vendor/tm/CalendarVariable.php');
        require_once(CRAFT_PLUGINS_PATH.'venti/vendor/tm/Venti_Calendar.php');
		require_once(CRAFT_PLUGINS_PATH.'venti/helpers/LocationHelper.php');
    }

	public function getName()
	{
		$plug = craft()->plugins->getPlugin('Venti', false);
		if($plug->isInstalled && $plug->isEnabled)
		{
			$settings = $this->getSettings();
			if ($settings->pluginName)
			{
				return $settings->pluginName;
			}
		}

	    return Craft::t('Venti');
	}

	public function getVersion()
	{
	    return '2.0.35';
	}

	public function getSchemaVersion()
    {
        return '2.0.0';
    }

	public function getDeveloper()
	{
	    return 'Tipping Media';
	}
	public function getDeveloperUrl()
	{
	    return 'http://tippingmedia.com';
	}
	public function getDescription()
	{
		return Craft::t("Powerful event calendar management");
	}
	public function getDocumentationUrl()
    {
        return 'https://venticalendar.io/docs/';
    }
	public function getIconPath()
    {
        return craft()->path->getPluginsPath().'/resources/img/venti.svg';
    }
	public function getReleaseFeedUrl()
	{
		return "https://venticalendar.io/plugin/releases_v2.json";
	}
	public function hasCpSection()
	{
		return true;
	}

    /**
     * Register Control Panel Routes
     */
	public function registerCpRoutes()
	{
		return array(
			#-- groups
			'venti/groups' 												=> array('action' => 'venti/groups/groupIndex'),
			'venti/groups/new' 											=> array('action' => 'venti/groups/editGroup'),
			'venti/groups/(?P<groupId>\d+)' 							=> array('action' => 'venti/groups/editGroup'),
			#-- locations
			'venti/locations' 											=> array('action' => 'venti/location/locationIndex'),
			'venti/location/new'										=> array('action' => 'venti/location/editLocation'),
			'venti/location/(?P<locationId>\d+)(?:-{slug})'			 	=> array('action' => 'venti/location/editLocation'),

			#-- calendar
			'venti/calendar' 											=> array('action' => 'venti/calendar/calendarIndex'),
			'venti/feed/(?P<groupId>\d+)/(?P<localeId>\w+)' 			=> array('action' => 'venti/calendar/calendarFeed'),

			#-- event
			'venti' 													=> array('action' => 'venti/event/eventIndex'),
			'venti/(?P<groupHandle>{handle})/new' 						=> array('action' => 'venti/event/editEvent'),
			'venti/(?P<groupHandle>{handle})/new?/(?P<localeId>\w+)' 	=> array('action' => 'venti/event/editEvent'),
			'venti/(?P<groupHandle>{handle})/(?P<eventId>\d+)(?:-{slug})' 						=> array('action' => 'venti/event/editEvent'),
			'venti/(?P<groupHandle>{handle})/(?P<eventId>\d+)(?:-{slug})?/(?P<localeId>\w+)' 	=> array('action' => 'venti/event/editEvent'),
		 	#-- Settings
			'venti/settings/license'                                     => array('action' => 'venti/settings/license'),
            'venti/settings/general'                                     => array('action' => 'venti/settings/general'),
            'venti/settings/events'                                      => array('action' => 'venti/settings/events'),
            'venti/settings/groups'                                		 => array('action' => 'venti/settings/groups'),

		);
	}


	public function registerSiteRoutes()
	{
	    return array(
	        'event/(?P<slug>{slug})/(?P<eid>\w+)' => array('action' => 'venti/event/viewEventByEid'),
			'event/(?P<slug>{slug})/(?P<year>\d{4})-(?P<month>(?:0?[1-9]|1[012]))-(?P<day>(?:0?[1-9]|[12][0-9]|3[01]))' => array('action' => 'venti/event/viewEventByStartDate'),
			'calendar/ics/(?P<groupId>\d+)' => array('action' => 'venti/event/viewICS')
	    );
	}


	public function registerCachePaths()
	{
	    return array(
	        craft()->path->getStoragePath().'venti/' => Craft::t('Venti'),
	    );
	}



	/**
	 * @return array
	 */
	public function registerUserPermissions()
	{
		$groups = craft()->venti_groups->getAllGroups();
		$groupEditPermissions = array();
		foreach ($groups as $group) {
			$groupEditPermissions['editGroupEvents:'.$group['id']] = array('label' => Craft::t('Edit') . " ". $group['name'] . " ". Craft::t('events'));
			$groupEditPermissions['editGroupEvents:'.$group['id']]['nested']['createEvents:'.$group['id']] = array('label'=> Craft::t('Create events in') ." ". $group['name']);
			$groupEditPermissions['editGroupEvents:'.$group['id']]['nested']['publishEvents:'.$group['id']] = array('label'=> Craft::t('Publish events in') ." ". $group['name']);
			$groupEditPermissions['editGroupEvents:'.$group['id']]['nested']['deleteEvents:'.$group['id']] = array('label'=> Craft::t('Delete events in') ." ". $group['name']);
		}

		$permissions =  array(
			'ventiEditSettings' 		=> array('label' => Craft::t('Venti: Settings')),
			'ventiEditLocations' 		=> array('label' => Craft::t('Venti: Locations'),
				'nested' 	=> array(
					'createLocations' 	=> array('label' => Craft::t('Create')),
					'publishLocations' 	=> array('label' => Craft::t('Publish')),
					'deleteLocations'   => array('label' => Craft::t('Delete'))
				)
			),
			'ventiEditEvents' 			=> array('label' => Craft::t('Venti: Edit Events'), 'nested' => $groupEditPermissions),
		);

		return $permissions;

	}


	/**
     * @return Venti_SettingsModel
     */
    protected function getSettingsModel()
    {
        return new Venti_SettingsModel();
    }


    /**
     * Get Settings URL
     */
    public function getSettingsHtml()
    {
		if (craft()->request->isCpRequest() && craft()->userSession->isLoggedIn() && craft()->userSession->isAdmin()) {
			return craft()->templates->render('venti/settings');
		}
    }

	/**
     * @return string
     */
    public function getSettingsUrl()
    {
        return 'venti/settings';
    }

	public function addTwigExtension()
    {
        Craft::import('plugins.venti.twigextensions.VentiTwigExtension');
        Craft::import('plugins.venti.twigextensions.Calendar_TokenParser');
        Craft::import('plugins.venti.twigextensions.Calendar_Node');

        return new VentiTwigExtension();
    }

}
