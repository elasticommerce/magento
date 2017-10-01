<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:41
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Type_Category
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_AbstractEav
    implements SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
{
    protected static $indexerType = 'category';

    /**
     * get current entity type code
     *
     * @return string
     */
    protected function getEntityTypeCode()
    {
        return Mage_Catalog_Model_Category::ENTITY;
    }

    /**
     * get Resource
     *
     * @return SmartDevs_ElastiCommerce_Model_Resource_Indexer_Type_Category
     */
    protected function getResourceModel()
    {
        return Mage::getResourceSingleton('elasticommerce/indexer_type_category');
    }

    /**
     * get Product chunks for reindex
     *
     * @return array
     */
    protected function getCategoryChunks()
    {
        $range = $this->getResourceModel()->getCategoryRange($this->getStoreGroupId());
        return $this->getChunksByRange((int)$range['start'], (int)$range['end']);
    }


    /**
     * reindex complete store
     *
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    public function reindexStore()
    {
        foreach ($this->getCategoryChunks() as $chunk) {
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO,
                sprintf('Reindexing category chunk %u - %u',
                    $chunk['from'],
                    $chunk['to']));
        }
        return $this;
    }
}