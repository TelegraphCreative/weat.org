<?php
namespace Craft;

class m170409_000000_charge_addAccountToChargeTable extends BaseMigration
{
    public function safeUp()
    {
        $chargesTable = $this->dbConnection->schema->getTable('{{charges}}');

        if ($chargesTable->getColumn('accountId') === null) {
            $this->addColumnAfter('charges', 'accountId', array('column' => ColumnType::Int, 'default' => null), 'customerId');
        }


        return true;
    }

}
