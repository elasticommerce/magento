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
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION
        ),
        Mage_Catalog_Model_Product::ENTITY => array(
            Mage_Index_Model_Event::TYPE_SAVE,
            Mage_Index_Model_Event::TYPE_DELETE,
            Mage_Index_Model_Event::TYPE_MASS_ACTION
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
        if($event->getEntity() == Mage_Catalog_Model_Product::ENTITY) {
            switch ($event->getType()) {
                case Mage_Index_Model_Event::TYPE_SAVE:
                    $this->_processUpdateEntry($event->getNewData('elasticommerce_update_product_id'));
                    break;
                case Mage_Index_Model_Event::TYPE_DELETE:
                    $this->_processUpdateEntry($event->getNewData('elasticommerce_delete_product_id'));
                    break;
                case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                    $productIds = $event->getNewData('elasticommerce_mass_action_product_ids');
                    $this->_processUpdateEntries($productIds);
                    break;
            }
        }
        return $this;
    }

    /**
     * @param $productId
     */
    private function _processUpdateEntry($productId, $storeId = 0){
        $productId = $productId['elasticommerce_update_product_id'];
        /** @var SmartDevs_ElastiCommerce_Model_Indexer_Processor $indexer */
        $store = Mage::app()->getDefaultStoreView();
        $indexer = $this->getIndexerFacade();
        $indexer->setStore($store)->reindex($productId, 'product');

        Mage::app()->getCacheInstance()->clean([Mage_Catalog_Model_Product::CACHE_TAG . '_' . $productId]);
    }

    /**
     * @param $productId
     */
    private function _processUpdateEntries($productIds, $storeId = 0){
        $productIds = $productIds['elasticommerce_mass_action_product_ids'];
        /** @var SmartDevs_ElastiCommerce_Model_Indexer_Processor $indexer */
        $store = Mage::app()->getDefaultStoreView();
        $indexer = $this->getIndexerFacade();
        
        $min = min($productIds);
        $max = max($productIds);
        $indexer->setStore($store)->reindexMultiple($min, $max, 'product');

        $cacheTags = [];
        foreach ($productIds as $productId){
            $cacheTags[] = Mage_Catalog_Model_Product::CACHE_TAG . '_' . $productId;
        }
        Mage::app()->getCacheInstance()->clean($cacheTags);

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
            case Mage_Catalog_Model_Product::ENTITY:
                switch ($event->getType()) {
                    case Mage_Index_Model_Event::TYPE_SAVE:
                        $event->addNewData('elasticommerce_update_product_id', $event->getDataObject()->getId());
                        break;
                    case Mage_Index_Model_Event::TYPE_DELETE:
                        $event->addNewData('elasticommerce_delete_product_id', $event->getDataObject()->getId());
                        break;
                    case Mage_Index_Model_Event::TYPE_MASS_ACTION:
                        $productIds = $event->getDataObject()->getProductIds();
                        $event->addNewData('elasticommerce_mass_action_product_ids', $productIds);
                        break;
                }
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