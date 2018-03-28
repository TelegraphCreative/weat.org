<?php

/**
 * Venti by TippingMedia
 *
 * @package   Venti
 * @author    Adam Randlett
 * @copyright Copyright (c) 2015, TippingMedia
 */


namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'venti/vendor/tm/calendar.class.php');
require_once(CRAFT_PLUGINS_PATH.'venti/vendor/autoload.php');

class Venti_CalendarService extends BaseApplicationComponent
{



    public function getCalendar($events,$month,$year)
    {
        $cal = new \Calendar($month,$year);
        return $cal->createCalendar($events,craft()->getTimeZone());
    }


    public function getCalendarFeed($start, $end, $groupId, $localeId = null)
    {

        //$groups = craft()->vent_groups->getAllGroups();

        $criteria = craft()->elements->getCriteria('Venti_Event');
        $criteria->groupId = $groupId;
        $criteria->locale = $localeId != null ? $localeId : craft()->language;
	    $criteria->between = [$start,$end];

        $feedData = array();


        //$feed = new Venti_CalendarFeedModel();

        foreach ($criteria->find() as $param) {

            $group = $param->getGroup();
            #-- Get appropriate color based on brightness and if a multi day event else use default dark color
            $color = new \Mexitek\PHPColors\Color($group->color);
            $textColor = "#222222";
            if ($color->isDark() && ($param['startDate']->format('Y-m-d') != $param['endDate']->format('Y-m-d')))
            {
                $textColor = "#ffffff";
            }

            $feedData[] = array(
                "id"        => $param['id'],
                "eid"       => $param['eid'],
                "title"     => $param['title'],
                "start"     => $param['startDate']->format('c'),
                "end"       => $param['endDate']->format('c'),
                "allDay"    => $param['allDay'],
                "summary"   => $param['summary'],
                "locale"    => $param['locale'],
                "repeat"    => $param['repeat'],
                "rRule"     => $param['rRule'],
                "multiDay"  => $param['startDate']->format('Y-m-d') != $param['endDate']->format('Y-m-d') ? true : false,
                "group"     => $group->name,
                "color"     => $group->color,
                "textColor" => $textColor
            );
        }

        return $feedData;

    }

    public function getCalendarSettingSources()
    {
        $groups = craft()->venti_groups->getAllGroups();
        $sources = array();

        $currentUser = craft()->userSession->getUser();

        foreach ($groups as $group)
        {
            $cpTrigger = craft()->config->get('cpTrigger');
            $sources[] = array(
                'url'           => "/".$cpTrigger."/venti/feed/" . $group['id'] . "/" . craft()->language,
                'id'            => $group['id'],
                'label'         => $group['name'],
                'color'         => $group['color'],
                'overlap'       => true,
                'canEdit'       => $currentUser->can('publishEvents:'.$group['id']),
                'canDelete'     => $currentUser->can('deleteEvents:'.$group['id'])
            );
        }

        return $sources;

    }
}
