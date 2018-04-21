<?php

return array(
  '*' => array(
    'appId' => 'weat',
    'sendPoweredByHeader' => false,
    'omitScriptNameInUrls' => true,
    'useEmailAsUsername' => true,
    'autoLoginAfterAccountActivation' => true,
    'loginPath' => 'login',
    'loginPath' => 'members/login',
    'logoutPath' => 'members/logout',
    'setPasswordPath' => 'members/setpassword',
    'autoLoginAfterAccountActivation' => true,
    'siteUrl' => null,
    'userSessionDuration' => '',

    'defaultWeekStartDay' => 0,
    'enableCsrfProtection' => (!isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] != '/actions/charge/webhook/callback'), // https://transition.topshelfcraft.com/software/craft/charge/usage/controllers/webhook-controller

    'environmentVariables' => array(),

    'cpTrigger' => 'admin',
    'devMode' => true,
  ),

   '.test' => array(
        'devMode' => true,
        'cache' => false,
        'siteUrl' => 'http://weat.org.test/'
    ),
);
