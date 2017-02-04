<?php

/**
 * elasticommerce catalog indexer
 *
 * @category  ElastiCommerce
 * @package   ElastiCommerce_Magento
 */
class SmartDevs_ElastiCommerce_Model_Indexer extends Mage_Index_Model_Indexer_Abstract
{
    /**
     * entities and action which match indexer
     *
     * @var array
     */
    protected $_matchedEntities = array(
        Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE
        ),
    );

    /**
     * Retrieve Catalog Indexer model
     *
     * @return Infinitescale_Elasticgento_Model_Indexer_Elasticsearch
     */
    protected function _getIndexer()
    {
        return Mage::getSingleton('elasticommerce/indexer_elasticsearch');
    }

    /**
     * Process event
     *
     * @param Mage_Index_Model_Event $event
     * @return Elasticgento_Catalog_Model_Product_Indexer
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getEntity()) {
            /** register eav attribute event */
            case Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY:
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                break;
        }
    }

    /**
     * Rebuild complete store index data
     *
     * @return Elasticgento_Catalog_Model_Indexer_Catalog
     */
    public function reindexAll()
    {
        return $this->_getIndexer()->reindexAll();
    }

    /**
     * Whether the indexer should be displayed on process/list page
     *
     * @todo backend flag
     * @return bool
     */
    public function isVisible()
    {
        return true;
    }

    /**
     * Retrieve Indexer name
     *
     * @return string
     */
    public function getName()
    {
        return Mage::helper('elasticommerce')->__('ElastiCommerce');
    }

    /**
     * Retrieve Indexer description
     *
     * @return string
     */
    public function getDescription()
    {
        return Mage::helper('elasticommerce')->__('Add data to ElastiCommerce index');
    }
}