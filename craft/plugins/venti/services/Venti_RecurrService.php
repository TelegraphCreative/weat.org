<?php
/**
 * Venti by TippingMedia
 *
 * @package   Venti
 * @author    Adam Randlett
 * @copyright Copyright (c) 2015, TippingMedia
 *
 * @class Venti Recurr Service
 */

namespace Craft;

require_once(CRAFT_PLUGINS_PATH.'venti/vendor/autoload.php');


class Venti_RecurrService extends BaseApplicationComponent
{


    /**
     * Generates End Date from difference in time of original Start & End Dates
     * Repeat dates need endDate but same time as startDate
     *
     * @return  DateTime
     */

     private function sameDateNewTime(\DateTime $date1, \DateTime  $date2, \DateInterval $difr)
     {

        $newDate = new \DateTime($date2->format('c'));
        $newDate->setTimezone(new \DateTimeZone("UTC"));
        $newDate1 = $newDate->add($difr);

        return $newDate1;
     }


    /**
     * Get dates array based on recur template.
     *
     * @return  array
     */

    public function getRecurDates($start, $rrule)
    {

        $timezone           = craft()->getTimeZone(); //'UTC','America/New_York','America/Denver' craft()->getTimeZone()
        //$startDate          = $start->format(DateTime::MYSQL_DATETIME);


        #-- returns null or datetime
        $endOn              = craft()->venti_rule->getEndOn($rrule);
        $rule               = new \Recurr\Rule($rrule, $start, $endOn, $timezone);
        $transformer        = new \Recurr\Transformer\ArrayTransformer();
        $transformerConfig  = new \Recurr\Transformer\ArrayTransformerConfig();

        $transformerConfig->enableLastDayOfMonthFix();
        $transformer->setConfig($transformerConfig);


        // if ($endOn !== null)
        // {
        //     $constraint = new \Recurr\Transformer\Constraint\BetweenConstraint($start, $endOn, true);
        // }
        // else
        // {
        //     $constraint = new \Recurr\Transformer\Constraint\AfterConstraint(new \DateTime(), true);
        // }


        return $transformer->transform($rule);

    }

    /**
     * Saving New Element based on recurring dates
     * @return --
     */

    public function saveRecurrData(Venti_EventModel $model, $eventRecord)
    {
        $startdate  = $model->getAttribute('startDate');
        $enddate    = $model->getAttribute('endDate');
        $diff       = $startdate->diff($enddate);
        $rule       = $model->getAttribute('rRule');
        $dates      = $this->getRecurDates($startdate,$rule);
        $dates      = $dates->toArray();



        #-- If recurr data already present delete to make way for he new
        if (($recurrRecord = Venti_RecurrRecord::model()->findByAttributes(array("cid" => $eventRecord->getAttribute('id'))))) {
            $recurrRecord->deleteAllByAttributes(array("cid" => $eventRecord->getAttribute('id')));
        }


        $i = 0;
        foreach ($dates as $key => $value)
        {

            #-- Returns DateTime::startdate & DateTime::endDate from Recur\Recurrece object
            $startDate      = $value->getStart();
            $endDate        = $value->getEnd();
            $recurrModel    = new Venti_RecurrModel();

            $recurrModel->setAttribute('cid', $eventRecord->getAttribute('id'));
            $recurrModel->setAttribute('startDate', $startDate);
            $recurrModel->setAttribute('endDate', $endDate);
            //$this->sameDateNewTime($model->endDate, $date, $diff)
            $recurrModel->setAttribute('isrepeat', $i == 0 ? 0 : 1);

            if(!$this->saveRecurrEvent($recurrModel)){
                return false;
            }
            $i++;
        }

        return true;

    }


    /**
	 * Saves a recurr event.
	 *
	 * @param Venti_EventModel $event
	 * @throws Exception
	 * @return bool
	 */
	public function saveRecurrEvent(Venti_RecurrModel $event)
	{
        $recurrRecord               = new Venti_RecurrRecord();
        $recurrRecord->cid          = $event->cid;
        $recurrRecord->startDate    = $event->startDate;
        $recurrRecord->endDate      = $event->endDate;
        $recurrRecord->isrepeat     = $event->isrepeat;

        $recurrRecord->validate();
        $event->addErrors($recurrRecord->getErrors());

        if (!$event->hasErrors())
        {
            $recurrRecord->save(false);
            return true;
        }

		return false;
    }


    /**
     * Convert date to MYSQL_DATETIME in UTC
     *
     * @return  Craft\DateTime
     */
    public function formatToMysqlDate(\Datetime $date)
    {
        $temp = DateTimeHelper::formatTimeForDb( $date->getTimestamp() );
        return  DateTime::createFromFormat( DateTime::MYSQL_DATETIME, $temp );
    }


    /**
       *
       * @param $recurRule recurrence rule - FREQ=YEARLY;INTERVAL=2;COUNT=3;
       * @return string recurrence string - every year for 3 times
       */
    public function recurTextTransform($recurRule, $lang = null)
    {
        #--- Recurr's supported locales
        $locales = ['de','en','eu','fr','it','sv','es'];

        $locale = in_array(craft()->language, $locales) ? craft()->language : "en";
        if ($lang != null && in_array($lang, $locales))
        {
            $locale = $lang;
        }

        $rule = new \Recurr\Rule($recurRule, new \DateTime());

        $textTransformer = new \Recurr\Transformer\TextTransformer(
            new \Recurr\Transformer\Translator($locale)
        );

        return $textTransformer->transform($rule);
    }


}
