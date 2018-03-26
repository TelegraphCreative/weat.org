<?php
namespace Craft;

class Charge_ConnectFieldType extends BaseFieldType
{
	// Public Methods
	// =========================================================================

	/**
	 * @inheritDoc IFieldType::prepValue()
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	public function prepValue($value)
	{
		// Existing element?
		if (!empty($this->element->id))
		{
			return craft()->charge_connect->getAccountForElement($this->element->id);
		}

		return null;
	}

	public function getName()
	{
		return Craft::t('Charge Connect');
	}

	public function defineContentAttribute()
	{
		return AttributeType::Mixed;
	}


	public function getInputHtml($name, $value)
	{
		if(!craft()->charge_connect->getConnectEnabledStatus()) {
			return '<p class="warning">Stripe Connect is not enabled on this install. <br/>You can configure it from Charge > Settings > Connect</p>';
		}

		// We can only sort this if the element is saved. ie. we need an id.
		if($this->element == null) {
			return '<p class="warning">Save this element first before connecting to Stripe</p>';
		}
		$elementId = $this->element->id;
		$accountStatus = craft()->charge_connect->getAccountStatus($elementId);

		$input = '';
		$input .= '<p><em>Stripe is in <strong>'.craft()->charge->getMode().'</strong> mode.</em></p>';

		$stateInfo = [];
        $stateInfo['elementId'] = $elementId;
        $stateInfo['uri'] = $_SERVER['REQUEST_URI'];
		$buttonUrl = craft()->charge_connect->getConnectUrl($stateInfo);


		if($accountStatus == 'unconnected') {
	        $input .= '<p><a class="btn submit" href="'.$buttonUrl.'">Connect with Stripe</a></p>';
		} else if($accountStatus == 'connected') {
	        $input .= '<p class="success">Connected to Stripe in '.craft()->charge->getMode().' mode</p>';
			$input .= '<p><a class="btn formsubmit" data-action="charge/connect/disconnectAccount" data-param="elementId" data-value="'.$elementId.'">Disconnect Account</a></p>';
		} else {
			$input .= '<p>Stripe Connect is disabled at this time.</p>';
		}

		return $input;
	}
}
