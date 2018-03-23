<?php
namespace Craft;

/**
 * Locations field type
 */
class Venti_LocationFieldType extends BaseElementFieldType
{
	/**
	 * @access protected
	 * @var string $elementType The element type this field deals with.
	 */
	protected $elementType = 'Venti_Location';


	public function getSearchKeywords($value)
	{

		return "location city state zipCode region province coordinates website";
	}

	/**
	 * Returns the label for the "Add" button.
	 *
	 * @access protected
	 * @return string
	 */
	protected function getAddButtonLabel()
	{
		return Craft::t('Add a location');
	}
}
