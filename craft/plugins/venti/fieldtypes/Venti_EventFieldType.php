<?php
namespace Craft;

/**
 * Entries field type
 */
class Venti_EventFieldType extends BaseElementFieldType
{
	/**
	 * @access protected
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Venti_Event';


	// public function defineContentAttribute()
    // {
    //     return array(AttributeType::Mixed);
    // }


	/**
     * Returns the field's input HTML.
     *
     * @param string $name
     * @param mixed  $value
     * @return string
     */
    // public function getInputHtml($name, $criteria)
    // {
    //     // LinkList Settings
    //     $settings = $this->getSettings();
	//
	// 	// current selected CP locale
    //     $locale = isset($this->element->locale) ? $this->element->locale : craft()->getLanguage();
	//
	// 	if (!($criteria instanceof ElementCriteriaModel))
	// 	{
	// 		$criteria = craft()->elements->getCriteria($this->elementType);
	// 		$criteria->id = false;
	// 	}
	//
	// 	$criteria->status = null;
	// 	$criteria->localeEnabled = null;
	//
	// 	// Element Select Options
    //     $elementSelectSettings = array(
	//
    //         'elementType' => new ElementTypeVariable( craft()->elements->getElementType('Venti_Event') ),
    //         'elements' => $criteria,
    //         'sources' => $settings->groupSources,
    //         'criteria' => array(
    //             'status' => null,
	// 			'startDate' => array('and','>='. new DateTime()),
	// 			'locale' => $locale,
	// 			'localeEnabled' => null,
	// 			'isrepeat' => null,
    //         ),
    //         'sourceElementId' => ( isset($this->element->id) ? $this->element->id : null ),
    //         'limit' => $settings->limit,
    //         'addButtonLabel' => Craft::t($settings->eventSelectionLabel),
    //         'storageKey' => 'field.'.$this->model->id,
	// 		'viewMode' => 'list',
	// 	);
	//
    //     return craft()->templates->render('venti/fields/eventInput', array(
    //         'name' => $name,
    //         'elementSelectSettings' => $elementSelectSettings,
	//
    //     ));
    // }


	/**
     * Returns the field's settings HTML.
     *
     * @return string|null
     */
    // public function getSettingsHtml()
    // {
    //     $eventElementType = craft()->elements->getElementType('Venti_Event');
	//
    //     return craft()->templates->render('venti/fields/eventFieldSettings', array(
    //         'sources'                   => $this->getElementSources($eventElementType),
    //         'settings'                  => $this->getSettings(),
	// 		'targetLocaleFieldHtml'     => $this->getTargetLocaleFieldHtml(),
    //     ));
    // }


	// /**
    //  * Returns the input value as it should be saved to the database.
    //  *
    //  * @param mixed $value
    //  * @return mixed
    //  */
    // public function prepValueFromPost($value)
    // {
    //    return json_encode($value);
    // }



    // public function prepValue($value)
    // {
	//
    //     if ($value != null && is_array($value))
    //     {
    //         return count($value) > 0 ? array_values($value) : null;
    //     }
    //     else
    //     {
    //         return $value;
    //     }
	//
    // }

	/**
     * Returns sources available to an element type.
     *
     * @access protected
     * @return mixed
     */
    // protected function getElementSources($elementType)
    // {
    //     $sources = array();
	//
    //     foreach ($elementType->getSources() as $key => $source)
    //     {
    //         if (!isset($source['heading']))
    //         {
    //             $sources[] = array('label' => $source['label'], 'value' => $key);
    //         }
    //     }
	//
    //     return $sources;
    // }


	public function getSearchKeywords($value)
	{

		return "startDate endDate summary";
	}

	/**
	 * Returns any additional criteria parameters limiting which elements the field should be able to select.
	 *
	 * @return array
	 */
	// protected function getInputSelectionCriteria()
	// {
	// 	return array(
	// 		'startDate' => array('and','>='. new DateTime()),
	// 		'isrepeat' => null,
	// 	);
	// }


	// Protected Methods
    // =========================================================================

    // protected function getSettingsModel()
    // {
    //     return new Venti_EventSettingsModel();
    // }



	/**
	 * Returns the label for the "Add" button.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add an event');
	}
}
