<?php
namespace Craft;

/**
 * BaseController is a base class that any event related controllers, such as {@link LocationController} and
 * {@link LocationRevisionsController} extend to share common functionality.
 *
 * It extend's Yii's {@link \CController} overwriting specific methods as required.
 *
 * @author    Tipping Media LLC. <support@tippingmedia.com>
 * @copyright Copyright (c) 2016, Tipping Media LLC.
 * @license   http://craftcms.com/license Craft License Agreement
 * @see       http://craftcms.com
 * @package   venti.controllers
 */
abstract class Venti_BaseLocationController extends BaseController
{
	// Protected Methods
	// =========================================================================

	/**
	 * Enforces all Edit Locaation permissions.
	 *
	 * @param Venti_LocationModel $location
	 *
	 * @return null
	 */
	protected function enforceEditLocationPermissions(Venti_LocationModel $location)
	{
		$userSessionService = craft()->userSession;

        // if (craft()->isLocalized())
		// {
		// 	// Make sure they have access to this locale
		// 	$userSessionService->requirePermission('editLocale:'.$event->locale);
		// }

		// Make sure the user is allowed to edit locations
		$userSessionService->requirePermission('publishLocations');

		// Is it a new event?
		if (!$location->id)
		{
			// Make sure they have permission to create new locations
			$userSessionService->requirePermission('createLocations');
		}
	}
}
