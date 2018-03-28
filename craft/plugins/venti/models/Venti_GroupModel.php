<?php
namespace Craft;

/**
 * Venti - Group model
 */
class Venti_GroupModel extends BaseModel
{

	/**
	 * @var
	 */
	private $_locales;


	/**
	 * Use the translated group name as the string representation.
	 *
	 * @return string
	 */
	function __toString()
	{
		return Craft::t($this->name);
	}


	/**
	 * Returns the section's locale models
	 *
	 * @return array
	 */
	public function getLocales()
	{
		if (!isset($this->_locales))
		{
			if ($this->id)
			{
				$this->_locales = craft()->venti_groups->getGroupLocales($this->id, 'locale');
			}
			else
			{
				$this->_locales = array();
			}
		}

		return $this->_locales;
	}


	/**
	 * Sets the group's locale models.
	 *
	 * @param array $locales
	 *
	 * @return null
	 */
	public function setLocales($locales)
	{
		$this->_locales = $locales;
	}



	/**
	 * Adds locale-specific errors to the model.
	 *
	 * @param array  $errors
	 * @param string $localeId
	 *
	 * @return null
	 */
	public function addLocaleErrors($errors, $localeId)
	{
		foreach ($errors as $attribute => $localeErrors)
		{
			$key = $attribute.'-'.$localeId;
			foreach ($localeErrors as $error)
			{
				$this->addError($key, $error);
			}
		}
	}



	/**
	 * Returns the section's URL format (or URL) for the current locale.
	 *
	 * @return string|null
	 */
	public function getUrlFormat()
	{
		$locales = $this->getLocales();

		if ($locales)
		{
			$localeIds = array_keys($locales);

			// Does this section target the current locale?
			if (in_array(craft()->language, $localeIds))
			{
				$localeId = craft()->language;
			}
			else
			{
				$localeId = $localeIds[0];
			}

			return $locales[$localeId]->urlFormat;
		}
	}


	public function getICSUrl()
	{
		return craft()->siteUrl . "calendar/ics/" . $this->id;
	}


	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'id'            => AttributeType::Number,
			'name'          => AttributeType::String,
			'handle'        => AttributeType::String,
			'fieldLayoutId' => AttributeType::Number,
			'hasUrls'       => array(AttributeType::Bool, 'default' => true),
			'template'      => AttributeType::String,
			'color'         => AttributeType::String,
			'description'   => AttributeType::Mixed,
		);
	}

	/**
	 * @return array
	 */
	public function behaviors()
	{
		return array(
			'fieldLayout' => new FieldLayoutBehavior('Venti_Event',"fieldLayoutId"),
		);
	}
}
