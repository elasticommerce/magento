<?php

/**
 * Created by PhpStorm.
 * User: dng
 * Date: 05.02.17
 * Time: 21:41
 */
class SmartDevs_ElastiCommerce_Model_Indexer_Type_Product
    extends SmartDevs_ElastiCommerce_Model_Indexer_Type_AbstractEav
    implements SmartDevs_ElastiCommerce_Model_Indexer_Type_Interface
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
        $chunks = $this->getChunksByRange((int)$range['start'], (int)$range['end'], 1500);
        Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexing %u chunks', count($chunks)));
        return $chunks;
    }

    /**
     * prepare product prefilter table for faster indexing speed
     *
     * @param array $productIds
     * @return bool
     */
    protected function prepareProductPreFilter(array $productIds)
    {
        $this->getResourceModel()->prepareProductPreFilter($this->getWebsiteId(), $productIds);
        return $this;
    }

    /**
     * creates index documents and attach it to the Document Collection in indexer Client
     *
     * @param array $chunk
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    protected function createIndexDocuments($chunk)
    {
        $rawData = $this->getResourceModel()->getDefaultProductAttributeValues($this->getWebsiteId());
        foreach ($rawData as $id => $rawData) {
            $document = $this->createNewDocument((int)$id);
            $document->addResultData($rawData);
            $this->getBulkCollection()->addItem($document);
        }
        return $this;
    }

    /**
     * add attribute data to documents
     *
     * @param Mage_Eav_Model_Entity_Attribute $attribute
     * @param $chunk
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    protected function addAttributeDataToDocuments(Mage_Eav_Model_Entity_Attribute $attribute, $chunk)
    {
        $rawData = $this->getResourceModel()->getAttributeValues($attribute, $this->getStoreId());
        foreach ($rawData as $id => $values) {
            /** @var SmartDevs_ElastiCommerce_IndexDocument $document */
            $document = $this->getDocument($this->getDocumentId($id));
            $document->addResultData($values);
            // system attributes need special handling
            if (false === boolval($attribute->getIsUserDefinded())) {
                #continue;
            }
            //add attribute to sort
            if (true === boolval($attribute->getUsedForSortBy())) {
                $document->addSort($attribute->getSortColumnField(), $values[$attribute->getSortColumnField()], $attribute->getSortFieldType());
            }
            // add filterable attribute data
            if (true === boolval($attribute->getIsFilterable())) {
                $document->addFilter($attribute->getAttributeCode(), $values[$attribute->getAttributeCode()]);
            }
            // add facette data
        }
        return $this;
    }

    /**
     * loop over all attributes and add them
     *
     * @param array $chunk
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    protected function addAllAttributeDataToDocuments($chunk)
    {
        foreach ($this->getEntityAttributes() as $attribute) {
            #$timeStart = microtime(true);
            $this->addAttributeDataToDocuments($attribute, $chunk);
            #Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Added Attribute "%s" Information to Documents in %.4f seconds', $attribute->getAttributeCode(), microtime(true) - $timeStart));
        }
        return $this;
    }

    /**
     * add product to category relations
     *
     * @param $chunk
     */
    protected function addCategoryRelations($chunk)
    {
        $result = $this->getResourceModel()->getProductToCategoryRelations($this->getWebsiteId(), $this->getStoreId(), $chunk);
        foreach ($result as $id => $data) {
            $document = $this->getDocument($this->getDocumentId($id));
            foreach ($data['sort'] as $key => $value) {
                $document->addSort($key, $value, \SmartDevs\ElastiCommerce\Index\Document::SORT_NUMBER);
            }
            $document->addFilter('categories', $data['categories'], \SmartDevs\ElastiCommerce\Index\Document::FILTER_NUMBER);
            $document->addFilter('anchors', $data['anchors'], \SmartDevs\ElastiCommerce\Index\Document::FILTER_NUMBER);
        }
    }

    /**
     * extend the default mapping for different actions
     *
     * @return $this
     */
    public function addTypeMapping()
    {
        $mapping = $this->getResultFieldMapping();
        foreach ($this->getEntityAttributes() as $attribute) {
            $columns = $attribute->getFlatColumns();
            foreach ($columns as $columnName => $columnValue) {
                if ($attribute->getIsSearchable() && sprintf('%s_value', $attribute->getAttributeCode()) === $columnName) {
                    $mapping->getCollection()->getField($columnName, 'string')->setIndex('no')->setStore('yes')->setCopyTo('search.fulltext')->setCopyTo('search.fulltext_boosted')->setCopyTo('completion');
                } elseif ($attribute->getIsSearchable() && false === array_key_exists(sprintf('%s_value', $attribute->getAttributeCode()), $columns)) {
                    $mapping->getCollection()->getField($columnName, 'string')->setIndex('no')->setStore('yes')->setCopyTo('search.fulltext')->setCopyTo('search.fulltext_boosted')->setCopyTo('completion');
                }
            }
        }
        return $this;
    }

    /**
     * reindex complete store
     *
     * @param Mage_Core_Model_Store $store
     * @return $this
     */
    public function reindexStore()
    {
        $storeTimeStart = microtime(true);
        foreach ($this->getProductChunks() as $chunk) {
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexing product chunk %u - %u', $chunk['from'], $chunk['to']));
            // prepare filter table for faster prefiltered queries
            $this->prepareProductPreFilter($chunk);
            $timeStart = microtime(true);
            $this->createIndexDocuments($chunk);
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Prepared chunk Documents in %.4f seconds', microtime(true) - $timeStart));
            $timeStart = microtime(true);
            $this->addAllAttributeDataToDocuments($chunk);
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Added all attribute data to chunk in %.4f seconds', microtime(true) - $timeStart));
            $this->addCategoryRelations($chunk);
            $timeStart = microtime(true);
            $this->getIndexerClient()->sendBulk();
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Added chunk data in  %.4f seconds', microtime(true) - $timeStart));
        }
        Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexed Store %u in  %.4f seconds', $this->getStoreId(), microtime(true) - $storeTimeStart));
        return $this;
    }
}