<?php

return array(
	'*' => array(
		'appId' => 'ICT',
		'sendPoweredByHeader' => false,
		'omitScriptNameInUrls' => true,
		'useEmailAsUsername' => true,
		'autoLoginAfterAccountActivation' => true,
		'loginPath' => 'login',
		'siteUrl' => null,

		'defaultWeekStartDay' => 0,
		'enableCsrfProtection' => true,
		
		'environmentVariables' => array(),

		'cpTrigger' => 'admin',
		'devMode' => true,
	),

 	'.test' => array(
        'devMode' => true,
        'cache' => false,
        'siteUrl' => 'http://weat.test/'
    ),
);
