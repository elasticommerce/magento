<?php

/**
 * elasticommerce module helper
 */
class SmartDevs_ElastiCommerce_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function createPeriodCondition($field, $period = 30)
    {
        $period = date('Y-m-d', time() - $period*24*3600);
        $period = "$field > '$period' ";

        return $period;
    }

    public function createStoreCondition($field, $storeId = null)
    {
        if($storeId == null) $storeId = Mage::app()->getStore()->getId();
        
        return "$field = " . $storeId;
    }
}