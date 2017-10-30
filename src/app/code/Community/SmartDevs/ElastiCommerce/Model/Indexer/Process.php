<?php

/**
 * elasticommerce catalog indexer
 *
 * @category  ElastiCommerce
 * @package   ElastiCommerce_Magento
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Process extends Mage_Index_Model_Indexer_Abstract
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
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Processor
     */
    protected function getIndexerFacade()
    {
        return Mage::getSingleton('elasticommerce/indexer_processor');
    }

    /**
     * Process event
     *
     * @SuppressWarnings("PHPMD.CamelCaseMethodName")
     * @param Mage_Index_Model_Event $event
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Process
     */
    protected function _processEvent(Mage_Index_Model_Event $event)
    {
        return $this;
    }

    /**
     * register event
     *
     * @SuppressWarnings("PHPMD.CamelCaseMethodName")
     * @param Mage_Index_Model_Event $event
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Process
     */
    protected function _registerEvent(Mage_Index_Model_Event $event)
    {
        switch ($event->getEntity()) {
            /** register eav attribute event */
            case Mage_Catalog_Model_Resource_Eav_Attribute::ENTITY:
                $process = $event->getProcess();
                $process->changeStatus(Mage_Index_Model_Process::STATUS_REQUIRE_REINDEX);
                break;
        }
        return $this;
    }

    /**
     * Rebuild complete store index data
     *
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Processor
     */
    public function reindexAll()
    {
        return $this->getIndexerFacade()->reindexAll();
    }

    /**
     * Whether the indexer should be displayed on process/list page
     *
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