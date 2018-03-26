<?php
namespace Craft;

class m170112_000000_charge_moveConnectToElements extends BaseMigration
{
    public function safeUp()
    {
        $this->renameColumn('charge_accounts', 'userId', 'elementId');
        return true;
    }

}
