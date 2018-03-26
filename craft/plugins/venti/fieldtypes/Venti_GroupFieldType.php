<?php
/**
 * Venti plugin for Craft CMS
 *
 * Venti_Group FieldType
 *
 * --snip--
 * Whenever someone creates a new field in Craft, they must specify what type of field it is. The system comes with
 * a handful of field types baked in, and we’ve made it extremely easy for plugins to add new ones.
 *
 * https://craftcms.com/docs/plugins/field-types
 * --snip--
 *
 * @author    Tipping Media LLC
 * @copyright Copyright (c) 2017 Tipping Media LLC
 * @link      http://tippingmedia.com
 * @package   Venti
 * @since     2.3.0
 */

namespace Craft;

class Venti_GroupFieldType extends BaseFieldType
{
    /**
     * Returns the name of the fieldtype.
     *
     * @return mixed
     */
    public function getName()
    {
        return Craft::t('Venti Calendar Group Selector');
    }

    /**
     * Returns the content attribute config.
     *
     * @return mixed
     */
    public function defineContentAttribute()
    {
        return AttributeType::Mixed;
    }

    /**
     * Defines the settings.
     *
     * @access protected
     * @return array
     */
    protected function defineSettings()
    {

        $settings['calendarGroups']  = AttributeType::Mixed;

        return $settings;
    }


    /**
     * Returns the field's input HTML.
     *
     * @param string $name
     * @param mixed  $value
     * @return string
     */
    public function getInputHtml($name, $values)
    {

        $id = craft()->templates->formatInputId($name);
        $namespacedId = craft()->templates->namespaceInputId($id);
        $settings = $this->getSettings();
        $allGroups = $this->getSources();

        $sources = [];
        
        // Grab all groups (id,name,handle) or ones selected in settings. 
        if( $settings['calendarGroups'] == "*") {
            $sources = $allGroups;
        } else {

            foreach($settings['calendarGroups'] as $groupId) {
                foreach($allGroups as $group) {
                    if($groupId == $group['value']){
                        array_push($sources, $group);
                    }
                }
            }
        }
        
        $flatValues = [];

        // Convert values back to array of group ids or * for all. 
        if($values) {
            if(count($values) == count($allGroups)) {
                $flatValues = "*";
            } else {
                foreach ($values as $group) {
                    array_push($flatValues, $group['id']);
                }
            }
        }
        
        
/* -- Variables to pass down to our rendered template */

        $variables = array(
            'id' => $id,
            'name' => $name,
            'namespaceId' => $namespacedId,
            'values' => $flatValues,
            'sources' => $sources,
            'settings' => $settings
        );

        return craft()->templates->render('venti/fields/groupInput.html', $variables);
    }

    /**
     * Returns the field's settings HTML.
     *
     * @return string|null
     */
    public function getSettingsHtml()
    {

        return craft()->templates->render('venti/fields/groupFieldSettings',[
            'sources' => $this->getSources(),
            'settings' => $this->getSettings()
        ]);
    }

    public function getGroups() {
        $groups = craft()->db->createCommand()
            ->select('id as id, name as name, handle as handle')
            ->from('venti_groups')
            ->queryAll();
        return $groups;
    }


    public function getSources() {
        $groups = $this->getGroups();

        $sources = [];
        
        foreach ($groups as $value) {
            array_push($sources,[
                'label' => $value['name'],
                'value' => $value['id']
            ]);
        }

        return $sources;
    }

    /**
     * Returns the input value as it should be saved to the database.
     *
     * @param mixed $value
     * @return mixed
     */
    public function prepValueFromPost($value)
    {
        $groups = $this->getGroups();
        if($value == "*") {
            return $groups;
        }
        
        foreach($groups as $key => $group) {
            if(!in_array($group['id'],$value)) {
                unset($groups[$key]);
            }
        }
        
        return $groups;
    }

    /**
     * Prepares the field's value for use.
     *
     * @param mixed $value
     * @return mixed
     */
    public function prepValue($value)
    {
        return $value;
    }
}
