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
        return $this->getChunksByRange((int)$range['start'], (int)$range['end'], 1500);
    }

    /**
     * creates index documents and attach it to the Document Collection in indexer Client
     *
     * @param array $chunk
     * @return SmartDevs_ElastiCommerce_Model_Indexer_Type_Abstract
     */
    protected function createIndexDocuments($chunk)
    {
        $rawResultData = $this->getResourceModel()->getStaticProductAttributes($this->getWebsiteId(), $chunk);
        foreach ($rawResultData as $id => $rawData) {
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
        $rawResultData = $this->getResourceModel()->getEavAttributeValues(
            $attribute,
            $this->getWebsiteId(),
            $this->getStoreId(),
            $chunk);
        foreach ($rawResultData as $id => $values) {
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
            #if (true === boolval($attribute->getIsFilterable())) {
            #    $document->addFilter($attribute->getSortColumnField(), $values[$attribute->getSortColumnField()], $attribute->getSortFieldType());
            #}
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
            $this->addAttributeDataToDocuments($attribute, $chunk);
        }
        return $this;
    }

    protected function addCategoryRelations($chunk)
    {
        $result = $this->getResourceModel()->getProductToCategoryRelations($this->getWebsiteId(), $this->getStoreId(), $chunk);
        foreach ($result as $id => $data) {
            $document = $this->getDocument($this->getDocumentId($id));
            foreach ($data['sort'] as $key => $value) {
                $document->addSort($key, $value, \SmartDevs\ElastiCommerce\Index\Document::SORT_NUMBER);
            }
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
        foreach ($this->getProductChunks() as $chunk) {
            Mage::helper('elasticommerce/log')->log(Zend_Log::INFO, sprintf('Reindexing product chunk %u - %u', $chunk['from'], $chunk['to']));
            // prepare filter table for faster prefiltered queries
            $this->getResourceModel()->prepareProductFilterTable($this->getWebsiteId(), $chunk);
            $this->createIndexDocuments($chunk);
            $this->addAllAttributeDataToDocuments($chunk);
            #$this->addCategoryRelations($chunk);
            $this->getIndexerClient()->sendBulk();
        }
        return $this;
    }
}