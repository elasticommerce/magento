<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:41
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Magento_Product
    extends SmartDevs_ElastiCommerce_Model_Indexer_Magento_AbstractEav
    implements SmartDevs_ElastiCommerce_Model_Indexer_Magento_Interface
{

    /**
     * indexer type string
     *
     * @var string
     */
    protected static $indexerType = 'product';

    /**
     * get current entity type code
     *
     * @return string
     */
    protected function getEntityTypeCode()
    {
        return Mage_Catalog_Model_Product::ENTITY;
    }

    /**
     * get Resource
     *
     * @return SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Product
     */
    protected function getResourceModel()
    {
        return Mage::getResourceSingleton('elasticommerce/indexer_type_product');
    }

    /**
     * get Product chunks for reindex
     *
     * @return array
     */
    protected function getProductChunks()
    {
        $range = $this->getResourceModel()->getProductRange($this->getWebsiteId());
        return $this->getChunksByRange((int)$range['start'], (int)$range['end']);
    }

    /**
     * reindex complete store
     *
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    public function reindexStore(Mage_Core_Model_Store $store)
    {
        $this->setStoreId($store->getId());
        $this->setStoreGroupId($store->getGroupId());
        $this->setWebsiteId($store->getWebsiteId());
        foreach ($this->getProductChunks() as $chunk) {
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO,
                sprintf('Reindexing product chunk %u - %u',
                    $chunk['from'],
                    $chunk['to']));
        }
        return $this;
    }
}