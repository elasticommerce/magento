<?php

/**
 * Class SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Abstract
 */
abstract class SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Abstract extends Mage_Core_Model_Resource_Db_Abstract
{

    /**
     * get prepared select
     *
     * @return Varien_Db_Select
     */
    protected function getSelect()
    {
        $select = $this->_getReadAdapter()->select();
        if (true === method_exists($select, 'sqlNoCache')) {
            $select->sqlNoCache(true);
        }
        return $select;
    }
}