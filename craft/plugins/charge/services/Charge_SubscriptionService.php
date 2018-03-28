<?php
namespace Craft;


class Charge_SubscriptionService extends BaseApplicationComponent
{

    public function findById($subscriptionId)
    {
        $subscriptionRecord = Charge_SubscriptionRecord::model()->findByAttributes(
            ['id' => $subscriptionId]);
        if ($subscriptionRecord == null) return null;

        $subscriptionModel = Charge_SubscriptionModel::populateModel($subscriptionRecord);

        return $subscriptionModel;
    }

    public function findByChargeId($chargeId)
    {
        $subscriptionRecord = Charge_SubscriptionRecord::model()->findByAttributes(
            ['chargeId' => $chargeId]);
        if ($subscriptionRecord == null) return null;

        $subscriptionModel = Charge_SubscriptionModel::populateModel($subscriptionRecord);

        return $subscriptionModel;
    }

    public function findByStripeId($stripeId)
    {
        $subscriptionRecord = Charge_SubscriptionRecord::model()->findByAttributes(
            ['stripeId' => $stripeId]);
        if ($subscriptionRecord == null) return null;

        $subscriptionModel = Charge_SubscriptionModel::populateModel($subscriptionRecord);

        return $subscriptionModel;
    }

    public function updateSubscriptionFromAction(Charge_SubscriptionModel $subscriptionModel, $subscriptionDetails)
    {
        $updatedModel = $subscriptionModel;
        $updatedModel->stripeId = (isset($subscriptionDetails['id']) ? $subscriptionDetails['id'] : null);
        $updatedModel->status = (isset($subscriptionDetails['status']) ? $subscriptionDetails['status'] : null);
        $updatedModel->start = (isset($subscriptionDetails['start']) ? $subscriptionDetails['start'] : null);
        $updatedModel->customerId = (isset($subscriptionDetails['customer']) ? $subscriptionDetails['customer'] : null);

        $updatedModel->cancelAtPeriodEnd = (isset($subscriptionDetails['cancel_at_period_end']) ? $subscriptionDetails['cancel_at_period_end'] : null);
        $updatedModel->currentPeriodStart = (isset($subscriptionDetails['current_period_start']) ? $subscriptionDetails['current_period_start'] : null);
        $updatedModel->currentPeriodEnd = (isset($subscriptionDetails['current_period_end']) ? $subscriptionDetails['current_period_end'] : null);

        $updatedModel->endedAt = (isset($subscriptionDetails['ended_at']) ? $subscriptionDetails['ended_at'] : null);
        $updatedModel->trialStart = (isset($subscriptionDetails['trial_start']) ? $subscriptionDetails['trial_start'] : null);
        $updatedModel->trialEnd = (isset($subscriptionDetails['trial_end']) ? $subscriptionDetails['trial_end'] : null);
        $updatedModel->canceledAt = (isset($subscriptionDetails['canceled_at']) ? $subscriptionDetails['canceled_at'] : null);
        $updatedModel->quantity = (isset($subscriptionDetails['quantity']) ? $subscriptionDetails['quantity'] : null);
        $updatedModel->applicationFeePercent = (isset($subscriptionDetails['application_fee_percent']) ? $subscriptionDetails['application_fee_percent'] : null);
        $updatedModel->discount = (isset($subscriptionDetails['discount']) ? $subscriptionDetails['discount'] : null);
        $updatedModel->taxPercent = (isset($subscriptionDetails['tax_percent']) ? $subscriptionDetails['tax_percent'] : null);

        $optional = ['amount' => 'planAmount', 'name' => 'planName', 'interval' => 'planInterval', 'interval_count' => 'planIntervalCount', 'trial_period_days'=> 'planTrialPeriodDays', 'currency' => 'planCurrency'];

        foreach($optional as $key => $opt) {
            if(isset($subscriptionDetails[$key])) {
                $updatedModel->$opt = $subscriptionDetails[$key];
            }
            if(isset($subscriptionDetails['plan'])) {
                if(isset($subscriptionDetails['plan'][$key])) {
                    $updatedModel->$opt = $subscriptionDetails['plan'][$key];
                }
            }
        }

        // Special handling for plan id
        if(isset($subscriptionDetails['plan']['id'])) {
            $updatedModel->planStripeId = $subscriptionDetails['plan']['id'];
        }

        $this->refreshSubscriptionRecord($updatedModel);
    }

    public function refreshSubscriptionRecord(Charge_SubscriptionModel $model)
    {
        craft()->charge_log->note('Refreshing subscription with new details', ['subscription' => $model]);
        if ($model->id) {
            $record = Charge_SubscriptionRecord::model()->findById($model->id);

            if (!$record) {
                throw new Exception(Craft::t('No subscription exists with the ID “{id}”',
                    ['id' => $model->id]));
            }
        } else {
            $record = new Charge_SubscriptionRecord();
        }

        $record->customerId = $model->customerId;
        $record->chargeId = $model->chargeId;
        $record->stripeId = $model->stripeId;
        $record->status = $model->status;
        $record->mode = $model->mode;
        $record->status = $model->status;
        $record->start = $model->start;
        $record->cancelAtPeriodEnd = $model->cancelAtPeriodEnd;
        $record->currentPeriodStart = $model->currentPeriodStart;
        $record->currentPeriodEnd = $model->currentPeriodEnd;
        $record->endedAt = $model->endedAt;
        $record->trialStart = $model->trialStart;
        $record->trialEnd = $model->trialEnd;
        $record->canceledAt = $model->canceledAt;
        $record->quantity = $model->quantity;
        $record->applicationFeePercent = $model->applicationFeePercent;
        $record->discount = $model->discount;
        $record->taxPercent = $model->taxPercent;
        $record->planAmount = $model->planAmount;
        $record->planName = $model->planName;
        $record->planInterval = $model->planInterval;
        $record->planIntervalCount = $model->planIntervalCount;
        $record->planTrialPeriodDays = $model->planTrialPeriodDays;
        $record->planCurrency = $model->planCurrency;
        $record->planStripeId = $model->planStripeId;

        $record->validate();
        $model->addErrors($record->getErrors());

        if (!$model->hasErrors()) {
            // Save it!
            $record->save(false);

            $model->id = $record->id;

            return true;
        } else {
            return false;
        }
    }

    /*
     * Ends a subscription from a webhook event
     *
     * This event is triggered when the subscription finally finishes
     * This will be at the period end of the customer's billing cycle
     * We trigger all our final logic against this event for consistency
     *
     */
    public function endSubscriptionFromWebhook($webhookBody)
    {
        if(!isset($webhookBody['id'])) {
            craft()->charge_log->note('Failed to find the subscription id from webhook event. No \'id\' in body', ['body' => $webhookBody]);
            return false;
        }

        $subscriptionStripeId = $webhookBody['id'];
        $subscription = craft()->charge_subscription->findByStripeId($subscriptionStripeId);
        if(is_null($subscription)) {
            craft()->charge_log->note('Failed to a matching subscription with stripeId : '.$subscriptionStripeId, ['body' => $webhookBody]);
            return false;
        }

        // Now we have the sub, get the parent charge, and from that the subscriber (if there is one)
        $charge = craft()->charge_charge->getChargeById($subscription->chargeId);
        if(is_null($charge)) {
            craft()->charge_log->note('Failed to the parent charge for subscription', ['subscription' => $subscription]);
            return false;
        }

        // At this point we should update the charge and subscription records
        // @todo

        // We might also have a subscriber that's attached to this subscription
        // Let's pass this down to the subscriber service for that to handle things
        craft()->charge_subscriber->findAndEndSubscibersSubscription($charge);

        // Also trigger any end of life behaviours on the charge
        // This might suspend or delete the user account based on the guest settings
        craft()->charge_userRegistration->triggerEndOfLife($charge);

        return;
    }

    /**
     * Ends an active payment subscription, but first runs through validation to make sure this user is allowed to end it
     *
     * Only the current owner of a subscription or an admin from the CP are allowed to endsubscriptions
     *
     * @param $subscriptionId The id for the subscription to end
     * @throws \Exception if the user doesn't have appropriate permissions
     */
    public function endSubscription($subscriptionId)
    {
        // Get the subscription for this id, so we can check validitiy
        $subscriptionModel = craft()->charge_subscription->findById($subscriptionId);

        if(is_null($subscriptionModel)) {
            throw new Exception('Sorry, this is not a valid subscription');
        }

        // Check permissions on this subscription
        if(!$this->requestHasPermissionToUpdateSubscription($subscriptionModel)) {
            throw new Exception('You do not have permission to end this subscription');
        }

        // Ok. Appears good to attempt to end the subscription
        try {
            $realMode = null;
            // Make sure we're in the right mode too
            // We might be test mode, but trying to cancel a live sub, and v/v
            if(craft()->charge->getMode() != $subscriptionModel->mode) {
                // Shift the mode over
                $realMode = craft()->charge->getMode();
                craft()->charge->init($subscriptionModel->mode);
            }

            craft()->charge_log->note('Cancelling user payment subscription');
            $subscriptionArray = craft()->charge->stripe->subscriptions()->cancel($subscriptionModel->customerId, $subscriptionModel->stripeId, true);

            // Reset the mode
            if($realMode != null) {
                craft()->charge->init($realMode);
            }

            craft()->charge_log->success('User payment subscription ended');
            $this->updateSubscriptionFromAction($subscriptionModel, $subscriptionArray);

            return true;

            // Now update the
        } catch(\Exception $e) {
            craft()->charge_log->exception('Failed to end the subscription, with an api exception', ['subscription' => $subscriptionModel]);
            throw new $e;
        }


        return false;
    }



    /**
     * Attempts to reactivate apayment s ubscription, but first runs through validation to make sure this user is allowed to end it
     *
     * Only the current owner of a subscription or an admin from the CP are allowed to reactivate a subscription
     *
     * @param $subscriptionId The id for the subscription to reactivate
     * @throws \Exception if the user doesn't have appropriate permissions
     */
    public function reactivateSubscription($subscriptionId)
    {
        // Get the subscription for this id, so we can check validitiy
        $subscriptionModel = craft()->charge_subscription->findById($subscriptionId);

        if(is_null($subscriptionModel)) {
            throw new Exception('Sorry, this is not a valid subscription');
        }

        // Check permissions on this subscription
        if(!$this->requestHasPermissionToUpdateSubscription($subscriptionModel)) {
            throw new Exception('You do not have permission to reactivate this subscription');
        }

        // Ok. Appears good to attempt to reactivate the subscription
        try {
            $realMode = null;
            // Make sure we're in the right mode too
            // We might be test mode, but trying to cancel a live sub, and v/v
            if(craft()->charge->getMode() != $subscriptionModel->mode) {
                // Shift the mode over
                $realMode = craft()->charge->getMode();
                craft()->charge->init($subscriptionModel->mode);
            }

            craft()->charge_log->note('Reactivating user payment subscription');
            $subscriptionArray = craft()->charge->stripe->subscriptions()->reactivate($subscriptionModel->customerId, $subscriptionModel->stripeId);

            // Reset the mode
            if($realMode != null) {
                craft()->charge->init($realMode);
            }

            craft()->charge_log->success('User payment subscription reactivated');
            $this->updateSubscriptionFromAction($subscriptionModel, $subscriptionArray);

            return true;

            // Now update the
        } catch(\Exception $e) {
            craft()->charge_log->exception('Failed to reactivate the subscription, with an api exception', ['subscription' => $subscriptionModel]);
            throw new $e;
        }


        return false;
    }




    /**
     * Attempts to update a subscription with new details
     * This allows a user to change the details of their subscription
     *
     * Only the current owner of a subscription or an admin from the CP are allowed to update a subscription
     *
     * @param $subscriptionId The id for the subscription to reactivate
     * @throws \Exception if the user doesn't have appropriate permissions
     */
    public function updateSubscription($subscriptionId, ChargeModel $chargeModel)
    {
        // Get the subscription for this id, so we can check validitiy
        $subscriptionModel = craft()->charge_subscription->findById($subscriptionId);

        if(is_null($subscriptionModel)) {
            throw new Exception('Sorry, this is not a valid subscription');
        }

        // Check permissions on this subscription
        if(!$this->requestHasPermissionToUpdateSubscription($subscriptionModel)) {
            throw new Exception('You do not have permission to update this subscription');
        }



        // Ok. Appears good to attempt to reactivate the subscription
        try {
            $realMode = null;
            // Make sure we're in the right mode too
            // We might be test mode, but trying to cancel a live sub, and v/v
            if(craft()->charge->getMode() != $subscriptionModel->mode) {
                // Shift the mode over
                $realMode = craft()->charge->getMode();
                craft()->charge->init($subscriptionModel->mode);
            }

            craft()->charge_log->note('Updating user payment subscription');

            $updatedPlan = craft()->charge_plan->findOrCreate($chargeModel);
            if($updatedPlan == false || $updatedPlan == null) {
                craft()->charge_log->error('Failed creating a new plan for updated subscription');
                return false;
            }

            // Only attempt to change the plan if the new plan is different from the old plan
            if($updatedPlan->stripeId == $subscriptionModel->stripeId) {
                // These are the same.
                // Stop right there..
                craft()->charge_log->error('Attempted to update a user subscription to the same subscription. Stopping request');
                return false;
            }

            $options = [];
            $options['plan'] = $updatedPlan->stripeId;
            $options['prorate'] = true;

            $subscriptionArray = craft()->charge->stripe->subscriptions()->update($subscriptionModel->customerId, $subscriptionModel->stripeId, $options);

            // Reset the mode
            if($realMode != null) {
                craft()->charge->init($realMode);
            }

            craft()->charge_log->success('User payment subscription updated');
            $this->updateSubscriptionFromAction($subscriptionModel, $subscriptionArray);

            // Fire to the onChange action with the model
            craft()->charge_actions->fireOnChange($chargeModel);

			// Update the user's associated membership subscription and user group
			craft()->charge_subscriber->changeSubscription($chargeModel->actions['onSuccess']['subscription'], craft()->charge_charge->getChargeById($subscriptionModel->chargeId));

            return true;
        } catch(\Exception $e) {
            craft()->charge_log->exception('Failed to reactivate the subscription, with an api exception', ['subscription' => $subscriptionModel]);
            throw new $e;
        }


        return false;
    }


    // -------------------------------------------------------------------------------------------------------
    // PRIVATE METHODS ---------------------------------------------------------------------------------------
    // -------------------------------------------------------------------------------------------------------

    private function requestHasPermissionToUpdateSubscription(Charge_SubscriptionModel $subscriptionModel)
    {
        // In the CP and admin or has permission for this action
        if(craft()->request->isCpRequest()) {
            if(!craft()->userSession->isAdmin()) {
                if(craft()->userSession->checkPermission('accessPlugin-charge')) {
                    return true;
                }
            } else {
                return true;
            }
        }

        // Get the parent charge for the item.
        $charge = craft()->charge_charge->getChargeById($subscriptionModel->chargeId);
        if(!is_null($charge)) {
            // From the frontend, they must be the owner
            $user = craft()->userSession->getUser();
            if($user != null) {
                if($user->id == $charge->userId) {
                    return true;
                }
            }
        }

        return false;
    }

}
