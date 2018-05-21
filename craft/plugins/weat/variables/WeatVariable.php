<?php
/**
 * WEAT plugin for Craft CMS
 *
 * WEAT Variable
 *
 * @author    Bo Bartlett - Telegraph Creative
 * @copyright Copyright (c) 2018 Bo Bartlett - Telegraph Creative
 * @link      https://telegraphcreative.co/
 * @package   WEAT
 * @since     1.0.0
 */

namespace Craft;

class WeatVariable
{
	public function isWeatMember($userId = null)
	{
		if($userId == null) {
			$user = craft()->userSession->getUser();
			if($user != null) $userId = $user->id;
		}

		$charges = craft()->charge->getChargesByUserId($userId);
		if(count($charges > 1)) {
			WeatPlugin::log('Too many subscriptions for userId ' . $userId);
		}
		foreach ($charges as $charge) {
			/*if(isset($charge->subscription->currentPeriodEnd)) {
				$currentPeriodEnd = $charge->subscription->currentPeriodEnd;
			} else {
				$currentPeriodEnd = 0;
			}*/
			if(isset($charge->subscription)) {
				if($charge->subscription->status == 'active' or $charge->subscription->currentPeriodEnd >= time()) {
					return true;
				}
			}
		}

		return false;
	}

	public function getAllRegistrations()
	{
		return craft()->weat_registration->getAllRegistrations();
	}

	public function getRegistrationsByUser($userId)
	{
		return craft()->weat_registration->getRegistrationsByUser($userId);
	}

	public function getRegistrationsByEvent($key)
	{
		return craft()->weat_registration->getRegistrationsByEvent($key);
	}

	public function getSubscriptionDetails($userId = null)
	{
		if($userId == null) {
				$user = craft()->userSession->getUser();
				if($user != null) $userId = $user->id;
		}

		$charges = craft()->charge->getChargesByUserId($userId);
		if(count($charges > 1)) {
			WeatPlugin::log('Too many subscriptions for userId ' . $userId);
		}

		foreach ($charges as $charge) {
			if($charge->subscription->status == 'active') {
				return true;
			}
		}

		return [];

		/*
		return 'this and that';
		{% set charges = craft.charge.getChargesByUser %}
		{% set active = false %}
		{% for charge in charges %}
			{% set active = true %}
		{% endfor %}
		{% if currentUser and active %}
		Deets
		{#% for charge in charges %}
		<div class="panel panel-default">
				<div class="panel-body">
						{{ charge.id }} <code>{{ charge.mode }}</code> <strong>{{ charge.type }}</strong>
						<ul>
								{% for payment in charge.payments %}
										<li>{{ payment.formatAmount }} paid on {{ payment.dateCreated }}</li>
								{% endfor %}
						</ul>

						{% if charge.type == 'recurring' %}
								<p>This is a recurring Charge</p>
								<p>Subscription is : <span class="label label-success">{{ charge.subscription.status }}</span></p>

								{% if charge.subscription.status == 'active' %}
										<h4>End Subscription</h4>
										<p>You can cancel your recurring payment. <br/>
												You will still have access until the end of the current payment period on <em>{{ charge.subscription.currentPeriodEnd | date }}</em></p>

										<form class="form-horizontal" method="post">
												{{ getCsrfInput() }}
												{{ forms.hidden({ name: 'action', value: 'charge/endSubscription'}) }}
												{{ forms.hidden({ name: 'subscriptionId', value: charge.subscription.id }) }}

												<input type="submit" class="btn btn-danger" value="End this Subscription"/>
										</form>
								{% endif %}

								{% if charge.subscription.status == 'cancelled' %}
										<p>This subscription has been cancelled. You'll still have access until <em>{{ charge.subscription.currentPeriodEnd | date }}</em>.</p>
								{% endif %}
						{% endif %}
				</div>
		</div>
		{% endfor %#}


		{% else %}
		<a href="{{ url('members/subscribe')}}">Join Now</a>
		{% endif %}
*/
	}


}
