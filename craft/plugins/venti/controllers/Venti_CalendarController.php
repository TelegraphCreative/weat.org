<?php
namespace Craft;

/**
 * Venti_Event controller
 */
class Venti_CalendarController extends BaseController
{
    /**
	 * Event index
	 */
	public function actionCalendarIndex()
	{
        $localeData = craft()->i18n->getLocaleData(craft()->language);
        $dateFormatter = $localeData->getDateFormatter();
        $dateFormat = $dateFormatter->getDatepickerPhpFormat();
        $timeFormat = $dateFormatter->getTimepickerPhpFormat();

		$variables['dateFormat'] = $dateFormat;
		$variables['timeFormat'] = $timeFormat;

		$variables['groups'] = craft()->venti_groups->getAllGroups();
        $variables['timezone'] = craft()->getTimeZone();

        //Which locales can be edited
        $currentUser = craft()->userSession->getUser();
        $locales = craft()->i18n->getSiteLocales();
        $editLocales = array();
        foreach ($locales as $locale)
        {
            $editLocales[$locale->id] = $currentUser->can('editLocale:'.$locale->id);
        }

        $variables['editLocales'] = $editLocales;
        
        //Render Template
		$this->renderTemplate('venti/calendar/_index', $variables);
	}

    public function actionCalendarFeed()
    {

        $start      = craft()->request->getParam('start');
        $end        = craft()->request->getParam('end');
        $groupId    = craft()->request->getSegment(3);
        $localeId   = craft()->request->getSegment(4);

        $output = craft()->venti_calendar->getCalendarFeed($start, $end, $groupId, $localeId);

        $this->returnJson( $output );

    }
}
