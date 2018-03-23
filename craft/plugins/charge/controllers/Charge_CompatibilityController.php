<?php
namespace Craft;

class Charge_CompatibilityController extends Charge_BaseCpController
{

    public function actionIndex(array $variables = [])
    {
        $this->requireAdmin();
        // Test for any critical
        $variables['messages'] = craft()->charge_compatibility->test();

        $this->renderTemplate('charge/settings/compatibility/index', $variables);
    }


    public function actionIssue(array $variables = [])
    {
        $this->requireAdmin();

        $this->renderTemplate('charge/settings/compatibility/issue', $variables);
    }

}
