<?php
/**
 * Weat plugin for Craft CMS
 *
 * WEAT plugin for Craft CMS
 *
 * --snip--
 * Craft plugins are very much like little applications in and of themselves. We’ve made it as simple as we can,
 * but the training wheels are off. A little prior knowledge is going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL, as well as some semi-
 * advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://craftcms.com/docs/plugins/introduction
 * --snip--
 *
 * @author    Bo Bartlett - Telegraph Creative
 * @copyright Copyright (c) 2018 Bo Bartlett - Telegraph Creative
 * @link      https://telegraphcreative.co/
 * @package   Weat
 * @since     1.0.0
 */

namespace Craft;

class WeatPlugin extends BasePlugin
{
	/**
	 * Called after the plugin class is instantiated; do any one-time initialization here such as hooks and events:
	 *
	 * craft()->on('entries.saveEntry', function(Event $event) {
	 *    // ...
	 * });
	 *
	 * or loading any third party Composer packages via:
	 *
	 * require_once __DIR__ . '/vendor/autoload.php';
	 *
	 * @return mixed
	 */
	public function init()
	{
		parent::init();
		craft()->on('charge.onCharge', function(Event $event) {
			//WeatPlugin::log($event);
			//$type = $event->params['charge']->type;
			//WeatPlugin::log('Charge type of ' . $type);
			$meta = $event->params['charge']->meta;
			//$chargeType = $event->params['charge']->chargeType;
			switch ($meta['type']) {
				case 'registration':
					craft()->weat_registration->saveRegistration($event);
					craft()->weat_registration->updateUser($event);
					craft()->weat_registration->switchSubscription($event);
					break;
				case 'join':
					craft()->weat_registration->updateUser($event);
					craft()->weat_registration->switchSubscription($event);
					break;
				default:
					WeatPlugin::log('Charge type does not match anything. ' . $type);
			}
			//craft()->weat_payments->exampleService();
		});

		craft()->on('charge.onBeforeCharge', function(Event $event) {
			WeatPlugin::log('onBeforeCharge');
			//WeatPlugin::log($event);
			//$type = $event->params['charge']->type;
			//WeatPlugin::log('Charge type of ' . $type);
			$meta = $event->params['charge']->meta;
			//$chargeType = $event->params['charge']->chargeType;
			switch ($meta['type']) {
				case 'registration':
					//craft()->weat_registration->saveRegistration($event);
					//craft()->weat_registration->saveUser($user);
					break;
				case 'join':
					WeatPlugin::log('join');
					//echo "Your favorite color is blue!";
					if(craft()->userSession->isGuest()) {
						WeatPlugin::log('Is a guest');
						craft()->weat_registration->updateUser($event);
					}
					break;
				default:
					WeatPlugin::log('Charge type does not match anything. ' . $type);
			}
			//craft()->weat_payments->exampleService();
		});
		craft()->on('users.onSaveUser', function(Event $event) {
			//craft()->weat_registration->saveRegistration($event);
			craft()->weat_registration->switchSubscription();
		});

		/*
		craft()->on('users.onSaveUser', function (Event $event) {
			$user = $event->params['user'];
			$isNewUser = $event->params['isNewUser'];
			if ($user->talentJobFamily->value != '') {
				$user->getContent()->setAttributes([
					'talentStage' => 'engage',
				]);
				craft()->users->saveUser($user);
			}
		});
		*/

		// If this is a CP request, register the commerce.prepCpTemplate hook
		if (craft()->request->isCpRequest()) {
			//$this->includeCpResources();
			craft()->templates->hook('weat.prepCpTemplate', array($this, 'prepCpTemplate'));
		}
	}

	/**
	 * Returns the user-facing name.
	 *
	 * @return mixed
	 */
	public function getName()
	{
			 return Craft::t('WEAT');
	}

	/**
	 * Plugins can have descriptions of themselves displayed on the Plugins page by adding a getDescription() method
	 * on the primary plugin class:
	 *
	 * @return mixed
	 */
	public function getDescription()
	{
			return Craft::t('WEAT plugin for Craft CMS');
	}

	/**
	 * Plugins can have links to their documentation on the Plugins page by adding a getDocumentationUrl() method on
	 * the primary plugin class:
	 *
	 * @return string
	 */
	public function getDocumentationUrl()
	{
			return 'https://github.com/TelegraphCreative/weat.org/weat/blob/master/README.md';
	}

	/**
	 * Plugins can now take part in Craft’s update notifications, and display release notes on the Updates page, by
	 * providing a JSON feed that describes new releases, and adding a getReleaseFeedUrl() method on the primary
	 * plugin class.
	 *
	 * @return string
	 */
	public function getReleaseFeedUrl()
	{
			return 'https://raw.githubusercontent.com/TelegraphCreative/weat.org/weat/master/releases.json';
	}

	/**
	 * Returns the version number.
	 *
	 * @return string
	 */
	public function getVersion()
	{
			return '1.0.0';
	}

	/**
	 * As of Craft 2.5, Craft no longer takes the whole site down every time a plugin’s version number changes, in
	 * case there are any new migrations that need to be run. Instead plugins must explicitly tell Craft that they
	 * have new migrations by returning a new (higher) schema version number with a getSchemaVersion() method on
	 * their primary plugin class:
	 *
	 * @return string
	 */
	public function getSchemaVersion()
	{
			return '1.0.0';
	}

	/**
	 * Returns the developer’s name.
	 *
	 * @return string
	 */
	public function getDeveloper()
	{
			return 'Telegraph Creative';
	}

	/**
	 * Returns the developer’s website URL.
	 *
	 * @return string
	 */
	public function getDeveloperUrl()
	{
			return 'https://telegraphcreative.co/';
	}

	/**
	 * Returns whether the plugin should get its own tab in the CP header.
	 *
	 * @return bool
	 */
	public function hasCpSection()
	{
			return true;
	}

	/**
	 * Called right before your plugin’s row gets stored in the plugins database table, and tables have been created
	 * for it based on its records.
	 */
	public function onBeforeInstall()
	{
	}

	/**
	 * Called right after your plugin’s row has been stored in the plugins database table, and tables have been
	 * created for it based on its records.
	 */
	public function onAfterInstall()
	{
	}

	/**
	 * Called right before your plugin’s record-based tables have been deleted, and its row in the plugins table
	 * has been deleted.
	 */
	public function onBeforeUninstall()
	{
	}

	/**
	 * Called right after your plugin’s record-based tables have been deleted, and its row in the plugins table
	 * has been deleted.
	 */
	public function onAfterUninstall()
	{
	}
	public function registerSiteRoutes()
	{
			return array(
					'payments/thanks' => array('action' => 'weat/payments'),
					'payments/thanks' => array('action' => 'weat/payments/thanks')
			);
	}

	public function prepCpTemplate(&$context)
	{
		//$user = craft()->userSession->getUser();
		$context['subnav'] = array();
		$context['subnav']['charge'] = ['label' => 'Weat', 'url' => 'weat'];

		$context['subnav']['connect'] = ['label' => 'Accounts', 'url' => 'charge/connect'];
			/*if (craft()->charge_membershipSubscription->systemHasAnySubscriptions()) {
					$context['subnav']['subscribers'] = ['label' => Craft::t('Subscribers'), 'url' => 'charge/subscribers'];
			}

			if (craft()->charge_connect->getConnectEnabledStatus()) {
					$context['subnav']['connect'] = ['label' => Craft::t('Accounts'), 'url' => 'charge/connect'];
			}

			if (craft()->userSession->isAdmin() || $user->can('accessPlugin-charge')) {
					if (craft()->charge_log->getLogEnabledStatus()) {
							$context['subnav']['logs'] = ['label' => Craft::t('Logs'), 'url' => 'charge/logs'];
					}
					$context['subnav']['settings'] = ['label' => Craft::t('Settings'), 'url' => 'charge/settings'];
			}*/
	}
}
