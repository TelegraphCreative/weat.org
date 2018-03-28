<?php
namespace Craft;

/**
 * Venti - Settings model
 */
class Venti_SettingsModel extends BaseModel
{
    const DEFAULT_TIME_INTERVAL     = 30;
    const DEFAULT_DURATION          = 60;
    const DEFAULT_TRANSLATE         = false;
    const HIDE_LOCATION             = 0;
    const HIDE_REGISTRATION         = 0;

    private static $timeIntervals = array(
        15 => 15,
        30 => 30,
        60 => 60,
    );

    private static $eventDurations = array(
        30  => 30,
        60  => 60,
        90  => 90,
        120 => 120,
    );

    /**
     * Setting default values upon construction
     *
     * @param null $attributes
     */
    public function __construct($attributes = null)
    {
        parent::__construct($attributes);

        $this->timeInterval        = self::DEFAULT_TIME_INTERVAL;
        $this->eventDuration       = self::DEFAULT_DURATION;
        $this->pluginName          = "Venti";
        $this->translate           = self::DEFAULT_TRANSLATE;
        $this->hideLocation        = self::HIDE_LOCATION;
        $this->hideRegistration    = self::HIDE_REGISTRATION;
    }


    /**
     * CP Plugin name
     * @return array
     */
     public static function getPluginName()
     {
         return self::$pluginName;
     }


    /**
     * Time select dropdown interval options
     * @return array
     */
    public static function getTimeIntervals()
    {
        return self::$timeIntervals;
    }


   /**
    * Date duration for quick end date set.
    * @return array
    */
    public static function getEventDurations()
    {
        return self::$eventDurations;
    }


    /**
     * Translation toggle if Venti event fields are translateable.
     * @return array
     */
     public static function getTranslate()
     {
         return self::$translate;
     }


     /**
      * Hides Location fields in groups if toggled.
      * @return array
      */
      public static function getHideLocation()
      {
          return self::$hideLocation;
      }

      /**
       * Hides Registration fields in groups if toggled.
       * @return array
       */
       public static function getHideRegistration()
       {
           return self::$hideRegistration;
       }



    /**
     * @return FieldLayoutModel
     */
    public function getFieldLayout()
    {
        return craft()->fields->getLayoutByType('Venti_Event_Default');
    }

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
            'timeInterval'           => array(AttributeType::Enum, 'values' => self::$timeIntervals),
            'eventDuration'          => array(AttributeType::Enum, 'values' => self::$eventDurations),
            'pluginName'             => array(AttributeType::String, 'default' => 'Venti'),
            'translate'              => array(AttributeType::Bool, 'default' => self::DEFAULT_TRANSLATE),
            'license'                => array(AttributeType::String, 'default' => null),
            'googleMapsApiKey'       => array(AttributeType::String, 'default' => null),
            'country'                => array(AttributeType::String),
            'hideRegistration'       => array(AttributeType::Bool, 'default' => self::HIDE_REGISTRATION),
            'hideLocation'           => array(AttributeType::Bool, 'default' => self::HIDE_LOCATION),
		));
	}
}
