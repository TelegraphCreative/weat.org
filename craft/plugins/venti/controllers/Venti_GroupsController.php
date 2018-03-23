<?php
namespace Craft;

/**
 * Group controller
 */
class Venti_GroupsController extends BaseController
{

	// Protected Methods
	// =========================================================================

	/**
	 * Enforces all Groups Management permissions.
	 *
	 * @return null
	 */
	protected function enforceAccessGroupsPermissions()
	{
		$userSessionService = craft()->userSession;

		// Make sure the user is allowed to edit events in this group
		$userSessionService->requirePermission('ventiEditSettings');
	}
 
	 /**
	  * User with Venti Settings permission requried to access controller.
	  */
	public function init()
	{
		$this->enforceAccessGroupsPermissions();
	}

	/**
	 * Group index
	 */
	public function actionGroupIndex()
	{
		$variables['groups'] = craft()->venti_groups->getAllGroups();

		$this->renderTemplate('venti/groups', $variables);
	}

	/**
	 * Edit a group.
	 *
	 * @param array $variables
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditGroup(array $variables = array())
	{
		$variables['brandNewGroup'] = false;

		if (!empty($variables['groupId']))
		{
			if (empty($variables['group']))
			{
				$variables['group'] = craft()->venti_groups->getGroupById($variables['groupId']);

				if (!$variables['group'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['group']->name;
		}
		else
		{
			if (empty($variables['group']))
			{
				$variables['group'] = new Venti_GroupModel();
				$variables['brandNewGroup'] = true;
			}

			$variables['title'] = Craft::t('Create a new group');
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Events'), 'url' => UrlHelper::getUrl('venti')),
			array('label' => Craft::t('Groups'), 'url' => UrlHelper::getUrl('venti/groups')),
		);

		$this->renderTemplate('venti/groups/_edit', $variables);
	}

	/**
	 * Saves a group
	 */
	public function actionSaveGroup()
	{
		$this->requirePostRequest();

		$group = new Venti_GroupModel();

		// Shared attributes
		$group->id         		= craft()->request->getPost('groupId');
		$group->name       		= craft()->request->getPost('name');
		$group->handle     		= craft()->request->getPost('handle');
		$group->color      		= craft()->request->getPost('color');
		$group->description		= craft()->request->getPost('description');

		//\CVarDumper::dump(craft()->request->getPost(), 5, true);exit;

		// Type-specific attributes
		$group->hasUrls    = (bool) craft()->request->getPost('groups.hasUrls', true);
		$group->template   = craft()->request->getPost('groups.template', 'events/_entry');


		// Set the field layout
		// Use the default from the groups settings or use group specific
		if (craft()->request->getPost('defaultFieldLayout') == "1")
		{

			$fieldLayout = craft()->fields->getLayoutByType('Venti_Event_Default');
			$group->fieldLayoutId = $fieldLayout->id;

		}
		else
		{
			$fieldLayout = craft()->fields->assembleLayoutFromPost();
			$fieldLayout->type = 'Venti_Event';
			$group->setFieldLayout($fieldLayout);
		}




		//\CVarDumper::dump($group, 5, true);exit;
		// Locale-specific attributes
		$locales = array();

		if (craft()->isLocalized())
		{
			$localeIds = craft()->request->getPost('locales', array());
		}
		else
		{
			$primaryLocaleId = craft()->i18n->getPrimarySiteLocaleId();
			$localeIds = array($primaryLocaleId);
		}

		foreach ($localeIds as $localeId)
		{
			$urlFormat       = craft()->request->getPost('groups.urlFormat.'.$localeId);

			$locales[$localeId] = new Venti_GroupLocaleModel(array(
				'locale'           => $localeId,
				'enabledByDefault' => (bool) craft()->request->getPost('defaultLocaleStatuses_'.$localeId),
				'urlFormat'        => $urlFormat,
			));
		}

		$group->setLocales($locales);


		// Save it
		if (craft()->venti_groups->saveGroup($group))
		{
			craft()->userSession->setNotice(Craft::t('Group saved'));
			$this->redirectToPostedUrl($group);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save group'));
		}

		// Send the group back to the template
		craft()->urlManager->setRouteVariables(array(
			'group' => $group
		));
	}

	/**
	 * Deletes a group.
	 */
	public function actionDeleteGroup()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$groupId = craft()->request->getRequiredPost('id');

		craft()->venti_groups->deleteGroupById($groupId);
		$this->returnJson(array('success' => true));
	}

}
