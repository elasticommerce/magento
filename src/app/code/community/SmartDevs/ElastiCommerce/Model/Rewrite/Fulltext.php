<?php

/**
 * elasticgento Fulltext indexer rewrite
 *
 * @category  ElastiCommerce
 * @package   ElastiCommerce_Magento
 */
class SmartDevs_ElastiCommerce_Model_Rewrite_Fulltext extends Mage_CatalogSearch_Model_Indexer_Fulltext
{
    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('elasticommerce')->__('[Disabled] Rebuild Catalog product fulltext search index (indexing done within ElastiCommerce indexer)');

    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    /**
     * make indexer not usable when elasticsearch is enabled
     *
     * @param Mage_Index_Model_Event $event
     * @return bool
     */
    public function matchEvent(Mage_Index_Model_Event $event)
    {
        return false;
    }

    /**
     * Rebuild all index data when elasticsearch is disabled
     */
    public function reindexAll()
    {
        return $this;
    }
}