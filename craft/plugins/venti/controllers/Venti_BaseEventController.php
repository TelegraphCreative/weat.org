<?php
namespace Craft;

/**
 * BaseController is a base class that any event related controllers, such as {@link EntriesController} and
 * {@link EventRevisionsController} extend to share common functionality.
 *
 * It extend's Yii's {@link \CController} overwriting specific methods as required.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   venti.controllers
 */
abstract class Venti_BaseEventController extends BaseController
{
	// Protected Methods
	// =========================================================================

	/**
	 * Enforces all Edit Event permissions.
	 *
	 * @param Venti_EventModel $event
	 *
	 * @return null
	 */
	protected function enforceEditEventPermissions(Venti_EventModel $event)
	{
		$userSessionService = craft()->userSession;
		$permissionSuffix = ':'.$event->groupId;

		if (craft()->isLocalized())
		{
			// Make sure they have access to this locale
			$userSessionService->requirePermission('editLocale:'.$event->locale);
		}

		// Make sure the user is allowed to edit events in this group
		$userSessionService->requirePermission('publishEvents'.$permissionSuffix);

		// Is it a new event?
		if (!$event->id)
		{
			// Make sure they have permission to create new entries in this group
			$userSessionService->requirePermission('createEvents'.$permissionSuffix);
		}
	}
}
