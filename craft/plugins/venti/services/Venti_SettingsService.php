<?php

/**
 * Venti by TippingMedia
 *
 * @package   Venti
 * @author    Adam Randlett
 * @copyright Copyright (c) 2015, TippingMedia
 */


namespace Craft;

class Venti_SettingsService extends BaseApplicationComponent
{
    /* Venti_SettingsModel */
    private static $settingsModel;

    /**
     * @return int
     */
    public function getTimeInterval()
    {
        return $this->getSettingsModel()->timeInterval;
    }

    /**
     * @return int
     */
    public function getEventDuration()
    {
        return $this->getSettingsModel()->eventDuration;
    }

    /**
     * @return bool
     */
    public function isTranslate()
    {
        return $this->getSettingsModel()->translate;
    }

    /**
     * @return string
     */
    public function getPluginName()
    {
        return $this->getSettingsModel()->pluginName;
    }


    public function getGoogleMapsApiKey()
    {
        return $this->getSettingsModel()->googleMapsApiKey;
    }

    /**
     * @return bool
     */
    public function getHideLocation()
    {
        return $this->getSettingsModel()->hideLocation;
    }

    /**
     * @return bool
     */
    public function getHideRegistration()
    {
        return $this->getSettingsModel()->hideRegistration;
    }



    /**
     * @return Venti_SettingsModel
     */
    public function getSettingsModel()
    {
        if (is_null(self::$settingsModel)) {
            $plugin              = craft()->plugins->getPlugin('venti');
            self::$settingsModel = $plugin->getSettings();
        }

        return self::$settingsModel;
    }
}
